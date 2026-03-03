<?php

namespace PeopleFinders\PowerUserSurvey;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;

class PowerUserSurveyServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/power-user-survey.php', 'power-user-survey');
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

        Blade::directive('powerUserSurveyModal', function ($expression) {
            return "<?php echo \\PeopleFinders\\PowerUserSurvey\\Blade\\PowerUserSurveyBlade::render($expression); ?>";
        });
    }
}
