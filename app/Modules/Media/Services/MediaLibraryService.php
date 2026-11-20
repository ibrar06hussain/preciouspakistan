<?php

namespace App\Modules\Media\Services;

use App\Models\User;
use App\Modules\Media\Contracts\VirusScanner;
use App\Modules\Media\Models\MediaAsset;
use App\Modules\Media\Models\MediaUsage;
use App\Modules\Rbac\Services\AuditLogger;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

/**
 * Central optimized image library:
 * - Strict MIME/size validation + virus scanner hook.
 * - SHA-256 content deduplication.
 * - Generates WebP + AVIF (when GD supports AVIF) + responsive thumbnail widths (400, 800, 1440).
 * - Preserves originals optionally and blocks accidental deletion while active usages exist.
 */
final class MediaLibraryService
{
    /** @var list<int> */
    private const RESPONSIVE_WIDTHS = [400, 800, 1440];

    public function __construct(
        private readonly VirusScanner $scanner,
        private readonly AuditLogger $audit,
    ) {}

    public function uploadImage(
        UploadedFile $file,
        ?User $uploader = null,
        ?string $altText = null,
        ?string $caption = null,
        ?string $licenseAttribution = null,
        bool $preserveOriginal = true,
        string $disk = 'uploads',
    ): MediaAsset {
        $allowedMimes = config('precious.uploads.allowed_image_mimes', ['image/jpeg', 'image/png', 'image/webp', 'image/avif']);
        $maxKb = (int) config('precious.uploads.max_image_kb', 8192);

        $realPath = $file->getRealPath();
        if ($realPath === false || ! is_file($realPath)) {
            throw new InvalidArgumentException('Uploaded file could not be read.');
        }

        $this->scanner->scanPath($realPath);

        $mime = $file->getMimeType() ?: 'application/octet-stream';
        if (! in_array($mime, $allowedMimes, true)) {
            throw new InvalidArgumentException("Unsupported media MIME type [{$mime}].");
        }

        $sizeBytes = (int) $file->getSize();
        if ($sizeBytes <= 0 || $sizeBytes > ($maxKb * 1024)) {
            throw new InvalidArgumentException("Image exceeds maximum allowed size ({$maxKb} KB).");
        }

        $hash = hash_file('sha256', $realPath);
        if ($hash === false) {
            throw new RuntimeException('Failed to compute SHA-256 hash.');
        }

        // Deduplicate by SHA-256 content hash.
        $existing = MediaAsset::query()->where('sha256_hash', $hash)->first();
        if ($existing !== null) {
            return $existing;
        }

        $rawBytes = file_get_contents($realPath);
        if ($rawBytes === false) {
            throw new RuntimeException('Failed to read uploaded image bytes.');
        }

        $uuid = Str::uuid()->toString();
        $baseDir = 'media/'.now()->format('Y/m').'/'.$uuid;
        $storage = Storage::disk($disk);

        $originalExt = strtolower($file->getClientOriginalExtension() ?: 'img');
        $originalPath = null;
        if ($preserveOriginal) {
            $originalPath = "{$baseDir}/original.{$originalExt}";
            $storage->put($originalPath, $rawBytes);
        }

        $width = null;
        $height = null;
        $webpPath = null;
        $avifPath = null;
        $variants = [];

        $gdImage = @imagecreatefromstring($rawBytes);
        if ($gdImage !== false) {
            imagepalettetotruecolor($gdImage);
            imagealphablending($gdImage, true);
            imagesavealpha($gdImage, true);

            $width = imagesx($gdImage);
            $height = imagesy($gdImage);

            if (function_exists('imagewebp')) {
                ob_start();
                imagewebp($gdImage, null, 82);
                $webpBytes = ob_get_clean();
                if (is_string($webpBytes) && $webpBytes !== '') {
                    $webpPath = "{$baseDir}/optimized.webp";
                    $storage->put($webpPath, $webpBytes);
                }
            }

            if (function_exists('imageavif')) {
                ob_start();
                $ok = @imageavif($gdImage, null, 65);
                $avifBytes = ob_get_clean();
                if ($ok && is_string($avifBytes) && $avifBytes !== '') {
                    $avifPath = "{$baseDir}/optimized.avif";
                    $storage->put($avifPath, $avifBytes);
                }
            }

            foreach (self::RESPONSIVE_WIDTHS as $targetWidth) {
                if ($width <= $targetWidth) {
                    continue;
                }
                $scaled = imagescale($gdImage, $targetWidth);
                if ($scaled !== false && function_exists('imagewebp')) {
                    ob_start();
                    imagewebp($scaled, null, 80);
                    $thumbBytes = ob_get_clean();
                    imagedestroy($scaled);
                    if (is_string($thumbBytes) && $thumbBytes !== '') {
                        $thumbPath = "{$baseDir}/w{$targetWidth}.webp";
                        $storage->put($thumbPath, $thumbBytes);
                        $variants[(string) $targetWidth] = $thumbPath;
                    }
                }
            }

            imagedestroy($gdImage);
        }

        $asset = MediaAsset::query()->create([
            'uuid' => $uuid,
            'sha256_hash' => $hash,
            'original_filename' => mb_substr($file->getClientOriginalName(), 0, 255),
            'disk' => $disk,
            'original_path' => $originalPath,
            'webp_path' => $webpPath,
            'avif_path' => $avifPath,
            'variants' => $variants,
            'mime_type' => $mime,
            'size_bytes' => $sizeBytes,
            'width' => $width,
            'height' => $height,
            'alt_text' => $altText !== null ? mb_substr(trim($altText), 0, 255) : null,
            'caption' => $caption !== null ? mb_substr(trim($caption), 0, 500) : null,
            'license_attribution' => $licenseAttribution !== null ? mb_substr(trim($licenseAttribution), 0, 255) : null,
            'preserve_original' => $preserveOriginal,
            'uploaded_by' => $uploader?->id,
        ]);

        if ($uploader !== null) {
            $this->audit->record(
                actor: $uploader,
                event: 'media.asset_uploaded',
                summary: "Uploaded media asset [{$asset->original_filename}] ({$asset->uuid})",
                auditable: $asset,
                afterState: ['webp' => $webpPath !== null, 'avif' => $avifPath !== null, 'width' => $width, 'height' => $height],
            );
        }

        return $asset;
    }

    public function attachUsage(MediaAsset $asset, string $usableType, int $usableId, string $context = 'general'): MediaUsage
    {
        return MediaUsage::query()->firstOrCreate([
            'media_asset_id' => $asset->id,
            'usable_type' => $usableType,
            'usable_id' => $usableId,
            'context' => $context,
        ]);
    }

    public function deleteAsset(User $actor, MediaAsset $asset): void
    {
        $usageCount = $asset->usages()->count();
        if ($usageCount > 0) {
            throw new RuntimeException(
                "Cannot delete media asset [{$asset->original_filename}]: still referenced by {$usageCount} record(s)."
            );
        }

        $storage = Storage::disk($asset->disk);
        foreach (array_filter([$asset->original_path, $asset->webp_path, $asset->avif_path]) as $path) {
            $storage->delete($path);
        }
        foreach ($asset->variants ?? [] as $variantPath) {
            $storage->delete($variantPath);
        }

        $this->audit->record(
            actor: $actor,
            event: 'media.asset_deleted',
            summary: "Deleted unreferenced media asset [{$asset->original_filename}]",
            auditable: $asset,
        );

        $asset->delete();
    }
}
