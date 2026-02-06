export type TooltipContext = {
    open: () => boolean;
    setOpen: (value: boolean) => void;
};

export const TOOLTIP_CONTEXT = Symbol('tooltip');
