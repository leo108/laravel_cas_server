<?php
/**
 * Created by PhpStorm.
 * User: chenyihong
 * Date: 16/8/13
 * Time: 17:49
 */

namespace Leo108\Cas;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Leo108\Cas\Http\Controllers\SecurityController;
use Leo108\Cas\Http\Controllers\ValidateController;
use Leo108\Cas\Services\CasConfig;

class CasServerServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(CasConfig::class, function (): CasConfig {
            $config = config('cas');

            if (! is_array($config)) {
                throw new \RuntimeException('Invalid CAS config');
            }

            return new CasConfig($config);
        });
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes(
                [
                    __DIR__.'/../config/cas.php' => config_path('cas.php'),
                ],
                'config'
            );

            $this->publishes(
                [
                    __DIR__.'/../database/migrations/' => database_path('migrations'),
                ],
                'migrations'
            );
        }

        $this->mergeConfigFrom(__DIR__.'/../config/cas.php', 'cas');

        if (! $this->app->routesAreCached()) {
            $this->registerRoutes();
        }
    }

    protected function registerRoutes(): void
    {
        $options = [
            'prefix' => config('cas.router.prefix'),
            'namespace' => '\Leo108\Cas\Http\Controllers',
        ];

        $middleware = config('cas.middleware.common');

        if ($middleware !== null && $middleware !== '') {
            $options['middleware'] = $middleware;
        }

        Route::group(
            $options,
            function () {
                $p = config('cas.router.name_prefix');
                Route::get('login', [SecurityController::class, 'showLogin'])->name($p.'login.get');
                Route::post('login', [SecurityController::class, 'login'])->name($p.'login.post');
                Route::get('logout', [SecurityController::class, 'logout'])->name($p.'logout');
                Route::get('p3/login', [SecurityController::class, 'showLogin'])->name($p.'v3.login.get');
                Route::post('p3/login', [SecurityController::class, 'login'])->name($p.'v3.login.post');
                Route::get('p3/logout', [SecurityController::class, 'logout'])->name($p.'v3.logout');
                Route::any('validate', [ValidateController::class, 'v1ValidateAction'])->name($p.'v1.validate');
                Route::any('serviceValidate', [ValidateController::class, 'v2ServiceValidateAction'])->name($p.'v2.validate.service');
                Route::any('proxyValidate', [ValidateController::class, 'v2ProxyValidateAction'])->name($p.'v2.validate.proxy');
                Route::any('proxy', [ValidateController::class, 'proxyAction'])->name($p.'proxy');
                Route::any('p3/serviceValidate', [ValidateController::class, 'v3ServiceValidateAction'])->name($p.'v3.validate.service');
                Route::any('p3/proxyValidate', [ValidateController::class, 'v3ProxyValidateAction'])->name($p.'v3.validate.proxy');
            }
        );
    }
}
