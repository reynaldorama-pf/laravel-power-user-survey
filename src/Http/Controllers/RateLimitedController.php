<?php

namespace PeopleFinders\LaravelPowerUserSurvey\Http\Controllers;

use Illuminate\Routing\Controller;
use PeopleFinders\LaravelPowerUserSurvey\PowerUserState;

class RateLimitedController extends Controller
{
    private $state;

    public function __construct(PowerUserState $state)
    {
        $this->state = $state;
    }

    public function show()
    {
        $request = request();
        $mode = $request->query('mode'); // captcha|survey (legacy support)
        $redirectTo = $request->query('r');

        if (method_exists($request, 'hasSession') && $request->hasSession()) {
            if ($mode === null || $mode === '') {
                $mode = $request->session()->get('pus.rate_limited.mode');
            }

            if ($redirectTo === null || $redirectTo === '') {
                $redirectTo = $request->session()->get('pus.rate_limited.redirect_to');
            }
        }

        $ip = $request->ip();
        $st = $this->state->get($ip);
        $now = time();

        if ($mode !== 'captcha' && $mode !== 'survey') {
            if (!empty($st['blocked_until']) && $now < (int) $st['blocked_until']) $mode = 'survey';
            else if (!empty($st['require_captcha'])) $mode = 'captcha';
            else $mode = 'captcha';
        }

        return view('power-user-survey::rate-limited', [
            'mode' => $mode,
            'redirectTo' => $redirectTo,
        ]);
    }
}
