<?php

use App\Enums\BetaRequestStatus;
use App\Models\BetaRequestModel;
use App\Models\User;
use App\Notifications\Beta\BetaRequestApproved;
use App\Notifications\Beta\BetaRequestReceived;
use App\Notifications\Beta\BetaRequestSubmitted;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Laravel\Pennant\Feature;

function setRegistrationMode(string $mode): void
{
    config()->set('features.registration', $mode);
    Feature::flushCache();
    Feature::purge('registration');
}

test('the beta request page renders in invitation mode', function () {
    setRegistrationMode('invitation');

    $this->get(route('beta.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('beta/register'));
});

test('the beta request page is hidden when registration is open', function () {
    setRegistrationMode('open');

    $this->get(route('beta.create'))->assertNotFound();
});

test('the beta request page is hidden when registration is closed', function () {
    setRegistrationMode('closed');

    $this->get(route('beta.create'))->assertNotFound();
});

test('a visitor can request beta access', function () {
    setRegistrationMode('invitation');
    Notification::fake();
    config()->set('admin.emails', ['boss@example.com']);

    $response = $this->post(route('beta.store'), [
        'name' => 'Ada Lovelace',
        'email' => 'ada@example.com',
        'message' => 'I give a lot of talks.',
    ]);

    $response->assertRedirect(route('home'));

    $request = BetaRequestModel::first();
    expect($request)->not->toBeNull()
        ->and($request->name)->toBe('Ada Lovelace')
        ->and($request->email)->toBe('ada@example.com')
        ->and($request->message)->toBe('I give a lot of talks.')
        ->and($request->status)->toBe(BetaRequestStatus::Pending);

    Notification::assertSentOnDemand(BetaRequestSubmitted::class);
    Notification::assertSentOnDemand(BetaRequestReceived::class);
});

test('the stored email is encrypted at rest and keyed by hash', function () {
    setRegistrationMode('invitation');
    Notification::fake();

    $this->post(route('beta.store'), [
        'name' => 'Ada Lovelace',
        'email' => 'ada@example.com',
    ]);

    $raw = DB::table('beta_requests')->first();

    expect($raw->email)->not->toBe('ada@example.com')
        ->and(Crypt::decrypt($raw->email, false))->toBe('ada@example.com')
        ->and($raw->email_hash)->toBe(hash('sha256', 'ada@example.com'));
});

test('requesting beta access requires a name and a valid email', function () {
    setRegistrationMode('invitation');
    Notification::fake();

    $this->post(route('beta.store'), ['email' => 'not-an-email'])
        ->assertSessionHasErrors(['name', 'email']);

    expect(BetaRequestModel::count())->toBe(0);
    Notification::assertNothingSent();
});

test('a repeat request for the same email does not create a duplicate', function () {
    setRegistrationMode('invitation');
    Notification::fake();

    $this->post(route('beta.store'), ['name' => 'Ada', 'email' => 'ada@example.com']);
    $this->post(route('beta.store'), ['name' => 'Ada L', 'email' => 'ada@example.com', 'message' => 'Updated']);

    expect(BetaRequestModel::count())->toBe(1);

    $request = BetaRequestModel::first();
    expect($request->name)->toBe('Ada L')
        ->and($request->message)->toBe('Updated');
});

test('a visitor cannot request beta access when registration is closed', function () {
    setRegistrationMode('closed');
    Notification::fake();

    $this->post(route('beta.store'), ['name' => 'Ada', 'email' => 'ada@example.com'])
        ->assertNotFound();

    expect(BetaRequestModel::count())->toBe(0);
});

test('an admin can approve a beta request', function () {
    config()->set('admin.emails', ['boss@example.com']);
    Notification::fake();

    $admin = User::factory()->create(['email' => 'boss@example.com']);
    $request = BetaRequestModel::factory()->forEmail('ada@example.com')->create();

    $this->actingAs($admin)
        ->post(route('admin.beta-requests.approve', ['betaRequest' => $request->id]))
        ->assertRedirect(route('admin.beta-requests'));

    expect($request->refresh()->status)->toBe(BetaRequestStatus::Approved);
    Notification::assertSentOnDemand(BetaRequestApproved::class);
});

test('an admin can reject a beta request without emailing the requester', function () {
    config()->set('admin.emails', ['boss@example.com']);
    Notification::fake();

    $admin = User::factory()->create(['email' => 'boss@example.com']);
    $request = BetaRequestModel::factory()->create();

    $this->actingAs($admin)
        ->post(route('admin.beta-requests.reject', ['betaRequest' => $request->id]))
        ->assertRedirect(route('admin.beta-requests'));

    expect($request->refresh()->status)->toBe(BetaRequestStatus::Rejected);
    Notification::assertNothingSent();
});

test('non-admins cannot approve beta requests', function () {
    config()->set('admin.emails', ['boss@example.com']);

    $user = User::factory()->create(['email' => 'nobody@example.com']);
    $request = BetaRequestModel::factory()->create();

    $this->actingAs($user)
        ->post(route('admin.beta-requests.approve', ['betaRequest' => $request->id]))
        ->assertForbidden();

    expect($request->refresh()->status)->toBe(BetaRequestStatus::Pending);
});

test('the admin beta requests page lists requests with decrypted emails', function () {
    config()->set('admin.emails', ['boss@example.com']);

    $admin = User::factory()->create(['email' => 'boss@example.com']);
    BetaRequestModel::factory()->forEmail('ada@example.com')->create(['name' => 'Ada']);

    $this->actingAs($admin)
        ->get(route('admin.beta-requests'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/BetaRequests')
            ->has('requests', 1)
            ->where('requests.0.email', 'ada@example.com')
            ->where('requests.0.name', 'Ada'),
        );
});
