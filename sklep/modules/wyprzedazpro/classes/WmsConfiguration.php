<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

class WmsConfiguration
{
    public function processSettings()
    {
        Configuration::updateValue('WYPRZEDAZPRO_DISCOUNT_SHORT', (float)Tools::getValue('WYPRZEDAZPRO_DISCOUNT_SHORT'));
        Configuration::updateValue('WYPRZEDAZPRO_DISCOUNT_30', (float)Tools::getValue('WYPRZEDAZPRO_DISCOUNT_30'));
        Configuration::updateValue('WYPRZEDAZPRO_DISCOUNT_90', (float)Tools::getValue('WYPRZEDAZPRO_DISCOUNT_90'));
        Configuration::updateValue('WYPRZEDAZPRO_DISCOUNT_OVER', (float)Tools::getValue('WYPRZEDAZPRO_DISCOUNT_OVER'));
        Configuration::updateValue('WYPRZEDAZPRO_SHORT_DATE_DAYS', (int)Tools::getValue('WYPRZEDAZPRO_SHORT_DATE_DAYS'));
        Configuration::updateValue('WYPRZEDAZPRO_DISCOUNT_VERY_SHORT', (float)Tools::getValue('WYPRZEDAZPRO_DISCOUNT_VERY_SHORT'));
        Configuration::updateValue('WYPRZEDAZPRO_DISCOUNT_BIN', (float)Tools::getValue('WYPRZEDAZPRO_DISCOUNT_BIN'));
        Configuration::updateValue('WYPRZEDAZPRO_IGNORE_BIN_EXPIRY', (int)Tools::getValue('WYPRZEDAZPRO_IGNORE_BIN_EXPIRY'));
        
        Configuration::updateValue('WYPRZEDAZPRO_ENABLE_OVER90_LONGEXP', (int)Tools::getValue('WYPRZEDAZPRO_ENABLE_OVER90_LONGEXP'));
        Configuration::updateValue('WYPRZEDAZPRO_DISCOUNT_OVER90_LONGEXP', (float)Tools::getValue('WYPRZEDAZPRO_DISCOUNT_OVER90_LONGEXP'));
        
        return true;
    }
}