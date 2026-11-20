<?php

namespace App\Modules\Media\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Media\Models\MediaAsset;
use App\Modules\Media\Services\MediaLibraryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

final class JoditMediaController extends Controller
{
    public function __construct(
        private readonly MediaLibraryService $media,
    ) {}

    public function upload(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'max:'.config('precious.uploads.max_image_kb', 8192)],
            'alt_text' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $asset = $this->media->uploadImage(
                file: $request->file('file'),
                uploader: $request->user(),
                altText: $request->input('alt_text'),
            );
        } catch (InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json([
            'success' => true,
            'asset_id' => $asset->id,
            'uuid' => $asset->uuid,
            'url' => $asset->preferredUrl(),
            'webp_path' => $asset->webp_path,
            'avif_path' => $asset->avif_path,
            'alt_text' => $asset->alt_text,
        ]);
    }

    public function browse(): JsonResponse
    {
        $assets = MediaAsset::query()
            ->orderByDesc('id')
            ->limit(60)
            ->get()
            ->map(fn (MediaAsset $a): array => [
                'id' => $a->id,
                'uuid' => $a->uuid,
                'filename' => $a->original_filename,
                'url' => $a->preferredUrl(),
                'alt_text' => $a->alt_text,
                'width' => $a->width,
                'height' => $a->height,
            ])
            ->all();

        return response()->json(['success' => true, 'files' => $assets]);
    }
}
