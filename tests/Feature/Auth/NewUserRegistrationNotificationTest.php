<?php

namespace Tests\Feature\Auth;

use App\Mail\Auth\NewUserRegistered;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class NewUserRegistrationNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    /**
     * Test that an email notification is sent to configured recipients upon new user registration.
     */
    #[Test]
    public function new_user_registration_sends_notification_email(): void
    {
        Mail::fake();

        // Set test recipients in config
        config()->set('notifications.new_user_registration.recipients', ['test1@example.com', 'test2@example.com']);
        $newUserEmail = 'test_new_user@example.com';
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => $newUserEmail,
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertRedirect('/dashboard');

        // Assert a mailable was sent
        Mail::assertSent(NewUserRegistered::class, function ($mail) use ($newUserEmail) {
            Log::info('Mail: '.print_r($mail, true));
            $recipients = array_map(fn ($recipient) => $recipient['address'], $mail->to);
            $this->assertEquals(['test1@example.com', 'test2@example.com'], $recipients);

            // Assert the mailable contains the correct user data
            $this->assertEquals('Test User', $mail->user->name);
            $this->assertEquals($newUserEmail, $mail->user->email);
            $this->assertNotNull($mail->user->organizations()->first());

            return true;
        });

        $this->assertDatabaseHas('users', [
            'email' => $newUserEmail,
        ]);
    }

    /**
     * Test that no email is sent if recipients are not configured.
     */
    #[Test]
    public function no_email_sent_if_no_recipients_configured(): void
    {
        Mail::fake();

        // Ensure no recipients are configured
        config()->set('notifications.new_user_registration.recipients', []);

        $response = $this->post('/register', [
            'name' => 'Another User',
            'email' => 'another@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertRedirect('/dashboard');

        // Assert no mailable was sent
        Mail::assertNothingSent();
    }
}
