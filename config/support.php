<?php

return [
    'agent_token' => env('CENTRAL_SUPPORT_AGENT_TOKEN'),
    'enrollment_key' => env('CENTRAL_SUPPORT_ENROLLMENT_KEY', env('APP_ENV') === 'local' ? 'ecclesiaos-local-enrollment' : ''),
    'installation_token_prefix' => env('CENTRAL_SUPPORT_TOKEN_PREFIX', 'eco_'),
    'live_online' => env('CENTRAL_SUPPORT_LIVE_ONLINE', true),
    'average_response_minutes' => (int) env('CENTRAL_SUPPORT_AVERAGE_RESPONSE_MINUTES', 30),
    'update_agent_url' => env('UPDATE_AGENT_URL'),
    'update_agent_token' => env('UPDATE_AGENT_TOKEN'),
    'update_ref' => env('UPDATE_REF', 'latest'),
    'update_repository' => env('UPDATE_REPOSITORY', 'visezion/EcclesiaOS-Central-Support'),
    'update_channel' => env('UPDATE_CHANNEL', 'stable'),
    'update_require_immutable' => filter_var(env('UPDATE_REQUIRE_IMMUTABLE', true), FILTER_VALIDATE_BOOLEAN),
    'update_github_api_url' => env('UPDATE_GITHUB_API_URL', 'https://api.github.com'),
];
