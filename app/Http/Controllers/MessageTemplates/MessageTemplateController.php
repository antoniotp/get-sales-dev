<?php

namespace App\Http\Controllers\MessageTemplates;

use App\Contracts\Services\MessageTemplate\MessageTemplateServiceInterface;
use App\DataTransferObjects\MessageTemplate\MessageTemplateData;
use App\Http\Controllers\Controller;
use App\Http\Requests\MessageTemplates\StoreMessageTemplateRequest;
use App\Http\Requests\MessageTemplates\UpdateMessageTemplateRequest;
use App\Models\Chatbot;
use App\Models\MessageTemplate;
use App\Models\MessageTemplateCategory;
use App\Models\Organization;
use Inertia\Inertia;
use Inertia\Response;

class MessageTemplateController extends Controller
{
    public function __construct(
        private Organization $organization,
        private MessageTemplateServiceInterface $messageTemplateService
    ) {}

    public function index(Chatbot $chatbot): Response
    {
        $chatbotId = $chatbot->id;

        // TODO: also filter by channel if needed
        $baseQuery = MessageTemplate::query()
            ->select([
                'message_templates.*',
            ])
            ->with(['category'])
            ->whereHas('chatbotChannel.chatbot', function ($query) use ($chatbotId) {
                $query->where('chatbots.organization_id', $this->organization->id)
                    ->where('chatbots.id', $chatbotId);
            });

        // All templates (including soft deleted)
        $allTemplates = (clone $baseQuery)
            ->withTrashed()
            ->orderBy('created_at', 'desc')
            ->get();

        // Only active templates
        $activeTemplates = (clone $baseQuery)
            ->active()
            ->withoutTrashed()
            ->orderBy('created_at', 'desc')
            ->get();

        // Only deleted templates
        $deletedTemplates = (clone $baseQuery)
            ->onlyTrashed()
            ->orderBy('created_at', 'desc')
            ->get();

        // TODO: Adjust DTO with proper example_data parsing for the frontend
        $mapTemplate = fn (MessageTemplate $template) => MessageTemplateData::fromMessageTemplate($template)->toArray();

        return Inertia::render('message_templates/index', [
            'allTemplates' => $allTemplates->map($mapTemplate),
            'activeTemplates' => $activeTemplates->map($mapTemplate),
            'deletedTemplates' => $deletedTemplates->map($mapTemplate),
            'flash' => [
                'success' => session('success'),
                'error' => session('error'),
            ],
        ]);
    }

    public function create(Chatbot $chatbot): Response
    {
        return Inertia::render('message_templates/form', [
            'categories' => MessageTemplateCategory::query()->select(['id', 'name'])
                ->active()->get(),
            'chatbotChannels' => $chatbot->chatbotChannels()->with('channel')->get(),
            'template' => null,
        ]);
    }

    public function edit(Chatbot $chatbot, MessageTemplate $template): Response
    {
        // TODO: Ensure example_data is parsed back into header_variable and variables_schema
        // for the frontend form to correctly display the data.
        return Inertia::render('message_templates/form', [
            'categories' => MessageTemplateCategory::query()->select(['id', 'name'])
                ->active()->get(),
            'chatbotChannels' => $chatbot->chatbotChannels()->with('channel')->get(),
            'template' => $template->load(['category', 'chatbotChannel']),
        ]);
    }

    public function store(StoreMessageTemplateRequest $request, Chatbot $chatbot)
    {
        $template = $this->messageTemplateService->createTemplate($request->validated(), $chatbot);

        return redirect()->route('message-templates.index', $chatbot->id)
            ->with('success', 'Template created successfully and submitted for review. You will be notified once it is approved.');
    }

    public function update(UpdateMessageTemplateRequest $request, Chatbot $chatbot, MessageTemplate $template)
    {
        $this->messageTemplateService->updateTemplate($template, $request->validated());

        return redirect()->route('message-templates.index', $chatbot->id)
            ->with('success', 'Template updated successfully.');
    }

    public function destroy(Chatbot $chatbot, MessageTemplate $template)
    {
        $template->delete();

        return redirect()->route('message-templates.index', $chatbot->id)
            ->with('success', 'Template deleted successfully.');
    }
}
