# One Click Module

**OneClickModule** is a Laravel package that simplifies creating and managing CRUD operations within a modular structure. It provides tools to generate complete modules or entities with all necessary files and safely remove them when no longer needed.

## Key Features

- **Rapid Scaffolding**: Build a complete CRUD-ready Laravel module structure in minutes.
- **Entity Deletion**: Remove entities and their associated files with a single command.
- **Repository Pattern**: Implements clean, reusable data access with repository interfaces and implementations.
- **Service Layer**: Separates business logic using a service layer with dependency injection.
- **API Support**: Generates API routes with middleware (e.g., Sanctum, CORS, throttling).
- **Customizable Models**: Define model attributes interactively, including types, modifiers, and translatable fields.
- **Translatable Fields**: Supports multilingual attributes using `spatie/laravel-translatable`.
- **Standardized Responses**: Delivers consistent JSON API outputs with `ResponseTrait`.
- **Flexible Attributes**: Supports modifiers like `nullable`, `unique`, and `default` for model attributes.
- **Enum Columns**: Enables enum columns with predefined values in migrations.
- **Foreign Keys**: Configures foreign key constraints and model relationships automatically.
- **Validation Rules**: Generates request classes with validation based on attribute types.
- **Database Seeding**: Creates seeders and updates the database seeder for easy data population.
- **Dynamic Registration**: Registers service providers automatically in `bootstrap/providers.php`.
- **Image Management**: Handles image uploads, updates, and deletions with `ImageTrait`.
- **Route Management**: Dynamically includes API routes within the module structure.

## Installation

Install via Composer:

```bash
composer require islamwalied/one-click-module
```

The package auto-registers its service provider and commands using Laravel's discovery feature.

## Requirements

- **PHP**: 8.0 or higher
- **Laravel**: 10.x or 11.x
- **Composer**
- **spatie/laravel-translatable** (optional, for translatable fields)
- **GD Library**: Required for image handling (`ext-gd`)

### Composer Configuration

Add the GD extension to your `composer.json`:

```json
"require": {
    "ext-gd": "*"
}
```

And Also Add Modules autoload to `composer.json` inside psr-4 append
```json
"psr-4": {
    "Modules\\": "Modules/",
}
```


#### Important Tip
`you should run these commands or just the last one
everytime you make new model in the module`
```bash
composer dump-autoload
php artisan cache:clear
php artisan config:clear
php artisan config:cache
php artisan route:clear
php artisan route:cache
php artisan optimize:clear
php artisan optimize
```

### Optional Dependency

For translatable fields, install:

```bash
composer require spatie/laravel-translatable
```

## Usage

The package provides two Artisan commands for managing modules and entities:

### 1. Create a Module or Entity

Generate a module or entity with full CRUD functionality:

```bash
php artisan make:module {module} {entity}
```

- `{module}`: Module name (e.g., `Utilities`).
- `{entity}`: Entity name (e.g., `Country`).

#### Example

```bash
php artisan make:module Utilities Country
```

##### Interactive Setup
1. **Model Attributes**: Define column names, types, and modifiers:
   - Example:
     ```
     Enter an attribute (or type "done" to finish): name:json:nullable
     Enter an attribute: population:integer
     Enter an attribute: capital:string:unique
     Enter an attribute: done
     ```
   - Supported types: `string`, `json`, `integer`, `unsignedBigInteger`, `foreignId`, `boolean`, `text`, `longText`, `date`, `timestamp`, `decimal`, `float`, `double`, `enum`, `uuid`, `char`, `mediumText`, `tinyInteger`, `bigInteger`.
   - Modifiers: `nullable`, `unique`, `default=value`.
2. **Foreign Keys**: Specify the referenced table for `foreignId` or `unsignedBigInteger`:
   - Example:
     ```
     Enter the table this foreign key references (e.g., countries): regions
     ```
3. **Enum Values**: Provide allowed values for `enum` types:
   - Example:
     ```
     Enter the allowed enum values (comma-separated, e.g., active,inactive): north,south,east,west
     ```
4. **Translations**: Enable and select translatable attributes:
   - Example:
     ```
     Do you want to enable translations for this entity? (yes/no): yes
     Enter an attribute name to make translatable (or type "done" to finish): name
     Enter an attribute name to make translatable: done
     ```

