<?php

namespace App\Http\Middleware;

use App\Contracts\Services\Chatbot\ChatbotServiceInterface;
use App\Contracts\Services\Organization\OrganizationServiceInterface;
use Illuminate\Http\Request;
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

    public function __construct(
        private readonly OrganizationServiceInterface $organizationService,
        private readonly ChatbotServiceInterface $chatbotService,
    ) {}

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
        $userRoleLevel = null;

        if ($user) {
            $currentOrganization = $this->organizationService->getCurrentOrganization($request, $user);
            if ($currentOrganization) {
                $role = $user->getRoleInOrganization($currentOrganization);
                if ($role) {
                    $userRoleLevel = $role->level;
                }
            }
        }

        $chatbot = $request->route('chatbot');

        // If a chatbot is present in the URL, make sure its ID is stored in the session
        if ($chatbot && $user && session('chatbot_id') != $chatbot->id) {
            session(['chatbot_id' => $chatbot->id]);
        }

        if (! $chatbot && $user && session()->has('chatbot_id')) {
            $chatbotFromSession = $this->chatbotService->findForUser(session('chatbot_id'), $user);

            if ($chatbotFromSession) {
                $chatbot = $chatbotFromSession;
            } else {
                // The chatbot in the session is invalid or inaccessible, so clear it
                session()->forget('chatbot_id');
            }
        }

        $data = [
            ...parent::share($request),
            'name' => config('app.name'),
            'quote' => ['message' => '', 'author' => ''],
            'auth' => [
                'user' => $user ? array_merge($user->toArray(), ['level' => $userRoleLevel]) : null,
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
        $this->addFlashMessagesToData($data);

        return $data;
    }

    private function addFlashMessagesToData(array &$data): void
    {
        if (session('success')) {
            $data['flash']['success'] = session('success');
        }
        if (session('error')) {
            $data['flash']['error'] = session('error');
        }
        if (session('warning')) {
            $data['flash']['warning'] = session('warning');
        }
        if (session('info')) {
            $data['flash']['info'] = session('info');
        }
    }
}
