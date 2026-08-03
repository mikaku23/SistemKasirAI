<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class UserSchemaTest extends TestCase
{
    public function test_users_table_has_is_active_column(): void
    {
        $this->assertTrue(Schema::hasColumn('users', 'is_active'));
    }
}
