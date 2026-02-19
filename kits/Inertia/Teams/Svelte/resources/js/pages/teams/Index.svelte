<script lang="ts">
    import { Link, router } from '@inertiajs/svelte';
    import Edit from 'lucide-svelte/icons/edit';
    import Eye from 'lucide-svelte/icons/eye';
    import Plus from 'lucide-svelte/icons/plus';
    import Star from 'lucide-svelte/icons/star';
    import AppHead from '@/components/AppHead.svelte';
    import CreateTeamModal from '@/components/CreateTeamModal.svelte';
    import Heading from '@/components/Heading.svelte';
    import { Badge } from '@/components/ui/badge';
    import { Button } from '@/components/ui/button';
    import {
        Tooltip,
        TooltipContent,
        TooltipProvider,
        TooltipTrigger,
    } from '@/components/ui/tooltip';
    import AppLayout from '@/layouts/AppLayout.svelte';
    import SettingsLayout from '@/layouts/settings/Layout.svelte';
    import type { BreadcrumbItem, Team } from '@/types';
    import { edit, index, switchMethod } from '@/routes/teams';

    let {
        teams,
    }: {
        teams: Team[];
    } = $props();

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Teams',
            href: index().url,
        },
    ];

    const switchTeam = (team: Team) => router.post(switchMethod(team.slug).url);

    const callClickHandler = (handler: unknown, event: MouseEvent) => {
        if (typeof handler === 'function') {
            handler(event);
        }
    };

    const handleCreateTeamClick = (props: Record<string, unknown>, event: MouseEvent) => {
        callClickHandler(props.onClick, event);
    };
</script>

<AppHead title="Teams" />

<AppLayout {breadcrumbs}>
    <h1 class="sr-only">Teams</h1>

    <SettingsLayout>
        <div class="flex flex-col space-y-6">
            <div class="flex items-center justify-between">
                <Heading
                    variant="small"
                    title="Teams"
                    description="Manage your teams and team memberships"
                />

                <CreateTeamModal>
                    {#snippet children(props)}
                        <Button onclick={(event) => handleCreateTeamClick(props, event)}>
                            <Plus class="h-4 w-4" /> Create Team
                        </Button>
                    {/snippet}
                </CreateTeamModal>
            </div>

            <div class="space-y-3">
                {#each teams as team (team.id)}
                    <div class="flex items-center justify-between rounded-lg border p-4">
                        <div class="flex items-center gap-4">
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="font-medium">{team.name}</span>

                                    {#if team.is_current}
                                        <Badge class="bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300">
                                            Current
                                        </Badge>
                                    {/if}

                                    {#if team.is_personal}
                                        <Badge variant="secondary">
                                            Personal
                                        </Badge>
                                    {/if}
                                </div>

                                <span class="text-sm text-muted-foreground">{team.role_label}</span>
                            </div>
                        </div>

                        <TooltipProvider delayDuration={0}>
                            <div class="flex items-center gap-2">
                                {#if !team.is_current}
                                    <Tooltip>
                                        <TooltipTrigger>
                                            {#snippet child({ props })}
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    {...props}
                                                    onclick={(event) => {
                                                        callClickHandler(props.onClick, event);
                                                        switchTeam(team);
                                                    }}
                                                >
                                                    <Star class="h-4 w-4" />
                                                </Button>
                                            {/snippet}
                                        </TooltipTrigger>
                                        <TooltipContent>
                                            <p>Set as current team</p>
                                        </TooltipContent>
                                    </Tooltip>
                                {/if}

                                {#if team.role === 'member'}
                                    <Tooltip>
                                        <TooltipTrigger>
                                            {#snippet child({ props })}
                                                <Button variant="ghost" size="sm" asChild {...props}>
                                                    {#snippet children(buttonProps)}
                                                        <Link
                                                            {...buttonProps}
                                                            href={edit(team.slug).url}
                                                        >
                                                            <Eye class="h-4 w-4" />
                                                        </Link>
                                                    {/snippet}
                                                </Button>
                                            {/snippet}
                                        </TooltipTrigger>
                                        <TooltipContent>
                                            <p>View team</p>
                                        </TooltipContent>
                                    </Tooltip>
                                {:else}
                                    <Tooltip>
                                        <TooltipTrigger>
                                            {#snippet child({ props })}
                                                <Button variant="ghost" size="sm" asChild {...props}>
                                                    {#snippet children(buttonProps)}
                                                        <Link
                                                            {...buttonProps}
                                                            href={edit(team.slug).url}
                                                        >
                                                            <Edit class="h-4 w-4" />
                                                        </Link>
                                                    {/snippet}
                                                </Button>
                                            {/snippet}
                                        </TooltipTrigger>
                                        <TooltipContent>
                                            <p>Edit team</p>
                                        </TooltipContent>
                                    </Tooltip>
                                {/if}
                            </div>
                        </TooltipProvider>
                    </div>
                {/each}

                {#if teams.length === 0}
                    <p class="py-8 text-center text-muted-foreground">You don't belong to any teams yet.</p>
                {/if}
            </div>
        </div>
    </SettingsLayout>
</AppLayout>
