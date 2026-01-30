export type TeamRole = 'owner' | 'admin' | 'member' | 'viewer';

export type TeamOption = {
    id: number;
    name: string;
    slug: string;
    is_personal: boolean;
};

export type Team = {
    id: number;
    name: string;
    slug: string;
    is_personal: boolean;
    role?: TeamRole;
    role_label?: string;
    is_current?: boolean;
};

export type TeamMember = {
    id: number;
    name: string;
    email: string;
    avatar?: string | null;
    role: TeamRole;
    role_label: string;
};

export type TeamInvitation = {
    code: string;
    email: string;
    role: TeamRole;
    role_label: string;
    created_at: string;
};

export type TeamPermissions = {
    canUpdateTeam: boolean;
    canDeleteTeam: boolean;
    canAddMember: boolean;
    canUpdateMember: boolean;
    canRemoveMember: boolean;
    canCreateInvitation: boolean;
    canCancelInvitation: boolean;
};

export type RoleOption = {
    value: TeamRole;
    label: string;
};
