<?php

namespace App\Http\Controllers;

use App\Contracts\Services\Notification\PushSubscriptionServiceInterface;
use App\Http\Requests\Notification\StorePushSubscriptionRequest;
use Illuminate\Http\JsonResponse;

class PushSubscriptionController extends Controller
{
    private PushSubscriptionServiceInterface $pushSubscriptionService;

    public function __construct(PushSubscriptionServiceInterface $pushSubscriptionService)
    {
        $this->pushSubscriptionService = $pushSubscriptionService;
    }

    /**
     * Store a new push subscription.
     */
    public function store(StorePushSubscriptionRequest $request): JsonResponse
    {
        $this->pushSubscriptionService->store($request->user(), $request->validated());

        return response()->json(['success' => true], 201);
    }
}
