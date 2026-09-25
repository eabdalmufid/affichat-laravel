<?php

return [
    // API Key from AffiChat Dashboard (https://chat.affidev.com/dashboard/api-docs)
    'api_key' => env('AFFICHAT_API_KEY', ''),

    // Default WhatsApp session ID used for outgoing notifications
    'default_session_id' => env('AFFICHAT_SESSION_ID', 'default'),

    // HTTP request timeout in seconds
    'timeout' => (int) env('AFFICHAT_TIMEOUT', 15),
];
