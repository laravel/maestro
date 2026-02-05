<?php

namespace App\Enums;

enum StarterKit: string
{
    case Livewire = 'Livewire';
    case React = 'React';
    case Svelte = 'Svelte';
    case Vue = 'Vue';

    /**
     * Get all starter kit values as an array.
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
