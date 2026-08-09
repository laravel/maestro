<script lang="ts">
    let {
        message = '',
        class: className = '',
    }: {
        message?: string | string[];
        class?: string;
    } = $props();
</script>

{#if Array.isArray(message) ? message.length : message}
    <div class={className}>
        {#each Array.isArray(message) ? message : [message] as msg (msg)}
            {#if msg}
                <p class="text-sm text-red-600 dark:text-red-500">{msg}</p>
            {/if}
        {/each}
    </div>
{/if}
