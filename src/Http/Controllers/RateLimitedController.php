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
        $mode = request()->query('mode'); // captcha|survey
        $redirectTo = request()->query('r');

        $ip = request()->ip();
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
