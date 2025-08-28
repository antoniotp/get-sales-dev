<?php

namespace App\Http\Controllers\MessageTemplates;

use App\DataTransferObjects\MessageTemplate\MessageTemplateData;
use App\Events\MessageTemplateCreated;
use App\Http\Controllers\Controller;
use App\Models\Chatbot;
use App\Models\MessageTemplate;
use App\Models\MessageTemplateCategory;
use App\Models\Organization;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MessageTemplateController extends Controller
{
    public function __construct(private Organization $organization)
    {
    }

    public function index(Request $request, Chatbot $chatbot): Response
    {
        $chatbotId = $chatbot->id;

        //TODO: also filter by channel if needed
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

        $mapTemplate = fn(MessageTemplate $template) => MessageTemplateData::fromMessageTemplate($template)->toArray();

        return Inertia::render('message_templates/index', [
            'allTemplates' => $allTemplates->map($mapTemplate),
            'activeTemplates' => $activeTemplates->map($mapTemplate),
            'deletedTemplates' => $deletedTemplates->map($mapTemplate),
            'flash' => [
                'success' => session('success'),
                'error' => session('error'),
            ]
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('message_templates/form', [
            'categories' => MessageTemplateCategory::query()->select(['id', 'name'])
                ->active()->get(),
            'template' => null,
        ]);
    }

    public function edit(MessageTemplate $template): Response
    {
        return Inertia::render('message_templates/form', [
            'categories' => MessageTemplateCategory::query()->select(['id', 'name'])
                ->active()->get(),
            'template' => $template->load('category'),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:message_template_categories,id',
            'language' => 'required|string|max:10',
            'header_type' => 'required|in:none,text,image,video,document',
            'header_content' => 'nullable|string',
            'body_content' => 'required|string',
            'footer_content' => 'nullable|string',
            'button_config' => 'nullable|array',
            'variables_schema' => 'nullable|array',
        ]);

        // Set default values
        $validated['chatbot_channel_id'] = 1; // Temporary hardcoded value
        $validated['status'] = 'pending';
        $validated['platform_status'] = 1;
        $validated['variables_count'] = substr_count($validated['body_content'], '{{');

        $template = MessageTemplate::create($validated);

        // Dispatch event to trigger template submission for review
        event(new MessageTemplateCreated($template));

        return redirect()->route('message-templates.index')
            ->with('success', 'Template created successfully and submitted for review. You will be notified once it is approved.');
    }

    public function update(Request $request, MessageTemplate $template)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:message_template_categories,id',
            'language' => 'required|string|max:10',
            'header_type' => 'required|in:none,text,image,video,document',
            'header_content' => 'nullable|string',
            'body_content' => 'required|string',
            'footer_content' => 'nullable|string',
            'button_config' => 'nullable|array',
            'variables_schema' => 'nullable|array',
        ]);

        $validated['variables_count'] = substr_count($validated['body_content'], '{{');

        $template->update($validated);


        // Dispatch event to trigger template submission for review
        event(new MessageTemplateCreated($template));

        return redirect()->route('message-templates.index')
            ->with('success', 'Template updated successfully.');
    }

    public function destroy(MessageTemplate $template)
    {
        $template->delete(); // This will soft delete the template

        return redirect()->route('message-templates.index')
            ->with('success', 'Template deleted successfully.');
    }
}
