<?php

namespace Trov\Linkables;

use Livewire\Livewire;
use Filament\Facades\Filament;
use Filament\PluginServiceProvider;
use Spatie\LaravelPackageTools\Package;

class TrovLinkablesServiceProvider extends PluginServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('trov-linkables')
            ->hasViews()
            ->hasConfigFile(['trov-linkables'])
            ->hasCommand(Commands\InstallTrovLinkables::class)
            ->hasMigrations([
                'create_link_sets_table',
            ]);
    }
}
