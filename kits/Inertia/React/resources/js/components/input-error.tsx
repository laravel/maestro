import type { HTMLAttributes } from 'react';
import { cn } from '@/lib/utils';

export default function InputError({
    message,
    className = '',
    ...props
}: HTMLAttributes<HTMLParagraphElement> & { message?: string | string[] }) {
    const messages = Array.isArray(message) ? message : message ? [message] : [];

    return messages.length ? (
        <p
            {...props}
            className={cn('text-sm text-red-600 dark:text-red-400', className)}
        >
            {messages.map((message, index) => (
                <span key={index} className="block">
                    {message}
                </span>
            ))}
        </p>
    ) : null;
}
