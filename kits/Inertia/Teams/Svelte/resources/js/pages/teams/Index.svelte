<script lang="ts">
    import { Link, router } from '@inertiajs/svelte';
    import CheckCircle from 'lucide-svelte/icons/check-circle';
    import Circle from 'lucide-svelte/icons/circle';
    import Eye from 'lucide-svelte/icons/eye';
    import Pencil from 'lucide-svelte/icons/pencil';
    import Plus from 'lucide-svelte/icons/plus';
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
    import { edit, index, switchMethod } from '@/routes/teams';
    import type { BreadcrumbItem, Team } from '@/types';

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

    const handleCreateTeamClick = (
        props: Record<string, unknown>,
        event: MouseEvent,
    ) => {
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
                        <Button
                            onclick={(event) =>
                                handleCreateTeamClick(props, event)}
                        >
                            <Plus class="h-4 w-4" /> Create Team
                        </Button>
                    {/snippet}
                </CreateTeamModal>
            </div>

            <div class="space-y-3">
                {#each teams as team (team.id)}
                    <div
                        class="flex items-center justify-between rounded-lg border p-4 {team.isCurrent
                            ? 'border-ring/60'
                            : ''}"
                    >
                        <div class="flex items-center gap-4">
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="font-medium">{team.name}</span>

                                    {#if team.isPersonal}
                                        <Badge variant="secondary">
                                            Personal
                                        </Badge>
                                    {/if}
                                </div>

                                <span class="text-sm text-muted-foreground"
                                    >{team.roleLabel}</span
                                >
                            </div>
                        </div>

                        <TooltipProvider delayDuration={0}>
                            <div class="flex items-center gap-2">
                                {#if team.isCurrent}
                                    <Tooltip>
                                        <TooltipTrigger>
                                            {#snippet child({ props })}
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    {...props}
                                                >
                                                    <CheckCircle
                                                        class="h-4 w-4"
                                                    />
                                                </Button>
                                            {/snippet}
                                        </TooltipTrigger>
                                        <TooltipContent>
                                            <p>Current team</p>
                                        </TooltipContent>
                                    </Tooltip>
                                {:else}
                                    <Tooltip>
                                        <TooltipTrigger>
                                            {#snippet child({ props })}
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    {...props}
                                                    onclick={(event) => {
                                                        callClickHandler(
                                                            props.onClick,
                                                            event,
                                                        );
                                                        switchTeam(team);
                                                    }}
                                                >
                                                    <Circle
                                                        class="h-4 w-4 text-muted-foreground"
                                                    />
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
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    asChild
                                                    {...props}
                                                >
                                                    {#snippet children(
                                                        buttonProps,
                                                    )}
                                                        <Link
                                                            {...buttonProps}
                                                            href={edit(
                                                                team.slug,
                                                            ).url}
                                                        >
                                                            <Eye
                                                                class="h-4 w-4"
                                                            />
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
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    asChild
                                                    {...props}
                                                >
                                                    {#snippet children(
                                                        buttonProps,
                                                    )}
                                                        <Link
                                                            {...buttonProps}
                                                            href={edit(
                                                                team.slug,
                                                            ).url}
                                                        >
                                                            <Pencil
                                                                class="h-4 w-4"
                                                            />
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
                    <p class="py-8 text-center text-muted-foreground">
                        You don't belong to any teams yet.
                    </p>
                {/if}
            </div>
        </div>
    </SettingsLayout>
</AppLayout>
