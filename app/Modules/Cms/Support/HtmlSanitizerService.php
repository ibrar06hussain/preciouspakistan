<?php

namespace App\Modules\Cms\Support;

use InvalidArgumentException;
use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

final class HtmlSanitizerService
{
    private HtmlSanitizer $sanitizer;

    /**
     * Executable server-side or script patterns strictly forbidden inside CMS content.
     *
     * @var list<string>
     */
    private const FORBIDDEN_EXECUTABLE_PATTERNS = [
        '/<\?(?:php|=|\s)/i',
        '/@(?:php|endphp|inject|include|includeIf|includeWhen|eval|dd|dump|verbatim)\b/i',
        '/\{\{.*?\}\}/s',
        '/\{!!.*?!!\}/s',
    ];

    public function __construct()
    {
        $config = (new HtmlSanitizerConfig)
            ->allowSafeElements()
            ->allowRelativeLinks()
            ->allowLinkSchemes(['http', 'https', 'mailto', 'tel'])
            ->allowMediaSchemes(['http', 'https'])
            ->allowRelativeMedias()
            ->dropElement('script')
            ->dropElement('style')
            ->dropElement('iframe')
            ->dropElement('object')
            ->dropElement('embed')
            ->dropElement('form')
            ->dropElement('input')
            ->dropElement('textarea')
            ->dropElement('select')
            ->dropElement('button')
            ->forceAttribute('a', 'rel', 'noopener noreferrer');

        $this->sanitizer = new HtmlSanitizer($config);
    }

    public function assertNoExecutableSyntax(string $value, string $field = 'content'): void
    {
        foreach (self::FORBIDDEN_EXECUTABLE_PATTERNS as $pattern) {
            if (preg_match($pattern, $value) === 1) {
                throw new InvalidArgumentException("Executable PHP or Blade syntax is not permitted in [{$field}].");
            }
        }
    }

    public function sanitizeRichHtml(string $html, string $field = 'html'): string
    {
        $this->assertNoExecutableSyntax($html, $field);

        return trim($this->sanitizer->sanitize($html));
    }

    public function sanitizePlainText(?string $text, string $field = 'text', int $maxLength = 1000): string
    {
        $raw = (string) ($text ?? '');
        $this->assertNoExecutableSyntax($raw, $field);

        $cleaned = trim(strip_tags($raw));

        return mb_substr($cleaned, 0, $maxLength);
    }

    public function sanitizeUrl(?string $url, string $field = 'url'): string
    {
        $raw = trim((string) ($url ?? ''));

        if ($raw === '') {
            return '';
        }

        $this->assertNoExecutableSyntax($raw, $field);

        if (preg_match('/^(?:javascript|vbscript|data):/i', $raw) === 1) {
            throw new InvalidArgumentException("Unsafe URL scheme rejected in [{$field}].");
        }

        if (str_starts_with($raw, '/') || str_starts_with($raw, '#')) {
            return mb_substr($raw, 0, 255);
        }

        $scheme = strtolower((string) parse_url($raw, PHP_URL_SCHEME));
        if (! in_array($scheme, ['http', 'https', 'mailto', 'tel'], true)) {
            throw new InvalidArgumentException("URL in [{$field}] must use a relative path, anchor, http(s), mailto, or tel scheme.");
        }

        return mb_substr($raw, 0, 255);
    }
}
