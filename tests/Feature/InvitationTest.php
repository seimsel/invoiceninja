<?php

namespace Feature;

use App\DataMapper\ClientSettings;
use App\DataMapper\DefaultSettings;
use App\Events\Invoice\InvoiceWasMarkedSent;
use App\Factory\InvoiceInvitationFactory;
use App\Jobs\Account\CreateAccount;
use App\Listeners\Invoice\CreateInvoiceInvitations;
use App\Models\Account;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceInvitation;
use App\Models\User;
use App\Utils\Traits\MakesHash;
use App\Utils\Traits\UserSessionAttributes;
use Faker\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Session;
use Tests\TestCase;

/**
 * @test
 * @covers App\Models\InvoiceInvitation\InvoiceInvitationFactory
 */

class InvitationTest extends TestCase
{
	use MakesHash;
    use DatabaseTransactions;

    public function setUp() :void
    {
        parent::setUp();

        Session::start();

        $this->faker = \Faker\Factory::create();

        Model::reguard();
    }

    public function testInvoiceCreationAfterInvoiceMarkedSent()
    {
		$account = factory(\App\Models\Account::class)->create();
		        $company = factory(\App\Models\Company::class)->create([
		            'account_id' => $account->id,
		        ]);

        $account->default_company_id = $company->id;
        $account->save();

        $user = factory(\App\Models\User::class)->create([
        //    'account_id' => $account->id,
            'confirmation_code' => $this->createDbHash(config('database.default'))
        ]);


        $userPermissions = collect([
                                    'view_invoice',
                                    'view_client',
                                    'edit_client',
                                    'edit_invoice',
                                    'create_invoice',
                                    'create_client'
                                ]);

        $userSettings = DefaultSettings::userSettings();

        $user->companies()->attach($company->id, [
            'account_id' => $account->id,
            'is_owner' => 1,
            'is_admin' => 1,
            'permissions' => $userPermissions->toJson(),
            'settings' => json_encode($userSettings),
            'is_locked' => 0,
        ]);

        factory(\App\Models\Client::class)->create(['user_id' => $user->id, 'company_id' => $company->id])->each(function ($c) use ($user, $company){

            factory(\App\Models\ClientContact::class,1)->create([
                'user_id' => $user->id,
                'client_id' => $c->id,
                'company_id' => $company->id,
                'is_primary' => 1
            ]);

            factory(\App\Models\ClientContact::class,2)->create([
                'user_id' => $user->id,
                'client_id' => $c->id,
                'company_id' => $company->id
            ]);

        });

        $client = Client::whereUserId($user->id)->whereCompanyId($company->id)->first();

        factory(\App\Models\Invoice::class,5)->create(['user_id' => $user->id, 'company_id' => $company->id, 'client_id' => $client->id]);

        $invoice = Invoice::whereUserId($user->id)->whereCompanyId($company->id)->whereClientId($client->id)->first();

        $this->assertNotNull($invoice);
        $this->assertNotNull($invoice->client);
        $this->assertNotNull($invoice->client->primary_contact);


        $i = InvoiceInvitationFactory::create($invoice->company_id, $invoice->user_id);
        $i->client_contact_id = $client->primary_contact->first()->id;
        $i->invoice_id = $invoice->id;
        $i->save();

        $this->assertNotNull($invoice->invitations);
        
    }
}
