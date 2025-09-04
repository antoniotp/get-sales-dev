<?php

namespace App\Http\Controllers\Auth;

use App\Contracts\Services\Auth\RegistrationServiceInterface;
use App\Contracts\Services\Invitation\InvitationServiceInterface;
use App\Contracts\Services\Organization\OrganizationServiceInterface;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Show the registration page.
     */
    public function create(Request $request): Response
    {
        return Inertia::render('auth/register', [
            'email' => $request->query('email'),
            'token' => $request->query('token'),
        ]);
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(
        Request $request,
        RegistrationServiceInterface $registrationService,
        OrganizationServiceInterface $organizationService,
        InvitationServiceInterface $invitationService
    ): RedirectResponse {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:' . User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'token' => 'nullable|string|exists:invitations,token',
        ]);

        $invitationToken = $request->input('token');
        $shouldCreateOrg = !$invitationToken;

        $user = $registrationService->register($request->only('name', 'email', 'password'), $shouldCreateOrg);

        event(new Registered($user));

        Auth::login($user);

        if ($invitationToken) {
            try {
                $invitationService->accept($invitationToken, $user);
            } catch (\Exception $e) {
                Log::error('Could not accept invitation for user ' . $user->id . ' after registration: ' . $e->getMessage());
                // The user is registered, but the invitation failed.
                // They can accept it manually later.
            }
        } else {
            // This logic runs only for standard registration
            $firstOrganization = $user->organizations()->first();
            if ($firstOrganization) {
                $organizationService->setCurrentOrganization($request, $user, $firstOrganization->id);
            }
        }

        return redirect()->intended(route('dashboard', absolute: false));
    }
}