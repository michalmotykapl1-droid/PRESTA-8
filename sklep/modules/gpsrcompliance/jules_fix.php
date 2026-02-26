<?php
require_once(dirname(__FILE__).'/../../config/config.inc.php');
require_once(dirname(__FILE__).'/../../init.php');
require_once _PS_MODULE_DIR_.'gpsrcompliance/classes/GpsrService.php';

// Fix for BARULAB manufacturer
$barulabId = (int)Db::getInstance()->getValue('SELECT id_manufacturer FROM '._DB_PREFIX_.'manufacturer WHERE name = "BARULAB"');
if ($barulabId) {
    echo "Found manufacturer BARULAB with ID: " . $barulabId . "<br>";
    $mappingExists = (int)Db::getInstance()->getValue('SELECT 1 FROM '._DB_PREFIX_.'gpsr_brand_map WHERE id_manufacturer = ' . $barulabId);
    if (!$mappingExists) {
        echo "Mapping does not exist for BARULAB. Creating it...<br>";
        // Check if a 'gpsr_producer' named BARULAB exists, if not, create it.
        $gpsrProducerId = (int)Db::getInstance()->getValue('SELECT id_gpsr_producer FROM '._DB_PREFIX_.'gpsr_producer WHERE name = "BARULAB"');
        if (!$gpsrProducerId) {
            echo "GPSR Producer 'BARULAB' not found. Creating it...<br>";
            Db::getInstance()->insert('gpsr_producer', [
                'name' => 'BARULAB',
                'alias' => 'BARULAB',
                'active' => 1,
            ]);
            $gpsrProducerId = (int)Db::getInstance()->Insert_ID();
            echo "Created GPSR Producer with ID: " . $gpsrProducerId . "<br>";
        } else {
            echo "Found existing GPSR Producer with ID: " . $gpsrProducerId . "<br>";
        }

        if ($gpsrProducerId) {
             GpsrService::saveBrandRecord($barulabId, $gpsrProducerId, null);
             echo "Successfully created brand mapping for BARULAB.<br>";
        }
    } else {
        echo "Mapping for BARULAB already exists. No action needed.<br>";
    }
} else {
    echo "Manufacturer 'BARULAB' not found in PrestaShop.<br>";
}

echo "Fix script finished.";