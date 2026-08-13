<?php

namespace App\Console\Commands;

use App\Models\Installation;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

final class CreateInstallationToken extends Command
{
    protected $signature = 'support:installation-token {installation_id} {church_name}';

    protected $description = 'Create or rotate an EcclesiaOS installation API token.';

    public function handle(): int
    {
        $token = config('support.installation_token_prefix', 'eco_').Str::random(56);
        Installation::query()->updateOrCreate(
            ['installation_id' => $this->argument('installation_id')],
            ['church_name' => $this->argument('church_name'), 'token_hash' => hash('sha256', $token), 'enabled' => true],
        );
        $this->line('Installation token (shown once):');
        $this->info($token);

        return self::SUCCESS;
    }
}
