<?php

namespace Securas\LaravelCyberShield;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;
use cybershield\src\Commands\CyberShieldApiKeyVerifyCommand;
use cybershield\src\Middleware\CyberShieldMiddleware;
/**
 * Laravel base service provider.
 *
 * @author DSecuras
 */
class CyberShieldServiceProvider extends ServiceProvider {

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register() {}

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot() {
        $this->bootConfigurations() ;
        $this->bootMiddlewares() ;
        $this->bootCommands() ;

    }


    /**
     * Boot the middlewares.
     *
     * @return void
     */
    protected function bootMiddlewares() {
        /** @var \Illuminate\Routing\Router $router */
        $router = $this->app['router'] ;
        $router->pushMiddlewareToGroup('cybershield', CyberShieldMiddleware::class) ;
        $router->addRoute('GET', '/wp-json/securas/waf/siteinfo/{provider}', ['uses' => 'Securas\LaravelCyberShield\Controllers\WebhookController@siteInfo']);
    }

    /**
     * Boot the configurations.
     *
     * @return void
     */
    protected function bootConfigurations() {
        $this->mergeConfigFrom($config = __DIR__ . '/../config/cybershield.php', 'cybershield') ;

        // If the application is running in the console, allow the user
        // to publish the configs.
        if($this->app->runningInConsole()) {
            $this->publishes([
                $config => config_path('cybershield.php')
            ], "config") ;
        }
    }

    /**
     * Boot the commands.
     *
     * @return void
     */
    protected function bootCommands() {
        // Let the CyberShield commands be available on console.
        $this->commands([
            CyberShieldApiKeyVerifyCommand::class,
        ]) ;

        // schedule verify api key command hourly
        $this->app->booted(function() {
            /** @var Schedule $schedule */
            $schedule = $this->app->make(Schedule::class) ;
            $schedule->command('cyberchield:apikey_verify')->hourly()
                ->sendOutputTo( storage_path('logs/cyberchield-'.date('Y-m-d_H_i').'.log'));
        }) ;

    }

}
