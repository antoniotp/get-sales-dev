<?php

namespace App\Http\Controllers\Public;

use App\Contracts\Services\PublicForm\PublicContactFormServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Public\StorePublicContactRequest;
use App\Models\PublicFormLink;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\App;

class PublicFormController extends Controller
{
    public function __construct(
        private readonly PublicContactFormServiceInterface $contactFormService
    ) {}

    /**
     * Display the specified public form.
     */
    public function show(PublicFormLink $formLink)
    {
        // Load the related publicFormTemplate
        $formLink->load('publicFormTemplate');

        // Check if formLink is active, otherwise abort
        if (! $formLink->is_active) {
            abort(404);
        }

        // Pass the formLink to a Blade view
        return view('public.contact-registration.show', compact('formLink'));
    }

    /**
     * Store a new contact from the public form submission.
     */
    public function store(Request $request, PublicFormLink $formLink)
    {
        App::setLocale('es');
        $formRequest = StorePublicContactRequest::createFrom($request)
            ->setContainer(app())
            ->setRedirector(app(Redirector::class));

        $formRequest->validateResolved();

        $this->contactFormService->register($formLink, $formRequest->validated());

        return response()->json([
            'message' => $formLink->success_message ?? '¡Registro completado con éxito!',
            'redirect_url' => $formLink->redirect_on_success,
        ]);
    }
}
