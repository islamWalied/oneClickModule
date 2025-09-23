<?php

namespace IslamWalied\OneClickModule\Installer;

use Composer\Script\Event;
use Composer\Installer\PackageEvent;

class PostInstallScript
{
    public static function postInstall(Event $event)
    {
        $io = $event->getIO();

        $io->write([
            '',
            '🚀 <info>One Click Module installed successfully!</info>',
            '',
            '📖 <comment>To complete the installation, please run:</comment>',
            '   <info>php artisan one-click-module:install</info>',
            '',
            '📋 <comment>This will:</comment>',
            '   • Install Laravel Sanctum',
            '   • Publish necessary files',
            '   • Run migrations',
            '',
        ]);
    }

    public static function postUpdate(Event $event)
    {
        $io = $event->getIO();

        $io->write([
            '',
            '✅ <info>One Click Module updated successfully!</info>',
            '',
            '🔄 <comment>If you encounter any issues, try:</comment>',
            '   <info>php artisan one-click-module:install --force</info>',
            '',
        ]);
    }
}