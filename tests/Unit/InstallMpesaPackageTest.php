<?php

namespace Konectdigital\Mpesa\Tests\Unit;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Konectdigital\Mpesa\Tests\TestCase;

class InstallMpesaPackageTest extends TestCase
{
    /** @test */
    function the_install_command_copies_the_configuration()
    {
        // make sure we're starting from a clean state
        if (File::exists(config_path('mpesa.php'))) {
            unlink(config_path('mpesa.php'));
        }

        $this->assertFalse(File::exists(config_path('mpesa.php')));

        Artisan::call('mpesa:install');

        $this->assertTrue(File::exists(config_path('mpesa.php')));
    }
}
