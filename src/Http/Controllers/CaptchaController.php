<?php

namespace PeopleFinders\LaravelPowerUserSurvey\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use PeopleFinders\LaravelPowerUserSurvey\PowerUserState;
use GuzzleHttp\Client;

class CaptchaController extends Controller
{
    private $state;

    public function __construct(PowerUserState $state)
    {
        $this->state = $state;
    }

    public function verify(Request $request)
    {
        if (!config('power-user-survey.enabled') || !config('power-user-survey.recaptcha.enabled')) {
            return response()->json(['ok' => false, 'error' => 'disabled'], 400);
        }

        $token = (string) $request->input('token', '');
        if ($token === '') {
            return response()->json(['ok' => false, 'error' => 'missing_token'], 400);
        }

        $secret = (string) config('power-user-survey.recaptcha.secret_key', '');
        if ($secret === '') {
            return response()->json(['ok' => false, 'error' => 'missing_secret'], 400);
        }

        $verifyUrl = (string) config('power-user-survey.recaptcha.verify_url', 'https://www.google.com/recaptcha/api/siteverify');

        $client = new Client(['timeout' => 3.0]);

        try {
            $resp = $client->post($verifyUrl, [
                'form_params' => [
                    'secret' => $secret,
                    'response' => $token,
                    'remoteip' => $request->ip(),
                ],
            ]);
            $data = json_decode((string) $resp->getBody(), true);
        } catch (\Exception $e) {
            return response()->json(['ok' => false, 'error' => 'verify_failed'], 200);
        }

        $success = is_array($data) && !empty($data['success']);
        if (!$success) return response()->json(['ok' => false], 200);

        $ip = $request->ip();
        $state = $this->state->get($ip);

        $cooldownMinutes = max(0, (int) config('power-user-survey.limits.cooldown_minutes', 2));
        $now = time();

        $state['require_captcha'] = false;
        $state['pending_captcha'] = false;
        $state['reentry_captcha'] = false;
        $state['cooldown_until'] = $now + ($cooldownMinutes * 60);
        $state['views'] = 0;
        $state['cycle'] = (int) ($state['cycle'] ?? 0) + 1;

        $this->state->put($ip, $state, $this->state->ttlSecondsFor($state));

        $request->session()->forget('pus.started_counting');

        return response()->json(['ok' => true], 200);
    }
}
