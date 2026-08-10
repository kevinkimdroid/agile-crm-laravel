<?php

$vendorConfig = require __DIR__ . '/../vendor/webklex/php-imap/src/config/imap.php';

return array_merge($vendorConfig, [
    'default' => env('IMAP_ACCOUNT', 'agilecraft'),

    'accounts' => array_merge($vendorConfig['accounts'] ?? [], [
        'agilecraft' => [
            'host' => env('IMAP_HOST', 'mail.agilecraft.co.ke'),
            'port' => (int) env('IMAP_PORT', 993),
            'protocol' => 'imap',
            'encryption' => env('IMAP_ENCRYPTION', 'ssl'),
            'validate_cert' => filter_var(env('IMAP_VALIDATE_CERT', false), FILTER_VALIDATE_BOOLEAN),
            'username' => env('IMAP_USERNAME', 'info@agilecraft.co.ke'),
            'password' => env('IMAP_PASSWORD', ''),
            'authentication' => null,
            'proxy' => [
                'socket' => null,
                'request_fulluri' => false,
                'username' => null,
                'password' => null,
            ],
            'timeout' => (int) env('IMAP_TIMEOUT', 30),
            'extensions' => [],
        ],
    ]),

    'options' => array_merge($vendorConfig['options'] ?? [], [
        'soft_fail' => true,
        'fetch_order' => 'desc',
        'debug' => filter_var(env('IMAP_DEBUG', false), FILTER_VALIDATE_BOOLEAN),
    ]),
]);
