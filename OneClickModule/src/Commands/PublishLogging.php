<?php

namespace IslamWalied\OneClickModule\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class PublishLogging extends Command
{
    protected $signature = 'make:publish-logging
                            {--force : Overwrite any existing files}';
    protected $description = 'Publish logging from your package';

    public function handle(Filesystem $filesystem)
    {
        $this->info('Publishing logging...');

        $sourcePath = __DIR__ . '/../Logging';
        $destinationPath = app_path('Logging');

        if (!$filesystem->exists($destinationPath)) {
            $filesystem->makeDirectory($destinationPath, 0755, true);
        }

        $traitFiles = $filesystem->files($sourcePath);

        foreach ($traitFiles as $file) {
            $destinationFile = $destinationPath . '/' . $file->getFilename();

            if ($filesystem->exists($destinationFile) && !$this->option('force')) {
                $this->warn("File {$file->getFilename()} already exists. Use --force to overwrite.");
                continue;
            }

            $filesystem->copy($file->getPathname(), $destinationFile);
            $this->line("Published <info>{$file->getFilename()}</info>");
        }

        $this->info('Logging published successfully!');

        return 0;
    }
}
