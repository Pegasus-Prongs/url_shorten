<script setup>
import { ref, computed } from 'vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import { useToast } from '@/Composables/useToast';

const props = defineProps({
    urls: Array,
});

// Form for creating new URLs
const form = useForm({
    original_url: '',
    title: '',
    custom_code: '',
});

const showAdvanced = ref(false);
const { success, error } = useToast();
const page = usePage();

const submit = () => {
    form.post(route('urls.store'), {
        onSuccess: () => {
            form.reset();
            showAdvanced.value = false;
            success('URL shortened successfully!');
        },
        onError: () => {
            error('Failed to shorten URL. Please check your input and try again.');
        },
    });
};

const copyToClipboard = async (text) => {
    try {
        await navigator.clipboard.writeText(text);
        success('Copied to clipboard!');
    } catch (err) {
        console.error('Failed to copy: ', err);
        error('Failed to copy to clipboard');
    }
};

const deleteUrl = (url) => {
    if (confirm('Are you sure you want to delete this URL?')) {
        form.delete(route('urls.destroy', url.id), {
            onSuccess: () => {
                success('URL deleted successfully!');
            },
            onError: () => {
                error('Failed to delete URL. Please try again.');
            },
        });
    }
};
</script>

<template>
    <Head title="My URLs" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                My URLs
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- URL Creation Form -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6 text-gray-900">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">
                            Shorten a new URL
                        </h3>
                        
                        <form @submit.prevent="submit" class="space-y-4">
                            <div>
                                <InputLabel for="original_url" value="Long URL" />
                                <TextInput
                                    id="original_url"
                                    v-model="form.original_url"
                                    type="url"
                                    class="mt-1 block w-full"
                                    placeholder="https://example.com/very/long/url"
                                    required
                                />
                                <InputError class="mt-2" :message="form.errors.original_url" />
                            </div>

                            <!-- Advanced Options Toggle -->
                            <div>
                                <button
                                    type="button"
                                    @click="showAdvanced = !showAdvanced"
                                    class="text-sm text-blue-600 hover:text-blue-500"
                                >
                                    {{ showAdvanced ? 'Hide' : 'Show' }} advanced options
                                </button>
                            </div>

                            <!-- Advanced Options -->
                            <div v-if="showAdvanced" class="space-y-4 border-t pt-4">
                                <div>
                                    <InputLabel for="title" value="Title (optional)" />
                                    <TextInput
                                        id="title"
                                        v-model="form.title"
                                        type="text"
                                        class="mt-1 block w-full"
                                        placeholder="Custom title for your link"
                                    />
                                    <InputError class="mt-2" :message="form.errors.title" />
                                </div>

                                <div>
                                    <InputLabel for="custom_code" value="Custom short code (optional)" />
                                    <TextInput
                                        id="custom_code"
                                        v-model="form.custom_code"
                                        type="text"
                                        class="mt-1 block w-full"
                                        placeholder="mycustomcode"
                                    />
                                    <InputError class="mt-2" :message="form.errors.custom_code" />
                                    <p class="mt-1 text-sm text-gray-500">
                                        Leave empty for auto-generated code. Must be 3-20 characters, letters and numbers only.
                                    </p>
                                </div>
                            </div>

                            <div class="flex items-center justify-end">
                                <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                                    Shorten URL
                                </PrimaryButton>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- URLs List -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">
                            Your shortened URLs
                        </h3>

                        <div v-if="urls.length === 0" class="text-center py-8 text-gray-500">
                            <p>No URLs yet. Create your first shortened URL above!</p>
                        </div>

                        <div v-else class="space-y-4">
                            <div
                                v-for="url in urls"
                                :key="url.id"
                                class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50"
                            >
                                <div class="flex items-start justify-between">
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center space-x-2 mb-2">
                                            <h4 class="text-sm font-medium text-gray-900 truncate">
                                                {{ url.title || 'Untitled' }}
                                            </h4>
                                            <span
                                                v-if="!url.is_active"
                                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800"
                                            >
                                                Inactive
                                            </span>
                                        </div>
                                        
                                        <div class="space-y-1">
                                            <div class="flex items-center space-x-2">
                                                <span class="text-sm text-gray-500">Short URL:</span>
                                                <code class="text-sm bg-gray-100 px-2 py-1 rounded">{{ url.short_url }}</code>
                                                <button
                                                    @click="copyToClipboard(url.short_url)"
                                                    class="text-blue-600 hover:text-blue-500 text-sm"
                                                >
                                                    Copy
                                                </button>
                                            </div>
                                            
                                            <div class="flex items-center space-x-2">
                                                <span class="text-sm text-gray-500">Original:</span>
                                                <a
                                                    :href="url.original_url"
                                                    target="_blank"
                                                    class="text-sm text-blue-600 hover:text-blue-500 truncate max-w-md"
                                                >
                                                    {{ url.original_url }}
                                                </a>
                                            </div>
                                        </div>

                                        <div class="flex items-center space-x-4 mt-2 text-sm text-gray-500">
                                            <span>{{ url.click_count }} clicks</span>
                                            <span>Created {{ url.created_at }}</span>
                                            <span v-if="url.last_clicked_at">Last clicked {{ url.last_clicked_at }}</span>
                                        </div>
                                    </div>

                                    <div class="flex items-center space-x-2 ml-4">
                                        <button
                                            @click="$inertia.get(route('urls.stats', url.id))"
                                            class="text-blue-600 hover:text-blue-500 text-sm"
                                        >
                                            Stats
                                        </button>
                                        <button
                                            @click="deleteUrl(url)"
                                            class="text-red-600 hover:text-red-500 text-sm"
                                        >
                                            Delete
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
