<?php

namespace IslamWalied\OneClickModule\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class PublishTraits extends Command
{
    protected $signature = 'make:publish-traits 
                            {--force : Overwrite any existing files}';
    protected $description = 'Publish traits from your package';

    public function handle(Filesystem $filesystem)
    {
        $this->info('Publishing traits...');

        $sourcePath = __DIR__ . '/../Traits';
        $destinationPath = app_path('Traits');

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

        $this->info('Traits published successfully!');

        return 0;
    }
}
