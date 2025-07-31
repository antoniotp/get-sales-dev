<?php

namespace App\Http\Controllers\Chatbot;

use App\Contracts\Services\Organization\OrganizationServiceInterface;
use App\Http\Controllers\Controller;
use App\Models\Chatbot;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ChatbotController extends Controller
{
    public function __construct(private OrganizationServiceInterface $organizationService)
    {
    }

    /**
     * Display a listing of the chatbots for the authenticated user's organization.
     */
    public function index(): Response
    {
        $organization = $this->organizationService->getCurrentOrganization(request(), auth()->user());
        $chatbots = $organization->chatbots()
            ->select(['id', 'name', 'description', 'status', 'created_at'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($chatbot) {
                return [
                    'id' => $chatbot->id,
                    'name' => $chatbot->name,
                    'description' => $this->truncateDescription($chatbot->description),
                    'status' => $chatbot->status,
                    'created_at' => $chatbot->created_at->format('M d, Y'),
                ];
            });

        return Inertia::render('chatbots/index', [
            'chatbots' => $chatbots,
            'hasNoChatbots' => $chatbots->isEmpty(),
        ]);
    }

    /**
     * Truncate description to a specific character limit.
     */
    private function truncateDescription(?string $description, int $limit = 120): ?string
    {
        if (!$description) {
            return null;
        }

        return strlen($description) > $limit
            ? substr($description, 0, $limit) . '...'
            : $description;
    }
}
