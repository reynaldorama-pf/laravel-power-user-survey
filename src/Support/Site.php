<?php

namespace PeopleFinders\LaravelPowerUserSurvey\Support;

class Site
{
    public static function resolveSiteId()
    {
        $configured = config('power-user-survey.site_id');
        if (!empty($configured)) return (string) $configured;

        $appUrl = config('app.url') ?: '';
        $host = parse_url($appUrl, PHP_URL_HOST) ?: '';

        if ($host) {
            $parts = explode('.', $host);
            if (count($parts) >= 2) {
                $candidate = $parts[count($parts) - 2];
                $candidate = strtolower(preg_replace('/[^a-z0-9_]+/', '', $candidate));
                if (!empty($candidate)) return $candidate;
            }
        }

        $name = config('app.name') ?: 'app';
        $name = strtolower(preg_replace('/[^a-z0-9_]+/', '', $name));
        return $name ?: 'app';
    }

    public static function resolveJoinUrl($siteId)
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
