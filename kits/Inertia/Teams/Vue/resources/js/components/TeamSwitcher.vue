<script setup lang="ts">
import { usePage, router } from '@inertiajs/vue3';
import { Check, ChevronsUpDown, Plus } from 'lucide-vue-next';
import { computed } from 'vue';
import CreateTeamModal from '@/components/CreateTeamModal.vue';
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

const page = usePage();
const { isMobile } = useSidebar();

const currentTeam = computed(() => page.props.currentTeam);
const teams = computed(() => page.props.teams ?? []);

const switchTeam = (team: Team) => router.post(`/teams/${team.slug}/switch`);
</script>

<template>
    <SidebarMenu>
        <SidebarMenuItem>
            <DropdownMenu>
                <DropdownMenuTrigger as-child>
                    <SidebarMenuButton
                        size="lg"
                        class="data-[state=open]:bg-sidebar-accent data-[state=open]:text-sidebar-accent-foreground"
                    >
                        <div
                            class="grid flex-1 text-left text-sm leading-tight"
                        >
                            <span class="truncate font-semibold">{{
                                currentTeam?.name ?? 'Select Team'
                            }}</span>
                        </div>
                        <ChevronsUpDown class="ml-auto" />
                    </SidebarMenuButton>
                </DropdownMenuTrigger>
                <DropdownMenuContent
                    class="w-[--reka-dropdown-menu-trigger-width] min-w-56 rounded-lg"
                    :side="isMobile ? 'bottom' : 'right'"
                    align="start"
                    :side-offset="4"
                >
                    <DropdownMenuLabel class="text-xs text-muted-foreground">
                        Teams
                    </DropdownMenuLabel>
                    <DropdownMenuItem
                        v-for="team in teams"
                        :key="team.id"
                        class="cursor-pointer gap-2 p-2"
                        @click="switchTeam(team)"
                    >
                        {{ team.name }}
                        <Check
                            v-if="currentTeam?.id === team.id"
                            class="ml-auto h-4 w-4"
                        />
                    </DropdownMenuItem>
                    <DropdownMenuSeparator />
                    <CreateTeamModal>
                        <DropdownMenuItem
                            class="cursor-pointer gap-2 p-2"
                            @select.prevent
                        >
                            <Plus class="h-4 w-4" />
                            <span class="text-muted-foreground"
                                >Create Team</span
                            >
                        </DropdownMenuItem>
                    </CreateTeamModal>
                </DropdownMenuContent>
            </DropdownMenu>
        </SidebarMenuItem>
    </SidebarMenu>
</template>
