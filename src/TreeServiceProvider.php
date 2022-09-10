<?php

namespace Girover\Tree;

use Girover\Tree\Commands\TreeCommand;
use Illuminate\Support\Facades\Blade;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class TreeServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         */
        $package
            ->name('tree')
            ->hasConfigFile('tree')
            ->hasAssets()
            ->hasTranslations()
            ->hasMigrations(
                'create_tree_nodes_table',
                'create_marriages_table'
            );
    }

    public function boot()
    {
        
        if ($this->app->runningInConsole()) {
            $this->commands([
                TreeCommand::class,
            ]);
        }

        // @tree($html) Blade directive to render the tree
        Blade::directive('tree', function ($tree_html) {
            return "<?php echo $tree_html; ?>";
        });

        parent::boot();
    }
}
