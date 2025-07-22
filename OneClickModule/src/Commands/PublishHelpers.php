<?php

namespace IslamWalied\OneClickModule\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class PublishHelpers extends Command
{
    protected $signature = 'make:publish-helpers 
                            {--force : Overwrite any existing files}';
    protected $description = 'Publish helpers from your package';

    public function handle(Filesystem $filesystem)
    {
        $this->info('Publishing helpers...');

        $sourcePath = __DIR__ . '/../Helpers';
        $destinationPath = app_path('Helpers');

        if (!$filesystem->exists($destinationPath)) {
            $filesystem->makeDirectory($destinationPath, 0755, true);
        }

        // Publish all helper files recursively
        $this->publishDirectory($filesystem, $sourcePath, $destinationPath);

        $this->info('Helpers published successfully!');

        return 0;
    }

    protected function publishDirectory(Filesystem $filesystem, $sourcePath, $destinationPath)
    {
        // Create destination directory if it doesn't exist
        if (!$filesystem->exists($destinationPath)) {
            $filesystem->makeDirectory($destinationPath, 0755, true);
        }

        // Get all files and directories in the source path
        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($sourcePath, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($items as $item) {
            $target = $destinationPath . DIRECTORY_SEPARATOR . $items->getSubPathName();

            if ($item->isDir()) {
                // Create directory if it doesn't exist
                if (!$filesystem->exists($target)) {
                    $filesystem->makeDirectory($target, 0755, true);
                }
            } else {
                // Handle file publishing
                if ($filesystem->exists($target) && !$this->option('force')) {
                    $this->warn("File {$items->getSubPathName()} already exists. Use --force to overwrite.");
                    continue;
                }

                $filesystem->copy($item->getPathname(), $target);
                $this->line("Published <info>{$items->getSubPathName()}</info>");
            }
        }
    }
}