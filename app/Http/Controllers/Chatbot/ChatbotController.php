<?php

namespace App\Http\Controllers\Chatbot;

use App\Contracts\Services\Organization\OrganizationServiceInterface;
use App\DataTransferObjects\Chatbot\ChatbotData;
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
            ->map(fn(Chatbot $chatbot) => ChatbotData::fromChatbot($chatbot, true)->toArray());

        return Inertia::render('chatbots/index', [
            'chatbots' => $chatbots,
            'hasNoChatbots' => $chatbots->isEmpty(),
            'flash' => [
                'success' => session('success'),
                'error' => session('error'),
            ]
        ]);
    }


    /**
     * Show the form for creating a new chatbot.
     */
    public function create(): Response
    {
        return Inertia::render('chatbots/form');
    }

    /**
     * Store a newly created chatbot in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string|max:500',
            'system_prompt' => 'nullable|string',
            'response_delay_min' => 'nullable|integer|min:0',
            'response_delay_max' => 'nullable|integer|min:0',
        ]);

        $organization = $this->organizationService->getCurrentOrganization(request(), auth()->user());

        $chatbot = $organization->chatbots()->create([
            ...$validated,
            'status' => 1, // Active by default
        ]);

        return redirect()->route('chatbots.index')
            ->with('success', 'Chatbot created successfully.');
    }

    /**
     * Display the specified chatbot.
     */
    public function show(Chatbot $chatbot)
    {
        $this->authorizeForOrganization($chatbot);

        return Inertia::render('chatbots/show', [
            'chatbot' => $chatbot->load('organization'),
        ]);
    }

    /**
     * Show the form for editing the specified chatbot.
     */
    public function edit(Chatbot $chatbot)
    {
        $this->authorizeForOrganization($chatbot);

        return Inertia::render('chatbots/form', [
            'chatbot' => $chatbot,
        ]);
    }

    /**
     * Update the specified chatbot in storage.
     */
    public function update(Request $request, Chatbot $chatbot)
    {
        $this->authorizeForOrganization($chatbot);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string|max:500',
            'system_prompt' => 'nullable|string',
            'response_delay_min' => 'nullable|integer|min:0',
            'response_delay_max' => 'nullable|integer|min:0',
            'status' => 'required|integer|in:0,1',
        ]);

        $chatbot->update($validated);

        return redirect()->route('chatbots.index')
            ->with('success', 'Chatbot updated successfully.');
    }

    /**
     * Remove the specified chatbot from storage.
     */
    public function destroy(Chatbot $chatbot)
    {
        $this->authorizeForOrganization($chatbot);

        $chatbot->delete();

        return redirect()->route('chatbots.index')
            ->with('success', 'Chatbot deleted successfully.');
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

    /**
     * Ensure the chatbot belongs to the authenticated user's organization.
     */
    private function authorizeForOrganization(Chatbot $chatbot): void
    {
        $organization = $this->organizationService->getCurrentOrganization(request(), auth()->user());
        if ($chatbot->organization_id !== $organization->id) {
            abort(403, 'Unauthorized access to this chatbot.');
        }
    }
}
