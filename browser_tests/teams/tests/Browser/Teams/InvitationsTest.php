<?php

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;
use Illuminate\Support\Facades\Notification;

use function Pest\Laravel\actingAs;

test('owner can invite a member from the team edit page', function () {
    Notification::fake();

    $user = User::factory()->create();
    $team = Team::factory()->create(['name' => 'Invite Team']);
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $user->switchTeam($team);

    actingAs($user);

    visit(route('teams.edit', $team))
        ->click('@invite-member-button')
        ->waitForText('Invite a team member')
        ->fill('@invite-email', 'invited@example.com')
        ->pressAndWaitFor('@invite-submit')
        ->assertSee('invited@example.com')
        ->assertSee('Invitation sent.')
        ->assertNoConsoleLogs()
        ->assertNoJavaScriptErrors();

    expect(TeamInvitation::where('email', 'invited@example.com')->exists())->toBeTrue();
});

test('pending invitation appears in the UI', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create(['name' => 'Pending Team']);
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $user->switchTeam($team);

    TeamInvitation::factory()->create([
        'team_id' => $team->id,
        'email' => 'pending@example.com',
        'invited_by' => $user->id,
    ]);

    actingAs($user);

    visit(route('teams.edit', $team))
        ->assertSee('Pending invitations')
        ->assertSee('pending@example.com')
        ->assertVisible('@invitation-row')
        ->assertNoConsoleLogs()
        ->assertNoJavaScriptErrors();
});

test('invitation can be cancelled from the UI', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create(['name' => 'Cancel Team']);
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $user->switchTeam($team);

    TeamInvitation::factory()->create([
        'team_id' => $team->id,
        'email' => 'cancel@example.com',
        'invited_by' => $user->id,
    ]);

    actingAs($user);

    visit(route('teams.edit', $team))
        ->assertSee('cancel@example.com')
        ->click('@invitation-cancel-button')
        ->waitForText('Cancel invitation')
        ->pressAndWaitFor('@cancel-invitation-confirm')
        ->assertDontSee('cancel@example.com')
        ->assertSee('Invitation cancelled.')
        ->assertNoConsoleLogs()
        ->assertNoJavaScriptErrors();
});

test('invitation can be accepted by the invited user', function () {
    $owner = User::factory()->create();
    $team = Team::factory()->create(['name' => 'Accept Team']);
    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);

    $invitation = TeamInvitation::factory()->create([
        'team_id' => $team->id,
        'email' => 'newmember@example.com',
        'invited_by' => $owner->id,
    ]);

    $invitedUser = User::factory()->create(['email' => 'newmember@example.com']);

    actingAs($invitedUser);

    visit(route('invitations.accept', $invitation))
        ->assertPathEndsWith('/dashboard')
        ->assertPathContains($team->slug)
        ->assertNoConsoleLogs()
        ->assertNoJavaScriptErrors();

    expect($invitedUser->fresh()->currentTeam->id)->toBe($team->id);
});
