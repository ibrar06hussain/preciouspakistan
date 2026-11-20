<?php

namespace Tests\Feature\Phase3;

use App\Models\User;
use App\Modules\Cms\Services\PageBuilderService;
use App\Modules\Cms\Support\ComponentRegistry;
use App\Modules\Rbac\Support\RbacCatalog;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class CmsSanitizationAndSchemaSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RbacSeeder::class);
    }

    public function test_rich_html_strips_scripts_and_event_handlers_while_keeping_safe_formatting(): void
    {
        $registry = app(ComponentRegistry::class);

        $clean = $registry->validateAndSanitize('content_block', [
            'heading' => 'Safe Heading',
            'body_html' => '<p>Welcome to <strong>Hunza</strong></p><script>alert(1)</script><img src="x" onerror="alert(2)"><a href="https://preciouspakistan.com">Visit</a>',
            'arbitrary_injected_key' => 'must_be_dropped',
        ]);

        $this->assertArrayNotHasKey('arbitrary_injected_key', $clean);
        $this->assertStringContainsString('<strong>Hunza</strong>', $clean['body_html']);
        $this->assertStringContainsString('https://preciouspakistan.com', $clean['body_html']);
        $this->assertStringNotContainsString('<script>', $clean['body_html']);
        $this->assertStringNotContainsString('onerror', $clean['body_html']);
    }

    public function test_php_and_blade_syntax_are_rejected_in_cms_fields(): void
    {
        $registry = app(ComponentRegistry::class);

        $this->expectException(InvalidArgumentException::class);

        $registry->validateAndSanitize('content_block', [
            'heading' => 'Injected',
            'body_html' => '<p>{{ system("whoami") }}</p>',
        ]);
    }

    public function test_raw_php_tag_is_rejected(): void
    {
        $registry = app(ComponentRegistry::class);

        $this->expectException(InvalidArgumentException::class);

        $registry->validateAndSanitize('hero_banner', [
            'heading' => '<?php echo "hacked"; ?>',
        ]);
    }

    public function test_javascript_url_scheme_is_rejected(): void
    {
        $registry = app(ComponentRegistry::class);

        $this->expectException(InvalidArgumentException::class);

        $registry->validateAndSanitize('cta_banner', [
            'heading' => 'Click Me',
            'primary_cta_label' => 'Go',
            'primary_cta_href' => 'javascript:alert(1)',
        ]);
    }

    public function test_unknown_component_type_is_rejected(): void
    {
        $registry = app(ComponentRegistry::class);

        $this->expectException(InvalidArgumentException::class);

        $registry->validateAndSanitize('raw_blade_eval', ['code' => 'test']);
    }

    public function test_reserved_application_slug_is_rejected(): void
    {
        $editor = User::factory()->staff()->create();
        $editor->assignRole(RbacCatalog::ROLE_CONTENT_SEO_MANAGER);

        $this->expectException(InvalidArgumentException::class);

        app(PageBuilderService::class)->createPage($editor, [
            'title' => 'Fake Admin',
            'slug' => 'admin',
        ], []);
    }
}
