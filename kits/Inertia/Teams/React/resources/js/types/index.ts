export type * from './auth';
export type * from './navigation';
export type * from './teams';
export type * from './ui';

import type { Auth } from './auth';
import type { Team } from './teams';

export type SharedData = {
    name: string;
    auth: Auth;
    sidebarOpen: boolean;
    currentTeam: Team | null;
    teams: Team[];
    [key: string]: unknown;
};
