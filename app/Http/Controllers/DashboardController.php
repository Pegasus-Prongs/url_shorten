<?php

namespace App\Http\Controllers;

use App\Models\Url;
use App\Models\UrlClick;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DashboardController extends Controller
{
    /**
     * Display the dashboard with comprehensive metrics and analytics.
     */
    public function index()
    {
        $user = auth()->user();

        // Get key metrics with optimized queries
        $metrics = $this->getKeyMetrics($user);

        // Get recent URLs (last 10) with click counts
        $recentUrls = $this->getRecentUrls($user);

        // Get analytics data for the last 30 days
        $analyticsData = $this->getAnalyticsData($user);

        return Inertia::render('Dashboard', [
            'metrics' => $metrics,
            'recent_urls' => $recentUrls,
            'analytics_data' => $analyticsData,
        ]);
    }

    /**
     * Get key metrics with optimized database queries.
     */
    private function getKeyMetrics($user)
    {
        // Single query to get URL counts and total clicks (SQLite compatible)
        $urlStats = $user->urls()
            ->selectRaw('
                COUNT(*) as total_urls,
                SUM(click_count) as total_clicks,
                COUNT(CASE WHEN is_active = 1 AND (expires_at IS NULL OR expires_at > datetime("now")) THEN 1 END) as active_urls
            ')
            ->first();

        $totalUrls = (int) ($urlStats->total_urls ?? 0);
        $totalClicks = (int) ($urlStats->total_clicks ?? 0);
        $activeUrls = (int) ($urlStats->active_urls ?? 0);

        // Calculate click-through rate (average clicks per URL) with safety checks
        $clickThroughRate = 0;
        if ($totalUrls > 0 && is_numeric($totalClicks)) {
            $rate = $totalClicks / $totalUrls;
            $clickThroughRate = is_finite($rate) ? round($rate, 2) : 0;
        }

        // Optimized queries for time-based click counts using joins (SQLite compatible)
        $clickStats = DB::table('url_clicks')
            ->join('urls', 'url_clicks.url_id', '=', 'urls.id')
            ->where('urls.user_id', $user->id)
            ->selectRaw('
                COUNT(CASE WHEN date(url_clicks.created_at) = date("now") THEN 1 END) as clicks_today,
                COUNT(CASE WHEN url_clicks.created_at >= ? THEN 1 END) as clicks_this_week
            ', [now()->startOfWeek()])
            ->first();

        // Ensure all values are valid numbers
        $metrics = [
            'total_urls' => max(0, $totalUrls),
            'total_clicks' => max(0, $totalClicks),
            'click_through_rate' => max(0, $clickThroughRate),
            'active_urls' => max(0, $activeUrls),
            'clicks_today' => max(0, (int) ($clickStats->clicks_today ?? 0)),
            'clicks_this_week' => max(0, (int) ($clickStats->clicks_this_week ?? 0)),
        ];

        return $metrics;
    }

    /**
     * Get recent URLs with optimized query.
     */
    private function getRecentUrls($user)
    {
        return $user->urls()
            ->latest()
            ->take(10)
            ->get()
            ->map(function ($url) {
                return [
                    'id' => $url->id,
                    'original_url' => $url->original_url,
                    'short_code' => $url->short_code,
                    'short_url' => $url->short_url,
                    'title' => $url->title,
                    'click_count' => $url->click_count,
                    'is_active' => $url->is_active,
                    'created_at' => $url->created_at->format('M j, Y'),
                    'last_clicked_at' => $url->last_clicked_at?->format('M j, Y g:i A'),
                ];
            });
    }
    
    /**
     * Get analytics data for charts (last 30 days) with optimized query.
     */
    private function getAnalyticsData($user)
    {
        // Optimized query using join instead of whereHas for better performance (SQLite compatible)
        $dailyClicks = DB::table('url_clicks')
            ->join('urls', 'url_clicks.url_id', '=', 'urls.id')
            ->where('urls.user_id', $user->id)
            ->where('url_clicks.created_at', '>=', now()->subDays(30))
            ->selectRaw('date(url_clicks.created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        // Fill in missing dates with zero counts for consistent chart display
        $analytics = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $clickCount = $dailyClicks->get($date)?->count ?? 0;

            $analytics[] = [
                'date' => $date,
                'count' => $clickCount,
                'formatted_date' => now()->subDays($i)->format('M j'),
            ];
        }

        return $analytics;
    }
    
    /**
     * Get top performing URLs for the user.
     */
    private function getTopPerformingUrls($user, $limit = 5)
    {
        return $user->urls()
            ->orderBy('click_count', 'desc')
            ->take($limit)
            ->get()
            ->map(function ($url) {
                return [
                    'id' => $url->id,
                    'title' => $url->title ?: 'Untitled',
                    'short_code' => $url->short_code,
                    'click_count' => $url->click_count,
                    'created_at' => $url->created_at->format('M j, Y'),
                ];
            });
    }
}
