<?php

namespace PeopleFinders\LaravelPowerUserSurvey;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use PeopleFinders\LaravelPowerUserSurvey\Console\Commands\PublishPowerUserSurveyCommand;
use PeopleFinders\LaravelPowerUserSurvey\Http\Middleware\PowerUserRateLimiterMiddleware;

class PowerUserSurveyServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/power-user-survey.php', 'power-user-survey');

        $this->app->singleton(PowerUserState::class, function ($app) {
            return new PowerUserState($app['cache.store']);
        });
    }

    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../config/power-user-survey.php' => config_path('power-user-survey.php'),
        ], 'power-user-survey-config');

        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'power-user-survey');

        $this->publishes([
            __DIR__ . '/../resources/views' => resource_path('views/vendor/power-user-survey'),
        ], 'power-user-survey-views');

        $this->publishes([
            __DIR__ . '/../resources/js/power-user-survey.js' => resource_path('js/vendor/power-user-survey.js'),
        ], 'power-user-survey-assets');

        if ($this->app->runningInConsole()) {
            $this->commands([
                PublishPowerUserSurveyCommand::class,
            ]);
        }

        if (!config('power-user-survey.enabled')) {
            // Redirect all PUS-owned paths to homepage so stale links don't 404
            $prefixes = (array) config('power-user-survey.exclude_prefixes', []);
            foreach ($prefixes as $prefix) {
                $prefix = '/' . ltrim(trim($prefix), '/');
                Route::middleware(['web'])->get($prefix, fn () => redirect('/'));
                Route::middleware(['web'])->get($prefix . '/{any}', fn () => redirect('/'))->where('any', '.*');
            }
            return;
        }

        Blade::directive('powerUserSurveyPayload', function ($expression) {
            return "<?php echo \\PeopleFinders\\LaravelPowerUserSurvey\\Views\\Payload::render($expression); ?>";
        });

        $this->registerRoutes();

        $router = $this->app['router'];
        $router->aliasMiddleware('power-user-rate-limiter', PowerUserRateLimiterMiddleware::class);
        $router->pushMiddlewareToGroup('web', PowerUserRateLimiterMiddleware::class);
    }

    protected function registerRoutes()
    {
        Route::group([
            'namespace'  => 'PeopleFinders\\LaravelPowerUserSurvey\\Http\\Controllers',
            'middleware' => ['web'],
        ], function () {
            Route::get(config('power-user-survey.rate_limited_path'), 'RateLimitedController@show')
                ->name('pus.rate_limited');

            Route::post('/power-user-survey/recaptcha/verify', 'CaptchaController@verify')
                ->name('pus.recaptcha.verify');
        });
    }
}
