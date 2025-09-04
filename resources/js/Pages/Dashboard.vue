<script setup>
import { ref, computed, onMounted } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { useToast } from '@/Composables/useToast';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import {
    Chart as ChartJS,
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    Title,
    Tooltip,
    Legend,
    Filler,
} from 'chart.js';
import { Line } from 'vue-chartjs';

ChartJS.register(
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    Title,
    Tooltip,
    Legend,
    Filler
);

const props = defineProps({
    metrics: Object,
    recent_urls: Array,
    analytics_data: Array,
});

const { success, error } = useToast();

// Reactive data for animations
const animatedMetrics = ref({
    total_urls: 0,
    total_clicks: 0,
    click_through_rate: 0,
    active_urls: 0,
});

// Safe getter for metrics with defaults and NaN protection
const safeMetrics = computed(() => {
    const safeNumber = (value, defaultValue = 0) => {
        const num = Number(value);
        return Number.isFinite(num) ? num : defaultValue;
    };

    return {
        total_urls: safeNumber(props.metrics?.total_urls),
        total_clicks: safeNumber(props.metrics?.total_clicks),
        click_through_rate: safeNumber(props.metrics?.click_through_rate),
        active_urls: safeNumber(props.metrics?.active_urls),
    };
});

const isAnimating = ref(false);
const isChartLoading = ref(true);
const showQuickForm = ref(false);
const isPageLoading = ref(true);

// Table sorting and filtering
const sortField = ref('created_at');
const sortDirection = ref('desc');
const searchQuery = ref('');

// Quick URL creation form
const quickForm = useForm({
    original_url: '',
    title: '',
});

// Enhanced animation function for counters with easing
const animateCounter = (target, key, duration = 1500) => {
    // Ensure target is a valid finite number
    const safeTarget = Number.isFinite(target) ? target : 0;

    // If target is not valid, just set the value directly
    if (!Number.isFinite(target)) {
        animatedMetrics.value[key] = 0;
        return;
    }

    const startTime = Date.now();
    const startValue = Number.isFinite(animatedMetrics.value[key]) ? animatedMetrics.value[key] : 0;

    const animate = () => {
        const elapsed = Date.now() - startTime;
        const progress = Math.min(elapsed / duration, 1);

        // Easing function for smooth animation (ease-out)
        const easeOut = 1 - Math.pow(1 - progress, 3);

        const currentValue = startValue + (safeTarget - startValue) * easeOut;

        // Handle decimal values for click_through_rate with NaN protection
        let newValue;
        if (key === 'click_through_rate') {
            newValue = Math.round(currentValue * 100) / 100;
        } else {
            newValue = Math.floor(currentValue);
        }

        // Final safety check
        animatedMetrics.value[key] = Number.isFinite(newValue) ? newValue : 0;

        if (progress < 1) {
            requestAnimationFrame(animate);
        } else {
            animatedMetrics.value[key] = safeTarget;
            if (key === 'active_urls') {
                isAnimating.value = false;
            }
        }
    };

    requestAnimationFrame(animate);
};

// Start animations on mount with staggered timing
onMounted(() => {
    // Only start animations if we have valid metrics
    if (!props.metrics) {
        console.warn('Dashboard: No metrics provided');
        return;
    }

    isAnimating.value = true;
    isChartLoading.value = true;
    isPageLoading.value = true;

    // Stagger animations for better visual effect (all 1 second duration)
    setTimeout(() => animateCounter(safeMetrics.value.total_urls, 'total_urls', 1000), 200);
    setTimeout(() => animateCounter(safeMetrics.value.total_clicks, 'total_clicks', 1000), 400);
    setTimeout(() => animateCounter(safeMetrics.value.click_through_rate, 'click_through_rate', 1000), 600);
    setTimeout(() => animateCounter(safeMetrics.value.active_urls, 'active_urls', 1000), 800);

    // Handle chart loading state
    if (!chartData.value) {
        isChartLoading.value = false;
    }

    // Page loading complete after animations
    setTimeout(() => {
        isPageLoading.value = false;
    }, 2000);
});

