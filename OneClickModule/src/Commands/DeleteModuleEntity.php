<?php

namespace Islamwalied\OneClickModule\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;

class DeleteModuleEntity extends Command
{
    protected $signature = 'delete:module {module : The name of the module (e.g., Auth)} {entity : The name of the entity to delete (e.g., User)}';
    protected $description = 'Delete an entity and all its associated files from a module';

    public function handle()
    {
        $module = Str::studly($this->argument('module'));
        $entity = Str::studly($this->argument('entity'));
        $entityLower = Str::snake($entity);

        if (!$this->confirm("Are you sure you want to delete the {$entity} entity from the {$module} module? This will remove all associated files.", false)) {
            $this->info('Deletion cancelled.');
            return;
        }

        $modulePath = base_path("Modules/{$module}");

        if (!File::exists($modulePath)) {
            $this->error("Module {$module} does not exist.");
            return;
        }

        $filesToDelete = [
            "Models/{$entity}.php",
            "Http/Controllers/{$entity}Controller.php",
            "Services/Interface/{$entity}Service.php",
            "Services/Implementation/{$entity}ServiceImpl.php",
            "Repositories/Interface/{$entity}Repository.php",
            "Repositories/Implementation/{$entity}RepositoriesImpl.php",
            "Http/Requests/Store{$entity}Request.php",
            "Http/Requests/Update{$entity}Request.php",
            "Http/Resources/{$entity}Resource.php",
            "Http/Routes/{$entityLower}.php",
            "Database/Seeders/{$entity}Seeder.php",
        ];

        foreach ($filesToDelete as $file) {
            $fullPath = "{$modulePath}/{$file}";
            if (File::exists($fullPath)) {
                File::delete($fullPath);
                $this->info("Deleted: {$file}");
            }
        }

        $migrationsPath = "{$modulePath}/Database/Migrations";
        if (File::exists($migrationsPath)) {
            $migrationFiles = File::files($migrationsPath);
            $tableName = Str::snake(Str::plural($entity));
            foreach ($migrationFiles as $file) {
                if (str_contains($file->getFilename(), "create_{$tableName}_table")) {
                    File::delete($file->getPathname());
                    $this->info("Deleted migration: {$file->getFilename()}");
                }
            }
        }

        $this->updateDatabaseSeeder($module, $entity);

        $this->updateServiceProviders($module, $entity);

        $modelsPath = "{$modulePath}/Models";
        $isLastModel = true;
        if (File::exists($modelsPath)) {
            $modelFiles = File::files($modelsPath);
            foreach ($modelFiles as $file) {
                if ($file->getExtension() === 'php' && $file->getFilename() !== "{$entity}.php") {
                    $isLastModel = false;
                    break;
                }
            }
        }

        if ($isLastModel) {
            $this->removeModuleProvidersAndDirectory($module);
        }

        $this->info("Entity {$entity} and its associated files have been successfully deleted from {$module} module.");
    }

    protected function updateDatabaseSeeder($module, $entity)
    {
        $seederPath = base_path("Modules/{$module}/Database/Seeders/DatabaseSeeder.php");

        if (!File::exists($seederPath)) {
            $this->info("DatabaseSeeder not found at {$seederPath}. Skipping update.");
            return;
        }

        $content = File::get($seederPath);

        $callPattern = "/\s*\\\$this->call\s*\(\s*\[\s*{$entity}Seeder::class\s*\]\s*\);\s*\n/";

        $updatedContent = preg_replace($callPattern, '', $content);

        $updatedContent = preg_replace("/\s*DB::statement\s*\(\s*'SET FOREIGN_KEY_CHECKS=1;'\s*\);/", "\n        DB::statement('SET FOREIGN_KEY_CHECKS=1;');", $updatedContent);

        File::put($seederPath, $updatedContent);
        $this->info("Updated DatabaseSeeder: Removed {$entity}Seeder reference");
    }

    protected function updateServiceProviders($module, $entity)
    {
        $repositoryProviderPath = base_path("Modules/{$module}/Providers/RepositoryServiceProvider.php");
        $serviceProviderPath = base_path("Modules/{$module}/Providers/ServiceServiceProvider.php");
        $moduleServiceProviderPath = base_path("Modules/{$module}/Providers/{$module}ServiceProvider.php");

        if (File::exists($repositoryProviderPath)) {
            $content = File::get($repositoryProviderPath);
            $binding = "\s*\\\$this->app->bind\(.*{$entity}Repository::class.*{$entity}RepositoriesImpl::class.*;\n";
            $updatedContent = preg_replace("/{$binding}/", '', $content);
            File::put($repositoryProviderPath, $updatedContent);
            $this->info("Updated RepositoryServiceProvider: Removed {$entity} binding");
        }

        if (File::exists($serviceProviderPath)) {
            $content = File::get($serviceProviderPath);
            $binding = "\s*\\\$this->app->bind\(.*{$entity}Service::class.*{$entity}ServiceImpl::class.*;\n";
            $updatedContent = preg_replace("/{$binding}/", '', $content);
            File::put($serviceProviderPath, $updatedContent);
            $this->info("Updated ServiceServiceProvider: Removed {$entity} binding");
        }

        if (File::exists($moduleServiceProviderPath)) {
            $content = File::get($moduleServiceProviderPath);
            $entityLower = Str::snake($entity);
            $routeLoad = "\s*\\\$this->loadRoutesFrom\(__DIR__ \. '\/\.\.\/Http\/Routes\/{$entityLower}\.php'\);\n";
            $updatedContent = preg_replace("/{$routeLoad}/", '', $content);
            File::put($moduleServiceProviderPath, $updatedContent);
            $this->info("Updated {$module}ServiceProvider: Removed {$entity} routes");
        }
    }
    protected function removeModuleProvidersAndDirectory($module)
    {
        $providersPath = base_path('bootstrap/providers.php');

        if (File::exists($providersPath)) {
            $content = File::get($providersPath);

            $patterns = [
                "/\s*Modules\\\\{$module}\\\\Providers\\\\{$module}ServiceProvider::class,\s*\n/",
                "/\s*Modules\\\\{$module}\\\\Providers\\\\RepositoryServiceProvider::class,\s*\n/",
                "/\s*Modules\\\\{$module}\\\\Providers\\\\ServiceServiceProvider::class,\s*\n/",
            ];

            $updatedContent = $content;
            foreach ($patterns as $pattern) {
                $updatedContent = preg_replace($pattern, "\n", $updatedContent);
            }

            File::put($providersPath, $updatedContent);
            $this->info("Updated bootstrap/providers.php: Removed {$module} provider registrations");
        } else {
            $this->info("Providers file not found at {$providersPath}. Skipping provider update.");
        }

        $modulePath = base_path("Modules/{$module}");
        if (File::exists($modulePath)) {
            File::deleteDirectory($modulePath);
            $this->info("Deleted entire module directory: {$modulePath}");
        } else {
            $this->info("Module directory not found at {$modulePath}. Skipping deletion.");
        }
    }
}
