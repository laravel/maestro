<script setup lang="ts">
defineProps<{
    message?: string | string[];
}>();
</script>

<template>
    <div v-show="message?.length">
        <p
            v-for="(msg, index) in Array.isArray(message) ? message : [message]"
            :key="index"
            v-show="msg"
            class="text-sm text-red-600 dark:text-red-500"
        >
            {{ msg }}
        </p>
    </div>
</template>
