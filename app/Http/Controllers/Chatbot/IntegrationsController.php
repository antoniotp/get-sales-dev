<?php

namespace App\Http\Controllers\Chatbot;

use App\Contracts\Services\Organization\OrganizationServiceInterface;
use App\Http\Controllers\Controller;
use App\Models\Chatbot;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class IntegrationsController extends Controller
{
    public function __construct(private OrganizationServiceInterface $organizationService)
    {
    }

    public function index(Chatbot $chatbot, Request $request): Response
    {
        $organization = $this->organizationService->getCurrentOrganization($request, auth()->user());

        if (!$organization) {
            abort(403, 'No organization available');
        }
        $organizationId = $organization->id;

        if ( $chatbot->organization_id != $organizationId ) {
            abort(403, 'Unauthorized');
        }

        $linkedChannels = $chatbot->chatbotChannels()->where('credentials','!=','')->active()->get()->map(fn($channel) => [
            'id' => $channel->id,
            'chatbot_id' => $channel->chatbot_id,
            'channel_id' => $channel->channel_id,
            'data' => [
                'phone_number_verified_name' => $channel->credentials['phone_number_verified_name'] ?? '',
                'display_phone_number' => $channel->credentials['display_phone_number'] ?? '',
            ],
            'status' => $channel->status,
        ]);

        return Inertia::render('chatbots/integrations', [
            'chatbot' => $chatbot,
            'linkedChannels' => $linkedChannels,
        ]);
    }
}
