# Laravel Power User Survey (Centralized Rate Limit + CAPTCHA + Survey Funnel)

This package centralizes the full business logic described:

- Per-IP rate limiting with repeating CAPTCHA cycles and cooldowns
- 24-hour block after final threshold
- All popups (CAPTCHA modal OR Survey modal) happen on `/rate-limited`
- Survey modal uses the provided MTA Power User Survey API (5-step flow)
- After survey email submission, the modal no longer shows the step-by-step flow on refresh — it shows **"Claim your limited time offer!"**

## Compatibility

- PHP >= 7.2
- Laravel 5.7.x → 8.x.x
- Laravel Mix apps (to bundle the JS)
- Uses Guzzle for reCAPTCHA verification (server-side)

## Minimal install

### 1) Install via Composer

Use VCS (GitHub) or path repositories.

### 2) Publish assets (JS)

```bash
php artisan vendor:publish --tag=power-user-survey-assets
```

### 3) Add JS to your Laravel Mix entry

In your Mix entry (commonly `resources/js/app.js`):

```js
require('./vendor/power-user-survey');
```

Build:

```bash
npm run dev
# or npm run production
```

### 4) Required `.env`

Survey API key:

```env
PUS_API_KEY=YOUR_SURVEY_API_KEY
```

reCAPTCHA keys:

```env
PUS_RECAPTCHA_SITE_KEY=YOUR_RECAPTCHA_SITE_KEY
PUS_RECAPTCHA_SECRET_KEY=YOUR_RECAPTCHA_SECRET_KEY
```

### 5) Enable the middleware

**Apply to all web traffic** (recommended):

```php
protected $middlewareGroups = [
  'web' => [
    // ...
    \PeopleFinders\LaravelPowerUserSurvey\Http\Middleware\PowerUserRateLimiterMiddleware::class,
  ],
];
```

Or apply only to a route group:

```php
Route::middleware(['power-user-rate-limiter'])->group(function () {
  // protected pages
});
```

## How it works (defaults)

Per IP:

- 5 pageviews → CAPTCHA (redirect to `/rate-limited?mode=captcha&r=<original_url>`)
- CAPTCHA success → 2 minute cooldown (unlimited browsing), advance cycle
- Repeat CAPTCHA for 3 cycles
- Next threshold → 24 hour BLOCK (redirect to `/rate-limited?mode=survey&r=<original_url>`)
- After 24 hours expires → require CAPTCHA once to re-enter, then restart run

All popups render on `/rate-limited`.

## Survey behavior

- On `mode=survey`, the modal:
  - calls `POST /v1/survey` immediately (screen 1 impression)
  - proceeds through segment → interest → email → offer
- If user refreshes before email submission: starts again (POST fires again)
- After email submission:
  - localStorage marks completed
  - on future loads of `/rate-limited`, it shows the offer screen only
  - no survey API calls fire on those loads

Control:

```env
PUS_FORCE_STEP5_IF_COMPLETED=true
```

## Optional configuration

```env
PUS_PAGEVIEWS_PER_CYCLE=5
PUS_COOLDOWN_MINUTES=2
PUS_CAPTCHA_CYCLES=3
PUS_BLOCK_HOURS=24

# Optional scope (comma-separated prefixes)
PUS_APPLY_ONLY_PREFIXES=/person,/search

# Optional exclusions
PUS_EXCLUDE_PREFIXES=/rate-limited,/power-user-survey

# Theme
PUS_THEME_PRIMARY=#4A8075
PUS_THEME_PRIMARY_HOVER=#2f6f64
PUS_THEME_SELECTED_BG=#e9f3f1
PUS_THEME_SELECTED_BORDER=#76a79e
```
