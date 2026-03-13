<?php

namespace PeopleFinders\LaravelPowerUserSurvey;

use Illuminate\Contracts\Cache\Repository as CacheRepository;

class PowerUserState
{
    private $cache;

    public function __construct(CacheRepository $cache)
    {
        $this->cache = $cache;
    }

    public function keyForIp($ip)
    {
        $prefix = (string) config('power-user-survey.cache.prefix', 'pus');
        return $prefix . ':ip:' . $ip;
    }

    public function get($ip)
    {
        $key = $this->keyForIp($ip);
        $state = $this->cache->get($key);

        if (!is_array($state)) {
            $state = [
                'cycle' => 0,
                'views' => 0,
                'cooldown_until' => null,
                'blocked_until' => null,
                'require_captcha' => false,
                'pending_captcha' => false,
                'reentry_captcha' => false,
                'started_counting' => false,
            ];
            return $state;
        }

        $now = time();

        $state['cycle'] = max(0, (int) ($state['cycle'] ?? 0));
        $state['views'] = max(0, (int) ($state['views'] ?? 0));
        $state['cooldown_until'] = !empty($state['cooldown_until']) ? (int) $state['cooldown_until'] : null;
        $state['blocked_until'] = !empty($state['blocked_until']) ? (int) $state['blocked_until'] : null;
        $state['require_captcha'] = !empty($state['require_captcha']);
        $state['pending_captcha'] = !empty($state['pending_captcha']);
        $state['reentry_captcha'] = !empty($state['reentry_captcha']);
        $state['started_counting'] = !empty($state['started_counting']);

        if (
            $state['require_captcha'] &&
            !$state['pending_captcha'] &&
            $state['views'] === 0 &&
            $state['cooldown_until'] === null &&
            $state['blocked_until'] === null
        ) {
            $state['require_captcha'] = false;
            $state['pending_captcha'] = false;
            $state['reentry_captcha'] = false;
            $state['cycle'] = 0;
        }

        if ($state['cooldown_until'] !== null && $state['cooldown_until'] <= $now) {
            $state['cooldown_until'] = null;
        }

        if ($state['cooldown_until'] !== null && $state['cooldown_until'] > $now) {
            $state['require_captcha'] = false;
            $state['pending_captcha'] = false;
            $state['reentry_captcha'] = false;
        }

        return $state;
    }

    public function put($ip, array $state, $ttlSeconds)
    {
        $this->cache->put($this->keyForIp($ip), $state, $ttlSeconds);
    }

    public function ttlSecondsFor(array $state)
    {
        $now = time();
        $block = isset($state['blocked_until']) ? (int) $state['blocked_until'] : 0;
        $cool  = isset($state['cooldown_until']) ? (int) $state['cooldown_until'] : 0;
        $max = max($block, $cool);

        if ($max > $now) return ($max - $now) + 600;

        return 7200;
    }
}
