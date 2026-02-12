<?php
namespace AllegroPro\Service;

use Configuration;

class LabelConfig
{
    // Dostępne formaty papieru
    public const PAPER_A4 = 'A4';
    public const PAPER_A6 = 'A6'; // Standard dla etykiet termicznych (10x15cm)
    
    // Dostępne formaty plików
    public const FORMAT_PDF = 'PDF';
    public const FORMAT_ZPL = 'ZPL'; // Dla drukarek Zebra (przemysłowe)

    /**
     * Zwraca preferowany format pliku (PDF/ZPL)
     */
    public function getFileFormat(): string
    {
        $val = Configuration::get('ALLEGROPRO_LABEL_FORMAT');
        return ($val === self::FORMAT_ZPL) ? self::FORMAT_ZPL : self::FORMAT_PDF;
    }

    /**
     * Zwraca preferowany rozmiar papieru (A4/A6)
     */
    public function getPageSize(): string
    {
        $val = Configuration::get('ALLEGROPRO_LABEL_SIZE');
        // Domyślnie A4 jeśli nie ustawiono
        return ($val === self::PAPER_A6) ? self::PAPER_A6 : self::PAPER_A4;
    }

    /**
     * Zwraca tablicę konfiguracji gotową do wysłania do API Allegro
     * endpoint: /shipment-management/label
     */
    public function getApiConfig(): array
    {
        $pageSize = $this->getPageSize();
        $fileFormat = $this->getFileFormat();
        
        // Mapowanie na format API Allegro
        // Dla PDF A4 -> cutLine: true (żeby łatwiej ciąć nożyczkami)
        // Dla A6 -> cutLine: false (bo to naklejka)
        
        return [
            'fileFormat' => $fileFormat,
            'pageSize' => $pageSize,
            'cutLine' => ($pageSize === self::PAPER_A4 && $fileFormat === self::FORMAT_PDF), 
        ];
    }
}