import { router, usePage } from '@inertiajs/react';
import { Check, ChevronsUpDown, Plus } from 'lucide-react';
import CreateTeamModal from '@/components/create-team-modal';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    useSidebar,
} from '@/components/ui/sidebar';
import type { Team } from '@/types';

export function TeamSwitcher() {
    const page = usePage();
    const { isMobile } = useSidebar();
    const currentTeam = page.props.currentTeam;
    const teams = page.props.teams ?? [];

    const switchTeam = (team: Team) =>
        router.post(`/teams/${team.slug}/switch`);

    return (
        <SidebarMenu>
            <SidebarMenuItem>
                <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                        <SidebarMenuButton
                            size="lg"
                            className="data-[state=open]:bg-sidebar-accent data-[state=open]:text-sidebar-accent-foreground"
                        >
                            <div className="grid flex-1 text-left text-sm leading-tight">
                                <span className="truncate font-semibold">
                                    {currentTeam?.name ?? 'Select Team'}
                                </span>
                            </div>
                            <ChevronsUpDown className="ml-auto" />
                        </SidebarMenuButton>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent
                        className="w-(--radix-dropdown-menu-trigger-width) min-w-56 rounded-lg"
                        side={isMobile ? 'bottom' : 'right'}
                        align="start"
                        sideOffset={4}
                    >
                        <DropdownMenuLabel className="text-xs text-muted-foreground">
                            Teams
                        </DropdownMenuLabel>
                        {teams.map((team) => (
                            <DropdownMenuItem
                                key={team.id}
                                className="cursor-pointer gap-2 p-2"
                                onSelect={() => switchTeam(team)}
                            >
                                {team.name}
                                {currentTeam?.id === team.id && (
                                    <Check className="ml-auto h-4 w-4" />
                                )}
                            </DropdownMenuItem>
                        ))}
                        <DropdownMenuSeparator />
                        <CreateTeamModal>
                            <DropdownMenuItem
                                className="cursor-pointer gap-2 p-2"
                                onSelect={(event) => event.preventDefault()}
                            >
                                <Plus className="h-4 w-4" />
                                <span className="text-muted-foreground">
                                    Create Team
                                </span>
                            </DropdownMenuItem>
                        </CreateTeamModal>
                    </DropdownMenuContent>
                </DropdownMenu>
            </SidebarMenuItem>
        </SidebarMenu>
    );
}
