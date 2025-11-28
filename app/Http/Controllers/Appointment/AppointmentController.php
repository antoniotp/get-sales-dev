<?php

namespace App\Http\Controllers\Appointment;

use App\Contracts\Services\Appointment\AppointmentServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Appointment\StoreAppointmentRequest;
use App\Http\Requests\Appointment\UpdateAppointmentRequest;
use App\Models\Appointment;
use App\Models\Chatbot;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;

class AppointmentController extends Controller
{
    public function __construct(private readonly AppointmentServiceInterface $appointmentService) {}

    public function index(Chatbot $chatbot)
    {
        $chatbotChannels = $chatbot->chatbotChannels()->with('channel')->get();

        $transformedChannels = $chatbotChannels->map(function ($chatbotChannel) {
            return [
                'id' => $chatbotChannel->id,
                'name' => $chatbotChannel->channel->name,
                'credentials' => $chatbotChannel->credentials,
            ];
        });

        return Inertia::render('appointments/index', [
            'chatbot' => $chatbot,
            'chatbotChannels' => $transformedChannels,
        ]);
    }

    public function list(Request $request, Chatbot $chatbot)
    {
        $validated = $request->validate([
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
        ]);

        $startDate = $validated['start_date'];
        // Parse the end date and add a day to create an exclusive upper bound.
        $endDate = Carbon::parse($validated['end_date'])->addDay();

        $chatbotChannelIds = $chatbot->chatbotChannels()->pluck('id');

        $appointments = Appointment::whereIn('chatbot_channel_id', $chatbotChannelIds)
            ->where('appointment_at', '>=', $startDate)
            ->where('appointment_at', '<', $endDate)
            ->with('contact:id,first_name,last_name,phone_number') // Eager load contact details
            ->get();

        return response()->json($appointments);
    }

    public function store(StoreAppointmentRequest $request, Chatbot $chatbot): JsonResponse
    {
        $appointment = $this->appointmentService->schedule($chatbot, $request->validated());

        // Eager-load the contact relationship so it's included in the JSON response
        $appointment->load('contact');

        return response()->json($appointment, 201);
    }

    public function update(UpdateAppointmentRequest $request, Appointment $appointment): JsonResponse
    {
        $this->authorize('update', $appointment);

        $updatedAppointment = $this->appointmentService->update($appointment, $request->validated());
        $updatedAppointment->load('contact');

        return response()->json($updatedAppointment);
    }

    public function destroy(Appointment $appointment): JsonResponse
    {
        $this->authorize('delete', $appointment);

        $this->appointmentService->cancel($appointment);

        return response()->json(null, 204);
    }
}
