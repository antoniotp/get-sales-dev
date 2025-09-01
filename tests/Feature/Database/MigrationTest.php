<?php

namespace Tests\Feature\Database;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MigrationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function users_table_exists_after_migrations()
    {
        $this->assertTrue(Schema::hasTable('users'));
    }

    #[Test]
    public function users_table_has_expected_columns()
    {
        $this->assertTrue(
            Schema::hasColumns( 'users',
                [
                    'id',
                    'name',
                    'email',
                    'password',
                    'last_organization_id',
                    'remember_token',
                    'created_at',
                    'updated_at',
                    'email_verified_at'
                ]
            )
        );
    }
}
