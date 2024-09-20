<?php

namespace Tv2regionerne\StatamicLargeAssets;

use Illuminate\Support\Facades\Config;
use Statamic\Providers\AddonServiceProvider;
use Statamic\Statamic;

class ServiceProvider extends AddonServiceProvider
{
    protected $routes = [
        'cp' => __DIR__.'/../routes/cp.php',
    ];

    protected $fieldtypes = [
        Fieldtypes\LargeAssets::class,
    ];

    protected $commands = [
        Console\Commands\S3Cors::class,
    ];

    protected $vite = [
        'input' => [
            'resources/js/addon.js',
        ],
        'publicDirectory' => 'resources/dist',
    ];

    public function register()
    {
        Config::set('tus.path', 'cp/large-assets/api/upload-tus/endpoint');
        Config::set('tus.middleware', ['statamic.cp', 'statamic.cp.authenticated']);
    }

    public function bootAddon()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/large-assets.php', 'large-assets');

        $this->publishes([
            __DIR__.'/../../config/large-assets.php' => config_path('large-assets.php'),
        ], 'large-assets-config');

        Statamic::provideToScript([
            'large_assets' => config('large-assets'),
        ]);
    }
}
