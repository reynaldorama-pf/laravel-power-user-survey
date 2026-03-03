# PeopleFinders Power User Survey (Modal + Offer)

This package adds the MTA “Power User Survey” modal flow and PeopleFinders offer CTA.
Target: Laravel **5.7.x → 8.x.x** (PHP >= 7.2).  
(Works best on apps that already use Laravel Mix to bundle JS.)

## What it does

- Renders a **forced** survey modal (no close button by default) with **5 steps**:
  1) Intro
  2) Segment selection
  3) Interest selection
  4) Email capture
  5) PeopleFinders offer (Claim / Maybe later)
- Implements the API call map:
  - POST `/v1/survey` on modal display (when not completed)
  - PATCH `/segment`, `/interest`, `/email`, `/offer`
- State behavior:
  - If email not submitted: refresh → starts at step 1 again (POST fires again)
  - If email submitted: can force showing offer only on refresh (config)

## Install (minimal)

### 1) Require the package

If you host the package internally, a simple option is a **path repository**.

In your app `composer.json`:

```json
{
  "repositories": [
    { "type": "path", "url": "../peoplefinders-power-user-survey", "options": { "symlink": true } }
  ],
  "require": {
    "peoplefinders/power-user-survey": "*"
  }
}
```

Then:

```bash
composer update peoplefinders/power-user-survey
```

### 2) Publish assets (JS)

```bash
php artisan vendor:publish --tag=power-user-survey-assets
```

> You can also publish config if you want to override defaults:
>
> ```bash
> php artisan vendor:publish --tag=power-user-survey-config
> ```

### 3) Add JS to your Laravel Mix entry

In `resources/js/app.js` (or your equivalent entry file):

```js
require('./vendor/power-user-survey');
```

Then build:

```bash
npm run dev
# or npm run production
```

### 4) Add your API key

In `.env`:

```env
PUS_API_KEY=YOUR_KEY_HERE
```

### 5) Render the modal on your blocked page

In the Blade template for your rate-limited/blocked page:

```blade
@powerUserSurveyModal()
```

That’s it.

## How siteId and joinUrl are determined (to minimize settings)

- `siteId`:
  1) If `PUS_SITE_ID` is set, it is used.
  2) Otherwise it is derived from `APP_URL` hostname:
     - `staging.fastpeoplesearch.com` → `fastpeoplesearch`
     - `www.usphonebook.com` → `usphonebook`
- `joinUrl`:
  - If `PUS_JOIN_URL` is set, it is used.
  - Otherwise it is built as:
    `https://www.peoplefinders.com/join?utm_source={siteId}&utm_campaign=pow&utm_medium=rate_limit_modal`

## Behavior options

### Force offer screen after email submit

```env
PUS_FORCE_STEP5_IF_COMPLETED=true
```

- `true` (default): once email is submitted, future loads show offer screen only and **no API calls** fire.
- `false`: future loads will re-run the flow (POST /survey) unless you customize logic.

## Theme per application (override colors)

The modal uses CSS variables. Override in each app’s `.env`:

```env
PUS_THEME_PRIMARY=#4A8075
PUS_THEME_PRIMARY_HOVER=#2f6f64
PUS_THEME_SELECTED_BG=#e9f3f1
PUS_THEME_SELECTED_BORDER=#76a79e
```

## Optional overrides

```env
PUS_ENABLED=true
PUS_BASE_URL=https://angs3br1jh.execute-api.us-east-1.amazonaws.com/prod
PUS_SITE_ID=usphonebook
PUS_JOIN_URL=https://www.peoplefinders.com/join?utm_source=usphonebook&utm_campaign=pow&utm_medium=rate_limit_modal
PUS_SHOW_CLOSE=false
PUS_MOUNT_SELECTOR=body
```

## Troubleshooting

- Ensure `@powerUserSurveyModal()` is rendered on the blocked page.
- Ensure Mix bundle includes `resources/js/vendor/power-user-survey.js`.
- The JS swallows API errors intentionally (modal must not break browsing). Use DevTools Network tab to debug calls.
