<?php

namespace App\Console\Commands\Appointments;

use App\Models\Appointment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendAppointmentReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'appointments:send-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scans for appointments that are due and sends a reminder message.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking for appointments that need reminders...');
        Log::info('Checking for appointments that need reminders...');

        // Get all appointments that are scheduled, in the future, and haven't been reminded yet,
        // and where the associated channel has a reminder lead time set.
        $appointments = Appointment::with('chatbotChannel')
            ->where('status', 'scheduled')
            ->whereNull('reminder_sent_at')
            ->where('appointment_at', '>', now())
            ->whereHas('chatbotChannel', function ($query) {
                $query->whereNotNull('reminder_lead_time_minutes');
            })
            ->get();

        if ($appointments->isEmpty()) {
            $this->info('No appointments require reminders at this time.');
            Log::info('No appointments require reminders at this time.');

            return;
        }

        $remindersSentCount = 0;

        foreach ($appointments as $appointment) {
            $reminderLeadTime = $appointment->chatbotChannel->reminder_lead_time_minutes;
            $reminderTime = $appointment->appointment_at->subMinutes($reminderLeadTime);

            // Check if it's time to send the reminder
            if (now()->gte($reminderTime)) {
                // In a real application, you would dispatch a job or event here to send the message.
                // For now, we'll just log it to confirm the logic is working.
                Log::info('Sending reminder for appointment ID: '.$appointment->id);
                $this->line('Sending reminder for appointment ID: '.$appointment->id);

                // Update the appointment to mark the reminder as sent
                $appointment->update([
                    'status' => 'reminded',
                    'reminder_sent_at' => now(),
                ]);

                $remindersSentCount++;
            }
        }
        Log::info("Finished. Sent {$remindersSentCount} reminders.");
        $this->info("Finished. Sent {$remindersSentCount} reminders.");
    }
}
