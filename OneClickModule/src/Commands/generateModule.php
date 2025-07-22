<?php

namespace IslamWalied\OneClickModule\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;

class generateModule extends Command
{
    protected $signature = 'make:module {module : The name of the module (e.g., Utilities)} {entity : The name of the entity (e.g., Country)}';
    protected $description = 'Create a new module or entity with CRUD operations by interactively entering attributes (type "done" to finish)';

    public function handle()
    {
        $module = Str::studly($this->argument('module'));
        $entity = Str::studly($this->argument('entity'));

        $data = $this->collectAttributes();
        $attributes = $data['attributes'];
        $translatableAttributes = $data['translatableAttributes'];

        $modulePath = base_path("Modules/{$module}");
        $configPath = "{$modulePath}/Config";
        $databasePath = "{$modulePath}/Database";
        $migrationsPath = "{$databasePath}/Migrations";
        $seedersPath = "{$databasePath}/Seeders";
        $httpPath = "{$modulePath}/Http";
        $controllersPath = "{$httpPath}/Controllers";
        $requestsPath = "{$httpPath}/Requests";
        $resourcesPath = "{$httpPath}/Routes";
        $routesPath = "{$httpPath}/Resources";
        $modelsPath = "{$modulePath}/Models";
        $repositoriesPath = "{$modulePath}/Repositories";
        $repositoriesImplPath = "{$repositoriesPath}/Implementation";
        $repositoriesInterfacePath = "{$repositoriesPath}/Interface";
        $servicesPath = "{$modulePath}/Services";
        $servicesImplPath = "{$servicesPath}/Implementation";
        $servicesInterfacePath = "{$servicesPath}/Interface";
        $providersPath = "{$modulePath}/Providers";
        $isFirstInstall = !File::exists(base_path('Modules'));
        if (!File::exists($modulePath)) {
            File::makeDirectory($modulePath, 0755, true);
            File::makeDirectory($configPath, 0755, true);
            File::makeDirectory($migrationsPath, 0755, true);
            File::makeDirectory($seedersPath, 0755, true);
            File::makeDirectory($controllersPath, 0755, true);
            File::makeDirectory($requestsPath, 0755, true);
            File::makeDirectory($resourcesPath, 0755, true);
            File::makeDirectory($routesPath, 0755, true);
            File::makeDirectory($modelsPath, 0755, true);
            File::makeDirectory($repositoriesImplPath, 0755, true);
            File::makeDirectory($repositoriesInterfacePath, 0755, true);
            File::makeDirectory($servicesImplPath, 0755, true);
            File::makeDirectory($servicesInterfacePath, 0755, true);
            File::makeDirectory($providersPath, 0755, true);

            $this->createConfigFile($configPath, $module);
            $this->createModuleServiceProvider(base_path('app/Providers'));
            $this->createBaseServiceProvider($providersPath, $module);

            $this->createRepositoryServiceProvider($providersPath, $module);

            $this->createServiceServiceProvider($providersPath, $module);
            $this->registerProvidersInBootstrap($module);
            if ($isFirstInstall) {
                $this->updateMainDatabaseSeeder();
            }
            $this->info("Module {$module} created successfully.");
        }

        $this->createMigration($migrationsPath, $entity, $attributes);
        $this->createModel($modelsPath, $module, $entity, $attributes, $translatableAttributes);
        $this->createSeeder($seedersPath, $module, $entity, $attributes);
        $this->createDatabaseSeeder($seedersPath, $module, $entity);
        $this->createRepositoryInterface($repositoriesInterfacePath, $module, $entity);
        $this->createRepositoryImplementation($repositoriesImplPath, $module, $entity);
        $this->createServiceInterface($servicesInterfacePath, $module, $entity);
        $this->createBaseService($servicesInterfacePath, $module);
        $this->createServiceImplementation($servicesImplPath, $module, $entity, $attributes, $translatableAttributes);
        $this->createController($controllersPath, $module, $entity);
        $this->createStoreRequest($requestsPath, $module, $entity, $attributes);
        $this->createUpdateRequest($requestsPath, $module, $entity, $attributes);
        $this->createResource($routesPath, $module, $entity, $attributes);
        $this->createRoutes($resourcesPath, $module, $entity);
        $this->updateServiceProvider($providersPath, $module, $entity);

        $this->info("Entity {$entity} created successfully in {$module} module.");
    }

