<?php

namespace App\Http\Controllers;

use App\Models\Url;
use App\Models\UrlClick;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RedirectController extends Controller
{
    /**
     * Handle URL redirect and track click analytics.
     */
    public function redirect(Request $request, string $code)
    {
        // Find the URL by short code
        $url = Url::where('short_code', $code)
            ->active()
            ->first();

        if (!$url) {
            abort(404, 'Short URL not found or has expired.');
        }

        // Track the click asynchronously to avoid slowing down the redirect
        $this->trackClick($request, $url);

        // Increment click count
        $url->incrementClicks();

        // Redirect to the original URL
        return redirect($url->original_url, 301);
    }

    /**
     * Track click analytics.
     */
    private function trackClick(Request $request, Url $url): void
    {
        try {
            $clickData = [
                'url_id' => $url->id,
                'ip_address' => $this->getClientIp($request),
                'user_agent' => $request->userAgent(),
                'referer' => $request->header('referer'),
                'country' => $this->getCountryFromIp($this->getClientIp($request)),
                'device_type' => $this->getDeviceType($request->userAgent()),
                'created_at' => now(),
                'updated_at' => now(),
            ];

            // Use database transaction for consistency
            DB::transaction(function () use ($clickData) {
                UrlClick::create($clickData);
            });
        } catch (\Exception $e) {
            // Log error but don't fail the redirect
            Log::error('Failed to track click: ' . $e->getMessage(), [
                'url_id' => $url->id,
                'ip' => $this->getClientIp($request),
                'user_agent' => $request->userAgent(),
            ]);
        }
    }

    /**
     * Get the real client IP address.
     */
    private function getClientIp(Request $request): string
    {
        $ipKeys = [
            'HTTP_CF_CONNECTING_IP',     // Cloudflare
            'HTTP_CLIENT_IP',            // Proxy
            'HTTP_X_FORWARDED_FOR',      // Load balancer/proxy
            'HTTP_X_FORWARDED',          // Proxy
            'HTTP_X_CLUSTER_CLIENT_IP',  // Cluster
            'HTTP_FORWARDED_FOR',        // Proxy
            'HTTP_FORWARDED',            // Proxy
            'REMOTE_ADDR'                // Standard
        ];

        foreach ($ipKeys as $key) {
            if (!empty($_SERVER[$key])) {
                $ips = explode(',', $_SERVER[$key]);
                $ip = trim($ips[0]);

                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }

        return $request->ip();
    }

    /**
     * Get country from IP address (basic implementation).
     */
    private function getCountryFromIp(string $ip): ?string
    {
        // This is a basic implementation
        // In production, you might want to use a service like MaxMind GeoIP
        try {
            $response = @file_get_contents("http://ip-api.com/json/{$ip}?fields=countryCode");
            if ($response) {
                $data = json_decode($response, true);
                return $data['countryCode'] ?? null;
            }
        } catch (\Exception $e) {
            // Ignore errors
        }

        return null;
    }

    /**
     * Determine device type from user agent.
     */
    private function getDeviceType(?string $userAgent): string
    {
        if (!$userAgent) {
            return 'unknown';
        }

        $userAgent = strtolower($userAgent);

        // Mobile devices
        if (preg_match('/mobile|android|iphone|ipad|phone|blackberry|opera mini|iemobile|wpdesktop/', $userAgent)) {
            if (preg_match('/ipad|tablet/', $userAgent)) {
                return 'tablet';
            }
            return 'mobile';
        }

        // Tablets
        if (preg_match('/tablet|ipad/', $userAgent)) {
            return 'tablet';
        }

        // Bots/crawlers
        if (preg_match('/bot|crawler|spider|scraper/', $userAgent)) {
            return 'bot';
        }

        return 'desktop';
    }
}
