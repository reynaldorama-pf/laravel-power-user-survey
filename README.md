
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

# Publish Package Files

## Recommended publish command (overwrites existing files)

Run:

```
php artisan power-user-survey:publish
```

This publishes the package config, views, and assets and **overwrites existing files**.

You may also publish just one group:

```
php artisan power-user-survey:publish --only=config
php artisan power-user-survey:publish --only=views
php artisan power-user-survey:publish --only=assets
```

## Laravel vendor:publish

If you prefer Laravel's built-in publish command, use `--force` so existing files are overwritten:

```
php artisan vendor:publish --tag=power-user-survey-assets --force
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

### Rate-limit bypass toggle

```
PUS_BYPASS_RATE_LIMIT_RULES=false
```

- `true`: bypasses all Power User Survey rate-limit rules (no 5-pageview captcha flow, no cooldown, no block).
- `false`: keeps normal rate-limit behavior.

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


---

# Rate Limited Page Customization

When a visitor exceeds the allowed request limits they are redirected to:

```
/rate-limited
```

The package includes a **default minimal view** so it works immediately after installation.

Default message:

```
Rate Limit Exceeded
You have exceeded the amount of requests allowed in the allotted time limit.
```

However, most applications will want this page to **use the application's existing layout
(header, logo, navigation, footer, etc).**

To support this, the package allows the view to be **published and overridden**.

---

## Publish the Rate Limited View

Run:

```
php artisan power-user-survey:publish --only=views
```

If using `vendor:publish` directly, use:

```
php artisan vendor:publish --tag=power-user-survey-views --force
```

This will create the file:

```
resources/views/vendor/power-user-survey/rate-limited.blade.php
```

---

## Use Your Application Layout

Edit the published file and wrap it with your application's layout.

Example:

```blade
@extends('layouts.app')

@section('content')

<h1>Rate Limit Exceeded</h1>

<p>
You have exceeded the amount of requests allowed in the allotted time limit.
</p>

@endsection
```

Now the page will automatically use:

- application **header**
- **logo**
- **navigation**
- **footer**
- application **styling**

while still keeping the package logic intact.

No business logic is affected by overriding this view.

---

