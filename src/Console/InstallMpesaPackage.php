<?php

namespace konectdigital\Mpesa\Console;

use Illuminate\Console\Command;

class InstallBlogPackage extends Command
{
    protected $signature = 'mpesa:install';

    protected $description = 'Install Mpesa Package';

    public function handle()
    {
        $this->info('Installing MpesaPackage...');

        $this->info('Publishing configuration...');

        $this->call('vendor:publish', [
            '--provider' => "Konectdigital\Mpesa\MpesaServiceProvider",
            '--tag' => "config"
        ]);

        $this->info('Installed Mpesa');
    }
}
