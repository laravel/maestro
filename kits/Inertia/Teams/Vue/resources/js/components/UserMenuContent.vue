<script setup lang="ts">
import {
    DropdownMenuGroup,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuPortal,
    DropdownMenuSeparator,
    DropdownMenuSub,
    DropdownMenuSubContent,
    DropdownMenuSubTrigger,
} from '@/components/ui/dropdown-menu';
import UserInfo from '@/components/UserInfo.vue';
import { logout } from '@/routes';
import { edit } from '@/routes/profile';
import type { Team, User } from '@/types';
import { Link, router } from '@inertiajs/vue3';
import { Check, ChevronsUpDown, LogOut, Settings } from 'lucide-vue-next';

type Props = {
    user: User;
    teams?: Team[];
    currentTeam?: Team | null;
};

const props = withDefaults(defineProps<Props>(), {
    teams: () => [],
    currentTeam: null,
});

const handleLogout = () => router.flushAll();

const switchTeam = (team: Team) => router.post(`/teams/${team.id}/switch`);
</script>

<template>
    <DropdownMenuLabel class="p-0 font-normal">
        <div class="flex items-center gap-2 px-1 py-1.5 text-left text-sm">
            <UserInfo :user="user" :show-email="true" />
        </div>
    </DropdownMenuLabel>
    <DropdownMenuSeparator />

    <DropdownMenuGroup v-if="teams.length > 0">
        <DropdownMenuSub>
            <DropdownMenuSubTrigger>
                <ChevronsUpDown class="mr-2 h-4 w-4" />
                Switch Team
            </DropdownMenuSubTrigger>
            <DropdownMenuPortal>
                <DropdownMenuSubContent>
                    <DropdownMenuItem
                        v-for="team in teams"
                        :key="team.id"
                        class="cursor-pointer"
                        @click="switchTeam(team)"
                    >
                        <Check
                            class="mr-2 h-4 w-4"
                            :class="{ 'opacity-0': currentTeam?.id !== team.id }"
                        />
                        {{ team.name }}
                    </DropdownMenuItem>
                </DropdownMenuSubContent>
            </DropdownMenuPortal>
        </DropdownMenuSub>
        <DropdownMenuSeparator />
    </DropdownMenuGroup>

    <DropdownMenuGroup>
        <DropdownMenuItem :as-child="true">
            <Link class="block w-full cursor-pointer" :href="edit()" prefetch>
                <Settings class="mr-2 h-4 w-4" />
                Settings
            </Link>
        </DropdownMenuItem>
    </DropdownMenuGroup>
    <DropdownMenuSeparator />
    <DropdownMenuItem :as-child="true">
        <Link
            class="block w-full cursor-pointer"
            :href="logout()"
            @click="handleLogout"
            as="button"
            data-test="logout-button"
        >
            <LogOut class="mr-2 h-4 w-4" />
            Log out
        </Link>
    </DropdownMenuItem>
</template>
