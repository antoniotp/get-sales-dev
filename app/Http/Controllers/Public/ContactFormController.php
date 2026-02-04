<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\Public\ContactFormRequest;
use App\Mail\Contact\SalesContactMail;
use Illuminate\Support\Facades\Mail;

class ContactFormController extends Controller
{
    public function store(ContactFormRequest $request)
    {
        $data = $request->validated();

        Mail::to(config('notifications.sales_address.recipients'))
            ->queue(new SalesContactMail($data));

        return response()->json([
            'message' => __('Message sent successfully'),
        ]);
    }
}
