<?php

namespace Tests\Unit\Services\Contact;

use App\Contracts\Services\Contact\ContactServiceInterface;
use App\Models\Contact;
use App\Services\Contact\ContactService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[CoversClass(ContactService::class)]
class ContactServiceTest extends TestCase
{
    use RefreshDatabase;

    private ContactServiceInterface $contactService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);

        $this->contactService = $this->app->make(ContactServiceInterface::class);
    }

    #[Test]
    public function it_can_update_a_contacts_first_name(): void
    {
        // Arrange
        $contact = Contact::factory()->create(['first_name' => 'OldName']);
        $newName = 'NewName';

        // Act
        $result = $this->contactService->updateFirstName($contact->id, $newName);

        // Assert
        $this->assertTrue($result);
        $this->assertDatabaseHas('contacts', [
            'id' => $contact->id,
            'first_name' => $newName,
        ]);
        $contact->refresh();
        $this->assertEquals($newName, $contact->first_name);
    }

    #[Test]
    public function it_returns_false_when_contact_is_not_found(): void
    {
        // Arrange
        $nonExistentContactId = 999;
        $newName = 'NewName';

        // Act
        $result = $this->contactService->updateFirstName($nonExistentContactId, $newName);

        // Assert
        $this->assertFalse($result);
    }
}
