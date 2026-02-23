import { Head, Link, router } from '@inertiajs/react';
import { CheckCircle, Circle, Eye, Pencil, Plus } from 'lucide-react';
import CreateTeamModal from '@/components/create-team-modal';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import type { BreadcrumbItem, Team } from '@/types';
import { edit, index, switchMethod } from '@/routes/teams';

type Props = {
    teams: Team[];
};

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Teams',
        href: index().url,
    },
];

const switchTeam = (team: Team) => router.post(switchMethod(team.slug).url);

export default function TeamsIndex({ teams }: Props) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Teams" />

            <h1 className="sr-only">Teams</h1>

            <SettingsLayout>
                <div className="flex flex-col space-y-6">
                    <div className="flex items-center justify-between">
                        <Heading
                            variant="small"
                            title="Teams"
                            description="Manage your teams and team memberships"
                        />

                        <CreateTeamModal>
                            <Button>
                                <Plus /> Create Team
                            </Button>
                        </CreateTeamModal>
                    </div>

                    <div className="space-y-3">
                        {teams.map((team) => (
                            <div
                                key={team.id}
                                className={`flex items-center justify-between rounded-lg border p-4 ${team.is_current ? 'border-ring' : ''}`}
                            >
                                <div className="flex items-center gap-4">
                                    <div>
                                        <div className="flex items-center gap-2">
                                            <span className="font-medium">
                                                {team.name}
                                            </span>
                                            {team.is_personal ? (
                                                <Badge variant="secondary">
                                                    Personal
                                                </Badge>
                                            ) : null}
                                        </div>
                                        <span className="text-sm text-muted-foreground">
                                            {team.role_label}
                                        </span>
                                    </div>
                                </div>

                                <TooltipProvider>
                                    <div className="flex items-center gap-2">
                                        {team.is_current ? (
                                            <Tooltip>
                                                <TooltipTrigger asChild>
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                    >
                                                        <CheckCircle className="h-4 w-4" />
                                                    </Button>
                                                </TooltipTrigger>
                                                <TooltipContent>
                                                    <p>Current team</p>
                                                </TooltipContent>
                                            </Tooltip>
                                        ) : (
                                            <Tooltip>
                                                <TooltipTrigger asChild>
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        onClick={() =>
                                                            switchTeam(team)
                                                        }
                                                    >
                                                        <Circle className="h-4 w-4 text-muted-foreground" />
                                                    </Button>
                                                </TooltipTrigger>
                                                <TooltipContent>
                                                    <p>Set as current team</p>
                                                </TooltipContent>
                                            </Tooltip>
                                        )}

                                        {team.role === 'member' ? (
                                            <Tooltip>
                                                <TooltipTrigger asChild>
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        asChild
                                                    >
                                                        <Link
                                                            href={
                                                                edit(team.slug)
                                                                    .url
                                                            }
                                                        >
                                                            <Eye className="h-4 w-4" />
                                                        </Link>
                                                    </Button>
                                                </TooltipTrigger>
                                                <TooltipContent>
                                                    <p>View team</p>
                                                </TooltipContent>
                                            </Tooltip>
                                        ) : (
                                            <Tooltip>
                                                <TooltipTrigger asChild>
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        asChild
                                                    >
                                                        <Link
                                                            href={
                                                                edit(team.slug)
                                                                    .url
                                                            }
                                                        >
                                                            <Pencil className="h-4 w-4" />
                                                        </Link>
                                                    </Button>
                                                </TooltipTrigger>
                                                <TooltipContent>
                                                    <p>Edit team</p>
                                                </TooltipContent>
                                            </Tooltip>
                                        )}
                                    </div>
                                </TooltipProvider>
                            </div>
                        ))}

                        {teams.length === 0 ? (
                            <p className="py-8 text-center text-muted-foreground">
                                You don't belong to any teams yet.
                            </p>
                        ) : null}
                    </div>
                </div>
            </SettingsLayout>
        </AppLayout>
    );
}
