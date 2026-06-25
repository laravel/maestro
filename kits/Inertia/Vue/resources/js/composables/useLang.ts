import { usePage } from '@inertiajs/vue3';

type Replacements = Record<string, string | number>;

export function useLang() {
    const lang = (usePage().props.lang ?? {}) as Record<string, string>;

    function __(key: string, replace: Replacements = {}): string {
        let line = lang[key] ?? key;

        for (const [k, v] of Object.entries(replace)) {
            line = line.replaceAll(`:${k}`, String(v));
        }

        return line;
    }

    return { __ };
}
