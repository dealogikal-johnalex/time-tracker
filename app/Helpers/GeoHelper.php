<?php

namespace App\Helpers;

class GeoHelper
{
    public static function getAddress($lat, $lng)
    {
        if (!$lat || !$lng) return null;

        $url = "https://nominatim.openstreetmap.org/reverse?format=json&lat={$lat}&lon={$lng}&zoom=18&addressdetails=1";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_USERAGENT, 'LaravelTimeTracker/1.0');
        $response = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($response, true);
        return $data['display_name'] ?? null;
    }

    public static function getRealIpFGC(Request $request) : string
    {
        $ip = $request->ip();

        // If it's localhost, fallback to public IP via external API
        if ($ip === '127.0.0.1' || $ip === '::1') {
            try {
                $ip = file_get_contents('https://api.ipify.org');
            } catch (\Exception $e) {
                // fallback if the request fails
                $ip = 'UNKNOWN';
            }
        }

        return $ip;
    }


    public static function getRealIp(Request $request) : string
    {
        $ip = $request->ip();

        if ($ip === '127.0.0.1' || $ip === '::1') {
            $ch = curl_init('https://api.ipify.org');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $ip = curl_exec($ch);
            curl_close($ch);
        }

        return $ip ?: 'UNKNOWN';
    }


    public static function getIpInfo(Request $request)
    {
        $ip = $request->ip();

        // If running locally, fallback to public IP
        if ($ip === '127.0.0.1' || $ip === '::1') {
            $ip = file_get_contents('https://api.ipify.org');
        }

        $info = [
            'ip' => $ip,
            'country' => null,
            'region' => null,
            'city' => null,
            'isp' => null,
        ];

        try {
            // Use ipapi.co (free, no key needed)
            $response = file_get_contents("https://ipapi.co/{$ip}/json/");
            $data = json_decode($response, true);

            $info['country'] = $data['country_name'] ?? null;
            $info['region']  = $data['region'] ?? null;
            $info['city']    = $data['city'] ?? null;
            $info['isp']     = $data['org'] ?? null;
        } catch (\Exception $e) {
            // ignore errors, fallback to IP only
        }

        return $info;
    }

    public static function getLocation(string $ip): array
    {
        try {
            $url = "http://ip-api.com/json/{$ip}";
            $response = @file_get_contents($url);

            if (!$response) {
                return [
                    'city' => 'Unknown',
                    'region' => 'Unknown',
                    'country' => 'Unknown',
                ];
            }

            $data = json_decode($response, true);

            return [
                'city' => $data['city'] ?? 'Unknown',
                'region' => $data['regionName'] ?? 'Unknown',
                'country' => $data['country'] ?? 'Unknown',
            ];
        } catch (\Exception $e) {
            return [
                'city' => 'Unknown',
                'region' => 'Unknown',
                'country' => 'Unknown',
            ];
        }
    }

    public static function getLocationShort($ip)
    {
        $url = "http://ip-api.com/json/{$ip}";
        $data = json_decode(file_get_contents($url), true);

        return [
            'city' => $data['city'] ?? 'Unknown',
            'region' => $data['regionName'] ?? 'Unknown',
            'country' => $data['country'] ?? 'Unknown',
        ];
    }
}
