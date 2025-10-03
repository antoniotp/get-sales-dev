<?php

namespace App\Http\Controllers\Chatbot;

use App\Contracts\Services\Chatbot\ChatbotServiceInterface;
use App\Enums\Chatbot\AgentVisibility;
use App\Http\Controllers\Controller;
use App\Models\Chatbot;
use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ChatbotController extends Controller
{
    public function __construct(private Organization $organization)
    {
    }

    /**
     * Display a listing of the chatbots for the authenticated user's organization.
     */
    public function index( ChatbotServiceInterface $chatbotService ): Response
    {
        $chatbots = $chatbotService->getChatbotsByOrganization( $this->organization );

        return Inertia::render('chatbots/index', [
            'chatbots' => $chatbots,
            'hasNoChatbots' => empty($chatbots),
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
            'ai_enabled' => 'required|boolean',
            'agent_visibility' => ['required', Rule::enum(AgentVisibility::class)],
        ]);

        $chatbot = $this->organization->chatbots()->create([
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
        return Inertia::render('chatbots/show', [
            'chatbot' => $chatbot->load('organization'),
        ]);
    }

    /**
     * Show the form for editing the specified chatbot.
     */
    public function edit(Chatbot $chatbot)
    {
        return Inertia::render('chatbots/form', [
            'chatbot' => $chatbot,
        ]);
    }

    /**
     * Update the specified chatbot in storage.
     */
    public function update(Request $request, Chatbot $chatbot)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string|max:500',
            'system_prompt' => 'nullable|string',
            'response_delay_min' => 'nullable|integer|min:0',
            'response_delay_max' => 'nullable|integer|min:0',
            'status' => 'required|integer|in:0,1',
            'ai_enabled' => 'required|boolean',
            'agent_visibility' => ['required', Rule::enum(AgentVisibility::class)],
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
}
