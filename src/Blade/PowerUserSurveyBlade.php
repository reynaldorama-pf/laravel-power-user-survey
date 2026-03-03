<?php

namespace PeopleFinders\PowerUserSurvey\Blade;

class PowerUserSurveyBlade
{
    /**
     * Render the config payload and mount point for the modal JS.
     *
     * Usage:
     *   @powerUserSurveyModal()
     *   @powerUserSurveyModal(['ip' => request()->ip()])
     *
     * @param array|null $opts
     * @return string
     */
    public static function render($opts = null)
    {
        $opts = is_array($opts) ? $opts : [];

        $siteId = self::resolveSiteId();
        $joinUrl = self::resolveJoinUrl($siteId);

        $payload = [
            'enabled'               => (bool) config('power-user-survey.enabled'),
            'baseUrl'               => rtrim((string) config('power-user-survey.base_url'), '/'),
            'apiKey'                => (string) config('power-user-survey.api_key'),
            'siteId'                => $siteId,
            'joinUrl'               => $joinUrl,
            'showClose'             => (bool) config('power-user-survey.show_close_button'),
            'mountSelector'         => (string) config('power-user-survey.mount_selector'),
            'storage'               => (array) config('power-user-survey.storage'),
            'forceStep5IfCompleted' => (bool) config('power-user-survey.force_step5_if_completed'),
            'theme'                 => (array) config('power-user-survey.theme'),
            'ip'                    => $opts['ip'] ?? request()->ip(),
        ];

        return view('power-user-survey::modal', [
            'pusPayload' => $payload,
        ])->render();
    }

    private static function resolveSiteId()
    {
        $configured = config('power-user-survey.site_id');
        if (!empty($configured)) return (string) $configured;

        $appUrl = config('app.url') ?: '';
        $host = parse_url($appUrl, PHP_URL_HOST) ?: '';

        if ($host) {
            $parts = explode('.', $host);
            if (count($parts) >= 2) {
                // Take second-level label as the site identifier:
                // staging.fastpeoplesearch.com -> fastpeoplesearch
                // www.usphonebook.com -> usphonebook
                $candidate = $parts[count($parts) - 2];
                $candidate = strtolower(preg_replace('/[^a-z0-9_]+/', '', $candidate));
                if (!empty($candidate)) return $candidate;
            }
        }

        $name = config('app.name') ?: 'app';
        $name = strtolower(preg_replace('/[^a-z0-9_]+/', '', $name));
        return $name ?: 'app';
    }

    private static function resolveJoinUrl($siteId)
    {
        $configured = config('power-user-survey.join_url');
        if (!empty($configured)) return (string) $configured;

        $siteId = $siteId ?: 'app';

        return 'https://www.peoplefinders.com/join'
            . '?utm_source=' . rawurlencode($siteId)
            . '&utm_campaign=pow'
            . '&utm_medium=rate_limit_modal';
    }
}
