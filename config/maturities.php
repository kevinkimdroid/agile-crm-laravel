<?php

return [

    'discharge_voucher' => [
        'document_title' => env('DISCHARGE_VOUCHER_TITLE', 'Discharge Voucher'),
        'subtitle' => env('DISCHARGE_VOUCHER_SUBTITLE', 'Policy maturity — discharge of obligations under the policy contract'),
        'issuer_line' => env('DISCHARGE_VOUCHER_ISSUER', null),
        'signatory_label' => env('DISCHARGE_VOUCHER_SIGNATORY_LABEL', 'Authorised signatory'),
        'extra_paragraph' => env('DISCHARGE_VOUCHER_EXTRA', ''),
    ],

    'investment_notifications' => [
        'to' => env('INVESTMENT_MATURITY_NOTIFY_TO', 'douglas.nyakwara@geminialife.co.ke'),
        'cc' => array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env('INVESTMENT_MATURITY_NOTIFY_CC', 'kelvin.kimutai@geminialife.co.ke,caroline.njogu@geminialife.co.ke'))
        ))),
        'products' => array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env('INVESTMENT_MATURITY_PRODUCTS', ''))
        ))),
        'product_codes' => array_values(array_filter(array_map(
            fn ($v) => (int) trim($v),
            explode(',', (string) env('INVESTMENT_MATURITY_PRODUCT_CODES', '2024608,2025615,2025621'))
        ), fn ($v) => $v > 0)),
        'days' => max(1, min(30, (int) env('INVESTMENT_MATURITY_NOTIFY_DAYS', 14))),
    ],

    'client_notifications' => [
        'email_subject_maturity' => env('MATURITY_CLIENT_EMAIL_SUBJECT', 'Policy :policy — maturity on :date'),
        'email_subject_renewal' => env('RENEWAL_CLIENT_EMAIL_SUBJECT', 'Policy :policy — renewal due on :date'),
        'email_body_maturity' => env('MATURITY_CLIENT_EMAIL_BODY', "Dear :name,\n\nThis is a reminder that your policy :policy (:product) is due to mature on :date.\n\nPlease contact :company to discuss your options.\n\nThank you."),
        'email_body_renewal' => env('RENEWAL_CLIENT_EMAIL_BODY', "Dear :name,\n\nThis is a reminder that your policy :policy is due for renewal on :date.\n\nPlease contact :company to renew and avoid a lapse.\n\nThank you."),
        'sms_maturity' => env('MATURITY_CLIENT_SMS', ':company: Policy :policy matures on :date. Contact us to discuss next steps.'),
        'sms_renewal' => env('RENEWAL_CLIENT_SMS', ':company: Policy :policy renewal due :date. Contact us to renew.'),
    ],

];
