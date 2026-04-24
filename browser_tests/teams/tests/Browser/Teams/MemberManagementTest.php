<?php

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;

use function Pest\Laravel\actingAs;

test('owner can see member list on team edit page', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create(['name' => 'Members Team']);
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);

    $member = User::factory()->create(['name' => 'Team Member']);
    $team->members()->attach($member, ['role' => TeamRole::Member->value]);

    $user->switchTeam($team);

    actingAs($user);

    visit(route('teams.edit', $team))
        ->assertSee('Team members')
        ->assertSee($user->name)
        ->assertSee('Team Member')
        ->assertNoConsoleLogs()
        ->assertNoJavaScriptErrors();
});

test('owner can change member role', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create(['name' => 'Role Team']);
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);

    $member = User::factory()->create(['name' => 'Promote Me']);
    $team->members()->attach($member, ['role' => TeamRole::Member->value]);

    $user->switchTeam($team);

    actingAs($user);

    visit(route('teams.edit', $team))
        ->click('@member-role-trigger')
        ->waitForText('Admin')
        ->click('Admin')
        ->assertSee('Admin')
        ->assertSee('Member role updated.')
        ->assertNoConsoleLogs()
        ->assertNoJavaScriptErrors();
});

test('owner can remove a member from the team', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create(['name' => 'Remove Team']);
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);

    $member = User::factory()->create(['name' => 'Remove Me']);
    $team->members()->attach($member, ['role' => TeamRole::Member->value]);

    $user->switchTeam($team);

    actingAs($user);

    visit(route('teams.edit', $team))
        ->assertSee('Remove Me')
        ->click('@member-remove-button')
        ->waitForText('Remove team member')
        ->pressAndWaitFor('@remove-member-confirm')
        ->assertDontSee('Remove Me')
        ->assertSee('Member removed.')
        ->assertNoConsoleLogs()
        ->assertNoJavaScriptErrors();
});

test('removed member falls back to personal team', function () {
    $owner = User::factory()->create();
    $team = Team::factory()->create(['name' => 'Fallback Team']);
    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);

    $member = User::factory()->create(['name' => 'Removed Member']);
    $team->members()->attach($member, ['role' => TeamRole::Member->value]);
    $member->switchTeam($team);

    $owner->switchTeam($team);

    actingAs($owner);

    visit(route('teams.edit', $team))
        ->click('@member-remove-button')
        ->waitForText('Remove team member')
        ->pressAndWaitFor('@remove-member-confirm')
        ->assertDontSee('Removed Member')
        ->assertSee('Member removed.')
        ->assertNoConsoleLogs()
        ->assertNoJavaScriptErrors();

    expect($member->fresh()->currentTeam->is_personal)->toBeTrue();
});
