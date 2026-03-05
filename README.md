
# Laravel Power User Survey Package

This package centralizes the **rate limiting, captcha verification, survey modal, and PeopleFinders offer funnel** used by MTA-family sites.

It implements the following flow:

1. 5 pageviews → show **reCAPTCHA modal**
2. User completes captcha → **2 minute cooldown**
3. Repeat captcha cycle **3 times**
4. Next threshold → **24 hour block**
5. During block → user is redirected to `/rate-limited`
6. `/rate-limited` shows the **Power User Survey**
7. Once the survey is completed:
   - Step form will no longer appear
   - Only the **"Claim your limited time offer!"** modal appears

All modal interactions occur on:

```
/rate-limited
```

---

# Requirements

- PHP **7.2+**
- Laravel **5.7 → 8.x**
- NodeJS (for Laravel Mix build)
- reCAPTCHA v2 keys

---

# Installation

You can install this package in **two ways**.

## Option 1 — Install from GitHub (Recommended)

This is the best option when using the package across multiple applications.

### 1. Push the package to GitHub

Example repository:

```
https://github.com/reynaldorama-pf/laravel-power-user-survey
```

The repository should contain:

```
composer.json
src/
config/
resources/
README.md
```

---

### 2. Add repository to your Laravel application

Open your **Laravel application's `composer.json`** and add:

```json
{
  "repositories": [
    {
      "type": "vcs",
      "url": "https://github.com/reynaldorama-pf/laravel-power-user-survey"
    }
  ]
}
```

---

### 3. Require the package

Run:

```
composer require peoplefinders/laravel-power-user-survey:*
```

Composer will install the package directly from GitHub.

---

## Option 2 — Install using Local Path Repository

This option is useful during **development or testing**.

Example folder structure:

```
C:/PF APPS/
    laravel-power-user-survey
    usphonebook
    fastpeoplesearch
```

---

### 1. Edit your Laravel application's `composer.json`

Add:

```json
{
  "repositories": [
    {
      "type": "path",
      "url": "../laravel-power-user-survey"
    }
  ]
}
```

---

### 2. Install the package

Run:

```
composer require peoplefinders/laravel-power-user-survey:*
```

Composer will link the local package.

Changes to the package will immediately apply to the application.

---

# Publish Package Assets

Run:

```
php artisan vendor:publish --tag=power-user-survey-assets
```

This will publish:

```
resources/js/vendor/power-user-survey.js
```

---

# Add JS to Laravel Mix

Edit:

```
resources/js/app.js
```

Add:

```javascript
require('./vendor/power-user-survey');
```

Then compile assets:

```
npm run dev
```

or

```
npm run production
```

---

# Environment Configuration

Add the following to `.env`.

### Survey API Key

```
PUS_API_KEY=YOUR_API_KEY
PUS_SITE_ID=YOUR_SITE_ID
```

### reCAPTCHA

```
PUS_RECAPTCHA_SITE_KEY=YOUR_SITE_KEY
PUS_RECAPTCHA_SECRET_KEY=YOUR_SECRET_KEY
```

---

# Enable Middleware

Open:

```
app/Http/Kernel.php
```

Add the middleware to the **web middleware group**:

```php
\PeopleFinders\LaravelPowerUserSurvey\Http\Middleware\PowerUserRateLimiterMiddleware::class,
```

Example:

```php
protected $middlewareGroups = [
    'web' => [
        \App\Http\Middleware\EncryptCookies::class,
        \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
        \Illuminate\Session\Middleware\StartSession::class,

        \PeopleFinders\LaravelPowerUserSurvey\Http\Middleware\PowerUserRateLimiterMiddleware::class,
    ],
];
```

---

# Default Rate Limit Rules

These match the **FastPeopleSearch implementation**.

```
5 pageviews → captcha
2 minute cooldown
repeat for 3 cycles
next threshold → 24 hour block
```

All values are configurable.

---

# Optional Configuration

You may override the defaults in `.env`.

### Pageview limits

```
PUS_PAGEVIEWS_PER_CYCLE=5
PUS_COOLDOWN_MINUTES=2
PUS_CAPTCHA_CYCLES=3
PUS_BLOCK_HOURS=24
```

---

### Restrict limiter to specific routes

```
PUS_APPLY_ONLY_PREFIXES=/person,/search
```

---

### Exclude routes

```
PUS_EXCLUDE_PREFIXES=/rate-limited,/power-user-survey
```

---

### UI Theme Overrides

Each application can customize the modal colors:

```
PUS_THEME_PRIMARY=#4A8075
PUS_THEME_PRIMARY_HOVER=#2f6f64
PUS_THEME_SELECTED_BG=#e9f3f1
PUS_THEME_SELECTED_BORDER=#76a79e
```

---

# Survey Behavior

Survey follows the **MTA Power User Survey API specification**.

Flow:

```
POST /v1/survey
PATCH /segment
PATCH /interest
PATCH /email
PATCH /offer
PATCH /close
```

Rules:

- API errors are **silently ignored**
- Survey restarts if not completed
- Once completed → only **Step 5 Offer modal** appears

---

# Notes

- CAPTCHA verification is handled server-side using **Google siteverify**
- Survey API requests are executed **from the browser**
- The modal should **never interrupt the browsing experience**
