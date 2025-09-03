<?php

namespace App\Http\Controllers;

use App\Models\Url;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class UrlController extends Controller
{
    /**
     * Display a listing of the user's URLs.
     */
    public function index()
    {
        $urls = auth()->user()->urls()
            ->latest()
            ->withCount('clicks')
            ->get()
            ->map(function ($url) {
                return [
                    'id' => $url->id,
                    'original_url' => $url->original_url,
                    'short_code' => $url->short_code,
                    'short_url' => $url->short_url,
                    'title' => $url->title,
                    'click_count' => $url->click_count,
                    'clicks_count' => $url->clicks_count,
                    'is_active' => $url->is_active,
                    'created_at' => $url->created_at->format('M j, Y'),
                    'last_clicked_at' => $url->last_clicked_at?->format('M j, Y g:i A'),
                ];
            });

        return Inertia::render('Urls/Index', [
            'urls' => $urls,
        ]);
    }

    /**
     * Store a newly created URL.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'original_url' => [
                'required',
                'url',
                'max:2048',
                function ($attribute, $value, $fail) {
                    // Additional URL validation
                    if (!filter_var($value, FILTER_VALIDATE_URL)) {
                        $fail('The URL format is invalid.');
                    }

                    // Check if URL is reachable (optional)
                    $headers = @get_headers($value, 1);
                    if (!$headers) {
                        $fail('The URL appears to be unreachable.');
                    }
                },
            ],
            'title' => 'nullable|string|max:255',
            'custom_code' => [
                'nullable',
                'string',
                'min:3',
                'max:20',
                'alpha_num',
                Rule::unique('urls', 'short_code'),
            ],
        ]);

        // Generate short code
        $shortCode = $validated['custom_code'] ?? $this->generateShortCode();

        // Ensure uniqueness
        while (Url::where('short_code', $shortCode)->exists()) {
            $shortCode = $this->generateShortCode();
        }

        $url = auth()->user()->urls()->create([
            'original_url' => $validated['original_url'],
            'short_code' => $shortCode,
            'title' => $validated['title'] ?? $this->extractTitle($validated['original_url']),
        ]);

        return redirect()->back()->with('success', 'URL shortened successfully!');
    }

    /**
     * Generate a cryptographically secure short code.
     */
    private function generateShortCode(): string
    {
        $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $length = 6;

        do {
            $shortCode = '';
            for ($i = 0; $i < $length; $i++) {
                $shortCode .= $characters[random_int(0, strlen($characters) - 1)];
            }
        } while (Url::where('short_code', $shortCode)->exists());

        return $shortCode;
    }

    /**
     * Extract title from URL (basic implementation).
     */
    private function extractTitle(string $url): ?string
    {
        try {
            $content = @file_get_contents($url);
            if ($content && preg_match('/<title[^>]*>(.*?)<\/title>/is', $content, $matches)) {
                return trim(html_entity_decode($matches[1]));
            }
        } catch (\Exception $e) {
            // Ignore errors and return null
        }

        return null;
    }

    /**
     * Show URL statistics.
     */
    public function stats(Url $url)
    {
        // Ensure user owns this URL
        if ($url->user_id !== auth()->id()) {
            abort(403);
        }

        $clicks = $url->clicks()
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $recentClicks = $url->clicks()
            ->latest()
            ->take(10)
            ->get()
            ->map(function ($click) {
                return [
                    'ip_address' => $click->ip_address,
                    'user_agent' => $click->user_agent,
                    'referer' => $click->referer,
                    'country' => $click->country,
                    'device_type' => $click->device_type,
                    'created_at' => $click->created_at->format('M j, Y g:i A'),
                ];
            });

        return Inertia::render('Urls/Stats', [
            'url' => [
                'id' => $url->id,
                'original_url' => $url->original_url,
                'short_code' => $url->short_code,
                'short_url' => $url->short_url,
                'title' => $url->title,
                'click_count' => $url->click_count,
                'created_at' => $url->created_at->format('M j, Y'),
            ],
            'clicks_over_time' => $clicks,
            'recent_clicks' => $recentClicks,
        ]);
    }

    /**
     * Delete a URL.
     */
    public function destroy(Url $url)
    {
        // Ensure user owns this URL
        if ($url->user_id !== auth()->id()) {
            abort(403);
        }

        $url->delete();

        return redirect()->back()->with('success', 'URL deleted successfully!');
    }
}
