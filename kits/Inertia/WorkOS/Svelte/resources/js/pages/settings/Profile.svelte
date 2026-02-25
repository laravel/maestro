<script lang="ts">
    import { Form, page } from '@inertiajs/svelte';
    import AppHead from '@/components/AppHead.svelte';
    import DeleteUser from '@/components/DeleteUser.svelte';
    import Heading from '@/components/Heading.svelte';
    import InputError from '@/components/InputError.svelte';
    import { Button } from '@/components/ui/button';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    import AppLayout from '@/layouts/AppLayout.svelte';
    import SettingsLayout from '@/layouts/settings/Layout.svelte';
    import type { BreadcrumbItem } from '@/types';
    import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
    import { edit } from '@/routes/profile';

    const breadcrumbItems: BreadcrumbItem[] = [
        {
            title: 'Profile settings',
            href: edit().url,
        },
    ];

    const user = $derived($page.props.auth.user);
</script>

<AppHead title="Profile settings" />

<AppLayout breadcrumbs={breadcrumbItems}>
    <h1 class="sr-only">Profile Settings</h1>

    <SettingsLayout>
        <div class="flex flex-col space-y-6">
            <Heading
                variant="small"
                title="Profile information"
                description="Update your name and email address"
            />

            <Form
                {...ProfileController.update.form()}
                class="space-y-6"
                options={{ preserveScroll: true }}
            >
                {#snippet children({ errors, processing, recentlySuccessful })}
                    <div class="grid gap-2">
                        <Label for="name">Name</Label>
                        <Input
                            id="name"
                            name="name"
                            class="mt-1 block w-full"
                            value={user.name}
                            required
                            autocomplete="name"
                            placeholder="Full name"
                        />
                        <InputError class="mt-2" message={errors.name} />
                    </div>

                    <div class="grid gap-2">
                        <Label for="email">Email address</Label>
                        <Input
                            id="email"
                            type="email"
                            name="email"
                            class="mt-1 block w-full"
                            value={user.email}
                            required
                            autocomplete="username"
                            placeholder="Email address"
                            disabled
                        />
                        <InputError class="mt-2" message={errors.email} />
                    </div>

                    <div class="flex items-center gap-4">
                        <Button
                            type="submit"
                            disabled={processing}
                            data-test="update-profile-button">Save</Button
                        >

                        {#if recentlySuccessful}
                            <p class="text-sm text-neutral-600">Saved.</p>
                        {/if}
                    </div>
                {/snippet}
            </Form>
        </div>

        <DeleteUser />
    </SettingsLayout>
</AppLayout>
