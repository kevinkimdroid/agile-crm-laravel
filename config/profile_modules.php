<?php

return [

    /*
    |--------------------------------------------------------------------------
    | App modules editable on Settings > Profiles (non-Vtiger tabs)
    |--------------------------------------------------------------------------
    |
    | Keys match config/modules.php app_to_vtiger keys where value is null.
    | Administrators always have full access regardless of profile settings.
    |
    */
    'app_modules' => [
        'dashboard' => 'Dashboards',
        'support.customers' => 'Clients',
        'support.serve-client' => 'Serve Client',
        'support.sms-notifier' => 'SMS Notifier',
        'support.email-client' => 'Email Client',
        'marketing.broadcast' => 'Email & SMS Broadcast',
        'marketing.social-media' => 'Social Media',
        'marketing.whatsapp' => 'WhatsApp',
        'work-tickets' => 'Work Tickets',
        'tools' => 'Tools Hub',
        'tools.pbx-manager' => 'PBX Manager',
        'tools.mail-manager' => 'Mail Manager',
        'tools.erp-messaging' => 'ERP Messaging',
        'calendar' => 'Calendar',
        'compliance.complaints' => 'Complaint Register',
    ],

    /*
    |--------------------------------------------------------------------------
    | Client life-system segments (Support > Clients filter pills)
    |--------------------------------------------------------------------------
    */
    'client_segments' => [
        'group' => 'Group Life',
        'individual' => 'Individual Life',
        'mortgage' => 'Mortgage',
        'group_pension' => 'Group Pension',
    ],

    /*
    |--------------------------------------------------------------------------
    | Vtiger tabs shown on Settings > Profiles privilege grid
    |--------------------------------------------------------------------------
    */
    'vtiger_tabs' => [
        'Home',
        'Potentials',
        'Contacts',
        'Leads',
        'Calendar',
        'Emails',
        'HelpDesk',
        'Campaigns',
        'Reports',
        'Documents',
    ],

];
