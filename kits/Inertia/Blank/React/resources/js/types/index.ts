export type * from './auth';

import type { Auth } from './auth';

export type SharedData = {
    name: string;
    auth: Auth;
    [key: string]: unknown;
};
