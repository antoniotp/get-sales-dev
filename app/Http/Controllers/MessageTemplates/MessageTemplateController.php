<?php

namespace App\Http\Controllers\MessageTemplates;

use App\Contracts\Services\MessageTemplate\MessageTemplateServiceInterface;
use App\Contracts\Services\MessageTemplate\TemplateVariableServiceInterface;
use App\Contracts\Services\WhatsApp\WabaLanguageServiceInterface;
use App\DataTransferObjects\MessageTemplate\MessageTemplateData;
use App\DataTransferObjects\MessageTemplate\MessageTemplateFormData;
use App\Http\Controllers\Controller;
use App\Http\Requests\MessageTemplates\StoreMessageTemplateRequest;
use App\Http\Requests\MessageTemplates\UpdateMessageTemplateRequest;
use App\Models\Chatbot;
use App\Models\MessageTemplate;
use App\Models\MessageTemplateCategory;
use App\Models\Organization;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MessageTemplateController extends Controller
{
    public function __construct(
        private Organization $organization,
        private MessageTemplateServiceInterface $messageTemplateService,
        private WabaLanguageServiceInterface $wabaLanguageService,
        private TemplateVariableServiceInterface $templateVariableService,
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
            'availableLanguages' => $this->wabaLanguageService->getEnabled(),
            'availableVariables' => $this->templateVariableService->getAvailableVariables($chatbot),
        ]);
    }

    public function edit(MessageTemplate $template): Response
    {
        $chatbot = $template->chatbotChannel->chatbot;

        return Inertia::render('message_templates/form', [
            'categories' => MessageTemplateCategory::query()->select(['id', 'name'])
                ->active()->get(),
            'chatbotChannels' => $chatbot->chatbotChannels()->with('channel')->get(),
            'template' => MessageTemplateFormData::fromMessageTemplate($template->load(['category', 'chatbotChannel'])),
            'availableLanguages' => $this->wabaLanguageService->getEnabled(),
            'availableVariables' => $this->templateVariableService->getAvailableVariables($chatbot),
        ]);
    }

    public function store(StoreMessageTemplateRequest $request)
    {
        $chatbot = $request->route('chatbot');
        $template = $this->messageTemplateService->createTemplate($request->validated(), $chatbot);

        return redirect()->route('message-templates.edit', ['template' => $template->id])
            ->with('success', 'Template created successfully and submitted for review. You will be notified once it is approved.');
    }

    public function update(UpdateMessageTemplateRequest $request, MessageTemplate $template)
    {
        $this->messageTemplateService->updateTemplate($template, $request->validated());

        return redirect()->route('message-templates.edit', $template->id)
            ->with('success', 'Template updated successfully.');
    }

    public function sendForReview(MessageTemplate $template)
    {
        $chatbot = $template->chatbotChannel->chatbot;
        $this->messageTemplateService->sendForReview($template);

        return redirect()->route('message-templates.index', $chatbot->id)
            ->with('success', 'Template submitted for review. You will be notified once it is approved.');
    }

    public function destroy(Chatbot $chatbot, MessageTemplate $template)
    {
        $template->delete();

        return redirect()->route('message-templates.index', $chatbot->id)
            ->with('success', 'Template deleted successfully.');
    }

    public function approved(Request $request, Chatbot $chatbot): JsonResponse
    {
        $request->validate([
            'chatbot_channel_id' => 'required|integer|exists:chatbot_channels,id,chatbot_id,'.$chatbot->id,
        ]);
        $templates = MessageTemplate::query()
            ->where('chatbot_channel_id', $request->chatbot_channel_id)
            ->approved()
            ->get(['id', 'name', 'display_name', 'variable_mappings']);

        return response()->json($templates);
    }
}
