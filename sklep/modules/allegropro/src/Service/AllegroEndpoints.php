<?php
namespace AllegroPro\Service;

class AllegroEndpoints
{
    public static function apiBase(string $env): string
    {
        return $env === 'sandbox'
            ? 'https://api.allegro.pl.allegrosandbox.pl'
            : 'https://api.allegro.pl';
    }

    public static function authBase(string $env): string
    {
        return $env === 'sandbox'
            ? 'https://allegro.pl.allegrosandbox.pl/auth/oauth'
            : 'https://allegro.pl/auth/oauth';
    }

    public static function tokenUrl(string $env): string
    {
        return self::authBase($env) . '/token';
    }

    public static function authorizeUrl(string $env): string
    {
        return self::authBase($env) . '/authorize';
    }
}
