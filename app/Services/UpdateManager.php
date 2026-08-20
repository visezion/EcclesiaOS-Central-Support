<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

final class UpdateManager
{
    public function request(): array
    {
        $url = (string) config('support.update_agent_url');
        $token = (string) config('support.update_agent_token');

        if ($url === '' || $token === '') {
            return ['accepted' => false, 'message' => 'The update agent is not configured on this server.'];
        }

        try {
            $response = Http::withToken($token)
                ->acceptJson()
                ->connectTimeout(3)
                ->timeout(8)
                ->post(rtrim($url, '/').'/update', ['ref' => config('support.update_ref', 'main')]);
        } catch (\Throwable $exception) {
            report($exception);

            return ['accepted' => false, 'message' => 'The update agent could not be reached. Check that the updater service is running and that UPDATE_AGENT_TOKEN matches in both services.'];
        }

        if ($response->successful()) {
            return ['accepted' => true, 'message' => $response->json('message', 'Update started.')];
        }

        return ['accepted' => false, 'message' => $response->json('message', 'The update agent rejected the request.')];
    }

    public function status(): array
    {
        $path = storage_path('framework/update-status.json');

        if (! is_file($path)) {
            return ['state' => 'idle', 'message' => 'No update has been run yet.'];
        }

        $status = json_decode((string) file_get_contents($path), true);

        return is_array($status) ? $status : ['state' => 'idle', 'message' => 'No update has been run yet.'];
    }
}
