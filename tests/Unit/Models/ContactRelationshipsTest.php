<?php

namespace Tests\Unit\Models;

use App\Models\Contact;
use App\Models\ContactAttribute;
use App\Models\ContactEntity;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ContactRelationshipsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    #[Test]
    public function a_contact_can_have_many_contact_entities(): void
    {
        $contact = Contact::factory()->create();
        ContactEntity::factory()->count(2)->create(['contact_id' => $contact->id]);

        $this->assertCount(2, $contact->contactEntities);
        $this->assertTrue($contact->contactEntities->first() instanceof ContactEntity);
    }

    #[Test]
    public function a_contact_entity_belongs_to_a_contact(): void
    {
        $contact = Contact::factory()->create();
        $entity = ContactEntity::factory()->create(['contact_id' => $contact->id]);

        $this->assertTrue($entity->contact instanceof Contact);
        $this->assertEquals($contact->id, $entity->contact->id);
    }

    #[Test]
    public function a_contact_entity_can_have_many_attributes(): void
    {
        $entity = ContactEntity::factory()->create();
        ContactAttribute::factory()->count(3)->create(['contact_entity_id' => $entity->id]);

        $this->assertCount(3, $entity->attributes);
        $this->assertTrue($entity->attributes->first() instanceof ContactAttribute);
    }

    #[Test]
    public function a_contact_attribute_belongs_to_a_contact_entity(): void
    {
        $entity = ContactEntity::factory()->create();
        $attribute = ContactAttribute::factory()->create(['contact_entity_id' => $entity->id]);

        $this->assertTrue($attribute->contactEntity instanceof ContactEntity);
        $this->assertEquals($entity->id, $attribute->contactEntity->id);
    }

    #[Test]
    public function a_contact_can_access_all_its_attributes_through_entities(): void
    {
        $contact = Contact::factory()->create();
        $entity1 = ContactEntity::factory()->create(['contact_id' => $contact->id]);
        $entity2 = ContactEntity::factory()->create(['contact_id' => $contact->id]);

        ContactAttribute::factory()->create(['contact_entity_id' => $entity1->id, 'attribute_name' => 'pet_name', 'attribute_value' => 'Fido']);
        ContactAttribute::factory()->create(['contact_entity_id' => $entity1->id, 'attribute_name' => 'pet_breed', 'attribute_value' => 'Golden']);
        ContactAttribute::factory()->create(['contact_entity_id' => $entity2->id, 'attribute_name' => 'pet_name', 'attribute_value' => 'Whiskers']);

        $this->assertCount(3, $contact->attributes); // Should get all 3 attributes from both entities
        $this->assertTrue($contact->attributes->first() instanceof ContactAttribute);

        $this->assertContains('Fido', $contact->attributes->pluck('attribute_value')->toArray());
        $this->assertContains('Whiskers', $contact->attributes->pluck('attribute_value')->toArray());
    }
}
