<?php

namespace App\Http\Controllers\MessageTemplates;

use App\Contracts\Services\MessageTemplate\MessageTemplateResolverServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\MessageTemplates\ResolveTemplatePreviewRequest;
use App\Models\Contact;
use App\Models\MessageTemplate;
use Illuminate\Http\JsonResponse;

class MessageTemplatePreviewController extends Controller
{
    public function __construct(
        private readonly MessageTemplateResolverServiceInterface $resolverService
    ) {}

    /**
     * Resolves the template variables and returns the rendered preview.
     */
    public function resolve(ResolveTemplatePreviewRequest $request, MessageTemplate $template): JsonResponse
    {
        $contact = Contact::findOrFail($request->input('contact_id'));
        $manualValues = $request->input('manual_values', []);

        $resolvedValues = $this->resolverService->resolveValues(
            $template,
            $contact,
            $manualValues,
            $request->user()
        );

        $rendered = $this->resolverService->render($template, $resolvedValues);

        return response()->json([
            'resolved_values' => $resolvedValues,
            'rendered' => $rendered,
        ]);
    }
}
