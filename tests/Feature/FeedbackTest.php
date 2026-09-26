<?php

use App\Models\Feedback;
use App\Models\User;
use App\Notifications\Feedback\FeedbackReceived;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;

test('guests cannot reach the feedback form', function () {
    $this->get(route('feedback.create'))->assertRedirect(route('login'));
});

test('the feedback form renders for authenticated users', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('feedback.create'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('feedback/Index'));
});

test('a user can submit feedback and it is stored against them', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('feedback.store'), ['message' => 'Please add dark mode.'])
        ->assertRedirect(route('feedback.create'));

    $this->assertDatabaseHas('feedback', [
        'user_id' => $user->id,
        'message' => 'Please add dark mode.',
    ]);
});

test('submitting feedback emails the admins', function () {
    Notification::fake();
    config()->set('admin.emails', ['boss@example.com']);

    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('feedback.store'), ['message' => 'Great app!']);

    Notification::assertSentOnDemand(
        FeedbackReceived::class,
        fn (FeedbackReceived $notification, array $channels, object $notifiable) => $notification->message === 'Great app!'
            && in_array('boss@example.com', (array) $notifiable->routes['mail'], true),
    );
});

test('feedback requires a non-empty message', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('feedback.store'), ['message' => ''])
        ->assertSessionHasErrors('message');

    expect(Feedback::count())->toBe(0);
});

test('the help navigation lists feedback below documentation', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('navigation.2.title', 'Help')
            ->where('navigation.2.children.0.title', 'Documentation')
            ->where('navigation.2.children.1.title', 'Feedback')
            ->where('navigation.2.children.1.url', route('feedback.create')),
        );
});

test('non-admins cannot reach the admin feedback list', function () {
    config()->set('admin.emails', ['boss@example.com']);

    $user = User::factory()->create(['email' => 'nobody@example.com']);

    $this->actingAs($user)->get(route('admin.feedback'))->assertForbidden();
});

test('admins see submitted feedback in the admin panel', function () {
    config()->set('admin.emails', ['boss@example.com']);

    $admin = User::factory()->create(['email' => 'boss@example.com']);
    $author = User::factory()->create(['name' => 'Ada Lovelace']);
    Feedback::factory()->fromUser($author)->create(['message' => 'Ship it.']);

    $this->actingAs($admin)
        ->get(route('admin.feedback'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/Feedback')
            ->has('feedback', 1)
            ->where('feedback.0.message', 'Ship it.')
            ->where('feedback.0.user_name', 'Ada Lovelace')
            ->where('feedback.0.user_email', $author->email),
        );
});
