<?php

return [
    'enabled' => env('PUS_ENABLED', true),

    // Survey API (browser calls)
    'survey_base_url' => env('PUS_SURVEY_BASE_URL', 'https://angs3br1jh.execute-api.us-east-1.amazonaws.com/prod'),
    'survey_api_key'  => env('PUS_API_KEY', ''),

    // Site ID derived from APP_URL unless overridden
    'site_id'  => env('PUS_SITE_ID', null),

    // Join URL fallback (used only if API doesn't return redirectUrl)
    'join_url' => env('PUS_JOIN_URL', null),

    // Rate limit rules (per IP) - FPS defaults
    'limits' => [
        'pageviews_per_cycle' => (int) env('PUS_PAGEVIEWS_PER_CYCLE', 5),
        'cooldown_minutes'    => (int) env('PUS_COOLDOWN_MINUTES', 2),
        'captcha_cycles'      => (int) env('PUS_CAPTCHA_CYCLES', 3),
        'block_hours'         => (int) env('PUS_BLOCK_HOURS', 24),
    ],

    // Scope
    'apply_only_prefixes' => array_filter(explode(',', (string) env('PUS_APPLY_ONLY_PREFIXES', ''))),

    // Exclusions
    'exclude_prefixes' => array_filter(explode(',', (string) env('PUS_EXCLUDE_PREFIXES', '/rate-limited,/power-user-survey'))),

    // reCAPTCHA (v2 checkbox)
    'recaptcha' => [
        'enabled'    => env('PUS_RECAPTCHA_ENABLED', true),
        'site_key'   => env('PUS_RECAPTCHA_SITE_KEY', ''),
        'secret_key' => env('PUS_RECAPTCHA_SECRET_KEY', ''),
        'verify_url' => env('PUS_RECAPTCHA_VERIFY_URL', 'https://www.google.com/recaptcha/api/siteverify'),
    ],

    // Rate-limited page
    'rate_limited_path' => env('PUS_RATE_LIMITED_PATH', '/rate-limited'),

    // Survey behavior
    'force_step5_if_completed' => env('PUS_FORCE_STEP5_IF_COMPLETED', true),

    // UI
    'show_close_button' => env('PUS_SHOW_CLOSE', false),
    'mount_selector'    => env('PUS_MOUNT_SELECTOR', 'body'),

    // localStorage keys
    'storage' => [
        'device_id'    => env('PUS_STORAGE_DEVICE_ID', '_pus_did'),
        'completed'    => env('PUS_STORAGE_COMPLETED', '_pus_completed'),
        'redirect_url' => env('PUS_STORAGE_REDIRECT', '_pus_redirect_url'),
    ],

    // Theme (CSS variables)
    'theme' => [
        'primary'         => env('PUS_THEME_PRIMARY', '#4A8075'),
        'primary_hover'   => env('PUS_THEME_PRIMARY_HOVER', '#2f6f64'),
        'selected_bg'     => env('PUS_THEME_SELECTED_BG', '#e9f3f1'),
        'selected_border' => env('PUS_THEME_SELECTED_BORDER', '#76a79e'),
    ],

    // Cache key prefix
    'cache' => [
        'prefix' => env('PUS_CACHE_PREFIX', 'pus'),
    ],
];
