export * from './auth';
export * from './navigation';
export * from './teams';
export * from './ui';

import type { Auth } from './auth';
import type { Team } from './teams';

export type SharedData = {
    name: string;
    auth: Auth;
    sidebarOpen: boolean;
    currentTeam: Team | null;
    teams: Team[];
};

export type AppPageProps<
    T extends Record<string, unknown> = Record<string, unknown>,
> = T & SharedData & {
    [key: string]: unknown;
};
