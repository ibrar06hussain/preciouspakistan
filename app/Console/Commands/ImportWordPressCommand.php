<?php

namespace App\Console\Commands;

use App\Modules\Migration\Services\WordPressImportService;
use Illuminate\Console\Command;
use Throwable;

class ImportWordPressCommand extends Command
{
    protected $signature = 'wp:import
        {source? : Path to an authorized local WordPress WXR XML or JSON export file (never connects to live WP)}
        {--dry-run : Parse and report planned mappings/warnings without writing database rows}
        {--rollback= : Roll back a previously imported batch code non-destructively}';

    protected $description = 'Non-destructively import content from an authorized local WordPress WXR XML or JSON export file';

    public function handle(WordPressImportService $importer): int
    {
        $rollbackCode = $this->option('rollback');
        if (is_string($rollbackCode) && trim($rollbackCode) !== '') {
            try {
                $result = $importer->rollbackBatch(trim($rollbackCode));
                $this->info("Rolled back batch [{$result['batch_code']}]: deleted {$result['deleted_pages']} page(s) and {$result['deleted_redirects']} redirect(s).");

                return self::SUCCESS;
            } catch (Throwable $e) {
                $this->error($e->getMessage());

                return self::FAILURE;
            }
        }

        $source = (string) ($this->argument('source') ?? '');
        if ($source === '') {
            $this->error('Provide a local export file path or pass --rollback={batch_code}.');

            return self::FAILURE;
        }

        try {
            $report = $importer->importFromFile($source, (bool) $this->option('dry-run'));
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $mode = $report['dry_run'] ? 'DRY-RUN' : 'IMPORTED';
        $this->info("[{$mode}] Batch {$report['batch_code']} — Total: {$report['total_items']}, Imported: {$report['imported']}, Skipped: {$report['skipped']}, Needs Review: {$report['review_flagged']}, Redirects: {$report['redirects_created']}");

        return self::SUCCESS;
    }
}
