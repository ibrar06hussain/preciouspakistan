<?php

namespace App\Console\Commands;

use App\Modules\Identity\Services\SuperAdminInstallerService;
use Illuminate\Console\Command;
use Throwable;

class InstallSuperAdminCommand extends Command
{
    protected $signature = 'precious:install-super-admin
        {--from-env : Read credentials from PRECIOUS_SUPER_ADMIN_NAME, PRECIOUS_SUPER_ADMIN_EMAIL, and PRECIOUS_SUPER_ADMIN_PASSWORD}';

    protected $description = 'Securely provision the initial Super Admin account without hardcoded credentials';

    public function handle(SuperAdminInstallerService $installer): int
    {
        if ($installer->hasExistingSuperAdmin()) {
            $this->error('A Super Admin account already exists. Aborting to prevent unintended privilege creation.');

            return self::FAILURE;
        }

        if ($this->option('from-env')) {
            $name = (string) env('PRECIOUS_SUPER_ADMIN_NAME', 'Precious Pakistan Super Admin');
            $email = (string) env('PRECIOUS_SUPER_ADMIN_EMAIL', '');
            $password = (string) env('PRECIOUS_SUPER_ADMIN_PASSWORD', '');

            if ($email === '' || $password === '') {
                $this->error('PRECIOUS_SUPER_ADMIN_EMAIL and PRECIOUS_SUPER_ADMIN_PASSWORD must be set when using --from-env.');

                return self::FAILURE;
            }
        } else {
            if (! $this->input->isInteractive()) {
                $this->error('Interactive mode is required unless --from-env is explicitly provided.');

                return self::FAILURE;
            }

            $name = (string) $this->ask('Super Admin full name', 'Precious Pakistan Super Admin');
            $email = (string) $this->ask('Super Admin email address');
            $password = (string) $this->secret('Super Admin password (min 12 chars, upper/lower/number/symbol)');
            $confirmation = (string) $this->secret('Confirm Super Admin password');

            if ($password !== $confirmation) {
                $this->error('Password confirmation does not match.');

                return self::FAILURE;
            }
        }

        try {
            $user = $installer->install($name, $email, $password);
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info("Super Admin [{$user->email}] created and verified successfully.");
        $this->line('Access the admin portal at /admin using these credentials.');

        return self::SUCCESS;
    }
}
