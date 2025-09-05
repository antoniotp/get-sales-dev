<?php

namespace App\Http\Middleware;

use App\Contracts\Services\Organization\OrganizationServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Middleware;
use Tighten\Ziggy\Ziggy;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    public function __construct( private OrganizationServiceInterface $organizationService)
    {
    }

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();
        $currentOrganization = null;

        if ( $user ) {
            $currentOrganization = $this->organizationService->getCurrentOrganization( $request, $user);
        }

        $chatbot = $request->route('chatbot');
        $data = [
            ...parent::share($request),
            'name' => config('app.name'),
            'quote' => ['message' => '', 'author' => ''],
            'auth' => [
                'user' => $request->user(),
            ],
            'ziggy' => fn (): array => [
                ...(new Ziggy)->toArray(),
                'location' => $request->url(),
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'organization' => $user ? [
                'current' => $currentOrganization,
                'list' => $user->organizations()->get(),
            ] : null,
            'chatbot' => $chatbot,
        ];
        if ( session('success') || session('error') ) {
            $data['flash'] = [
                'success' => session('success'),
                'error' => session('error'),
            ];
        }

        return $data;
    }
}
