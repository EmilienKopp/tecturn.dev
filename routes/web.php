<?php

use App\Http\Controllers\Admin\AdminBetaRequestsController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminFeatureFlagsController;
use App\Http\Controllers\Admin\AdminUsersController;
use App\Http\Controllers\Admin\ApproveBetaRequestController;
use App\Http\Controllers\Admin\RejectBetaRequestController;
use App\Http\Controllers\Beta\BetaRequestController;
use App\Http\Controllers\Contacts\AcceptFollowRequestController;
use App\Http\Controllers\Contacts\FollowController;
use App\Http\Controllers\Contacts\RejectFollowRequestController;
use App\Http\Controllers\ContactsController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Presentations\EmbedPresentationController;
use App\Http\Controllers\Presentations\EndSessionController;
use App\Http\Controllers\Presentations\ExportPresentationController;
use App\Http\Controllers\Presentations\GenerateDeckController;
use App\Http\Controllers\Presentations\ImportPresentationController;
use App\Http\Controllers\Presentations\PresentationBackgroundController;
use App\Http\Controllers\Presentations\PresentationController;
use App\Http\Controllers\Presentations\PresentPresentationController;
use App\Http\Controllers\Presentations\RecordReactionsController;
use App\Http\Controllers\Presentations\RehearsalController;
use App\Http\Controllers\Presentations\RequestRehearsalReviewController;
use App\Http\Controllers\Presentations\SendReactionController;
use App\Http\Controllers\Presentations\StartSessionController;
use App\Http\Controllers\Presentations\StartTranslationSessionController;
use App\Http\Controllers\Presentations\StopTranslationSessionController;
use App\Http\Controllers\Presentations\UploadPresentationImageController;
use App\Http\Controllers\Presentations\ViewerController;
use App\Http\Controllers\Reviews\AddReviewCommentController;
use App\Http\Controllers\Reviews\CompleteRehearsalReviewController;
use App\Http\Controllers\Reviews\RehearsalReviewController;
use App\Http\Controllers\Reviews\ShowReceivedReviewController;
use App\Http\Controllers\Reviews\StreamRehearsalAudioController;
use App\Http\Controllers\Teams\TeamInvitationController;
use App\Http\Middleware\EnsureTeamMembership;
use App\Http\Middleware\EnsureUserIsAdmin;
use Illuminate\Support\Facades\Route;
use Laravel\WorkOS\Http\Middleware\ValidateSessionWithWorkOS;

Route::inertia('/', 'Welcome')->name('home');

// Public product documentation. Registered before the "{current_team}" group
// so "docs" is never captured as a team slug.
Route::inertia('docs', 'docs/Index')->name('docs');

