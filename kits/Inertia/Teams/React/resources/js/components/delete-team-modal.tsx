import { router } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { destroy } from '@/routes/teams';
import type { Team } from '@/types';

type Props = {
    team: Team;
    isCurrentTeam: boolean;
    otherTeams: Team[];
    open: boolean;
    onOpenChange: (open: boolean) => void;
};

export default function DeleteTeamModal({
    team,
    isCurrentTeam,
    otherTeams,
    open,
    onOpenChange,
}: Props) {
    const [confirmationName, setConfirmationName] = useState('');
    const [newCurrentTeamId, setNewCurrentTeamId] = useState<number | null>(
        null,
    );

    const canDeleteTeam = useMemo(() => {
        const nameMatches = confirmationName === team.name;
        const hasNewTeamIfNeeded = !isCurrentTeam || newCurrentTeamId !== null;

        return nameMatches && hasNewTeamIfNeeded;
    }, [confirmationName, isCurrentTeam, newCurrentTeamId, team.name]);

    const resetDialog = () => {
        setConfirmationName('');
        setNewCurrentTeamId(null);
    };

    const handleOpenChange = (nextOpen: boolean) => {
        onOpenChange(nextOpen);

        if (!nextOpen) {
            resetDialog();
        }
    };

    const deleteTeam = () => {
        router.visit(destroy(team.slug), {
            data: {
                name: confirmationName,
                new_current_team_id: newCurrentTeamId,
            },
            onSuccess: () => handleOpenChange(false),
        });
    };

    return (
        <Dialog open={open} onOpenChange={handleOpenChange}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Are you sure?</DialogTitle>
                    <DialogDescription>
                        This action cannot be undone. This will permanently
                        delete the team <strong>"{team.name}"</strong> and
                        remove all of its members.
                    </DialogDescription>
                </DialogHeader>

                <div className="space-y-4 py-4">
                    <div className="grid gap-2">
                        <Label htmlFor="confirmation-name">
                            Type <strong>"{team.name}"</strong> to confirm
                        </Label>
                        <Input
                            id="confirmation-name"
                            value={confirmationName}
                            onChange={(event) =>
                                setConfirmationName(event.target.value)
                            }
                            placeholder="Enter team name"
                            autoComplete="off"
                        />
                    </div>

                    {isCurrentTeam && otherTeams.length > 0 ? (
                        <div className="grid gap-2">
                            <Label htmlFor="new-current-team">
                                Select a new current team
                            </Label>
                            <Select
                                value={newCurrentTeamId?.toString() ?? ''}
                                onValueChange={(value) =>
                                    setNewCurrentTeamId(Number(value))
                                }
                            >
                                <SelectTrigger className="w-full">
                                    <SelectValue placeholder="Select a team" />
                                </SelectTrigger>
                                <SelectContent>
                                    {otherTeams.map((otherTeam) => (
                                        <SelectItem
                                            key={otherTeam.id}
                                            value={otherTeam.id.toString()}
                                        >
                                            {otherTeam.name}
                                            {otherTeam.isPersonal ? (
                                                <span className="ml-2 text-muted-foreground">
                                                    (Personal)
                                                </span>
                                            ) : null}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <p className="text-sm text-muted-foreground">
                                You are deleting your current team. Please
                                select which team to switch to.
                            </p>
                        </div>
                    ) : null}

                    {isCurrentTeam && otherTeams.length === 0 ? (
                        <div className="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-800 dark:border-red-200/20 dark:bg-red-900/20 dark:text-red-200">
                            You cannot delete your current team because you have
                            no other teams to switch to. Please create or join
                            another team first.
                        </div>
                    ) : null}
                </div>

                <DialogFooter className="gap-2">
                    <DialogClose asChild>
                        <Button variant="secondary">Cancel</Button>
                    </DialogClose>

                    <Button
                        variant="destructive"
                        disabled={!canDeleteTeam}
                        onClick={deleteTeam}
                    >
                        Delete team
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
