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

test('a repeat request for an already-submitted email is rejected with a message', function () {
    setRegistrationMode('invitation');
    Notification::fake();

    $this->post(route('beta.store'), ['name' => 'Ada', 'email' => 'ada@example.com']);

    $this->post(route('beta.store'), ['name' => 'Ada L', 'email' => 'ada@example.com', 'message' => 'Updated'])
        ->assertSessionHasErrors('email');

    // The pending request is left untouched and no duplicate is created.
    expect(BetaRequestModel::count())->toBe(1);
    $request = BetaRequestModel::first();
    expect($request->name)->toBe('Ada')
        ->and($request->message)->toBeNull();

    // Only the first submission notified anyone.
    Notification::assertSentOnDemandTimes(BetaRequestReceived::class, 1);
});

test('an already-approved email cannot request access again', function () {
    setRegistrationMode('invitation');
    Notification::fake();
    BetaRequestModel::factory()->forEmail('ada@example.com')->approved()->create();

    $this->post(route('beta.store'), ['name' => 'Ada', 'email' => 'ada@example.com'])
        ->assertSessionHasErrors('email');

    Notification::assertNothingSent();
});

test('a rejected applicant can submit a fresh request', function () {
    setRegistrationMode('invitation');
    Notification::fake();
    BetaRequestModel::factory()->forEmail('ada@example.com')->rejected()->create();

    $this->post(route('beta.store'), ['name' => 'Ada', 'email' => 'ada@example.com'])
        ->assertRedirect(route('home'))
        ->assertSessionHasNoErrors();

    // The rejected row is reused and flipped back to pending.
    expect(BetaRequestModel::count())->toBe(1)
        ->and(BetaRequestModel::first()->status)->toBe(BetaRequestStatus::Pending);
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
            ->where('requests.0.name', 'Ada')
            ->where('requests.0.registered', false),
        );
});

test('a request whose requester already has an account is flagged registered', function () {
    config()->set('admin.emails', ['boss@example.com']);

    $admin = User::factory()->create(['email' => 'boss@example.com']);
    User::factory()->create(['email' => 'ada@example.com', 'created_at' => now()->subDays(3)]);
    BetaRequestModel::factory()->forEmail('ada@example.com')->create();

    $this->actingAs($admin)
        ->get(route('admin.beta-requests'))
        ->assertInertia(fn ($page) => $page
            ->has('requests', 1)
            ->where('requests.0.email', 'ada@example.com')
            ->where('requests.0.registered', true),
        );
});

test('account matching ignores email casing', function () {
    config()->set('admin.emails', ['boss@example.com']);

    $admin = User::factory()->create(['email' => 'boss@example.com']);
    User::factory()->create(['email' => 'Ada@Example.com', 'created_at' => now()->subDays(1)]);
    BetaRequestModel::factory()->forEmail('ada@example.com')->create();

    $this->actingAs($admin)
        ->get(route('admin.beta-requests'))
        ->assertInertia(fn ($page) => $page
            ->has('requests', 1)
            ->where('requests.0.registered', true),
        );
});

test('a request drops off once its requester has had an account for over two weeks', function () {
    config()->set('admin.emails', ['boss@example.com']);

    $admin = User::factory()->create(['email' => 'boss@example.com']);
    User::factory()->create(['email' => 'ada@example.com', 'created_at' => now()->subWeeks(3)]);
    BetaRequestModel::factory()->forEmail('ada@example.com')->create();

    // A fresh request with no account still shows.
    BetaRequestModel::factory()->forEmail('grace@example.com')->create();

    $this->actingAs($admin)
        ->get(route('admin.beta-requests'))
        ->assertInertia(fn ($page) => $page
            ->has('requests', 1)
            ->where('requests.0.email', 'grace@example.com'),
        );
});
