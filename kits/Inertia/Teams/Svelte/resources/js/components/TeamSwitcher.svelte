<script lang="ts">
    import { page, router } from '@inertiajs/svelte';
    import Check from 'lucide-svelte/icons/check';
    import ChevronsUpDown from 'lucide-svelte/icons/chevrons-up-down';
    import Plus from 'lucide-svelte/icons/plus';
    import Users from 'lucide-svelte/icons/users';
    import { onMount } from 'svelte';
    import CreateTeamModal from '@/components/CreateTeamModal.svelte';
    import { Button } from '@/components/ui/button';
    import {
        DropdownMenu,
        DropdownMenuContent,
        DropdownMenuItem,
        DropdownMenuLabel,
        DropdownMenuSeparator,
        DropdownMenuTrigger,
    } from '@/components/ui/dropdown-menu';
    import type { Team } from '@/types';
    import { switchMethod } from '@/routes/teams';

    let { inHeader = false }: { inHeader?: boolean } = $props();
    let isMobile = $state(false);

    const currentTeam = $derived($page.props.currentTeam as Team | null);
    const teams = $derived(($page.props.teams ?? []) as Team[]);

    const callClickHandler = (handler: unknown, event: MouseEvent) => {
        if (typeof handler === 'function') {
            handler(event);
        }
    };

    onMount(() => {
        const query = window.matchMedia('(max-width: 767px)');
        const update = () => (isMobile = query.matches);

        update();
        query.addEventListener('change', update);

        return () => query.removeEventListener('change', update);
    });

    const switchTeam = (team: Team) => {
        const previousTeamSlug = currentTeam?.slug;

        router.post(
            switchMethod(team.slug).url,
            {},
            {
                onFinish: () => {
                    if (!previousTeamSlug || typeof window === 'undefined') {
                        router.reload();

                        return;
                    }

                    const currentUrl = `${window.location.pathname}${window.location.search}${window.location.hash}`;
                    const segment = `/${previousTeamSlug}`;

                    if (currentUrl.includes(segment)) {
                        router.visit(
                            currentUrl.replace(segment, `/${team.slug}`),
                            {
                                replace: true,
                            },
                        );

                        return;
                    }

                    router.reload();
                },
            },
        );
    };
</script>

<DropdownMenu>
    <DropdownMenuTrigger asChild>
        {#snippet children(props)}
            <Button
                variant="ghost"
                class={inHeader
                    ? 'h-8 gap-1 px-2'
                    : 'has-[>svg]:px-2 w-full justify-start px-2 data-[state=open]:bg-sidebar-accent data-[state=open]:text-sidebar-accent-foreground'}
                onclick={props.onclick}
                aria-expanded={props['aria-expanded']}
                data-state={props['data-state']}
            >
                <Users
                    class={inHeader
                        ? 'hidden'
                        : 'hidden size-4 shrink-0 group-data-[collapsible=icon]:block'}
                />
                <div
                    class={inHeader
                        ? 'grid flex-1 text-left text-sm leading-tight'
                        : 'grid flex-1 text-left text-sm leading-tight group-data-[collapsible=icon]:hidden'}
                >
                    <span
                        class={inHeader
                            ? 'max-w-[120px] truncate font-medium'
                            : 'truncate font-semibold'}
                    >
                        {currentTeam?.name ?? 'Select Team'}
                    </span>
                </div>
                <ChevronsUpDown
                    class={inHeader
                        ? 'size-4 opacity-50'
                        : 'ml-auto size-4 group-data-[collapsible=icon]:hidden'}
                />
            </Button>
        {/snippet}
    </DropdownMenuTrigger>
    <DropdownMenuContent
        class={inHeader
            ? 'w-56'
            : 'w-[--reka-dropdown-menu-trigger-width] min-w-56 rounded-lg'}
        side={inHeader ? undefined : isMobile ? 'bottom' : 'right'}
        align={inHeader ? 'end' : 'start'}
        sideOffset={inHeader ? undefined : 4}
    >
        <DropdownMenuLabel class="text-xs text-muted-foreground"
            >Teams</DropdownMenuLabel
        >

        {#each teams as team (team.id)}
            <DropdownMenuItem asChild>
                {#snippet children(props)}
                    <button
                        type="button"
                        class="{props.class} gap-2 {inHeader ? '' : 'p-2'}"
                        onclick={(event) => {
                            props.onClick?.(event);
                            switchTeam(team);
                        }}
                    >
                        {team.name}
                        {#if currentTeam?.id === team.id}
                            <Check
                                class={inHeader
                                    ? 'ml-auto size-4'
                                    : 'ml-auto h-4 w-4'}
                            />
                        {/if}
                    </button>
                {/snippet}
            </DropdownMenuItem>
        {/each}

        <DropdownMenuSeparator />

        <CreateTeamModal>
            {#snippet children(triggerProps)}
                <DropdownMenuItem asChild>
                    {#snippet children(props)}
                        <button
                            type="button"
                            class="{props.class} gap-2 {inHeader ? '' : 'p-2'}"
                            onclick={(event) => {
                                callClickHandler(triggerProps.onClick, event);
                            }}
                        >
                            <Plus class={inHeader ? 'size-4' : 'h-4 w-4'} />
                            <span class="text-muted-foreground"
                                >Create Team</span
                            >
                        </button>
                    {/snippet}
                </DropdownMenuItem>
            {/snippet}
        </CreateTeamModal>
    </DropdownMenuContent>
</DropdownMenu>
