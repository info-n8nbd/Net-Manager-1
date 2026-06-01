<?php

namespace App\Http\Controllers;

abstract class Controller
{
    protected function isSafeRedirect(?string $url): bool
    {
        if (empty($url)) {
            return false;
        }

        if (str_starts_with($url, '/') && !str_starts_with($url, '//')) {
            return true;
        }

        $parsed = parse_url($url);
        if (!$parsed || !isset($parsed['host'])) {
            return false;
        }

        $appHost = parse_url(config('app.url'), PHP_URL_HOST);
        return $parsed['host'] === $appHost;
    }
}
