<?php

use App\Application\Actions\Auth\ProvisionUserFromWorkOS;
use App\Exceptions\RegistrationNotAllowed;
use App\Models\BetaRequestModel;
use App\Models\User;
use App\Support\RegistrationPolicy;
use Laravel\Pennant\Feature;
use Laravel\WorkOS\User as WorkOSUser;

function useRegistrationMode(string $mode): void
{
    config()->set('features.registration', $mode);
    Feature::flushCache();
    Feature::purge('registration');
}

function workosUser(string $email = 'ada@example.com'): WorkOSUser
{
    return new WorkOSUser(
        id: 'workos_'.md5($email),
        organizationId: null,
        firstName: 'Ada',
        lastName: 'Lovelace',
        email: $email,
        avatar: null,
    );
}

test('open mode lets any email register', function () {
    useRegistrationMode('open');

    expect(app(RegistrationPolicy::class)->allowsRegistration('stranger@example.com'))->toBeTrue();
});

test('closed mode blocks everyone, even an approved request', function () {
    useRegistrationMode('closed');
    BetaRequestModel::factory()->forEmail('ada@example.com')->approved()->create();

    expect(app(RegistrationPolicy::class)->allowsRegistration('ada@example.com'))->toBeFalse();
});

test('an allowlisted admin bypasses the gate in every mode', function (string $mode) {
    config()->set('admin.emails', ['boss@example.com']);
    useRegistrationMode($mode);

    $policy = app(RegistrationPolicy::class);

    // No beta request exists for the admin, yet they still get in.
    expect($policy->allowsRegistration('boss@example.com'))->toBeTrue()
        ->and($policy->allowsRegistration('Boss@Example.com'))->toBeTrue();
})->with(['open', 'invitation', 'closed']);

test('invitation mode allows an approved email and blocks the rest', function () {
    useRegistrationMode('invitation');
    BetaRequestModel::factory()->forEmail('ada@example.com')->approved()->create();
    BetaRequestModel::factory()->forEmail('pending@example.com')->create();

    $policy = app(RegistrationPolicy::class);

    expect($policy->allowsRegistration('ada@example.com'))->toBeTrue()
        ->and($policy->allowsRegistration('pending@example.com'))->toBeFalse()
        ->and($policy->allowsRegistration('nobody@example.com'))->toBeFalse();
});

test('invitation approval matches email case-insensitively', function () {
    useRegistrationMode('invitation');
    BetaRequestModel::factory()->forEmail('ada@example.com')->approved()->create();

    expect(app(RegistrationPolicy::class)->allowsRegistration('Ada@Example.com'))->toBeTrue();
});

test('provisioning creates the account for an allowed email', function () {
    useRegistrationMode('invitation');
    BetaRequestModel::factory()->forEmail('ada@example.com')->approved()->create();

    $user = app(ProvisionUserFromWorkOS::class)(workosUser('ada@example.com'));

    expect($user)->toBeInstanceOf(User::class);
    $this->assertDatabaseHas('users', [
        'email' => 'ada@example.com',
        'workos_id' => 'workos_'.md5('ada@example.com'),
    ]);
});

test('provisioning rejects an email with no approved request', function () {
    useRegistrationMode('invitation');

    expect(fn () => app(ProvisionUserFromWorkOS::class)(workosUser('stranger@example.com')))
        ->toThrow(RegistrationNotAllowed::class);

    $this->assertDatabaseMissing('users', ['email' => 'stranger@example.com']);
});

test('rejection funnels to the beta request form in invitation mode', function () {
    useRegistrationMode('invitation');

    $response = (new RegistrationNotAllowed)->render(request());

    expect($response->getTargetUrl())->toBe(route('beta.create'));
});

test('rejection returns to the landing page in closed mode', function () {
    useRegistrationMode('closed');

    $response = (new RegistrationNotAllowed)->render(request());

    expect($response->getTargetUrl())->toBe(route('home'));
});
