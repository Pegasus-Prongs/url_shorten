<script setup>
import { ref, onMounted, computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    canLogin: {
        type: Boolean,
    },
    canRegister: {
        type: Boolean,
    },
});

// Animated counters
const urlsShortened = ref(0);
const totalClicks = ref(0);
const activeUsers = ref(0);

// Animation states
const isVisible = ref(false);
const currentStep = ref(0);

// Demo URL transformation
const longUrl = "https://www.example.com/very/long/url/with/many/parameters?utm_source=social&utm_medium=facebook&utm_campaign=summer2024&ref=homepage";
const shortUrl = "short.ly/abc123";

// Animated counter function
const animateCounter = (target, duration = 2000) => {
    return new Promise((resolve) => {
        const start = 0;
        const increment = target / (duration / 16);
        let current = 0;

        const timer = setInterval(() => {
            current += increment;
            if (current >= target) {
                current = target;
                clearInterval(timer);
                resolve();
            }
            return Math.floor(current);
        }, 16);
    });
};

// Start animations when component mounts
onMounted(() => {
    setTimeout(() => {
        isVisible.value = true;

        // Animate counters
        setTimeout(() => {
            animateCounter(15247).then(val => urlsShortened.value = val);
            animateCounter(89432).then(val => totalClicks.value = val);
            animateCounter(2847).then(val => activeUsers.value = val);
        }, 500);

        // Animate how it works steps
        const stepInterval = setInterval(() => {
            currentStep.value = (currentStep.value + 1) % 4;
        }, 3000);

        // Clear interval after demo cycles
        setTimeout(() => clearInterval(stepInterval), 15000);
    }, 300);
});

// Computed values for animated counters
const animatedUrlsShortened = computed(() => Math.floor(urlsShortened.value));
const animatedTotalClicks = computed(() => Math.floor(totalClicks.value));
const animatedActiveUsers = computed(() => Math.floor(activeUsers.value));
</script>