// Computed properties for formatted display with comprehensive NaN protection
const formattedMetrics = computed(() => {
    const safeValue = (value, defaultValue = 0) => {
        const num = Number(value);
        return Number.isFinite(num) ? num : defaultValue;
    };

    // If no metrics are available, show loading state
    if (!props.metrics) {
        return {
            total_urls: '0',
            total_clicks: '0',
            click_through_rate: '0.0',
            active_urls: '0',
        };
    }

    const totalUrls = safeValue(animatedMetrics.value.total_urls);
    const totalClicks = safeValue(animatedMetrics.value.total_clicks);
    const clickThroughRate = safeValue(animatedMetrics.value.click_through_rate);
    const activeUrls = safeValue(animatedMetrics.value.active_urls);

    return {
        total_urls: totalUrls.toLocaleString(),
        total_clicks: totalClicks.toLocaleString(),
        click_through_rate: clickThroughRate.toFixed(1),
        active_urls: activeUrls.toLocaleString(),
    };
});

// URL management functions
const copyToClipboard = async (url) => {
    try {
        await navigator.clipboard.writeText(url.short_url);
        success('Short URL copied to clipboard!');
    } catch (err) {
        console.error('Failed to copy: ', err);
        error('Failed to copy to clipboard');
    }
};

const deleteUrl = (url) => {
    if (confirm(`Are you sure you want to delete "${url.title || 'Untitled'}"?`)) {
        router.delete(route('urls.destroy', url.id), {
            onSuccess: () => {
                success('URL deleted successfully!');
            },
            onError: () => {
                error('Failed to delete URL. Please try again.');
            },
        });
    }
};

// Quick form functions
const submitQuickForm = () => {
    quickForm.post(route('urls.store'), {
        onSuccess: () => {
            quickForm.reset();
            showQuickForm.value = false;
            success('URL shortened successfully!');
        },
        onError: () => {
            error('Failed to shorten URL. Please check your input and try again.');
        },
    });
};

const toggleQuickForm = () => {
    showQuickForm.value = !showQuickForm.value;
    if (!showQuickForm.value) {
        quickForm.reset();
        quickForm.clearErrors();
    }
};

// Sorting and filtering functionality
const sortedAndFilteredUrls = computed(() => {
    let urls = [...props.recent_urls];

    // Apply search filter
    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        urls = urls.filter(url =>
            (url.title && url.title.toLowerCase().includes(query)) ||
            url.original_url.toLowerCase().includes(query) ||
            url.short_code.toLowerCase().includes(query)
        );
    }

    // Apply sorting
    urls.sort((a, b) => {
        let aValue = a[sortField.value];
        let bValue = b[sortField.value];

        // Handle different data types
        if (sortField.value === 'click_count') {
            aValue = parseInt(aValue) || 0;
            bValue = parseInt(bValue) || 0;
        } else if (sortField.value === 'created_at') {
            aValue = new Date(aValue);
            bValue = new Date(bValue);
        } else {
            aValue = String(aValue || '').toLowerCase();
            bValue = String(bValue || '').toLowerCase();
        }

        if (aValue < bValue) return sortDirection.value === 'asc' ? -1 : 1;
        if (aValue > bValue) return sortDirection.value === 'asc' ? 1 : -1;
        return 0;
    });

    return urls;
});

const sortBy = (field) => {
    if (sortField.value === field) {
        sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortField.value = field;
        sortDirection.value = 'asc';
    }
};

const getSortIcon = (field) => {
    if (sortField.value !== field) return 'sort';
    return sortDirection.value === 'asc' ? 'sort-up' : 'sort-down';
};

