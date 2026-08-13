<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

final class CreateSupportAdmin extends Command
{
    protected $signature = 'support:create-admin {name} {email} {password}';

    protected $description = 'Create or update a central support staff login.';

    public function handle(): int
    {
        User::query()->updateOrCreate(
            ['email' => $this->argument('email')],
            ['name' => $this->argument('name'), 'password' => Hash::make($this->argument('password')), 'email_verified_at' => now()],
        );
        $this->info('Central support staff account is ready.');

        return self::SUCCESS;
    }
}
