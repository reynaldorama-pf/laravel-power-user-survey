<?php

return [
    'enabled' => env('PUS_ENABLED', true),

    'base_url' => env('PUS_BASE_URL', 'https://angs3br1jh.execute-api.us-east-1.amazonaws.com/prod'),

    // Required
    'api_key'  => env('PUS_API_KEY', ''),

    // Optional; if null we derive from APP_URL hostname
    'site_id'  => env('PUS_SITE_ID', null),

    // Optional; if null we build from siteId using the default template
    'join_url' => env('PUS_JOIN_URL', null),

    // If true: once email submitted, always show Step 5 on subsequent page loads (no API calls)
    'force_step5_if_completed' => env('PUS_FORCE_STEP5_IF_COMPLETED', true),

    // Match FPS behavior (no close button) by default
    'show_close_button' => env('PUS_SHOW_CLOSE', false),

    // Where modal is appended
    'mount_selector' => env('PUS_MOUNT_SELECTOR', 'body'),

    'storage' => [
        'device_id'    => env('PUS_STORAGE_DEVICE_ID', '_pus_did'),
        'completed'    => env('PUS_STORAGE_COMPLETED', '_pus_completed'),
        'redirect_url' => env('PUS_STORAGE_REDIRECT', '_pus_redirect_url'),
    ],

    // Themeable per application via CSS variables
    'theme' => [
        'primary'         => env('PUS_THEME_PRIMARY', '#4A8075'),
        'primary_hover'   => env('PUS_THEME_PRIMARY_HOVER', '#2f6f64'),
        'selected_bg'     => env('PUS_THEME_SELECTED_BG', '#e9f3f1'),
        'selected_border' => env('PUS_THEME_SELECTED_BORDER', '#76a79e'),
    ],
];
