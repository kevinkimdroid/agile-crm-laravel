<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Client branding (white-label)
    |--------------------------------------------------------------------------
    |
    | Override any value in .env, e.g. BRAND_CLIENT_NAME, BRAND_LOGO, etc.
    |
    */
    'client_name' => env('BRAND_CLIENT_NAME', 'Kenya Orient Insurance Limited'),
    'client_short' => env('BRAND_CLIENT_SHORT', 'Kenya Orient'),
    'app_name' => env('BRAND_APP_NAME', 'CRM'),
    'tagline' => env('BRAND_TAGLINE', 'Trusted · Innovative · Inspired'),

    'logo' => env('BRAND_LOGO', 'images/kenya-orient-logo.png'),
    'logo_display' => env('BRAND_LOGO_DISPLAY', 'images/kenya-orient-logo-light.png'),
    'logo_vector' => filter_var(env('BRAND_LOGO_VECTOR', true), FILTER_VALIDATE_BOOLEAN),
    'favicon' => env('BRAND_FAVICON', 'images/kenya-orient-mark.svg'),

    'primary' => env('BRAND_PRIMARY', '#1B3F7A'),
    'primary_dark' => env('BRAND_PRIMARY_DARK', '#122952'),
    'accent' => env('BRAND_ACCENT', '#E30613'),
    'logo_bg' => env('BRAND_LOGO_BG', '#0a0a0a'),
    'muted' => env('BRAND_MUTED', '#8fa3b8'),

    'platform_footer' => env('BRAND_PLATFORM_FOOTER', 'Powered by Agile CraftSolutions'),

    'social' => [
        'facebook' => env('BRAND_SOCIAL_FACEBOOK', 'https://facebook.com/kenyaorientinsurance'),
        'instagram' => env('BRAND_SOCIAL_INSTAGRAM', ''),
        'twitter' => env('BRAND_SOCIAL_TWITTER', 'https://twitter.com/KenyaOrient'),
    ],

];
