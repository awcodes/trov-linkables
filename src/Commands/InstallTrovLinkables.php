<?php

namespace Trov\Linkables\Commands;

use Illuminate\Support\Str;
use Filament\Facades\Filament;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Artisan;

class InstallTrovLinkables extends Command
{
    use \Trov\Commands\Concerns\CanManipulateFiles;

    public $signature = 'trov-linkables:install {--F|fresh}';

    public $description = "One Command to Rule them All 🔥";

    public function handle(): int
    {
        $this->alert('The Following operations will be performed.');
        $this->info('- Publish core package config');
        $this->info('- Publish core package migration');
        $this->warn('  - On fresh applications database will be migrated');
        $this->warn('  - You can also force this behavior by supplying the --fresh option');
        $this->info('- Publish Filament Resources');

        $confirmed = $this->confirm('Do you wish to continue?', true);

        if ($this->CheckIfAlreadyInstalled() && !$this->option('fresh')) {
            $this->comment('Seems you have already installed the Core package!');
            $this->comment('You should run `trov-linkables:install --fresh` instead to refresh the Core package tables and setup.');

            if ($this->confirm('Run `trov-linkables:install --fresh` instead?', false)) {
                $this->install(true);
            }

            return self::INVALID;
        }

        if ($confirmed) {
            $this->install($this->option('fresh'));
        } else {
            $this->comment('`trov-linkables:install` command was cancelled.');
        }

        return self::SUCCESS;
    }

    protected function CheckIfAlreadyInstalled(): bool
    {
        $count = $this->getTables()
            ->filter(function ($table) {
                return Schema::hasTable($table);
            })
            ->count();
        if ($count !== 0) {
            return true;
        }

        return false;
    }

    protected function getTables(): Collection
    {
        return collect(['link_sets']);
    }

    protected function install(bool $fresh = false)
    {
        $this->call('vendor:publish', [
            '--tag' => 'trov-linkables-migrations',
        ]);

        if ($fresh) {
            try {
                Schema::disableForeignKeyConstraints();
                DB::table('migrations')->where('migration', 'like', '%_create_link_sets_table')->delete();
                $this->getTables()->each(fn ($table) => DB::statement('DROP TABLE IF EXISTS ' . $table));
                Schema::enableForeignKeyConstraints();
            } catch (\Throwable $e) {
                $this->info($e);
            }

            $this->call('migrate');
            $this->info('Database migrations freshed up.');
        } else {
            $this->call('migrate');
            $this->info('Database migrated.');
        }

        $baseDatabaseFactoriesPath = database_path('factories');
        (new Filesystem())->ensureDirectoryExists($baseDatabaseFactoriesPath);
        (new Filesystem())->copyDirectory(__DIR__ . '/../../stubs/database/factories', $baseDatabaseFactoriesPath);

        $baseModelsPath = app_path('models');
        (new Filesystem())->ensureDirectoryExists($baseModelsPath);
        (new Filesystem())->copyDirectory(__DIR__ . '/../../stubs/models', $baseModelsPath);

        $baseResourcePath = app_path((string) Str::of('Filament\\Resources\\Trov')->replace('\\', '/'),);
        $roleResourcePath = app_path((string) Str::of('Filament\\Resources\\Trov\\LinkSetResource.php')->replace('\\', '/'),);

        if ($this->checkForCollision([$roleResourcePath])) {
            $confirmed = $this->confirm('Trov Linkable Resource already exists. Overwrite?', true);
            if (!$confirmed) {
                return self::INVALID;
            }
        }

        (new Filesystem())->ensureDirectoryExists($baseResourcePath);
        (new Filesystem())->copyDirectory(__DIR__ . '/../../stubs/resources', $baseResourcePath);

        $this->info('Trov Linkables\'s Resource has been published successfully!');

        $this->info('Trov Linkables is now installed.');

        return self::SUCCESS;
    }
}
