<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class DisableTwoFactor extends Command
{
    protected $signature = 'user:disable-2fa {email}';

    protected $description = 'Disable two-factor authentication for a user';

    public function handle()
    {
        $email = $this->argument('email');

        $user = User::where('email', $email)->first();

        if (! $user) {
            $this->error("User with email {$email} not found.");

            return 1;
        }

        $user->two_factor_secret = null;
        $user->two_factor_recovery_codes = null;
        $user->two_factor_confirmed_at = null;
        $user->save();

        $this->info("Two-factor authentication disabled for {$email}");

        return 0;
    }
}
