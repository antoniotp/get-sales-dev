<?php

namespace App\Http\Controllers\Contacts;

use App\Contracts\Services\Organization\OrganizationServiceInterface;
use App\DataTransferObjects\Contact\ContactData;
use App\Http\Controllers\Controller;
use App\Models\Channel;
use App\Models\Chatbot;
use App\Models\Contact;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ContactController extends Controller
{
    public function __construct(private readonly OrganizationServiceInterface $organizationService)
    {
    }

    public function index(Request $request): Response
    {
        $organization = $this->organizationService->getCurrentOrganization($request, auth()->user());

        if (!$organization) {
            abort(403, 'No organization available');
        }

        $query = Contact::query()
            ->where('organization_id', $organization->id)
            ->with(['contactChannels.channel', 'conversations.chatbotChannel.chatbot']);

        if ($request->filled('search')) {
            $search = strtolower($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(first_name) LIKE ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(last_name) LIKE ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(email) LIKE ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(phone_number) LIKE ?', ["%{$search}%"]);
            });
        }

        if ($request->filled('country')) {
            $query->where('country_code', $request->input('country'));
        }

        if ($request->filled('language')) {
            $query->where('language_code', $request->input('language'));
        }

        if ($request->filled('chatbot')) {
            $query->whereHas('conversations.chatbotChannel', function ($q) use ($request) {
                $q->where('chatbot_id', $request->input('chatbot'));
            });
        }

        if ($request->filled('channel')) {
            $query->whereHas('contactChannels', function ($q) use ($request) {
                $q->where('channel_id', $request->input('channel'));
            });
        }

        $contacts = $query->latest()->paginate(15)->withQueryString();

        $contacts->setCollection(
            $contacts->getCollection()->map(fn (Contact $contact) => ContactData::fromModel($contact))
        );

        return Inertia::render('contacts/index', [
            'contacts' => $contacts,
            'filters' => $request->only(['search', 'country', 'language', 'chatbot', 'channel']),
            'filterOptions' => [
                'countries' => Contact::where('organization_id', $organization->id)->whereNotNull('country_code')->distinct()->pluck('country_code'),
                'languages' => Contact::where('organization_id', $organization->id)->whereNotNull('language_code')->distinct()->pluck('language_code'),
                'chatbots' => Chatbot::where('organization_id', $organization->id)->get(['id', 'name']),
                'channels' => Channel::all(['id', 'name']),
            ],
        ]);
    }
}
