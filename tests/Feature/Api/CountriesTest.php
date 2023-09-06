<?php

namespace Tests\Feature\Api;

class CountriesTest extends BaseApiTest
{
    public function testIndex()
    {
        $this->getJson('/api/v1/countries')->assertOk();
    }
}