// Private beta signup. Only reachable while registration mode is "invitation";
// the controllers 404 otherwise.
Route::get('beta', [BetaRequestController::class, 'create'])->name('beta.create');
Route::post('beta', [BetaRequestController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('beta.store');

// Admin panel. Served on a dedicated subdomain when ADMIN_DOMAIN is set,
// otherwise under a "/admin" path prefix for local development. Gated by
// WorkOS auth plus the ADMIN_EMAILS allowlist. Registered before the team
// "{current_team}" group so "/admin" is never captured as a team slug.
$adminRoutes = Route::middleware(['auth', ValidateSessionWithWorkOS::class, EnsureUserIsAdmin::class])
    ->name('admin.');

if ($adminDomain = config('admin.domain')) {
    $adminRoutes->domain($adminDomain);
} else {
    $adminRoutes->prefix('admin');
}

$adminRoutes->group(function () {
    Route::get('/', AdminDashboardController::class)->name('dashboard');
    Route::get('users', [AdminUsersController::class, 'index'])->name('users');
    Route::get('users/{user}', [AdminUsersController::class, 'show'])->name('users.show');
    Route::get('beta-requests', AdminBetaRequestsController::class)->name('beta-requests');
    Route::post('beta-requests/{betaRequest}/approve', ApproveBetaRequestController::class)->name('beta-requests.approve');
    Route::post('beta-requests/{betaRequest}/reject', RejectBetaRequestController::class)->name('beta-requests.reject');
    Route::get('features', [AdminFeatureFlagsController::class, 'index'])->name('features');
    Route::post('features', [AdminFeatureFlagsController::class, 'update'])->name('features.update');
});

Route::get('embed/presentations/{presentation:embed_token}.js', EmbedPresentationController::class)
    ->middleware('throttle:60,1')
    ->name('presentations.embed');

Route::get('present/{presentation:embed_token}', ViewerController::class)
    ->name('presentations.viewer');

Route::middleware(['auth', ValidateSessionWithWorkOS::class])->group(function () {
    Route::get('contacts', ContactsController::class)->name('contacts.index');
    Route::post('contacts/{user}/follow', [FollowController::class, 'store'])->name('contacts.follow.store');
    Route::delete('contacts/{user}/follow', [FollowController::class, 'destroy'])->name('contacts.follow.destroy');
    Route::post('contacts/{user}/follow/accept', AcceptFollowRequestController::class)->name('contacts.follow.accept');
    Route::delete('contacts/{user}/follow/reject', RejectFollowRequestController::class)->name('contacts.follow.reject');

    // Reviewer-facing routes live outside the "{current_team}" group — the
    // reviewer is generally not a member of the requester's team. Registered
    // before that group so these paths are never captured as team slugs.
    Route::get('reviews', [RehearsalReviewController::class, 'index'])->name('reviews.index');
    Route::get('reviews/{rehearsal_review}', [RehearsalReviewController::class, 'show'])->name('reviews.show');
    Route::get('reviews/{rehearsal_review}/received', ShowReceivedReviewController::class)->name('reviews.received');
    Route::post('reviews/{rehearsal_review}/comments', AddReviewCommentController::class)->name('reviews.comments.store');
    Route::post('reviews/{rehearsal_review}/complete', CompleteRehearsalReviewController::class)->name('reviews.complete');
    Route::get('rehearsal-audio/{rehearsal}', StreamRehearsalAudioController::class)->name('rehearsals.audio');
});

Route::post('present/{presentation:embed_token}/reactions', SendReactionController::class)
    ->middleware('throttle:60,1')
    ->name('presentations.reactions');

Route::post('present/{presentation:embed_token}/reactions/batch', RecordReactionsController::class)
    ->middleware('throttle:60,1')
    ->name('presentations.reactions.batch');

Route::prefix('{current_team}')
    ->middleware(['auth', ValidateSessionWithWorkOS::class, EnsureTeamMembership::class])
    ->scopeBindings()
    ->group(function () {
        Route::get('dashboard', DashboardController::class)->name('dashboard');

        Route::get('rehearsals', [RehearsalController::class, 'index'])->name('rehearsals.index');
        Route::get('rehearsals/{rehearsal}', [RehearsalController::class, 'show'])->name('rehearsals.show');
        Route::post('rehearsals/{rehearsal}/reviews', RequestRehearsalReviewController::class)->name('rehearsals.reviews.store');

        Route::get('presentations', [PresentationController::class, 'index'])->name('presentations.index');
        Route::post('presentations', [PresentationController::class, 'store'])->name('presentations.store');
        Route::post('presentations/generate', GenerateDeckController::class)->name('presentations.generate');
        Route::post('presentations/import', ImportPresentationController::class)->name('presentations.importJson');
        Route::get('presentations/{presentation}', [PresentationController::class, 'edit'])->name('presentations.edit');
        Route::get('presentations/{presentation}/present', PresentPresentationController::class)->name('presentations.present');
        Route::post('presentations/{presentation}/rehearsals', [RehearsalController::class, 'store'])->name('presentations.rehearsal.store');
        Route::post('presentations/{presentation}/session', StartSessionController::class)->name('presentations.session.start');
        // POST (not DELETE) so the presenter's unload handler can close the
        // session via navigator.sendBeacon, which only issues POST requests.
        Route::post('presentations/{presentation}/session/close', EndSessionController::class)->name('presentations.session.end');
        Route::post('presentations/{presentation}/translation-session', StartTranslationSessionController::class)->name('presentations.translation-session.start');
        Route::delete('presentations/{presentation}/translation-session', StopTranslationSessionController::class)->name('presentations.translation-session.stop');
        Route::get('presentations/{presentation}/export', ExportPresentationController::class)->name('presentations.export');
        Route::put('presentations/{presentation}', [PresentationController::class, 'update'])->name('presentations.update');
        Route::post('presentations/{presentation}/background', [PresentationBackgroundController::class, 'store'])->name('presentations.background.store');
        Route::post('presentations/{presentation}/images', UploadPresentationImageController::class)->name('presentations.images.store');
        Route::delete('presentations/{presentation}/background', [PresentationBackgroundController::class, 'destroy'])->name('presentations.background.destroy');
        Route::delete('presentations/{presentation}', [PresentationController::class, 'destroy'])->name('presentations.destroy');
    });

Route::middleware(['auth'])->group(function () {
    Route::get('invitations/{invitation}/accept', [TeamInvitationController::class, 'accept'])->name('invitations.accept');
    Route::delete('invitations/{invitation}', [TeamInvitationController::class, 'decline'])->name('invitations.decline');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
