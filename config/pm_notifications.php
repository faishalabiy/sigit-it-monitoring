<?php

return [
    // Enable after SMTP is actually configured and verified. Log mailer is not delivery.
    'email_enabled'=>filter_var(env('PM_EMAIL_ENABLED',false),FILTER_VALIDATE_BOOLEAN),
    'reminder_hours'=>24,
    'scan_lookback_days'=>7,
    'backup_scan_enabled'=>true,
];