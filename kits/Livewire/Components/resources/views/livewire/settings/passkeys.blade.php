<section class="w-full">
    @include('partials.settings-heading')

    <x-settings.layout
        :heading="__('Passkeys')"
        :subheading="__('Manage your passkeys for passwordless sign-in')"
    >
        <div class="flex flex-col w-full mx-auto space-y-6 text-sm" wire:cloak>
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
    </x-settings.layout>

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
