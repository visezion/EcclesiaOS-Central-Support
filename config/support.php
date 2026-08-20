<?php

return [
    'agent_token' => env('CENTRAL_SUPPORT_AGENT_TOKEN'),
    'enrollment_key' => env('CENTRAL_SUPPORT_ENROLLMENT_KEY'),
    'installation_token_prefix' => env('CENTRAL_SUPPORT_TOKEN_PREFIX', 'eco_'),
    'live_online' => env('CENTRAL_SUPPORT_LIVE_ONLINE', true),
    'average_response_minutes' => (int) env('CENTRAL_SUPPORT_AVERAGE_RESPONSE_MINUTES', 30),
    'update_agent_url' => env('UPDATE_AGENT_URL'),
    'update_agent_token' => env('UPDATE_AGENT_TOKEN'),
    'update_ref' => env('UPDATE_REF', env('UPDATE_BRANCH', 'main')),
];
