<?php

return [

    'enabled' => env('WHATSAPP_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | Sandbox — test chat locally without Meta Cloud API
    |--------------------------------------------------------------------------
    | Set WHATSAPP_ENABLED=true and WHATSAPP_SANDBOX=true. No token required.
    | Outbound messages are stored only; use "Simulate customer message" in the UI.
    */
    'sandbox' => filter_var(env('WHATSAPP_SANDBOX', false), FILTER_VALIDATE_BOOLEAN),

    'sandbox_defaults' => [
        'display_phone' => env('WHATSAPP_SANDBOX_DISPLAY_PHONE', '+254 700 000 001'),
        'business_name' => env('WHATSAPP_SANDBOX_BUSINESS_NAME', 'Kenya Orient WhatsApp'),
        'demo_customer_phone' => env('WHATSAPP_SANDBOX_DEMO_PHONE', '254712345678'),
        'demo_customer_name' => env('WHATSAPP_SANDBOX_DEMO_NAME', 'Jane Demo Customer'),
        'demo_customer_message' => env('WHATSAPP_SANDBOX_DEMO_MESSAGE', 'Hello, I need help with my policy. Is this Kenya Orient WhatsApp?'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Meta WhatsApp Cloud API credentials
    |--------------------------------------------------------------------------
    | From Meta Business Suite → WhatsApp → API Setup, or developers.facebook.com
    */
    'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),
    'business_account_id' => env('WHATSAPP_BUSINESS_ACCOUNT_ID'),
    'access_token' => env('WHATSAPP_ACCESS_TOKEN', env('META_WHATSAPP_TOKEN')),
    'display_phone' => env('WHATSAPP_DISPLAY_PHONE'),
    'business_name' => env('WHATSAPP_BUSINESS_NAME', 'Kenya Orient WhatsApp'),
    'graph_version' => env('WHATSAPP_GRAPH_VERSION', 'v18.0'),

    /*
    |--------------------------------------------------------------------------
    | Client consultation — pricing overview (Meta conversation-based billing)
    |--------------------------------------------------------------------------
    | Share this with the client before go-live. Rates change; verify at:
    | https://developers.facebook.com/docs/whatsapp/pricing
    */
    'consultation' => [
        'summary' => 'WhatsApp Business Platform uses Meta Cloud API with conversation-based pricing (not per SMS).',
        'requirements' => [
            'Meta Business Manager account (verified for production volume)',
            'WhatsApp Business Account (WABA) linked to your business phone number',
            'Approved message templates for outbound marketing (utility/auth templates have separate rates)',
            'Webhook URL registered on the same Meta app (shared with Facebook/Instagram)',
        ],
        'pricing_notes' => [
            'Service conversations (customer messages you within 24h): typically includes a free tier (e.g. first 1,000 conversations/month per WABA — confirm current Meta policy).',
            'Marketing / utility / authentication templates: billed per conversation; Kenya rates vary (approx. USD 0.02–0.08 per conversation depending on category — verify on Meta rate card).',
            'You pay Meta directly (or via a BSP). This CRM does not add markup.',
            'Inbound messages via webhook are free to receive; replying inside the 24-hour window uses a service conversation.',
        ],
        'recommendation' => 'Start with customer-initiated support (service window) and approved templates for reminders. Review monthly volume with Meta Business Suite billing before scaling broadcast campaigns.',
        'meta_docs_url' => 'https://developers.facebook.com/docs/whatsapp/pricing',
        'setup_docs_url' => 'https://developers.facebook.com/docs/whatsapp/cloud-api/get-started',
    ],

    /*
    |--------------------------------------------------------------------------
    | Auto-create HelpDesk ticket on new WhatsApp conversation
    |--------------------------------------------------------------------------
    */
    'auto_ticket' => [
        'enabled' => filter_var(env('WHATSAPP_AUTO_TICKET_ENABLED', true), FILTER_VALIDATE_BOOLEAN),
        'assign_to_user_id' => (int) env('WHATSAPP_AUTO_TICKET_ASSIGN_TO', env('TICKET_AUTO_FROM_EMAIL_ASSIGN_TO', 468)),
        'category' => env('WHATSAPP_AUTO_TICKET_CATEGORY', 'Other'),
        'source' => env('WHATSAPP_AUTO_TICKET_SOURCE', 'WHATSAPP'),
        'auto_reply_enabled' => filter_var(env('WHATSAPP_AUTO_TICKET_REPLY_ENABLED', true), FILTER_VALIDATE_BOOLEAN),
        'auto_reply_body' => env('WHATSAPP_AUTO_TICKET_REPLY_BODY', "Thank you for contacting us on WhatsApp.\n\nWe have logged your request as ticket {ticket_no}.\n\nOur team will respond shortly."),
    ],

];
