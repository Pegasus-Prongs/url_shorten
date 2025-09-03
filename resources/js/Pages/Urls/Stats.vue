<script setup>
import { ref, onMounted, computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { useToast } from '@/Composables/useToast';
import {
    Chart as ChartJS,
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    Title,
    Tooltip,
    Legend,
} from 'chart.js';
import { Line } from 'vue-chartjs';

ChartJS.register(
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    Title,
    Tooltip,
    Legend
);

const props = defineProps({
    url: Object,
    clicks_over_time: Array,
    recent_clicks: Array,
});

const chartData = computed(() => {
    const labels = props.clicks_over_time.map(item => {
        const date = new Date(item.date);
        return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
    });
    
    const data = props.clicks_over_time.map(item => item.count);

    return {
        labels,
        datasets: [
            {
                label: 'Clicks per day',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                borderColor: 'rgba(59, 130, 246, 1)',
                borderWidth: 2,
                fill: true,
                tension: 0.4,
                data,
            },
        ],
    };
});

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            display: false,
        },
        title: {
            display: true,
            text: 'Clicks Over Time',
            font: {
                size: 16,
                weight: 'bold',
            },
        },
    },
    scales: {
        y: {
            beginAtZero: true,
            ticks: {
                stepSize: 1,
            },
        },
    },
};

const { success, error } = useToast();

const copyToClipboard = async (text) => {
    try {
        await navigator.clipboard.writeText(text);
        success('Copied to clipboard!');
    } catch (err) {
        console.error('Failed to copy: ', err);
        error('Failed to copy to clipboard');
    }
};

const totalClicks = computed(() => props.url.click_count);
const avgClicksPerDay = computed(() => {
    if (props.clicks_over_time.length === 0) return 0;
    const total = props.clicks_over_time.reduce((sum, item) => sum + item.count, 0);
    return Math.round((total / props.clicks_over_time.length) * 10) / 10;
});
</script>

<template>
    <Head :title="`Stats for ${url.title || 'Untitled'}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <Link
                        :href="route('urls.index')"
                        class="text-blue-600 hover:text-blue-500 text-sm mb-2 inline-block"
                    >
                        ← Back to URLs
                    </Link>
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                        URL Statistics
                    </h2>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <!-- URL Info Card -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">
                            {{ url.title || 'Untitled' }}
                        </h3>
                        
                        <div class="space-y-3">
                            <div class="flex items-center space-x-2">
                                <span class="text-sm text-gray-500 w-20">Short URL:</span>
                                <code class="text-sm bg-gray-100 px-2 py-1 rounded">{{ url.short_url }}</code>
                                <button
                                    @click="copyToClipboard(url.short_url)"
                                    class="text-blue-600 hover:text-blue-500 text-sm"
                                >
                                    Copy
                                </button>
                            </div>
                            
                            <div class="flex items-center space-x-2">
                                <span class="text-sm text-gray-500 w-20">Original:</span>
                                <a
                                    :href="url.original_url"
                                    target="_blank"
                                    class="text-sm text-blue-600 hover:text-blue-500 break-all"
                                >
                                    {{ url.original_url }}
                                </a>
                            </div>
                            
                            <div class="flex items-center space-x-2">
                                <span class="text-sm text-gray-500 w-20">Created:</span>
                                <span class="text-sm text-gray-900">{{ url.created_at }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Stats Overview -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div class="w-8 h-8 bg-blue-500 rounded-md flex items-center justify-center">
                                        <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M10 12a2 2 0 100-4 2 2 0 000 4z"/>
                                            <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"/>
                                        </svg>
                                    </div>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">Total Clicks</dt>
                                        <dd class="text-lg font-medium text-gray-900">{{ totalClicks }}</dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div class="w-8 h-8 bg-green-500 rounded-md flex items-center justify-center">
                                        <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M3 3a1 1 0 000 2v8a2 2 0 002 2h2.586l-1.293 1.293a1 1 0 101.414 1.414L10 15.414l2.293 2.293a1 1 0 001.414-1.414L12.414 15H15a2 2 0 002-2V5a1 1 0 100-2H3zm11.707 4.707a1 1 0 00-1.414-1.414L10 9.586 8.707 8.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                        </svg>
                                    </div>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">Avg. Daily Clicks</dt>
                                        <dd class="text-lg font-medium text-gray-900">{{ avgClicksPerDay }}</dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div class="w-8 h-8 bg-purple-500 rounded-md flex items-center justify-center">
                                        <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"/>
                                        </svg>
                                    </div>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">Days Tracked</dt>
                                        <dd class="text-lg font-medium text-gray-900">{{ clicks_over_time.length }}</dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Chart -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="h-64">
                            <Line
                                v-if="clicks_over_time.length > 0"
                                :data="chartData"
                                :options="chartOptions"
                            />
                            <div v-else class="flex items-center justify-center h-full text-gray-500">
                                <p>No click data available yet</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Clicks -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Recent Clicks</h3>
                        
                        <div v-if="recent_clicks.length === 0" class="text-center py-8 text-gray-500">
                            <p>No clicks recorded yet</p>
                        </div>
                        
                        <div v-else class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Time
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Location
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Device
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Referrer
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="click in recent_clicks" :key="click.id">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ click.created_at }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ click.country || 'Unknown' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ click.device_type || 'Unknown' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 max-w-xs truncate">
                                            {{ click.referer || 'Direct' }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
