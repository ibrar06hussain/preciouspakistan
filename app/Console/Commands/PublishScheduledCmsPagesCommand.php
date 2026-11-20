<?php

namespace App\Console\Commands;

use App\Modules\Cms\Services\PageBuilderService;
use Illuminate\Console\Command;

class PublishScheduledCmsPagesCommand extends Command
{
    protected $signature = 'cms:publish-scheduled';

    protected $description = 'Publish any scheduled CMS pages whose publication timestamp has been reached';

    public function handle(PageBuilderService $builder): int
    {
        $count = $builder->publishDueScheduledPages();
        $this->info("Published {$count} scheduled CMS page(s).");

        return self::SUCCESS;
    }
}
