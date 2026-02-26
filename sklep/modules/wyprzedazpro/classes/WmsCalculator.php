<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

class WmsCalculator
{
    public static function getDiscountByDates($data_waznosci, $data_przyjecia, $regal) 
    {
        if (trim(mb_strtoupper($regal, 'UTF-8')) === 'KOSZ') {
            return (float)Configuration::get('WYPRZEDAZPRO_DISCOUNT_BIN');
        }

        $today = new DateTime();
        $today->setTime(0, 0, 0);
        
        $dateWaznosci = DateTime::createFromFormat('d.m.Y', $data_waznosci);
        if (!$dateWaznosci) $dateWaznosci = DateTime::createFromFormat('Y-m-d', $data_waznosci);
        if (!$dateWaznosci) { return 0; }
        
        $daysToExpiry = ($dateWaznosci >= $today) ? $today->diff($dateWaznosci)->days : 0;
        
        $shortDateThreshold = (int)Configuration::get('WYPRZEDAZPRO_SHORT_DATE_DAYS', 14);
        $veryShortDiscount = (float)Configuration::get('WYPRZEDAZPRO_DISCOUNT_VERY_SHORT');

        if ($daysToExpiry < 7) {
            return $veryShortDiscount;
        } elseif ($daysToExpiry < $shortDateThreshold) { 
            return (float)Configuration::get('WYPRZEDAZPRO_DISCOUNT_SHORT'); 
        }

        $datePrzyjecia = DateTime::createFromFormat('d.m.Y', $data_przyjecia);
        if (!$datePrzyjecia) $datePrzyjecia = DateTime::createFromFormat('Y-m-d', $data_przyjecia);
        if (!$datePrzyjecia) { return 0; }
        
        $daysSinceReceipt = ($datePrzyjecia <= $today) ? $datePrzyjecia->diff($today)->days : 0;
        
        if (Configuration::get('WYPRZEDAZPRO_ENABLE_OVER90_LONGEXP') && $daysSinceReceipt > 90 && $daysToExpiry >= 180) {
            return (float)Configuration::get('WYPRZEDAZPRO_DISCOUNT_OVER90_LONGEXP');
        } elseif ($daysSinceReceipt <= 30) {
            return (float)Configuration::get('WYPRZEDAZPRO_DISCOUNT_30');
        } elseif ($daysSinceReceipt <= 90) { 
            return (float)Configuration::get('WYPRZEDAZPRO_DISCOUNT_90');
        } else {
            return (float)Configuration::get('WYPRZEDAZPRO_DISCOUNT_OVER');
        }
    }
}