<?php

namespace App\Http\Controllers\MessageTemplates;

use App\Http\Controllers\Controller;
use App\Models\MessageTemplate;
use App\Models\MessageTemplateCategory;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MessageTemplateController extends Controller
{
    public function index(): Response
    {
        // Get current organization (hardcoded for now, later from session)
        $organizationId = 1;

        // Get the current chatbot (hardcoded for now, later from user selection)
        $chatbotId = 1;

        $baseQuery = MessageTemplate::query()
            ->select([
                'message_templates.*',
            ])
            ->with(['category'])
            ->whereHas('chatbotChannel.chatbot', function ($query) use ($organizationId, $chatbotId) {
                $query->where('chatbots.organization_id', $organizationId)
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
            ->orderBy('created_at', 'desc')
            ->get();

        // Only deleted templates
        $deletedTemplates = (clone $baseQuery)
            ->onlyTrashed()
            ->orderBy('created_at', 'desc')
            ->get();

        $mapTemplate = function ($template) {
            return [
                'id' => $template->id,
                'name' => $template->name,
                'category' => $template->category->name,
                'status' => $template->status,
                'language' => $template->language,
            ];
        };

        return Inertia::render('message_templates/index', [
            'allTemplates' => $allTemplates->map($mapTemplate),
            'activeTemplates' => $activeTemplates->map($mapTemplate),
            'deletedTemplates' => $deletedTemplates->map($mapTemplate),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('message_templates/form', [
            'categories' => MessageTemplateCategory::all(['id', 'name']),
            'template' => null,
        ]);
    }

    public function edit(MessageTemplate $template): Response
    {
        return Inertia::render('message_templates/form', [
            'categories' => MessageTemplateCategory::all(['id', 'name']),
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

        MessageTemplate::create($validated);

        return redirect()->route('message-templates.index')
            ->with('success', 'Template created successfully.');
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
