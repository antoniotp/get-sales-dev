<?php

namespace App\Http\Controllers\MessageTemplates;

use App\Http\Controllers\Controller;
use App\Models\MessageTemplate;
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
}
