<script setup lang="ts">
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import type { BreadcrumbItem, Team } from '@/types';
import { Form, Head, Link, router } from '@inertiajs/vue3';
import { Edit, Eye, Plus, Star } from 'lucide-vue-next';
import { ref } from 'vue';

type Props = {
    teams: Team[];
};

defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Teams',
        href: '/teams',
    },
];

const createDialogOpen = ref(false);

const switchTeam = (team: Team) => router.post(`/teams/${team.id}/switch`);
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Teams" />

        <h1 class="sr-only">Teams</h1>

        <SettingsLayout>
            <div class="flex flex-col space-y-6">
                <div class="flex items-center justify-between">
                    <Heading
                        variant="small"
                        title="Teams"
                        description="Manage your teams and team memberships"
                    />

                    <Dialog v-model:open="createDialogOpen">
                        <DialogTrigger as-child>
                            <Button>
                                <Plus class="mr-2 h-4 w-4" />
                                Create Team
                            </Button>
                        </DialogTrigger>
                        <DialogContent>
                            <Form
                                action="/teams"
                                method="post"
                                class="space-y-6"
                                v-slot="{ errors, processing }"
                                @success="createDialogOpen = false"
                            >
                                <DialogHeader>
                                    <DialogTitle>Create a new team</DialogTitle>
                                    <DialogDescription>
                                        Create a new team to collaborate with others.
                                    </DialogDescription>
                                </DialogHeader>

                                <div class="grid gap-2">
                                    <Label for="name">Team Name</Label>
                                    <Input
                                        id="name"
                                        name="name"
                                        placeholder="My Team"
                                        required
                                    />
                                    <InputError :message="errors.name" />
                                </div>

                                <DialogFooter class="gap-2">
                                    <DialogClose as-child>
                                        <Button variant="secondary">
                                            Cancel
                                        </Button>
                                    </DialogClose>

                                    <Button type="submit" :disabled="processing">
                                        Create Team
                                    </Button>
                                </DialogFooter>
                            </Form>
                        </DialogContent>
                    </Dialog>
                </div>

                <div class="space-y-3">
                    <div
                        v-for="team in teams"
                        :key="team.id"
                        class="flex items-center justify-between rounded-lg border p-4"
                    >
                        <div class="flex items-center gap-4">
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="font-medium">{{ team.name }}</span>
                                    <Badge v-if="team.is_current" variant="secondary">
                                        Current
                                    </Badge>
                                    <Badge v-if="team.is_personal" variant="outline">
                                        Personal
                                    </Badge>
                                </div>
                                <span class="text-sm text-muted-foreground">
                                    {{ team.role_label }}
                                </span>
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            <Button
                                v-if="!team.is_current"
                                variant="ghost"
                                size="sm"
                                @click="switchTeam(team)"
                                title="Set as current team"
                            >
                                <Star class="h-4 w-4" />
                            </Button>

                            <Button
                                v-if="team.role === 'owner' || team.role === 'admin' || team.role === 'member'"
                                variant="ghost"
                                size="sm"
                                as-child
                            >
                                <Link :href="`/teams/${team.slug}`" title="Edit team">
                                    <Edit class="h-4 w-4" />
                                </Link>
                            </Button>

                            <Button
                                v-else
                                variant="ghost"
                                size="sm"
                                as-child
                            >
                                <Link :href="`/teams/${team.slug}`" title="View team">
                                    <Eye class="h-4 w-4" />
                                </Link>
                            </Button>
                        </div>
                    </div>

                    <p v-if="teams.length === 0" class="text-center text-muted-foreground py-8">
                        You don't belong to any teams yet.
                    </p>
                </div>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
