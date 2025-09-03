<script setup>
import { useToast } from '@/Composables/useToast';
import Toast from '@/Components/Toast.vue';

const { toasts, removeToast } = useToast();
</script>

<template>
    <div class="fixed top-4 right-4 z-50 space-y-2">
        <Toast
            v-for="toast in toasts"
            :key="toast.id"
            :show="toast.show"
            :type="toast.type"
            :message="toast.message"
            :duration="toast.duration"
            @close="removeToast(toast.id)"
        />
    </div>
</template>