##### Generated Files
Under `Modules/{Module}`:
- `Config/config.php` (Module configuration)
- `Database/Migrations/*_create_{table}_table.php` (Migration file)
- `Database/Seeders/{Entity}Seeder.php` (Entity seeder)
- `Database/Seeders/DatabaseSeeder.php` (Updated to include seeder)
- `Models/{Entity}.php` (Model with fillable and translatable attributes)
- `Repositories/Interface/{Entity}Repository.php` (Repository interface)
- `Repositories/Implementation/{Entity}RepositoriesImpl.php` (Repository implementation)
- `Services/Interface/BaseService.php` (Base service)
- `Services/Interface/{Entity}Service.php` (Service interface)
- `Services/Implementation/{Entity}ServiceImpl.php` (Service implementation)
- `Http/Controllers/{Entity}Controller.php` (Controller)
- `Http/Requests/Store{Entity}Request.php` (Store request with validation)
- `Http/Requests/Update{Entity}Request.php` (Update request with validation)
- `Http/Resources/{Entity}Resource.php` (API resource)
- `Http/Routes/{entity}.php` (API routes)
- `Providers/{Module}ServiceProvider.php` (Module provider)
- `Providers/RepositoryServiceProvider.php` (Repository bindings)
- `Providers/ServiceServiceProvider.php` (Service bindings)

### 2. Delete an Entity

Remove an entity and its associated files:

```bash
php artisan delete:module {module} {entity}
```

- `{module}`: Module name (e.g., `Utilities`).
- `{entity}`: Entity to delete (e.g., `Country`).

#### Example

```bash
php artisan delete:module Utilities Country
```

##### Actions
- Prompts for confirmation.
- Deletes model, controller, service, repository, requests, resource, routes, seeder, and migration files.
- Updates `DatabaseSeeder.php`, `RepositoryServiceProvider.php`, and `ServiceServiceProvider.php`.
- Removes the module directory and provider registrations if the entity is the last in the module.

### Publishing Files

Enable all features by publishing necessary files:

```bash
php artisan make:publish-helpers
php artisan make:publish-logging
php artisan make:publish-middleware
php artisan make:publish-traits
```

### Post-Generation Steps
1. **Register Providers**: Ensure `App\Providers\ModuleServiceProvider` is in `bootstrap/providers.php` (auto-handled, but can be added manually):
   ```php
   return [
       App\Providers\ModuleServiceProvider::class,
       Modules\{Module}\Providers\{Module}ServiceProvider::class,
       Modules\{Module}\Providers\RepositoryServiceProvider::class,
       Modules\{Module}\Providers\ServiceServiceProvider::class,
   ];
   ```
2. **Run Migrations**:
   ```bash
   php artisan migrate
   ```
3. **Test API**: Use tools like Postman to test endpoints (e.g., `GET /api/v1/countries`).

## Configuration

No additional setup is required by default. For translatable fields, configure `spatie/laravel-translatable`.

### Image Storage
The `ImageTrait` uses the `public` disk by default. Customize via `filesystems.php` or extend the trait.

### Logging
After publishing logging files, update `config/logging.php`:

```php
'daily_by_date' => [
    'driver' => 'custom',
    'via' => App\Logging\LogHandler::class,
    'level' => 'error',
],
```

## API Endpoints

Generated routes in `Modules/{Module}/Http/Routes/{entity}.php`:
- `GET /v1/{entities}`: List resources (e.g., `/v1/countries`).
- `GET /v1/{entities}/{entity}`: Show a resource (e.g., `/v1/countries/1`).
- `POST /v1/{entities}`: Create a resource.
- `PATCH /v1/{entities}/{entity}`: Update a resource.
- `DELETE /v1/{entities}/{entity}`: Delete a resource.

Routes are prefixed with `v1/` and include `cors`, `lang`, and `throttle` middleware. `auth:sanctum` is applied to `store`, `update`, and `destroy`.

## Advanced Features

### Attribute Modifiers
Supported modifiers:
- `nullable`: Allows NULL values.
- `unique`: Ensures unique column values.
- `default`: Sets a default value.

### Enum Columns
Define allowed values for `enum` types, included in migrations and validation.

### Foreign Keys
For `foreignId` or `unsignedBigInteger`, migrations include constraints with cascade rules, and request classes include `exists` validation.

### Translatable Fields
Uses `Spatie\Translatable\HasTranslations` for JSON-based translatable attributes.

### Service-Repository Pattern
- **Controllers**: Interact with services only.
- **Services**: Handle business logic, using repositories.
- **Repositories**: Manage data access.
- Components are bound via dependency injection.

## Notes

- **Localization**: Uses `__()` helper for messages (e.g., `messages.country.index_success`). Define in `lang` files.
- **Module Structure**: Assumes a `Modules/` directory.
- **Dependencies**: Requires `ResponseTrait` and `ImageTrait`, published via `--tag="traits"`.

## Contributing

1. Fork the repository.
2. Create a feature branch (`git checkout -b feature/your-feature`).
3. Commit changes (`git commit -m "Add your feature"`).
4. Push the branch (`git push origin feature/your-feature`).
5. Submit a Pull Request.

## Issues

Report issues or suggestions at [GitHub Issues](https://github.com/islamwalied/oneClickModule/issues).

## License

Licensed under the [MIT License](LICENSE).

## Credits

Developed by [Islam Walied](mailto:islam.walied96@gmail.com). Thanks to the Laravel community for inspiration and support.