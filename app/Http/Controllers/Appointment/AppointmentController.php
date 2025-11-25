<?php

namespace App\Http\Controllers\Appointment;

use App\Contracts\Services\Appointment\AppointmentServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Appointment\StoreAppointmentRequest;
use App\Models\Appointment;
use App\Models\Chatbot;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AppointmentController extends Controller
{
    public function __construct(private readonly AppointmentServiceInterface $appointmentService) {}

    public function index(Chatbot $chatbot)
    {
        return Inertia::render('appointments/index', [
            'chatbot' => $chatbot,
        ]);
    }

    public function list(Request $request, Chatbot $chatbot)
    {
        $request->validate([
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
        ]);

        $chatbotChannelIds = $chatbot->chatbotChannels()->pluck('id');

        $appointments = Appointment::whereIn('chatbot_channel_id', $chatbotChannelIds)
            ->whereBetween('appointment_at', [$request->start_date, $request->end_date])
            ->with('contact:id,first_name,last_name') // Eager load contact details
            ->get();

        return response()->json($appointments);
    }

    public function store(StoreAppointmentRequest $request, Chatbot $chatbot): JsonResponse
    {
        $appointment = $this->appointmentService->schedule($chatbot, $request->validated());

        return response()->json($appointment, 201);
    }
}
