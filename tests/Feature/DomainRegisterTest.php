<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\DomainRegisterService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DomainRegisterTest extends TestCase
{
    public function test_get_tld_list_errors()
    {
        $user = User::factory()
            ->withPersonalTeam()
            ->create();

        Http::fake([
            'https://api.sandbox.namecheap.com/*' => Http::response(file_get_contents(__DIR__ .
                '/data/namecheap/namecheap.domains.getTldList.errors.xml')),
        ]);

        $res = DomainRegisterService::getTldList($user->currentTeam);

        $this->assertNotEmpty($res['errors']);
        $this->assertEmpty($res['tlds']);
    }

    public function test_get_tld_list()
    {
        $user = User::factory()
            ->withPersonalTeam()
            ->create();

        Http::fake([
            'https://api.sandbox.namecheap.com/*' => Http::response(file_get_contents(__DIR__ .
                '/data/namecheap/namecheap.domains.getTldList.xml')),
        ]);

        $res = DomainRegisterService::getTldList($user->currentTeam);

        $this->assertEmpty($res['errors']);
        $this->assertNotEmpty($res['tlds']);
    }

    public function test_create_domain_errors()
    {
        $user = User::factory()
            ->withPersonalTeam()
            ->create();

        Http::fake([
            'https://api.sandbox.namecheap.com/*' => Http::response(file_get_contents(__DIR__ .
                '/data/namecheap/namecheap.domains.create.errors.xml')),
        ]);

        $res = DomainRegisterService::createDomain($user->currentTeam, 'smsedge.com');

        $this->assertNotEmpty($res['errors']);
        $this->assertEmpty($res['result']);
    }

    public function test_create_domain()
    {
        $user = User::factory()
            ->withPersonalTeam()
            ->create();

        Http::fake([
            'https://api.sandbox.namecheap.com/*' => Http::response(file_get_contents(__DIR__ .
                '/data/namecheap/namecheap.domains.create.xml')),
        ]);

        $res = DomainRegisterService::createDomain($user->currentTeam, 'smsedge.com');

        $this->assertEmpty($res['errors']);
        $this->assertNotEmpty($res['result']);
    }
}
