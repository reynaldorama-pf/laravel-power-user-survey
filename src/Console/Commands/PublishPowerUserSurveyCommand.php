<?php

namespace PeopleFinders\LaravelPowerUserSurvey\Console\Commands;

use Illuminate\Console\Command;

class PublishPowerUserSurveyCommand extends Command
{
    protected $signature = 'power-user-survey:publish {--only= : Publish only one tag: config, views, or assets}';

    protected $description = 'Publish Power User Survey package files and overwrite existing files';

    public function handle()
    {
        $only = (string) $this->option('only');

        $map = [
            'config' => ['power-user-survey-config'],
            'views' => ['power-user-survey-views'],
            'assets' => ['power-user-survey-assets'],
        ];

        if ($only !== '' && !array_key_exists($only, $map)) {
            $this->error('Invalid --only value. Use: config, views, or assets.');
            return 1;
        }

        $tags = $only !== ''
            ? $map[$only]
            : ['power-user-survey-config', 'power-user-survey-views', 'power-user-survey-assets'];

        foreach ($tags as $tag) {
            $this->info('Publishing [' . $tag . '] with overwrite...');
            $this->call('vendor:publish', [
                '--tag' => $tag,
                '--force' => true,
            ]);
        }

        $this->info('Power User Survey files published successfully.');

        return 0;
    }
}
