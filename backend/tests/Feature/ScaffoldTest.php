<?php

namespace Tests\Feature;

use Tests\TestCase;

class ScaffoldTest extends TestCase
{
    public function test_root_redirects_to_the_built_interface(): void
    {
        $this->get('/')->assertRedirect('/app/index.html');
    }

    public function test_health_route_is_available_without_a_database(): void
    {
        $this->get('/up')->assertOk();
    }
}
