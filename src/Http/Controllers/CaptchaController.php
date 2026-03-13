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

        $ip = $request->ip();
        $state = $this->state->get($ip);

        // Idempotent success: if captcha is no longer required (e.g. first verify
        // already succeeded and a duplicate request arrived), treat as success.
        if (empty($state['require_captcha'])) {
            return response()->json(['ok' => true, 'already_verified' => true], 200);
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
        if (!$success) {
            return response()->json([
                'ok' => false,
                'error' => 'captcha_failed',
                'error_codes' => is_array($data) ? (array) ($data['error-codes'] ?? []) : [],
            ], 200);
        }

        $cooldownMinutes = max(0, (int) config('power-user-survey.limits.cooldown_minutes'));
        $captchaCycles = max(0, (int) config('power-user-survey.limits.captcha_cycles'));
        $now = time();

        // Re-read latest state after remote verification to avoid double-increment races
        // when two verify requests for the same captcha arrive nearly at the same time.
        $state = $this->state->get($ip);
        if (empty($state['require_captcha'])) {
            return response()->json(['ok' => true, 'already_verified' => true], 200);
        }

        $isReentryCaptcha = !empty($state['reentry_captcha']);

        $state['require_captcha'] = false;
        $state['pending_captcha'] = false;
        $state['reentry_captcha'] = false;
        $state['cooldown_until'] = $now + ($cooldownMinutes * 60);
        $state['views'] = 0;

        if ($isReentryCaptcha) {
            // Re-entry captcha (after 24h block) should restart the process,
            // not count as one of the 3 captcha cycles.
            $state['cycle'] = 0;
        } else {
            $state['cycle'] = min($captchaCycles, ((int) ($state['cycle'] ?? 0)) + 1);
        }

        $this->state->put($ip, $state, $this->state->ttlSecondsFor($state));

        $request->session()->forget(['pus.started_counting', 'pus.last_counted_url']);

        return response()->json(['ok' => true], 200);
    }
}
