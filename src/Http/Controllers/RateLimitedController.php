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
        $mode = request()->session()->get('pus.rate_limited.mode'); // captcha|survey
        $redirectTo = request()->session()->get('pus.rate_limited.redirect_to');

        // Backward compatibility for old links
        if ($mode !== 'captcha' && $mode !== 'survey') {
            $mode = request()->query('mode');
        }
        if (empty($redirectTo)) {
            $redirectTo = request()->query('r');
        }

        $ip = request()->ip();
        $st = $this->state->get($ip);
        $now = time();

        if ($mode !== 'captcha' && $mode !== 'survey') {
            if (!empty($st['blocked_until']) && $now < (int) $st['blocked_until']) $mode = 'survey';
            else if (!empty($st['require_captcha'])) $mode = 'captcha';
            else $mode = 'captcha';
        }

        if ($mode !== 'captcha') {
            $redirectTo = null;
        }

        return view('power-user-survey::rate-limited', [
            'mode' => $mode,
            'redirectTo' => $redirectTo,
        ]);
    }
}
