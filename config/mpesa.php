<?php

return [

    'enabled' => filter_var(env('MPESA_ENABLED', false), FILTER_VALIDATE_BOOLEAN),

    'environment' => env('MPESA_ENV', 'sandbox'),

    /*
    | Local sandbox: skip Safaricom API and accept any phone number.
    | Requires MPESA_ENABLED=true and MPESA_ENV=sandbox.
    */
    'sandbox_simulate' => filter_var(env('MPESA_SANDBOX_SIMULATE', false), FILTER_VALIDATE_BOOLEAN),

    'consumer_key' => env('MPESA_CONSUMER_KEY', ''),
    'consumer_secret' => env('MPESA_CONSUMER_SECRET', ''),
    /*
    | Sandbox defaults (Daraja test paybill). Override in .env for production.
    */
    'passkey' => env('MPESA_PASSKEY', env('MPESA_ENV', 'sandbox') === 'sandbox'
        ? 'bfb279f9aa9bdbcf158e97dd71a467cd2e0c893059b10f78e6b72ada1ed2c919'
        : ''),
    'shortcode' => env('MPESA_SHORTCODE', env('MPESA_ENV', 'sandbox') === 'sandbox' ? '174379' : ''),

    /*
    | Paybill: CustomerPayBillOnline | Till: CustomerBuyGoodsOnline
    */
    'transaction_type' => env('MPESA_TRANSACTION_TYPE', 'CustomerPayBillOnline'),

    'callback_url' => env('MPESA_STK_CALLBACK_URL', ''),

    'oauth_url' => env('MPESA_OAUTH_URL', ''),
    'stk_push_url' => env('MPESA_STK_PUSH_URL', ''),
    'stk_query_url' => env('MPESA_STK_QUERY_URL', ''),

    'http_timeout' => max(5, (int) env('MPESA_HTTP_TIMEOUT', 30)),
    'connect_timeout' => max(2, (int) env('MPESA_CONNECT_TIMEOUT', 5)),

    'default_description' => env('MPESA_DEFAULT_DESCRIPTION', 'Premium payment'),

];
