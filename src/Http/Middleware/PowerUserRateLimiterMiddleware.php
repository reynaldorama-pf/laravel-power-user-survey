<?php

namespace PeopleFinders\LaravelPowerUserSurvey\Http\Middleware;

use Closure;
use PeopleFinders\LaravelPowerUserSurvey\PowerUserState;

class PowerUserRateLimiterMiddleware
{
    private $state;

    public function __construct(PowerUserState $state)
    {
        $this->state = $state;
    }

    public function handle($request, Closure $next)
    {
        if (!config('power-user-survey.enabled')) return $next($request);

        $path = '/' . ltrim($request->path(), '/');

        foreach ((array) config('power-user-survey.exclude_prefixes', []) as $prefix) {
            $prefix = trim($prefix);
            if ($prefix !== '' && strpos($path, $prefix) === 0) return $next($request);
        }

        $applyOnly = (array) config('power-user-survey.apply_only_prefixes', []);
        if (!empty($applyOnly)) {
            $matched = false;
            foreach ($applyOnly as $prefix) {
                $prefix = trim($prefix);
                if ($prefix !== '' && strpos($path, $prefix) === 0) { $matched = true; break; }
            }
            if (!$matched) return $next($request);
        }

        $ip = $request->ip();
        $now = time();
        $pageviews = max(1, (int) config('power-user-survey.limits.pageviews_per_cycle'));
        $captchaCycles = max(0, (int) config('power-user-survey.limits.captcha_cycles'));
        $blockHours = max(1, (int) config('power-user-survey.limits.block_hours'));

        $state = $this->state->get($ip);

        // Block expired => require captcha to re-enter and reset run
        if (!empty($state['blocked_until']) && $now >= (int) $state['blocked_until']) {
            $state['blocked_until'] = null;
            $state['cycle'] = 0;
            $state['views'] = 0;
            $state['cooldown_until'] = null;
            $state['require_captcha'] = true;
            $state['pending_captcha'] = true;
            $state['reentry_captcha'] = true;
        }

        // Still blocked => survey mode
        if (!empty($state['blocked_until']) && $now < (int) $state['blocked_until']) {
            $this->state->put($ip, $state, $this->state->ttlSecondsFor($state));
            return $this->redirectToRateLimited($request, 'survey');
        }

        // Cooldown => unlimited browsing
        if (!empty($state['cooldown_until']) && $now < (int) $state['cooldown_until']) {
            $this->state->put($ip, $state, $this->state->ttlSecondsFor($state));
            return $next($request);
        }

        $accept = strtolower((string) $request->header('Accept', ''));
        $secFetchMode = strtolower((string) $request->header('Sec-Fetch-Mode', ''));
        $secFetchDest = strtolower((string) $request->header('Sec-Fetch-Dest', ''));
        $purpose = strtolower((string) $request->header('Purpose', ''));
        $isPrefetch = ($purpose === 'prefetch') || (strtolower((string) $request->header('X-Moz', '')) === 'prefetch');
        $isDocumentNavigation = strpos($accept, 'text/html') !== false || $secFetchMode === 'navigate' || $secFetchDest === 'document';
        $shouldCountPageView = $request->isMethod('get') && !$request->ajax() && !$request->expectsJson() && !$isPrefetch && $isDocumentNavigation;

        if (!$shouldCountPageView) {
            $this->state->put($ip, $state, $this->state->ttlSecondsFor($state));
            return $next($request);
        }

        // First real page load in session should not count.
        if (!$request->session()->has('pus.started_counting')) {
            $request->session()->put('pus.started_counting', true);

            if (
                !empty($state['require_captcha']) &&
                empty($state['reentry_captcha']) &&
                empty($state['blocked_until']) &&
                empty($state['cooldown_until']) &&
                (int) ($state['views'] ?? 0) === 0
            ) {
                $state['require_captcha'] = false;
                $state['pending_captcha'] = false;
            }

            $this->state->put($ip, $state, $this->state->ttlSecondsFor($state));
            return $next($request);
        }

        // Captcha required => captcha mode
        if (!empty($state['require_captcha'])) {
            $this->state->put($ip, $state, $this->state->ttlSecondsFor($state));
            return $this->redirectToRateLimited($request, 'captcha');
        }

        // Count pageview
        $state['views'] = (int) ($state['views'] ?? 0) + 1;

        if ($state['views'] > $pageviews) {
            if ((int) ($state['cycle'] ?? 0) >= $captchaCycles) {
                // Final threshold => 24h block => survey mode
                $state['blocked_until'] = $now + ($blockHours * 3600);
                $state['views'] = 0;
                $state['cooldown_until'] = null;
                $state['require_captcha'] = false;
                $state['pending_captcha'] = false;
                $state['reentry_captcha'] = false;

                $this->state->put($ip, $state, $this->state->ttlSecondsFor($state));
                return $this->redirectToRateLimited($request, 'survey');
            }

            // Require captcha
            $state['require_captcha'] = true;
            $state['pending_captcha'] = true;
            $state['views'] = 0;

            $this->state->put($ip, $state, $this->state->ttlSecondsFor($state));
            return $this->redirectToRateLimited($request, 'captcha');
        }

        $this->state->put($ip, $state, $this->state->ttlSecondsFor($state));
        return $next($request);
    }

    protected function redirectToRateLimited($request, $mode)
    {
        $request->session()->put('pus.rate_limited.mode', $mode);
        $request->session()->put('pus.rate_limited.redirect_to', $mode === 'captcha' ? $request->fullUrl() : null);

        return redirect()->route('pus.rate_limited');
    }
}
