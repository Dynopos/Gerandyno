<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * Lets a DynoPOS admin set a new password for a customer login.
 *
 * The self-service "forgot password" flow only works if outbound mail is
 * configured and the merchant still has access to the mailbox they signed up
 * with — neither is guaranteed for a small shop. This gives the admin a way
 * to get a locked-out merchant back in over the phone, without touching the
 * server.
 *
 * Only customer logins can be reset here: the {user} binding is scoped to
 * the {company}, and admins carry no company_id, so no admin account can
 * ever be reached through this route.
 */
class CompanyUserPasswordController extends Controller
{
    public function edit(Company $company, User $user): View
    {
        $this->authorize('update', $company);

        return view('admin.companies.users.password', [
            'company' => $company,
            'user' => $user,
        ]);
    }

    public function update(Request $request, Company $company, User $user): RedirectResponse
    {
        $this->authorize('update', $company);

        $validated = $request->validate([
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user->forceFill([
            'password' => $validated['password'],
            // Any "remember me" cookie the old password left behind stops
            // working — otherwise a device that is already trusted keeps
            // access after the reset, which defeats the point of resetting.
            'remember_token' => Str::random(60),
        ])->save();

        Log::info('Admin reset a customer login password.', [
            'admin_id' => $request->user()->id,
            'user_id' => $user->id,
            'company_id' => $company->id,
        ]);

        return redirect()->route('admin.companies.edit', $company)
            ->with('status', __('app.admin.company_users.password_reset', ['name' => $user->name]));
    }
}