// Chart data and configuration
const chartData = computed(() => {
    if (!props.analytics_data || props.analytics_data.length === 0) {
        return null;
    }

    const labels = props.analytics_data.map(item => item.formatted_date);
    const data = props.analytics_data.map(item => item.count);

    // Calculate trend data for better visualization
    const maxValue = Math.max(...data);
    const totalClicks = data.reduce((sum, value) => sum + value, 0);
    const averageClicks = totalClicks / data.length;

    return {
        labels,
        datasets: [
            {
                label: 'Daily Clicks',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                borderColor: 'rgba(59, 130, 246, 1)',
                borderWidth: 2,
                fill: true,
                tension: 0.4,
                pointBackgroundColor: 'rgba(59, 130, 246, 1)',
                pointBorderColor: '#ffffff',
                pointBorderWidth: 2,
                pointRadius: 4,
                pointHoverRadius: 6,
                data,
            },
            // Add average line for context
            {
                label: 'Average',
                backgroundColor: 'transparent',
                borderColor: 'rgba(156, 163, 175, 0.5)',
                borderWidth: 1,
                borderDash: [5, 5],
                fill: false,
                pointRadius: 0,
                pointHoverRadius: 0,
                data: new Array(data.length).fill(averageClicks),
            },
        ],
        meta: {
            totalClicks,
            averageClicks: Math.round(averageClicks * 10) / 10,
            maxValue,
            trend: data.length > 1 ? (data[data.length - 1] - data[0]) : 0,
        },
    };
});

