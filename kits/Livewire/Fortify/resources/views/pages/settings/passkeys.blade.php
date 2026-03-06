<?php

use Laravel\Passkeys\Actions\DeletePasskey;
use Livewire\Attributes\Locked;
use Livewire\Component;

new class extends Component {
    /** @var array<int, array{id: int, name: string, authenticator: string|null, created_at_diff: string, last_used_at_diff: string|null}> */
    #[Locked]
    public array $passkeys = [];

    public bool $showDeleteModal = false;

    #[Locked]
    public ?int $deletingPasskeyId = null;

    #[Locked]
    public string $deletingPasskeyName = '';

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $this->loadPasskeys();
    }

    /**
     * Load the user's passkeys.
     */
    public function loadPasskeys(): void
    {
        $this->passkeys = auth()->user()->passkeys()
            ->select(['id', 'name', 'credential', 'created_at', 'last_used_at'])
            ->latest()
            ->get()
            ->map(fn ($passkey) => [
                'id' => $passkey->id,
                'name' => $passkey->name,
                'authenticator' => $passkey->authenticator,
                'created_at_diff' => $passkey->created_at->diffForHumans(),
                'last_used_at_diff' => $passkey->last_used_at?->diffForHumans(),
            ])
            ->toArray();
    }

    /**
     * Show the delete confirmation modal.
     */
    public function confirmDelete(int $passkeyId, string $passkeyName): void
    {
        $this->deletingPasskeyId = $passkeyId;
        $this->deletingPasskeyName = $passkeyName;
        $this->showDeleteModal = true;
    }

    /**
     * Delete the passkey.
     */
    public function deletePasskey(DeletePasskey $deletePasskey): void
    {
        if (! $this->deletingPasskeyId) {
            return;
        }

        $passkey = auth()->user()->passkeys()->findOrFail($this->deletingPasskeyId);

        $deletePasskey(auth()->user(), $passkey);

        $this->closeDeleteModal();
        $this->loadPasskeys();
    }

    /**
     * Close the delete confirmation modal.
     */
    public function closeDeleteModal(): void
    {
        $this->showDeleteModal = false;
        $this->deletingPasskeyId = null;
        $this->deletingPasskeyName = '';
    }
} ?>

<section class="w-full">
    @include('partials.settings-heading')

    <x-pages::settings.layout
        :heading="__('Passkeys')"
        :subheading="__('Manage your passkeys for passwordless sign-in')"
    >
        <div class="flex flex-col w-full mx-auto space-y-6 text-sm" wire:cloak>
            {{-- Passkey List --}}
            <div class="border rounded-lg border-zinc-200 dark:border-zinc-700 overflow-hidden">
                @forelse ($passkeys as $passkey)
                    <div class="flex items-center justify-between p-4 {{ ! $loop->last ? 'border-b border-zinc-200 dark:border-zinc-700' : '' }}">
                        <div class="flex items-center gap-4">
                            <div class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-zinc-100 dark:bg-zinc-800">
                                <flux:icon.key class="size-5 text-zinc-500 dark:text-zinc-400" />
                            </div>
                            <div class="space-y-1">
                                <div class="flex items-center gap-2.5">
                                    <p class="font-medium tracking-tight">{{ $passkey['name'] }}</p>
                                    @if ($passkey['authenticator'])
                                        <flux:badge size="sm">{{ $passkey['authenticator'] }}</flux:badge>
                                    @endif
                                </div>
                                <p class="text-zinc-500 dark:text-zinc-400 text-xs">
                                    {{ __('Added :time', ['time' => $passkey['created_at_diff']]) }}
                                    @if ($passkey['last_used_at_diff'])
                                        <span class="opacity-50 mx-1">/</span>
                                        {{ __('Last used :time', ['time' => $passkey['last_used_at_diff']]) }}
                                    @endif
                                </p>
                            </div>
                        </div>

                        <flux:button
                            variant="ghost"
                            size="sm"
                            icon="trash"
                            icon:variant="outline"
                            wire:click="confirmDelete({{ $passkey['id'] }}, '{{ addslashes($passkey['name']) }}')"
                            class="text-red-500 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-950/50"
                        />
                    </div>
                @empty
                    <div class="p-8 text-center">
                        <div class="mx-auto mb-4 flex size-14 items-center justify-center rounded-2xl bg-zinc-100 dark:bg-zinc-800">
                            <flux:icon.key class="size-7 text-zinc-400 dark:text-zinc-500" />
                        </div>
                        <p class="font-medium">{{ __('No passkeys yet') }}</p>
                        <flux:text class="mt-1">{{ __('Add a passkey to sign in without a password') }}</flux:text>
                    </div>
                @endforelse
            </div>

            <x-passkey-registration />
        </div>
    </x-pages::settings.layout>

    {{-- Delete Confirmation Modal --}}
    <flux:modal
        name="delete-passkey-modal"
        class="max-w-md md:min-w-md"
        @close="closeDeleteModal"
        wire:model="showDeleteModal"
    >
        <div class="space-y-6">
            <div class="space-y-2">
                <flux:heading size="lg">{{ __('Remove passkey') }}</flux:heading>
                <flux:text>
                    {{ __('Are you sure you want to remove the passkey ":name"? You will no longer be able to use it to sign in.', ['name' => $deletingPasskeyName]) }}
                </flux:text>
            </div>

            <div class="flex gap-3 justify-end">
                <flux:button
                    variant="outline"
                    wire:click="closeDeleteModal"
                >
                    {{ __('Cancel') }}
                </flux:button>
                <flux:button
                    variant="danger"
                    wire:click="deletePasskey"
                >
                    {{ __('Remove passkey') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>
</section>
