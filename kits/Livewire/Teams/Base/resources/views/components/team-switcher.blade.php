<?php

use App\Actions\Teams\CreateTeam;
use App\Models\Team;
use App\Rules\TeamName;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

new class extends Component {
    public string $name = '';

    public function currentTeam(): ?array
    {
        $team = Auth::user()->currentTeam;

        return ! $team
            ? null
            : [
                'id' => $team->id,
                'name' => $team->name,
                'slug' => $team->slug,
            ];
    }

    public function teams(): array
    {
        return Auth::user()->teams()->get()->map(fn (Team $team) => [
            'id' => $team->id,
            'name' => $team->name,
            'slug' => $team->slug,
            'is_current' => Auth::user()->isCurrentTeam($team),
        ])->toArray();
    }

    public function switchTeam(string $slug): void
    {
        $team = Team::where('slug', $slug)->firstOrFail();

        abort_unless(Auth::user()->belongsToTeam($team), 403);
        Auth::user()->switchTeam($team);

        $this->redirect(request()->header('Referer', route('dashboard')), navigate: true);
    }

    public function createTeam(CreateTeam $createTeam): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255', new TeamName],
        ]);

        $team = $createTeam->handle(Auth::user(), $validated['name']);

        $this->dispatch('close-modal', name: 'create-team-switcher');
        $this->reset('name');

        $this->redirectRoute('teams.edit', ['team' => $team->slug], navigate: true);
    }
}; ?>

<div>
    <flux:dropdown position="bottom" align="start">
        <flux:button variant="ghost" class="w-full" icon:trailing="chevrons-up-down">
            <span class="truncate font-semibold">{{ $this->currentTeam()['name'] ?? __('Select Team') }}</span>
        </flux:button>

        <flux:menu class="min-w-56">
            <flux:menu.heading>{{ __('Teams') }}</flux:menu.heading>

            @foreach ($this->teams() as $team)
                <flux:menu.item
                    wire:click="switchTeam('{{ $team['slug'] }}')"
                    class="cursor-pointer"
                >
                    <div class="flex w-full items-center justify-between">
                        <span>{{ $team['name'] }}</span>
                        @if ($team['is_current'])
                            <flux:icon name="check" class="size-4" />
                        @endif
                    </div>
                </flux:menu.item>
            @endforeach

            <flux:menu.separator />

            <flux:modal.trigger name="create-team-switcher">
                <flux:menu.item icon="plus" class="cursor-pointer">
                    {{ __('Create Team') }}
                </flux:menu.item>
            </flux:modal.trigger>
        </flux:menu>
    </flux:dropdown>

    <flux:modal name="create-team-switcher" :show="$errors->isNotEmpty()" focusable class="max-w-lg">
        <form wire:submit="createTeam" class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Create a new team') }}</flux:heading>
                <flux:subheading>{{ __('Give your team a name to get started.') }}</flux:subheading>
            </div>

            <flux:input wire:model="name" :label="__('Team name')" type="text" required autofocus />

            <div class="flex justify-end space-x-2 rtl:space-x-reverse">
                <flux:modal.close>
                    <flux:button variant="filled">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>

                <flux:button variant="primary" type="submit">
                    {{ __('Create team') }}
                </flux:button>
            </div>
        </form>
    </flux:modal>
</div>
