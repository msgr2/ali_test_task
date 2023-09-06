<?php

namespace Tests\Feature;

use App\Models\Clickhouse\Contact;
use App\Models\Clickhouse\Views\ContactSmsView;
use App\Services\CountryService;
use App\Services\SmsContactMobileNetworksService;
use PhpClickHouseLaravel\RawColumn;
use Ramsey\Uuid\Uuid;
use Tests\TestCase;

class ContactsTest extends TestCase
{
    public function testContactFactory()
    {
        $this->markTestIncomplete('TODO...');
        $contacts = Contact::factory()->saveAndReturn();
        $rows = ContactSmsView::where('team_id', $contacts[0]->team_id)
            ->getRows();

        $this->assertCount(100, $rows);
    }


    public function testContactNetworkInformation()
    {
        $this->markTestIncomplete('TODO...');
        $contacts = Contact::factory()->saveAndReturn('au');
        SmsContactMobileNetworksService::getNetworks($contacts);

        $rows = ContactSmsView::where('team_id', $contacts[0]->team_id)
            ->getRows();

        //@TODO: add assertions?
        $this->assertCount(100, $rows);
    }
}
