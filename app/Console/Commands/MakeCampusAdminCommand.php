<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class MakeCampusAdminCommand extends Command
{
    protected $signature = 'campusqueue:make-admin';

    protected $description = 'Create or promote a user account to CampusQ administrator';

    public function handle(): int
    {
        $email = $this->ask('Administrator email');
        $name = $this->ask('Administrator name');
        $password = $this->secret('Administrator password');

        if (! filter_var($email, FILTER_VALIDATE_EMAIL) || ! is_string($name) || trim($name) === '' || ! is_string($password) || strlen($password) < 8) {
            $this->error('Enter a valid email, name, and password with at least 8 characters.');

            return self::FAILURE;
        }

        User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make($password),
                'role' => 'admin',
                'email_verified_at' => now(),
            ],
        );

        $this->info('CampusQ administrator is ready. Sign in with the account just configured.');

        return self::SUCCESS;
    }
}
