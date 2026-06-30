<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Auto-Create Complaints from Inbound Email
    |--------------------------------------------------------------------------
    |
    | When emails are fetched (Microsoft Graph or IMAP), automatically create
    | a complaint in the Complaint Register for IRA compliance. Runs alongside
    | the auto-ticket-from-email flow - each qualifying email creates both
    | a ticket and a complaint.
    |
    | Set COMPLAINTS_AUTO_FROM_EMAIL_ENABLED=true
    |
    */
    'auto_from_email' => [
        'enabled' => filter_var(env('COMPLAINTS_AUTO_FROM_EMAIL_ENABLED', true), FILTER_VALIDATE_BOOLEAN),
        'nature' => env('COMPLAINTS_AUTO_FROM_EMAIL_NATURE', 'Other'),
        'priority' => env('COMPLAINTS_AUTO_FROM_EMAIL_PRIORITY', 'Medium'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Complaint classification (email auto-register)
    |--------------------------------------------------------------------------
    |
    | Scores inbound email content to distinguish real complaints from general
    | inquiries, auto-replies, marketing, and other non-complaint mail.
    |
    */
    'classification' => [
        'min_create_score' => max(1, (int) env('COMPLAINTS_MIN_CREATE_SCORE', 35)),
        'min_active_score' => max(1, (int) env('COMPLAINTS_MIN_ACTIVE_SCORE', 60)),
        'keywords' => [],
        'strong_phrases' => [],
        'exclude_patterns' => [],
        'notification_patterns' => [],
        'notification_sender_local_parts' => [],
        /*
         * Extra sender domains treated as system/partner notifications (in addition to
         * email-service.excluded_sender_domains). Comma-separated env optional.
         */
        'notification_sender_domains' => array_filter(array_map('strtolower', array_map('trim', explode(',', env('COMPLAINTS_NOTIFICATION_SENDER_DOMAINS', 'mpesa.co.ke,safaricom.co.ke,kcb.co.ke,equitybank.co.ke,co-opbank.co.ke,stanbic.co.ke,absa.africa,ncbagroup.com,dtbkenya.com,familybank.co.ke,nationalbank.co.ke,cbk.or.ke'))))),
    ],

];
