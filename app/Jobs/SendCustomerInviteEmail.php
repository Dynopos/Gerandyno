<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Password;

/**
 * Sends a newly-provisioned customer their password-reset link so they can
 * set their own password, rather than emailing them a plaintext temporary
 * one. Reuses the same Breeze forgot-password flow already wired up for
 * "I forgot my password" — see routes/auth.php.
 */
class SendCustomerInviteEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, SerializesModels;

    public function __construct(
        public readonly User $user,
    ) {}

    public function handle(): void
    {
        Password::sendResetLink(['email' => $this->user->email]);
    }
}
