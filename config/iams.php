<?php

return [
    /*
    |--------------------------------------------------------------------------
    | IAMS — IT Asset Management System Configuration
    |--------------------------------------------------------------------------
    */

    'admin_user_ids' => env('SIGIT_ADMIN_USER_IDS', ''),

    // Admin email for notifications
    'admin_email' => env('ADMIN_EMAIL', 'admin@example.com'),

    // Admin email name
    'admin_name' => env('ADMIN_NAME', 'IAMS Admin'),

    // Organization name (shown in emails)
    'organization' => env('ORG_NAME', 'Perusahaan/Instansi Anda'),

    // System name
    'system_name' => env('IAMS_NAME', 'IAMS'),

    // Auto approve borrowings
    'auto_approve' => env('IAMS_AUTO_APPROVE', true),

    // Max borrow days allowed
    'max_borrow_days' => env('IAMS_MAX_BORROW_DAYS', 30),

    // Send overdue reminders
    'send_overdue_reminders' => env('IAMS_OVERDUE_REMINDERS', true),
];
