<?php
namespace AllegroPro\Service;

use Configuration;

class Config
{
    public static function env(): string
    {
        $v = (string) Configuration::get('ALLEGROPRO_ENV');
        return $v === 'sandbox' ? 'sandbox' : 'prod';
    }

    public static function clientId(): string
    {
        return (string) Configuration::get('ALLEGROPRO_CLIENT_ID');
    }

    public static function clientSecret(): string
    {
        return (string) Configuration::get('ALLEGROPRO_CLIENT_SECRET');
    }

    public static function labelFormat(): string
    {
        $v = (string) Configuration::get('ALLEGROPRO_LABEL_FORMAT');
        return $v ?: 'PDF';
    }

    public static function pkgDefaults(): array
    {
        return [
            'type' => (string) Configuration::get('ALLEGROPRO_PKG_TYPE') ?: 'OTHER',
            'length' => (int) (Configuration::get('ALLEGROPRO_PKG_LEN') ?: 10),
            'width' => (int) (Configuration::get('ALLEGROPRO_PKG_WID') ?: 10),
            'height' => (int) (Configuration::get('ALLEGROPRO_PKG_HEI') ?: 10),
            'weight' => (float) (Configuration::get('ALLEGROPRO_PKG_WGT') ?: 1.0),
            'text' => (string) Configuration::get('ALLEGROPRO_PKG_TEXT') ?: 'towary',
        ];
    }
}
