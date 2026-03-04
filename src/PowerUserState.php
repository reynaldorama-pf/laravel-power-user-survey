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
                'reentry_captcha' => false,
            ];
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
