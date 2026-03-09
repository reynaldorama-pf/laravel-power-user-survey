<?php

namespace PeopleFinders\LaravelPowerUserSurvey\Views;

use PeopleFinders\LaravelPowerUserSurvey\Support\Site;

class Payload
{
    public static function render($opts = null)
    {
        $opts = is_array($opts) ? $opts : [];

        $siteId = Site::resolveSiteId();
        $joinUrl = Site::resolveJoinUrl($siteId);

        $payload = [
            'enabled'               => (bool) config('power-user-survey.enabled'),
            'appName'               => (string) config('power-user-survey.app_name'),
            'surveyBaseUrl'         => rtrim((string) config('power-user-survey.survey_base_url'), '/'),
            'surveyApiKey'          => (string) config('power-user-survey.survey_api_key'),
            'siteId'                => $siteId,
            'joinUrl'               => $joinUrl,
            'specialOfferUrl'       => (string) config('power-user-survey.special_offer_url'),
            'mountSelector'         => (string) config('power-user-survey.mount_selector'),
            'storage'               => (array) config('power-user-survey.storage'),
            'forceStep5IfCompleted' => (bool) config('power-user-survey.force_step5_if_completed'),
            'theme'                 => (array) config('power-user-survey.theme'),
            'ip'                    => $opts['ip'] ?? request()->ip(),

            'recaptchaEnabled'      => (bool) config('power-user-survey.recaptcha.enabled'),
            'recaptchaSiteKey'      => (string) config('power-user-survey.recaptcha.site_key'),

            'mode'                  => $opts['mode'] ?? 'none',
            'redirectTo'            => $opts['redirectTo'] ?? null,
        ];

        return view('power-user-survey::payload', ['pusPayload' => $payload])->render();
    }
}