const chartOptions = computed(() => ({
    responsive: true,
    maintainAspectRatio: false,
    animation: {
        duration: 1000,
        easing: 'easeInOutQuart',
        onComplete: () => {
            isChartLoading.value = false;
        },
    },
    plugins: {
        legend: {
            display: false,
        },
        title: {
            display: false,
        },
        tooltip: {
            backgroundColor: 'rgba(0, 0, 0, 0.8)',
            titleColor: '#ffffff',
            bodyColor: '#ffffff',
            borderColor: 'rgba(59, 130, 246, 1)',
            borderWidth: 1,
            cornerRadius: 8,
            displayColors: false,
            callbacks: {
                title: function(context) {
                    return `${context[0].label}`;
                },
                label: function(context) {
                    if (context.datasetIndex === 1) return null; // Hide average line tooltip
                    return `${context.parsed.y} clicks`;
                }
            }
        },
    },
    scales: {
        x: {
            grid: {
                display: false,
            },
            ticks: {
                color: '#6B7280',
                font: {
                    size: window.innerWidth < 640 ? 10 : 12,
                },
                maxTicksLimit: window.innerWidth < 640 ? 6 : 10,
            },
        },
        y: {
            beginAtZero: true,
            grid: {
                color: 'rgba(107, 114, 128, 0.1)',
            },
            ticks: {
                color: '#6B7280',
                font: {
                    size: window.innerWidth < 640 ? 10 : 12,
                },
                stepSize: 1,
                maxTicksLimit: window.innerWidth < 640 ? 5 : 8,
            },
        },
    },
    interaction: {
        intersect: false,
        mode: 'index',
    },
    elements: {
        point: {
            radius: window.innerWidth < 640 ? 2 : 4,
            hoverRadius: window.innerWidth < 640 ? 4 : 6,
        },
        line: {
            borderWidth: window.innerWidth < 640 ? 1.5 : 2,
        },
    },
}));
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center justify-between space-y-2 sm:space-y-0">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">
                        Dashboard
                    </h2>
                    <p class="text-gray-600 mt-1">
                        Welcome back! Here's your URL shortening overview.
                    </p>
                </div>
                <div class="flex items-center space-x-3">
                    <div class="hidden sm:flex items-center space-x-2 text-sm text-gray-500">
                        <div class="w-2 h-2 bg-green-400 rounded-full animate-pulse"></div>
                        <span>Live data</span>
                    </div>
                </div>
            </div>
        </template>

        <div class="py-6 sm:py-8 bg-gradient-to-br from-primary-50/30 via-white to-secondary-50/30 min-h-screen">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">


                <!-- Key Metrics Grid -->
                <div class="mb-6 sm:mb-8 grid grid-cols-1 gap-4 sm:gap-6 sm:grid-cols-2 lg:grid-cols-4">
                    <!-- Total URLs Card -->
                    <div class="card-modern group cursor-pointer animate-float">
                        <div class="p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div class="w-12 h-12 bg-gradient-button rounded-xl flex items-center justify-center shadow-gradient transition-all duration-300 group-hover:shadow-gradient-lg group-hover:scale-110 transform" aria-hidden="true">
                                        <svg class="w-6 h-6 text-white transition-transform duration-300 group-hover:rotate-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path>
                                        </svg>
                                    </div>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate group-hover:text-gray-600 transition-colors duration-300">Total URLs</dt>
                                        <dd class="text-3xl font-bold text-gray-900 transition-all duration-300 group-hover:text-gradient" :class="{ 'animate-pulse': isAnimating }" aria-live="polite">
                                            {{ formattedMetrics.total_urls }}
                                        </dd>
                                        <div class="text-xs text-gray-400 mt-1">
                                            <span class="inline-flex items-center">
                                                <svg class="w-3 h-3 mr-1 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M3.293 9.707a1 1 0 010-1.414l6-6a1 1 0 011.414 0l6 6a1 1 0 01-1.414 1.414L11 5.414V17a1 1 0 11-2 0V5.414L4.707 9.707a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                                                </svg>
                                                All time
                                            </span>
                                        </div>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Total Clicks Card -->
                    <div class="card-modern group cursor-pointer animate-float-delayed">
                        <div class="p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div class="w-12 h-12 bg-gradient-secondary rounded-xl flex items-center justify-center shadow-gradient transition-all duration-300 group-hover:shadow-gradient-lg group-hover:scale-110 transform">
                                        <svg class="w-6 h-6 text-gray-700 transition-transform duration-300 group-hover:scale-110" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M10 12a2 2 0 100-4 2 2 0 000 4z"/>
                                            <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"/>
                                        </svg>
                                    </div>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate group-hover:text-gray-600 transition-colors duration-300">Total Clicks</dt>
                                        <dd class="text-3xl font-bold text-gray-900 transition-all duration-300 group-hover:text-gradient" :class="{ 'animate-pulse': isAnimating }">
                                            {{ formattedMetrics.total_clicks }}
                                        </dd>
                                        <div class="text-xs text-gray-400 mt-1">
                                            <span class="inline-flex items-center">
                                                <svg class="w-3 h-3 mr-1 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-8.293l-3-3a1 1 0 00-1.414 0l-3 3a1 1 0 001.414 1.414L9 9.414V13a1 1 0 102 0V9.414l1.293 1.293a1 1 0 001.414-1.414z" clip-rule="evenodd"></path>
                                                </svg>
                                                Engagement
                                            </span>
                                        </div>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Average Clicks per URL Card -->
                    <div class="card-modern group cursor-pointer animate-float">
                        <div class="p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div class="w-12 h-12 bg-gradient-accent rounded-xl flex items-center justify-center shadow-gradient transition-all duration-300 group-hover:shadow-gradient-lg group-hover:scale-110 transform">
                                        <svg class="w-6 h-6 text-white transition-transform duration-300 group-hover:rotate-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                        </svg>
                                    </div>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate group-hover:text-gray-600 transition-colors duration-300">Avg. Clicks/URL</dt>
                                        <dd class="text-3xl font-bold text-gray-900 transition-all duration-300 group-hover:text-gradient" :class="{ 'animate-pulse': isAnimating }">
                                            {{ formattedMetrics.click_through_rate }}
                                        </dd>
                                        <div class="text-xs text-gray-400 mt-1">
                                            <span class="inline-flex items-center">
                                                <svg class="w-3 h-3 mr-1 text-purple-500" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M3 3a1 1 0 000 2v8a2 2 0 002 2h2.586l-1.293 1.293a1 1 0 101.414 1.414L10 15.414l2.293 2.293a1 1 0 001.414-1.414L12.414 15H15a2 2 0 002-2V5a1 1 0 100-2H3zm11.707 4.707a1 1 0 00-1.414-1.414L10 9.586 8.707 8.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                                </svg>
                                                Performance
                                            </span>
                                        </div>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Active URLs Card -->
                    <div class="card-modern group cursor-pointer animate-float-delayed">
                        <div class="p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div class="w-12 h-12 bg-gradient-royal rounded-xl flex items-center justify-center shadow-gradient transition-all duration-300 group-hover:shadow-gradient-lg group-hover:scale-110 transform">
                                        <svg class="w-6 h-6 text-white transition-transform duration-300 group-hover:rotate-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                    </div>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate group-hover:text-gray-600 transition-colors duration-300">Active URLs</dt>
                                        <dd class="text-3xl font-bold text-gray-900 transition-all duration-300 group-hover:text-gradient" :class="{ 'animate-pulse': isAnimating }">
                                            {{ formattedMetrics.active_urls }}
                                        </dd>
                                        <div class="text-xs text-gray-400 mt-1">
                                            <span class="inline-flex items-center">
                                                <svg class="w-3 h-3 mr-1 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                                </svg>
                                                Live status
                                            </span>
                                        </div>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Main Content Grid -->
                <div class="grid grid-cols-1 gap-6 sm:gap-8 lg:grid-cols-3">
                    <!-- Left Column: Recent URLs and Quick Actions -->
                    <div class="lg:col-span-2 space-y-6 sm:space-y-8">
                        <!-- Quick Actions Section -->
                        <div class="card-modern">
                            <div class="p-6">
                                <div class="flex items-center justify-between mb-6">
                                    <h3 class="text-xl font-bold text-gray-900">Quick Actions</h3>
                                    <div class="w-8 h-8 bg-gradient-button rounded-lg flex items-center justify-center">
                                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                        </svg>
                                    </div>
                                </div>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
                                    <Link
                                        :href="route('urls.index')"
                                        class="btn-gradient group"
                                    >
                                        <svg class="w-5 h-5 mr-3 transition-transform duration-300 group-hover:rotate-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        </svg>
                                        Manage URLs
                                    </Link>
                                    <button
                                        @click="toggleQuickForm"
                                        class="inline-flex items-center justify-center px-6 py-3 bg-white border-2 border-gray-200 rounded-lg font-medium text-gray-700 transition-all duration-300 hover:border-primary-300 hover:bg-primary-50 hover:text-primary-700 hover:shadow-lg hover:-translate-y-0.5 group"
                                    >
                                        <svg class="w-5 h-5 mr-3 transition-transform duration-300 group-hover:rotate-90" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                        </svg>
                                        {{ showQuickForm ? 'Cancel' : 'Create New URL' }}
                                    </button>
                                </div>

                                <!-- Quick URL Creation Form -->
                                <div v-if="showQuickForm" class="border-t pt-4 transition-all duration-300 ease-in-out">
                                    <form @submit.prevent="submitQuickForm" class="space-y-4">
                                        <div>
                                            <InputLabel for="quick_original_url" value="URL to shorten" />
                                            <TextInput
                                                id="quick_original_url"
                                                v-model="quickForm.original_url"
                                                type="url"
                                                class="mt-1 block w-full"
                                                placeholder="https://example.com/very-long-url"
                                                required
                                                autofocus
                                            />
                                            <InputError class="mt-2" :message="quickForm.errors.original_url" />
                                        </div>

                                        <div>
                                            <InputLabel for="quick_title" value="Title (optional)" />
                                            <TextInput
                                                id="quick_title"
                                                v-model="quickForm.title"
                                                type="text"
                                                class="mt-1 block w-full"
                                                placeholder="My awesome link"
                                            />
                                            <InputError class="mt-2" :message="quickForm.errors.title" />
                                        </div>

                                        <div class="flex items-center justify-end space-x-3">
                                            <button
                                                type="button"
                                                @click="toggleQuickForm"
                                                class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150"
                                            >
                                                Cancel
                                            </button>
                                            <PrimaryButton
                                                :class="{ 'opacity-25': quickForm.processing }"
                                                :disabled="quickForm.processing"
                                            >
                                                <svg v-if="quickForm.processing" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                </svg>
                                                {{ quickForm.processing ? 'Creating...' : 'Shorten URL' }}
                                            </PrimaryButton>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Recent URLs Table -->
                        <div class="card-modern">
                            <div class="p-6">
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-6 space-y-3 sm:space-y-0">
                                    <div class="flex items-center space-x-3">
                                        <h3 class="text-xl font-bold text-gray-900">Recent URLs</h3>
                                        <div class="px-2 py-1 bg-primary-100 text-primary-700 text-xs font-medium rounded-full">
                                            {{ recent_urls.length }}
                                        </div>
                                    </div>
                                    <div class="flex items-center space-x-3">
                                        <div class="relative">
                                            <input
                                                v-model="searchQuery"
                                                type="text"
                                                placeholder="Search URLs..."
                                                aria-label="Search URLs"
                                                class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                            >
                                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                                </svg>
                                            </div>
                                        </div>
                                        <Link
                                            :href="route('urls.index')"
                                            class="text-sm text-blue-600 hover:text-blue-500 font-medium whitespace-nowrap"
                                        >
                                            View all →
                                        </Link>
                                    </div>
                                </div>

                                <div v-if="recent_urls.length === 0" class="text-center py-8 text-gray-500">
                                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path>
                                    </svg>
                                    <p class="mt-2">No URLs created yet</p>
                                    <p class="text-sm text-gray-400">Create your first shortened URL to get started</p>
                                </div>

                                <!-- Desktop Table -->
                                <div v-else class="hidden md:block overflow-x-auto">
                                    <table class="min-w-full divide-y divide-gray-200" role="table" aria-label="Recent URLs table">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    <button
                                                        @click="sortBy('title')"
                                                        class="flex items-center space-x-1 hover:text-gray-700 transition-colors duration-150"
                                                    >
                                                        <span>URL</span>
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path v-if="getSortIcon('title') === 'sort'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"></path>
                                                            <path v-else-if="getSortIcon('title') === 'sort-up'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
                                                            <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                                        </svg>
                                                    </button>
                                                </th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    <button
                                                        @click="sortBy('short_code')"
                                                        class="flex items-center space-x-1 hover:text-gray-700 transition-colors duration-150"
                                                    >
                                                        <span>Short Code</span>
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path v-if="getSortIcon('short_code') === 'sort'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"></path>
                                                            <path v-else-if="getSortIcon('short_code') === 'sort-up'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
                                                            <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                                        </svg>
                                                    </button>
                                                </th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    <button
                                                        @click="sortBy('click_count')"
                                                        class="flex items-center space-x-1 hover:text-gray-700 transition-colors duration-150"
                                                    >
                                                        <span>Clicks</span>
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path v-if="getSortIcon('click_count') === 'sort'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"></path>
                                                            <path v-else-if="getSortIcon('click_count') === 'sort-up'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
                                                            <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                                        </svg>
                                                    </button>
                                                </th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    <button
                                                        @click="sortBy('created_at')"
                                                        class="flex items-center space-x-1 hover:text-gray-700 transition-colors duration-150"
                                                    >
                                                        <span>Created</span>
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path v-if="getSortIcon('created_at') === 'sort'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"></path>
                                                            <path v-else-if="getSortIcon('created_at') === 'sort-up'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
                                                            <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                                        </svg>
                                                    </button>
                                                </th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Actions
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white divide-y divide-gray-200">
                                            <tr v-for="url in sortedAndFilteredUrls" :key="url.id" class="hover:bg-gray-50 transition-colors duration-150">
                                                <td class="px-6 py-4">
                                                    <div class="max-w-xs">
                                                        <div class="text-sm font-medium text-gray-900 truncate">
                                                            {{ url.title || 'Untitled' }}
                                                        </div>
                                                        <div class="text-sm text-gray-500 truncate">
                                                            {{ url.original_url }}
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <code class="text-sm bg-gray-100 px-2 py-1 rounded font-mono">
                                                        {{ url.short_code }}
                                                    </code>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <div class="flex items-center">
                                                        <span class="text-sm font-medium text-gray-900">{{ url.click_count }}</span>
                                                        <svg class="ml-1 w-4 h-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                                            <path d="M10 12a2 2 0 100-4 2 2 0 000 4z"/>
                                                            <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"/>
                                                        </svg>
                                                    </div>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                    {{ url.created_at }}
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                    <div class="flex space-x-3">
                                                        <button
                                                            @click="copyToClipboard(url)"
                                                            class="text-blue-600 hover:text-blue-500 transition-colors duration-150 flex items-center"
                                                            title="Copy short URL"
                                                        >
                                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                                            </svg>
                                                            Copy
                                                        </button>
                                                        <Link
                                                            :href="route('urls.stats', url.id)"
                                                            class="text-green-600 hover:text-green-500 transition-colors duration-150 flex items-center"
                                                            title="View statistics"
                                                        >
                                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                                            </svg>
                                                            Stats
                                                        </Link>
                                                        <button
                                                            @click="deleteUrl(url)"
                                                            class="text-red-600 hover:text-red-500 transition-colors duration-150 flex items-center"
                                                            title="Delete URL"
                                                        >
                                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                            </svg>
                                                            Delete
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                                <!-- Mobile Cards -->
                                <div v-if="recent_urls.length > 0" class="md:hidden space-y-4">
                                    <div v-for="url in sortedAndFilteredUrls" :key="url.id" class="bg-gray-50 rounded-lg p-4 hover:bg-gray-100 transition-colors duration-150">
                                        <div class="flex items-start justify-between">
                                            <div class="flex-1 min-w-0">
                                                <div class="text-sm font-medium text-gray-900 truncate">
                                                    {{ url.title || 'Untitled' }}
                                                </div>
                                                <div class="text-xs text-gray-500 truncate mt-1">
                                                    {{ url.original_url }}
                                                </div>
                                                <div class="flex items-center mt-2 space-x-4">
                                                    <code class="text-xs bg-white px-2 py-1 rounded font-mono">
                                                        {{ url.short_code }}
                                                    </code>
                                                    <span class="text-xs text-gray-500">{{ url.click_count }} clicks</span>
                                                    <span class="text-xs text-gray-500">{{ url.created_at }}</span>
                                                </div>
                                            </div>
                                            <div class="flex flex-col space-y-1 ml-4">
                                                <button
                                                    @click="copyToClipboard(url)"
                                                    class="text-blue-600 hover:text-blue-500 text-xs font-medium flex items-center"
                                                >
                                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                                    </svg>
                                                    Copy
                                                </button>
                                                <Link
                                                    :href="route('urls.stats', url.id)"
                                                    class="text-green-600 hover:text-green-500 text-xs font-medium flex items-center"
                                                >
                                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                                    </svg>
                                                    Stats
                                                </Link>
                                                <button
                                                    @click="deleteUrl(url)"
                                                    class="text-red-600 hover:text-red-500 text-xs font-medium flex items-center"
                                                >
                                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                    </svg>
                                                    Delete
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column: Analytics Chart -->
                    <div class="space-y-6 sm:space-y-8">
                        <div class="card-modern">
                            <div class="p-6">
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-6 space-y-3 sm:space-y-0">
                                    <div class="flex items-center space-x-3">
                                        <h3 class="text-xl font-bold text-gray-900">Click Analytics</h3>
                                        <div class="w-8 h-8 bg-gradient-accent rounded-lg flex items-center justify-center">
                                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                            </svg>
                                        </div>
                                    </div>
                                    <div class="flex items-center space-x-4 text-sm text-gray-500">
                                        <div v-if="chartData && chartData.meta" class="flex items-center space-x-4">
                                            <div class="flex items-center">
                                                <div class="w-3 h-3 bg-blue-500 rounded-full mr-2"></div>
                                                <span>Avg: {{ chartData.meta.averageClicks }}/day</span>
                                            </div>
                                            <div class="flex items-center">
                                                <svg v-if="chartData.meta.trend > 0" class="w-4 h-4 text-green-500 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                                                </svg>
                                                <svg v-else-if="chartData.meta.trend < 0" class="w-4 h-4 text-red-500 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"></path>
                                                </svg>
                                                <svg v-else class="w-4 h-4 text-gray-500 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14"></path>
                                                </svg>
                                                <span>{{ chartData.meta.trend > 0 ? '+' : '' }}{{ chartData.meta.trend }}</span>
                                            </div>
                                        </div>
                                        <span>Last 30 days</span>
                                    </div>
                                </div>

                                <div class="h-48 sm:h-64 relative">
                                    <!-- Loading State -->
                                    <div v-if="isChartLoading && chartData" class="absolute inset-0 flex items-center justify-center bg-white bg-opacity-75 z-10">
                                        <div class="flex flex-col items-center">
                                            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500"></div>
                                            <p class="text-sm text-gray-500 mt-2">Loading chart...</p>
                                        </div>
                                    </div>

                                    <!-- Chart -->
                                    <Line
                                        v-if="chartData"
                                        :data="chartData"
                                        :options="chartOptions"
                                        class="w-full h-full"
                                        :class="{ 'opacity-50': isChartLoading }"
                                    />

                                    <!-- Empty State -->
                                    <div v-else class="flex flex-col items-center justify-center h-full text-gray-500">
                                        <svg class="w-12 h-12 text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                        </svg>
                                        <p class="text-sm font-medium">No click data available</p>
                                        <p class="text-xs text-gray-400 mt-1 text-center px-4">Data will appear once your URLs receive clicks</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Additional Stats -->
                        <div class="card-modern">
                            <div class="p-6">
                                <div class="flex items-center justify-between mb-6">
                                    <h3 class="text-xl font-bold text-gray-900">This Week</h3>
                                    <div class="w-8 h-8 bg-gradient-secondary rounded-lg flex items-center justify-center">
                                        <svg class="w-4 h-4 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                        </svg>
                                    </div>
                                </div>
                                <div class="space-y-4">
                                    <div class="flex items-center justify-between p-3 bg-gradient-to-r from-blue-50 to-blue-100 rounded-lg">
                                        <div class="flex items-center space-x-3">
                                            <div class="w-8 h-8 bg-blue-500 rounded-lg flex items-center justify-center">
                                                <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"></path>
                                                </svg>
                                            </div>
                                            <span class="text-sm font-medium text-gray-700">Clicks Today</span>
                                        </div>
                                        <span class="text-lg font-bold text-blue-600">{{ metrics.clicks_today }}</span>
                                    </div>
                                    <div class="flex items-center justify-between p-3 bg-gradient-to-r from-green-50 to-green-100 rounded-lg">
                                        <div class="flex items-center space-x-3">
                                            <div class="w-8 h-8 bg-green-500 rounded-lg flex items-center justify-center">
                                                <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"></path>
                                                </svg>
                                            </div>
                                            <span class="text-sm font-medium text-gray-700">Clicks This Week</span>
                                        </div>
                                        <span class="text-lg font-bold text-green-600">{{ metrics.clicks_this_week }}</span>
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
