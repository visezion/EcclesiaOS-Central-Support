<?php

return [
    'agent_token' => env('CENTRAL_SUPPORT_AGENT_TOKEN'),
    'installation_token_prefix' => env('CENTRAL_SUPPORT_TOKEN_PREFIX', 'eco_'),
    'live_online' => env('CENTRAL_SUPPORT_LIVE_ONLINE', true),
    'average_response_minutes' => (int) env('CENTRAL_SUPPORT_AVERAGE_RESPONSE_MINUTES', 30),
];
