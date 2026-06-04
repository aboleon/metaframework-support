<?php

declare(strict_types=1);

namespace MetaFramework\Support;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class SupportServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/Resources/views', 'mfw-support');
        Blade::componentNamespace('MetaFramework\\Support\\View\\Components', 'mfw-support');

        // Load translations
        $this->loadTranslationsFrom(__DIR__ . '/../publishable/lang', 'mfw-support');

        if ($this->app->runningInConsole()) {
            // Publish AJAX JavaScript file
            $this->publishes([
                __DIR__ . '/../publishable/public/js/mfw-ajax.js' => public_path('vendor/mfw-support/js/mfw-ajax.js'),
                __DIR__ . '/../publishable/public/js/mfw-action-client.js' => public_path('vendor/mfw-support/js/mfw-action-client.js'),
            ], 'mfw-support-assets');

            // Publish translations
            $this->publishes([
                __DIR__ . '/../publishable/lang' => $this->app->langPath('vendor/mfw-support'),
            ], 'mfw-support-translations');

            // Publish everything
            $this->publishes([
                __DIR__ . '/../publishable/public/js/mfw-ajax.js' => public_path('vendor/mfw-support/js/mfw-ajax.js'),
                __DIR__ . '/../publishable/public/js/mfw-action-client.js' => public_path('vendor/mfw-support/js/mfw-action-client.js'),
                __DIR__ . '/../publishable/lang' => $this->app->langPath('vendor/mfw-support'),
            ], 'mfw-support');

            // Register commands
            $this->commands([
                Console\PublishAssetsCommand::class,
                Console\PublishTranslationsCommand::class,
            ]);
        }
    }
}
