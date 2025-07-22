<?php

namespace IslamWalied\OneClickModule\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class PublishMiddlewares extends Command
{
    protected $signature = 'make:publish-middleware
                            {--force : Overwrite any existing files}';
    protected $description = 'Publish middleware from your package';

    public function handle(Filesystem $filesystem)
    {
        $this->info('Publishing middleware...');

        $sourcePath = __DIR__ . '/../Middleware';
        $destinationPath = app_path('Http/Middleware');

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

        $this->info('Middleware published successfully!');

        return 0;
    }
}