<template>
    <Head title="Smart URL Shortener" />
    <div class="min-h-screen bg-gradient-to-br from-accent-50 via-primary-50/30 to-secondary-50/30">
        <!-- Navigation -->
        <nav class="bg-white/95 backdrop-blur-sm border-b border-gray-100 sticky top-0 z-50">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex items-center">
                        <div class="flex items-center space-x-3">
                            <div class="w-8 h-8 bg-gradient-button rounded-lg flex items-center justify-center shadow-gradient">
                                <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                                </svg>
                            </div>
                            <span class="text-xl font-semibold text-gray-900">Shortify</span>
                        </div>
                    </div>
                    <div v-if="canLogin" class="flex items-center space-x-6">
                        <Link
                            v-if="$page.props.auth.user"
                            :href="route('dashboard')"
                            class="btn-gradient text-sm"
                        >
                            Dashboard
                        </Link>
                        <template v-else>
                            <Link
                                :href="route('login')"
                                class="text-gray-600 hover:text-primary-600 text-sm font-medium transition-colors"
                            >
                                Sign in
                            </Link>
                            <Link
                                v-if="canRegister"
                                :href="route('register')"
                                class="btn-gradient text-sm"
                            >
                                Get started
                            </Link>
                        </template>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Hero Section -->
        <div class="relative section-gradient-subtle">
            <!-- Gradient Background -->
            <div class="absolute inset-0 bg-gradient-to-br from-primary-50 via-white to-secondary-50"></div>

            <!-- Content -->
            <div class="relative max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 pt-20 pb-16">
                <div class="text-center">
                    <div class="mb-6">
                        <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-medium bg-white/80 backdrop-blur-sm text-gray-700 shadow-modern border border-white/20">
                            <div class="w-2 h-2 bg-gradient-button rounded-full mr-2"></div>
                            {{ animatedActiveUsers.toLocaleString() }}+ active users
                        </span>
                    </div>

                    <h1 class="text-5xl md:text-6xl lg:text-7xl font-bold text-gray-900 mb-6 leading-tight tracking-tight">
                        Shorten links.<br>
                        <span class="text-gradient">Track everything.</span>
                    </h1>

                    <p class="mt-6 max-w-2xl mx-auto text-xl text-gray-600 leading-relaxed">
                        Create short, branded links and get detailed analytics on every click. Perfect for marketing campaigns, social media, and more.
                    </p>

                    <div class="mt-10 flex flex-col sm:flex-row gap-4 justify-center">
                        <Link
                            v-if="!$page.props.auth.user"
                            :href="route('register')"
                            class="btn-gradient text-base"
                        >
                            Start for free
                        </Link>
                        <Link
                            v-else
                            :href="route('dashboard')"
                            class="btn-gradient text-base"
                        >
                            Go to dashboard
                        </Link>
                        <button class="inline-flex items-center justify-center px-6 py-3 text-base font-medium rounded-lg text-gray-700 bg-white/80 backdrop-blur-sm hover:bg-white transition-all duration-300 border border-gray-200 shadow-modern hover:shadow-modern-lg hover:-translate-y-0.5">
                            <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h1m4 0h1m-6 4h.01M15 14h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Watch demo
                        </button>
                    </div>
                </div>
            </div>

            <!-- Demo Section -->
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 pb-20">
                <div class="card-gradient p-8 backdrop-blur-sm">
                    <div class="text-center mb-8">
                        <h2 class="text-2xl font-bold text-gray-900 mb-2">See it in action</h2>
                        <p class="text-gray-600">Paste any URL and watch the magic happen</p>
                    </div>

                    <!-- URL Input Demo -->
                    <div class="space-y-6">
                        <div class="relative">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Long URL</label>
                            <div class="flex items-center space-x-3">
                                <div class="flex-1 relative">
                                    <input
                                        type="text"
                                        :value="longUrl"
                                        readonly
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg bg-gray-50 text-sm font-mono text-gray-600 transition-all duration-300 hover:border-gray-400 focus:border-primary-500 focus:ring-2 focus:ring-primary-200"
                                    />
                                    <div class="absolute right-3 top-3 text-xs text-red-500 font-medium bg-white px-2 py-1 rounded-full">
                                        {{ longUrl.length }} chars
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Arrow -->
                        <div class="flex justify-center">
                            <div class="w-10 h-10 bg-gradient-button rounded-full flex items-center justify-center shadow-gradient">
                                <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                                </svg>
                            </div>
                        </div>

                        <div class="relative">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Short URL</label>
                            <div class="flex items-center space-x-3">
                                <div class="flex-1 relative">
                                    <input
                                        type="text"
                                        :value="shortUrl"
                                        readonly
                                        class="w-full px-4 py-3 border border-primary-300 rounded-lg bg-primary-50 text-sm font-mono text-gray-900 transition-all duration-300 hover:border-primary-400 focus:border-primary-500 focus:ring-2 focus:ring-primary-200"
                                    />
                                    <div class="absolute right-3 top-3 text-xs text-primary-600 font-medium bg-white px-2 py-1 rounded-full">
                                        {{ shortUrl.length }} chars
                                    </div>
                                </div>
                                <button class="btn-gradient text-sm">
                                    Copy
                                </button>
                            </div>
                        </div>

                        <div class="bg-gradient-to-r from-primary-50 to-secondary-50 rounded-lg p-4 border border-primary-100">
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-gray-600 font-medium">Reduction:</span>
                                <span class="font-semibold text-gradient">{{ Math.round(((longUrl.length - shortUrl.length) / longUrl.length) * 100) }}% shorter</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Features Section -->
        <div class="py-20 bg-white">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-16">
                    <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
                        Everything you need to manage links
                    </h2>
                    <p class="text-xl text-gray-600 max-w-2xl mx-auto">
                        Powerful features designed for modern teams and individuals who need more than just URL shortening.
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <!-- Feature 1 -->
                    <div class="text-center group">
                        <div class="w-12 h-12 bg-gradient-button rounded-xl flex items-center justify-center mx-auto mb-4 shadow-gradient transition-all duration-300 group-hover:shadow-gradient-lg group-hover:-translate-y-1">
                            <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">Lightning Fast</h3>
                        <p class="text-gray-600">Generate short URLs in milliseconds with our optimized global infrastructure.</p>
                    </div>

                    <!-- Feature 2 -->
                    <div class="text-center group">
                        <div class="w-12 h-12 bg-gradient-secondary rounded-xl flex items-center justify-center mx-auto mb-4 shadow-gradient transition-all duration-300 group-hover:shadow-gradient-lg group-hover:-translate-y-1">
                            <svg class="w-6 h-6 text-gray-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">Detailed Analytics</h3>
                        <p class="text-gray-600">Track clicks, locations, devices, and referrers with comprehensive analytics.</p>
                    </div>

                    <!-- Feature 3 -->
                    <div class="text-center group">
                        <div class="w-12 h-12 bg-gradient-accent rounded-xl flex items-center justify-center mx-auto mb-4 shadow-gradient transition-all duration-300 group-hover:shadow-gradient-lg group-hover:-translate-y-1">
                            <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">Secure & Reliable</h3>
                        <p class="text-gray-600">Enterprise-grade security with 99.9% uptime and malware protection.</p>
                    </div>

                    <!-- Feature 4 -->
                    <div class="text-center group">
                        <div class="w-12 h-12 bg-gradient-primary rounded-xl flex items-center justify-center mx-auto mb-4 shadow-gradient transition-all duration-300 group-hover:shadow-gradient-lg group-hover:-translate-y-1">
                            <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zM7 3H5a2 2 0 00-2 2v12a4 4 0 004 4h2a2 2 0 002-2V5a2 2 0 00-2-2H7z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">Custom Domains</h3>
                        <p class="text-gray-600">Use your own domain for branded short links that build trust.</p>
                    </div>

                    <!-- Feature 5 -->
                    <div class="text-center group">
                        <div class="w-12 h-12 bg-gradient-fresh rounded-xl flex items-center justify-center mx-auto mb-4 shadow-gradient transition-all duration-300 group-hover:shadow-gradient-lg group-hover:-translate-y-1">
                            <svg class="w-6 h-6 text-gray-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">Team Collaboration</h3>
                        <p class="text-gray-600">Share and manage links with your team using workspaces and permissions.</p>
                    </div>

                    <!-- Feature 6 -->
                    <div class="text-center group">
                        <div class="w-12 h-12 bg-gradient-ocean rounded-xl flex items-center justify-center mx-auto mb-4 shadow-gradient transition-all duration-300 group-hover:shadow-gradient-lg group-hover:-translate-y-1">
                            <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">Developer API</h3>
                        <p class="text-gray-600">Integrate with your apps using our comprehensive REST API and webhooks.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stats Section -->
        <div class="py-20 bg-gradient-to-br from-primary-50 via-white to-secondary-50 relative">
            <!-- Background Pattern -->
            <div class="absolute inset-0 bg-gradient-to-r from-primary-100/20 to-secondary-100/20"></div>

            <div class="relative max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-16">
                    <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
                        Trusted by thousands worldwide
                    </h2>
                    <p class="text-xl text-gray-600">
                        Join the growing community of users who rely on our platform daily.
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                    <div class="text-center group">
                        <div class="bg-white/80 backdrop-blur-sm rounded-2xl p-6 shadow-modern transition-all duration-300 hover:shadow-modern-lg hover:-translate-y-1 border border-white/20">
                            <div class="text-4xl md:text-5xl font-bold text-gradient mb-2">{{ animatedUrlsShortened.toLocaleString() }}+</div>
                            <div class="text-gray-600 font-medium">URLs shortened</div>
                        </div>
                    </div>
                    <div class="text-center group">
                        <div class="bg-white/80 backdrop-blur-sm rounded-2xl p-6 shadow-modern transition-all duration-300 hover:shadow-modern-lg hover:-translate-y-1 border border-white/20">
                            <div class="text-4xl md:text-5xl font-bold text-gradient mb-2">{{ animatedTotalClicks.toLocaleString() }}+</div>
                            <div class="text-gray-600 font-medium">Total clicks</div>
                        </div>
                    </div>
                    <div class="text-center group">
                        <div class="bg-white/80 backdrop-blur-sm rounded-2xl p-6 shadow-modern transition-all duration-300 hover:shadow-modern-lg hover:-translate-y-1 border border-white/20">
                            <div class="text-4xl md:text-5xl font-bold text-gradient mb-2">{{ animatedActiveUsers.toLocaleString() }}+</div>
                            <div class="text-gray-600 font-medium">Active users</div>
                        </div>
                    </div>
                    <div class="text-center group">
                        <div class="bg-white/80 backdrop-blur-sm rounded-2xl p-6 shadow-modern transition-all duration-300 hover:shadow-modern-lg hover:-translate-y-1 border border-white/20">
                            <div class="text-4xl md:text-5xl font-bold text-gradient mb-2">99.9%</div>
                            <div class="text-gray-600 font-medium">Uptime</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- CTA Section -->
        <div class="py-20 bg-white">
            <div class="max-w-4xl mx-auto text-center px-4 sm:px-6 lg:px-8">
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
                    Ready to get started?
                </h2>
                <p class="text-xl text-gray-600 mb-8 max-w-2xl mx-auto">
                    Join thousands of users who trust our platform for their URL shortening needs.
                </p>
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <Link
                        v-if="!$page.props.auth.user"
                        :href="route('register')"
                        class="btn-gradient text-base"
                    >
                        Start for free
                    </Link>
                    <Link
                        v-else
                        :href="route('dashboard')"
                        class="btn-gradient text-base"
                    >
                        Go to dashboard
                    </Link>
                    <button class="inline-flex items-center justify-center px-6 py-3 text-base font-medium rounded-lg text-gray-700 bg-white/80 backdrop-blur-sm hover:bg-white transition-all duration-300 border border-gray-200 shadow-modern hover:shadow-modern-lg hover:-translate-y-0.5">
                        Contact sales
                    </button>
                </div>
                <div class="mt-6 text-gray-500 text-sm">
                    No credit card required
                </div>
            </div>
        </div>

        <!-- Footer -->
        <footer class="bg-gradient-to-br from-primary-50 via-white to-secondary-50 border-t border-primary-100/50">
            <div class="max-w-6xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
                <div class="flex flex-col md:flex-row justify-between items-center">
                    <div class="flex items-center space-x-3 mb-4 md:mb-0">
                        <div class="w-8 h-8 bg-gradient-button rounded-lg flex items-center justify-center shadow-gradient">
                            <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                            </svg>
                        </div>
                        <span class="text-xl font-semibold text-gray-900">Shortify</span>
                    </div>

                    <div class="flex items-center space-x-6 mb-4 md:mb-0">
                        <a href="#" class="text-gray-600 hover:text-primary-600 transition-colors text-sm font-medium">Privacy</a>
                        <a href="#" class="text-gray-600 hover:text-primary-600 transition-colors text-sm font-medium">Terms</a>
                        <a href="#" class="text-gray-600 hover:text-primary-600 transition-colors text-sm font-medium">Support</a>
                        <a href="#" class="text-gray-600 hover:text-primary-600 transition-colors text-sm font-medium">API</a>
                    </div>

                    <p class="text-gray-500 text-sm">
                        &copy; 2025 Shortify. All rights reserved.
                    </p>
                </div>
            </div>
        </footer>
    </div>
</template>