    protected function collectAttributes()
    {
        $attributes = [];
        $translatableAttributes = [];
        $dataTypes = [
            'string' => 'String',
            'json' => 'JSON (for translatable fields)',
            'integer' => 'Integer',
            'unsignedbiginteger' => 'Unsigned Big Integer (e.g., for foreign keys)',
            'foreignid' => 'Foreign ID (e.g., for foreign keys with constrained)',
            'boolean' => 'Boolean',
            'text' => 'Text',
            'longtext' => 'LongText',
            'date' => 'Date',
            'timestamp' => 'Timestamp',
            'decimal' => 'Decimal',
            'float' => 'Float',
            'double' => 'Double',
            'enum' => 'Enum (e.g., for predefined values)',
            'uuid' => 'UUID',
            'char' => 'Char (fixed-length string)',
            'mediumtext' => 'MediumText',
            'tinyinteger' => 'Tiny Integer (e.g., for small integers)',
            'biginteger' => 'Big Integer',
        ];

        $this->info('Enter attributes in the format: name:type:modifier1:modifier2 (e.g., name:json:unique:nullable:default=example). Type "done" to finish.');
        while (true) {
            $input = $this->ask('Enter an attribute (or type "done" to finish)');

            if (strtolower($input) === 'done') {
                break;
            }

            $parts = explode(':', $input);
            if (count($parts) < 2) {
                $this->error('Invalid format. Please use: name:type:modifier1:modifier2 (e.g., name:json:unique:nullable:default=example)');
                continue;
            }

            $name = $parts[0];
            $dataType = strtolower(trim($parts[1]));
            $modifiers = array_slice($parts, 2);

            if (!array_key_exists($dataType, $dataTypes)) {
                $this->error('Invalid data type. Available types: ' . implode(', ', array_keys($dataTypes)));
                continue;
            }

            $attribute = ['name' => $name, 'type' => $dataType];

            if (in_array($dataType, ['unsignedbiginteger', 'foreignid'])) {
                $foreignTable = $this->ask('Enter the table this foreign key references (e.g., countries)');
                $attribute['foreign'] = $foreignTable;
            } elseif ($dataType === 'enum') {
                $enumValues = $this->ask('Enter the allowed enum values (comma-separated, e.g., active,inactive,pending)');
                $attribute['enum_values'] = explode(',', $enumValues);
            }

            foreach ($modifiers as $modifier) {
                $modifier = strtolower(trim($modifier));
                if ($modifier === 'unique') {
                    $attribute['unique'] = true;
                } elseif ($modifier === 'nullable') {
                    $attribute['nullable'] = true;
                } elseif (str_starts_with($modifier, 'default=')) {
                    $defaultValue = substr($modifier, strlen('default='));
                    $attribute['default'] = $defaultValue;
                }
            }

            $attributes[] = $attribute;
        }

        if (empty($attributes)) {
            $this->info('No attributes provided. Adding default "name:json" attribute.');
            $attributes[] = ['name' => 'name', 'type' => 'json'];
        }

        $enableTranslations = $this->confirm('Do you want to enable translations for this entity?', false);
        if ($enableTranslations) {
            $this->info('Select which attributes should be translatable. Enter the attribute names (one per line). Type "done" to finish.');
            while (true) {
                $input = $this->ask('Enter an attribute name to make translatable (or type "done" to finish)');
                if (strtolower($input) === 'done') {
                    break;
                }

                $attributeExists = false;
                foreach ($attributes as $attribute) {
                    if ($attribute['name'] === $input) {
                        $translatableAttributes[] = $input;
                        $attributeExists = true;
                        break;
                    }
                }

                if (!$attributeExists) {
                    $this->error("Attribute '{$input}' not found in the provided attributes.");
                }
            }
        }

        return [
            'attributes' => $attributes,
            'translatableAttributes' => $translatableAttributes,
        ];
    }
    protected function updateMainDatabaseSeeder()
    {
        $seederPath = database_path('seeders/DatabaseSeeder.php');
        $content = <<<PHP
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        \$modules = File::directories(base_path('Modules'));

        foreach (\$modules as \$module) {
            \$moduleName = basename(\$module);
            \$seederClass = "Modules\\\\{\$moduleName}\\\\Database\\\\Seeders\\\\DatabaseSeeder";

            if (class_exists(\$seederClass)) {
                \$this->call(\$seederClass);
            }
        }
    }
}
PHP;

        // Only update if the file doesn't exist or doesn't contain the module seeder logic
        if (!File::exists($seederPath) || !str_contains(File::get($seederPath), 'Modules\\')) {
            File::put($seederPath, $content);
            $this->info('Main DatabaseSeeder updated successfully.');
        }
    }

    protected function createConfigFile($path, $module)
    {
        $content = <<<PHP
<?php

return [
    'name' => '{$module}',
    'prefix' => strtolower('{$module}'),
];
PHP;
        File::put("{$path}/config.php", $content);
    }

    protected function createModuleServiceProvider($path)
    {
        $content = <<<PHP
<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\File;

class ModuleServiceProvider extends ServiceProvider
{
    public function boot()
    {
        \$modules = File::directories(base_path('Modules'));

        Factory::guessFactoryNamesUsing(function (string \$modelName) use (\$modules) {
            foreach (\$modules as \$module) {
                \$moduleName = basename(\$module);
                \$modelNamespace = "Modules\\\\{\$moduleName}\\\\Models\\\\";
                \$factoryNamespace = "Modules\\\\{\$moduleName}\\\\Database\\\\Factories\\\\";

                if (str_starts_with(\$modelName, \$modelNamespace)) {
                    return \$factoryNamespace . class_basename(\$modelName) . 'Factory';
                }
            }

            return 'Database\\\\Factories\\\\' . class_basename(\$modelName) . 'Factory';
        });

        foreach (\$modules as \$module) {
            \$moduleName = basename(\$module);

            \$providerClass = "Modules\\\\{\$moduleName}\\\\Providers\\\\{\$moduleName}ServiceProvider";
            if (class_exists(\$providerClass)) {
                \$this->app->register(\$providerClass);
            }

            \$routesPath = \$module . '/Http/Routes';
            if (File::exists(\$routesPath)) {
                Route::middleware('api')
                    ->prefix('api')
                    ->group(function () use (\$routesPath) {
                        \$routeFiles = File::glob(\$routesPath . '/*.php');
                        foreach (\$routeFiles as \$routeFile) {
                            \$this->loadRoutesFrom(\$routeFile);
                        }
                    });
            }

            if (File::exists(\$module . '/Database/Migrations')) {
                \$this->loadMigrationsFrom(\$module . '/Database/Migrations');
            }

            if (File::exists(\$module . '/Config')) {
                \$configFiles = File::glob(\$module . '/Config/*.php');
                foreach (\$configFiles as \$configFile) {
                    \$configName = strtolower(\$moduleName) . '.' . pathinfo(\$configFile, PATHINFO_FILENAME);
                    \$this->mergeConfigFrom(\$configFile, \$configName);
                }
            }
        }
    }
}
PHP;
        File::put("{$path}/ModuleServiceProvider.php", $content);
    }

    protected function createMigration($path, $entity, $attributes)
    {
        $tableName = Str::snake(Str::plural($entity));
        $migrationName = date('Y_m_d_His') . "_create_{$tableName}_table.php";
        $fields = '';
        foreach ($attributes as $attribute) {
            $name = $attribute['name'];
            $type = $attribute['type'];
            $fieldType = match ($type) {
                'foreignid' => 'foreignId',
                'unsignedbiginteger' => 'unsignedBigInteger',
                'longtext' => 'longText',
                'mediumtext' => 'mediumText',
                'tinyinteger' => 'tinyInteger',
                'biginteger' => 'bigInteger',
                'enum' => "enum('{$name}', ['" . implode("','", $attribute['enum_values']) . "'])",
                default => $type,
            };
            $field = "\$table->{$fieldType}('{$name}')";

            if (isset($attribute['nullable']) && $attribute['nullable']) {
                $field .= "->nullable()";
            }
            if (isset($attribute['unique']) && $attribute['unique']) {
                $field .= "->unique()";
            }
            if (isset($attribute['default'])) {
                $default = $attribute['default'];
                if (is_numeric($default)) {
                    $field .= "->default({$default})";
                } else {
                    $field .= "->default('{$default}')";
                }
            }
            if (isset($attribute['foreign'])) {
                $field .= "->constrained('{$attribute['foreign']}')->cascadeOnUpdate()->cascadeOnDelete()";
            }

            $fields .= "            {$field};\n";
        }

        $content = <<<PHP
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('{$tableName}', function (Blueprint \$table) {
            \$table->id();
{$fields}            \$table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('{$tableName}');
    }
};
PHP;
        File::put("{$path}/{$migrationName}", $content);
    }

    protected function createModel($path, $module, $entity, $attributes, $translatableAttributes)
    {
        $fillable = '';
        $translatable = '';
        $hasTranslatable = !empty($translatableAttributes);

        foreach ($attributes as $attribute) {
            $name = $attribute['name'];
            $fillable .= "        '{$name}',\n";
        }

        foreach ($translatableAttributes as $translatableAttribute) {
            $translatable .= "        '{$translatableAttribute}',\n";
        }

        $translatableTrait = $hasTranslatable ? "use Spatie\\Translatable\\HasTranslations;\n" : '';
        $translatableUse = $hasTranslatable ? "use HasTranslations;\n" : '';
        $translatableProperty = $hasTranslatable ? "    public \$translatable = [\n{$translatable}    ];\n\n" : '';

        $content = <<<PHP
<?php

namespace Modules\\{$module}\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
{$translatableTrait}
class {$entity} extends Model
{
    use HasFactory;
    {$translatableUse}
{$translatableProperty}    protected \$fillable = [
{$fillable}    ];
}
PHP;
        File::put("{$path}/{$entity}.php", $content);
    }

    protected function createSeeder($path, $module, $entity, $attributes)
    {
        $data = '';
        foreach ($attributes as $attribute) {
            $name = $attribute['name'];
            $type = $attribute['type'];
            $value = $this->getSampleValue($type, $name, $attribute);
            $data .= "            '{$name}' => {$value},\n";
        }

        $content = <<<PHP
<?php

namespace Modules\\{$module}\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\\{$module}\Models\\{$entity};

class {$entity}Seeder extends Seeder
{
    public function run(): void
    {
        \$data = [
{$data}        ];
        {$entity}::create(\$data);
    }
}
PHP;
        File::put("{$path}/{$entity}Seeder.php", $content);
    }

    protected function getSampleValue($type, $name, $attribute)
    {
        if (isset($attribute['default'])) {
            return is_numeric($attribute['default']) ? $attribute['default'] : "'{$attribute['default']}'";
        }

        switch ($type) {
            case 'string':
                return "'{$name}'";
            case 'json':
                return "[
                'en' => '{$name}',
                'ar' => '{$name}',
            ]";
            case 'integer':
            case 'biginteger':
            case 'unsignedbiginteger':
            case 'foreignid':
                return 1;
            case 'tinyinteger':
                return 0;
            case 'decimal':
            case 'float':
            case 'double':
                return 1.1;
            case 'boolean':
                return 'true';
            case 'text':
            case 'mediumtext':
            case 'longtext':
                return "'Sample text for {$name}'";
            case 'date':
                return "'2025-09-25'";
            case 'timestamp':
                return "'2025-09-25 12:00:00'";
            case 'enum':
                return isset($attribute['enum_values'][0]) ? "'{$attribute['enum_values'][0]}'" : "'default'";
            case 'uuid':
                return "'" . Str::uuid() . "'";
            case 'char':
                return "'" . substr($name, 0, 1) . "'";
            default:
                return "'sample_{$name}'";
        }
    }

    protected function createDatabaseSeeder($path, $module, $entity)
    {
        $existingContent = File::exists("{$path}/DatabaseSeeder.php") ? File::get("{$path}/DatabaseSeeder.php") : null;
        $call = "        \$this->call([{$entity}Seeder::class]);\n";

        if ($existingContent) {
            $foreignKeyEnd = "        DB::statement('SET FOREIGN_KEY_CHECKS=1;');";
            if (str_contains($existingContent, $foreignKeyEnd)) {
                $content = str_replace($foreignKeyEnd, $call . $foreignKeyEnd, $existingContent);
            } else {
                $content = str_replace('    }', $call . '    }', $existingContent);
            }
        } else {
            $content = <<<PHP
<?php

namespace Modules\\{$module}\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
{$call}        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}
PHP;
        }
        File::put("{$path}/DatabaseSeeder.php", $content);
    }

    protected function createRepositoryInterface($path, $module, $entity)
    {
        $content = <<<PHP
<?php

namespace Modules\\{$module}\Repositories\Interface;

interface {$entity}Repository
{
    public function get(\$query, \$limit);
    public function show(\$model);
    public function store(\$model);
    public function update(\$model);
    public function delete(\$model);
}
PHP;
        File::put("{$path}/{$entity}Repository.php", $content);
    }

    protected function createRepositoryImplementation($path, $module, $entity)
    {
        $content = <<<PHP
<?php

namespace Modules\\{$module}\Repositories\Implementation;

use Modules\\{$module}\Models\\{$entity};
use Modules\\{$module}\Repositories\Interface\\{$entity}Repository;

class {$entity}RepositoriesImpl implements {$entity}Repository
{
    public function get(\$query, \$limit)
    {
        return {$entity}::orderBy('created_at','desc')
                ->paginate(\$limit);
    }

    public function show(\$model)
    {
        return {$entity}::findOrFail(\$model);
    }

    public function store(\$model)
    {
        return {$entity}::create(\$model);
    }

    public function update(\$model)
    {
        \$model->save();
        return \$model;
    }

    public function delete(\$model)
    {
        \$model->delete();
    }
}
PHP;
        File::put("{$path}/{$entity}RepositoriesImpl.php", $content);
    }

    protected function createServiceInterface($path, $module, $entity)
    {
        $entityLower = Str::camel($entity);
        $content = <<<PHP
<?php

namespace Modules\\{$module}\Services\Interface;

interface {$entity}Service
{
    public function index(\$query, \$limit);
    public function show(\${$entityLower});
    public function store(\$request);
    public function update(\$request, \${$entityLower});
    public function delete(\${$entityLower});
}
PHP;
        File::put("{$path}/{$entity}Service.php", $content);
    }

    protected function createBaseService($path, $module)
    {
        if (File::exists("{$path}/BaseService.php")) {
            return;
        }

        $content = <<<PHP
<?php

namespace Modules\\{$module}\Services\Interface;

use Illuminate\Http\JsonResponse;
use App\Traits\ImageTrait;
use App\Traits\ResponseTrait;

class BaseService
{
    use ResponseTrait, ImageTrait;
}
PHP;
        File::put("{$path}/BaseService.php", $content);
    }

    protected function createServiceImplementation($path, $module, $entity, $attributes, $translatableAttributes)
    {
        $entityLower = Str::camel($entity);
        $dataArray = '';
        $updateAssignments = '';
        foreach ($attributes as $attribute) {
            $name = $attribute['name'];
            $dataArray .= "                '{$name}' => \$request->{$name},\n";
            $updateAssignments .= "        \${$entityLower}->{$name} = \$request->{$name} ?? \${$entityLower}->{$name};\n";
        }

        $content = <<<PHP
<?php

namespace Modules\\{$module}\Services\Implementation;

use Modules\\{$module}\Http\Resources\\{$entity}Resource;
use Illuminate\Support\Facades\Log;
use Modules\\{$module}\Repositories\Interface\\{$entity}Repository;
use Modules\\{$module}\Services\Interface\\{$entity}Service;
use Modules\\{$module}\Services\Interface\BaseService;

class {$entity}ServiceImpl extends BaseService implements {$entity}Service
{
    private {$entity}Repository \${$entityLower}Repository;

    public function __construct({$entity}Repository \${$entityLower}Repository)
    {
        \$this->{$entityLower}Repository = \${$entityLower}Repository;
    }

    public function index(\$query, \$limit)
    {
        try {
            Log::info('{$entity} index request', ['limit' => \$limit]);
            \${$entityLower} = \$this->{$entityLower}Repository->get(\$query, \$limit);
            Log::info('{$entity} index successful', ['count' => \${$entityLower}->count()]);
            return \$this->returnData(__('messages.{$entityLower}.index_success'), 200, {$entity}Resource::collection(\${$entityLower}));
        } catch (\Exception \$e) {
            Log::error('{$entity} fetch Error: ', ['error' => \$e->getMessage()]);
            return \$this->returnError(__('messages.{$entityLower}.index_failed'), 500);
        }
    }

    public function show(\${$entityLower})
    {
        try {
            Log::info('{$entity} show request', ['id' => \${$entityLower}->id]);
            \${$entityLower} = \$this->{$entityLower}Repository->show(\${$entityLower}->id);
            Log::info('{$entity} show successful', ['id' => \${$entityLower}->id]);
            return \$this->returnData(__('messages.{$entityLower}.show_success'), 200, new {$entity}Resource(\${$entityLower}));
        } catch (\Exception \$e) {
            Log::error('{$entity} fetch Error:', ['error' => \$e->getMessage()]);
            return \$this->returnError(__('messages.{$entityLower}.show_failed'), 500);
        }
    }

    public function store(\$request)
    {
        try {
            Log::info('{$entity} store request', \$request->all());
            \${$entityLower} = [
{$dataArray}            ];
            \${$entityLower} = \$this->{$entityLower}Repository->store(\${$entityLower});
            Log::info('{$entity} created successfully');
            return \$this->returnData(__('messages.{$entityLower}.create_success'), 201, new {$entity}Resource(\${$entityLower}));
        } catch (\Exception \$e) {
            Log::error('{$entity} Create Error', ['error' => \$e->getMessage()]);
            return \$this->returnError(__('messages.{$entityLower}.create_failed'), 500);
        }
    }

    public function update(\$request, \${$entityLower})
    {
        try {
            Log::info('{$entity} update request', ['id' => \${$entityLower}->id, 'data' => \$request->all()]);
    {$updateAssignments}            \${$entityLower} = \$this->{$entityLower}Repository->update(\${$entityLower});
            Log::info('{$entity} updated successfully', ['id' => \${$entityLower}->id]);
            return \$this->returnData(__('messages.{$entityLower}.update_success'), 200, new {$entity}Resource(\${$entityLower}));
        } catch (\Exception \$e) {
            Log::error('{$entity} Update Error', ['error' => \$e->getMessage()]);
            return \$this->returnError(__('messages.{$entityLower}.update_failed'), 500);
        }
    }

    public function delete(\${$entityLower})
    {
        try {
            Log::info('{$entity} delete request', ['id' => \${$entityLower}->id]);
            \$this->{$entityLower}Repository->delete(\${$entityLower});
            Log::info('{$entity} deleted successfully', ['id' => \${$entityLower}->id]);
            return \$this->success(__('messages.{$entityLower}.delete_success'), 204);
        } catch (\Exception \$e) {
            Log::error('{$entity} Delete Error', ['error' => \$e->getMessage()]);
            return \$this->returnError(__('messages.{$entityLower}.delete_failed'), 500);
        }
    }
}
PHP;
        File::put("{$path}/{$entity}ServiceImpl.php", $content);
    }

    protected function createController($path, $module, $entity)
    {
        $entityLower = Str::camel($entity);
        $content = <<<PHP
<?php

namespace Modules\\{$module}\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\\{$module}\Http\Requests\Store{$entity}Request;
use Modules\\{$module}\Http\Requests\Update{$entity}Request;
use Modules\\{$module}\Models\\{$entity};
use Modules\\{$module}\Services\Interface\\{$entity}Service;

class {$entity}Controller extends Controller
{
    protected {$entity}Service \${$entityLower}Service;

    public function __construct({$entity}Service \${$entityLower}Service)
    {
        \$this->{$entityLower}Service = \${$entityLower}Service;
    }

    public function index(): JsonResponse
    {
        return \$this->{$entityLower}Service->index(
            request('query'),
            request('per_page', 10)
        );
    }

    public function show({$entity} \${$entityLower}): JsonResponse
    {
        return \$this->{$entityLower}Service->show(\${$entityLower});
    }

    public function store(Store{$entity}Request \$request): JsonResponse
    {
        return \$this->{$entityLower}Service->store(\$request);
    }

    public function update(Update{$entity}Request \$request, {$entity} \${$entityLower}): JsonResponse
    {
        return \$this->{$entityLower}Service->update(\$request, \${$entityLower});
    }

    public function destroy({$entity} \${$entityLower}): JsonResponse
    {
        return \$this->{$entityLower}Service->delete(\${$entityLower});
    }
}
PHP;
        File::put("{$path}/{$entity}Controller.php", $content);
    }

    protected function createStoreRequest($path, $module, $entity, $attributes)
    {
        $rules = '';
        foreach ($attributes as $attribute) {
            $name = $attribute['name'];
            $type = $attribute['type'];
            $rule = $this->getValidationRule($entity, $type, $attribute, true);
            $rules .= "            '{$name}' => '{$rule}',\n";
        }

        $content = <<<PHP
<?php

namespace Modules\\{$module}\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class Store{$entity}Request extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
{$rules}        ];
    }
}
PHP;
        File::put("{$path}/Store{$entity}Request.php", $content);
    }

    protected function createUpdateRequest($path, $module, $entity, $attributes)
    {
        $rules = '';
        foreach ($attributes as $attribute) {
            $name = $attribute['name'];
            $type = $attribute['type'];
            $rule = $this->getValidationRule($entity, $type, $attribute, false);
            $rules .= "            '{$name}' => '{$rule}',\n";
        }

        $content = <<<PHP
<?php

namespace Modules\\{$module}\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class Update{$entity}Request extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
{$rules}        ];
    }
}
PHP;
        File::put("{$path}/Update{$entity}Request.php", $content);
    }

    protected function getValidationRule($entity, $type, $attribute, $isStore = true)
    {
        $rules = [];
        $required = $isStore && !isset($attribute['nullable']) ? 'required' : ($isStore ? 'sometimes' : 'nullable');

        switch ($type) {
            case 'string':
                $rules[] = $required;
                $rules[] = 'string';
                $rules[] = 'max:255';
                break;
            case 'char':
                $rules[] = $required;
                $rules[] = 'string';
                $rules[] = 'size:1';
                break;
            case 'json':
                $rules[] = $required;
                $rules[] = 'array';
                break;
            case 'integer':
            case 'biginteger':
            case 'tinyinteger':
            case 'unsignedbiginteger':
            case 'foreignid':
                $rules[] = $required;
                $rules[] = 'integer';
                if (isset($attribute['foreign'])) {
                    $rules[] = "exists:{$attribute['foreign']},id";
                }
                break;
            case 'boolean':
                $rules[] = $required;
                $rules[] = 'boolean';
                break;
            case 'text':
            case 'mediumtext':
            case 'longtext':
                $rules[] = $required;
                $rules[] = 'string';
                break;
            case 'date':
            case 'timestamp':
                $rules[] = $required;
                $rules[] = 'date';
                break;
            case 'decimal':
            case 'float':
            case 'double':
                $rules[] = $required;
                $rules[] = 'decimal:0,3';
                break;
            case 'enum':
                $enumValues = isset($attribute['enum_values']) ? implode(',', $attribute['enum_values']) : '';
                $rules[] = $required;
                $rules[] = "in:{$enumValues}";
                break;
            case 'uuid':
                $rules[] = $required;
                $rules[] = 'uuid';
                break;
            default:
                $rules[] = $required;
                $rules[] = 'string';
        }

        if (isset($attribute['unique']) && $attribute['unique']) {
            $tableName = Str::snake(Str::plural($entity));
            $rules[] = "unique:{$tableName},{$attribute['name']}";
        }

        return implode('|', $rules);
    }

    protected function createResource($path, $module, $entity, $attributes)
    {
        $fields = '';
        foreach ($attributes as $attribute) {
            $name = $attribute['name'];
            $fields .= "            '{$name}' => \$this->{$name},\n";
        }

        $content = <<<PHP
<?php

namespace Modules\\{$module}\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class {$entity}Resource extends JsonResource
{
    public function toArray(Request \$request): array
    {
        return [
            'id' => \$this->id,
{$fields}        ];
    }
}
PHP;
        File::put("{$path}/{$entity}Resource.php", $content);
    }

    protected function createRoutes($path, $module, $entity)
    {
        $entityLower = Str::camel($entity);
        $entityPlural = Str::kebab(Str::plural($entity));
        $content = <<<PHP
<?php

use Illuminate\Support\Facades\Route;
use Modules\\{$module}\Http\Controllers\\{$entity}Controller;

Route::middleware(['cors', 'lang', 'throttle'])->prefix('v1/')->group(function () {
    Route::middleware(['auth:sanctum'])->group(function () {
        Route::get('{$entityPlural}', [{$entity}Controller::class, 'index']);
        Route::get('{$entityPlural}/{{$entityLower}}', [{$entity}Controller::class, 'show']);
        Route::post('{$entityPlural}', [{$entity}Controller::class, 'store']);
        Route::patch('{$entityPlural}/{{$entityLower}}', [{$entity}Controller::class, 'update']);
        Route::delete('{$entityPlural}/{{$entityLower}}', [{$entity}Controller::class, 'destroy']);
    });
});
PHP;
        File::put("{$path}/{$entityLower}.php", $content);
    }

    protected function createBaseServiceProvider($path, $module)
    {
        $moduleLower = strtolower($module);
        $content = <<<PHP
<?php

namespace Modules\\{$module}\Providers;

use Illuminate\Support\ServiceProvider;

class {$module}ServiceProvider extends ServiceProvider
{
    public function register(): void
    {
    }

    public function boot(): void
    {

    }
}
PHP;
        File::put("{$path}/{$module}ServiceProvider.php", $content);
    }

    protected function createRepositoryServiceProvider($path, $module)
    {
        $content = <<<PHP
<?php

namespace Modules\\{$module}\Providers;

use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
    }
}
PHP;
        File::put("{$path}/RepositoryServiceProvider.php", $content);
    }

    protected function createServiceServiceProvider($path, $module)
    {
        $content = <<<PHP
<?php

namespace Modules\\{$module}\Providers;

use Illuminate\Support\ServiceProvider;

class ServiceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
    }
}
PHP;
        File::put("{$path}/ServiceServiceProvider.php", $content);
    }

    protected function updateServiceProvider($path, $module, $entity)
    {
        $repositoryProviderPath = "{$path}/RepositoryServiceProvider.php";
        $serviceProviderPath = "{$path}/ServiceServiceProvider.php";

        $repositoryBinding = "        \$this->app->bind(\Modules\\{$module}\Repositories\Interface\\{$entity}Repository::class, \Modules\\{$module}\Repositories\Implementation\\{$entity}RepositoriesImpl::class);\n";
        $repositoryContent = File::get($repositoryProviderPath);
        if (!str_contains($repositoryContent, $repositoryBinding)) {
            $registerEnd = '    }';
            $repositoryContent = str_replace($registerEnd, $repositoryBinding . $registerEnd, $repositoryContent);
            File::put($repositoryProviderPath, $repositoryContent);
        }

        $serviceBinding = "        \$this->app->bind(\Modules\\{$module}\Services\Interface\\{$entity}Service::class, \Modules\\{$module}\Services\Implementation\\{$entity}ServiceImpl::class);\n";
        $serviceContent = File::get($serviceProviderPath);
        if (!str_contains($serviceContent, $serviceBinding)) {
            $registerEnd = '    }';
            $serviceContent = str_replace($registerEnd, $serviceBinding . $registerEnd, $serviceContent);
            File::put($serviceProviderPath, $serviceContent);
        }
    }

    protected function registerProvidersInBootstrap($module)
    {
        $bootstrapPath = base_path('bootstrap/providers.php');
        if (!File::exists($bootstrapPath)) {
            $content = <<<PHP
<?php

return [
    App\Providers\AppServiceProvider::class,
    App\Providers\ModuleServiceProvider::class,
];
PHP;
            File::put($bootstrapPath, $content);
            $this->info("Created bootstrap/providers.php with ModuleServiceProvider registered.");
            return;
        }

        $content = File::get($bootstrapPath);
        $providers = [
            "Modules\\{$module}\\Providers\\{$module}ServiceProvider::class",
            "Modules\\{$module}\\Providers\\RepositoryServiceProvider::class",
            "Modules\\{$module}\\Providers\\ServiceServiceProvider::class",
            "App\\Providers\\ModuleServiceProvider::class",
        ];

        $providerLines = "\n";
        foreach ($providers as $provider) {
            if (!str_contains($content, $provider)) {
                $providerLines .= "    {$provider},\n";
            }
        }

        if ($providerLines) {
            $pattern = "/return\s*\[([\s\S]*?)];/";
            if (preg_match($pattern, $content, $matches)) {
                $providersArrayContent = $matches[1];
                $newProvidersArrayContent = rtrim($providersArrayContent, ",\n") . ",\n" . $providerLines;
                $content = str_replace($providersArrayContent, $newProvidersArrayContent, $content);
                File::put($bootstrapPath, $content);
                $this->info("Appended {$module} providers to bootstrap/providers.php.");
            } else {
                $this->error('Could not find providers array in bootstrap/providers.php.');
            }
        }
    }
}
