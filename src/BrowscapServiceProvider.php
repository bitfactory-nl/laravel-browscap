<?php

declare(strict_types=1);

namespace Propa\BrowscapPHP;

use BrowscapPHP\Browscap;
use BrowscapPHP\BrowscapInterface;
use Illuminate\Contracts\Container\Container as ContainerContract;
use Illuminate\Foundation\Application as LaravelApplication;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;
use Laravel\Lumen\Application as LumenApplication;
use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;
use MatthiasMullie\Scrapbook\Adapters\Flysystem;
use MatthiasMullie\Scrapbook\Psr16\SimpleCache;

/**
 * Browscap service provider
 *
 * @author Pavel Kulbakin <p.kulbakin@gmail.com>
 */
class BrowscapServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot(): void
    {
        $this->setupConfig();
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register(): void
    {
        $this->app->singleton('browscap', function (ContainerContract $app) {
            $adapter    = new LocalFilesystemAdapter(Config::get('browscap.cache'));
            $filesystem = new Filesystem($adapter);
            $cache      = new SimpleCache(
                new Flysystem($filesystem)
            );

            $bc = new Browscap(
                $cache,
                $app->make('log')
            );

            return $bc;
        });
        $this->app->bind(BrowscapInterface::class, 'browscap');

        if ($this->app->runningInConsole()) {
            $this->commands([
                Console\CheckUpdateCommand::class,
                Console\ConvertCommand::class,
                Console\FetchCommand::class,
                Console\ParserCommand::class,
                Console\UpdateCommand::class,
            ]);
        }
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides(): array
    {
        return ['browscap'];
    }

    protected function setupConfig()
    {
        $source = realpath($raw = __DIR__.'/../config/browscap.php') ?: $raw;

        if ($this->app instanceof LaravelApplication) {
            $this->publishes([$source => config_path('browscap.php')]);
        } elseif ($this->app instanceof LumenApplication) {
            $this->app->configure('browscap');
        }

        $this->mergeConfigFrom($source, 'browscap');
    }
}
