<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    href: {
        type: String,
        required: true,
    },
});
</script>

<template>
    <Link
        :href="href"
        class="block w-full px-4 py-3 text-start text-sm font-medium leading-5 text-gray-700 transition duration-200 ease-in-out hover:bg-primary-50 hover:text-primary-700 focus:bg-primary-50 focus:text-primary-700 focus:outline-none rounded-md mx-1"
    >
        <slot />
    </Link>
</template>
