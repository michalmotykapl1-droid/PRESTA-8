<?php
namespace AllegroPro\Service;

class LabelStorage
{
    private string $dir;

    public function __construct()
    {
        // Katalog prywatny (poza publicznym /modules), aby etykiety nie były dostępne po URL.
        $this->dir = rtrim(_PS_CACHE_DIR_, '/\\') . DIRECTORY_SEPARATOR . 'allegropro_labels' . DIRECTORY_SEPARATOR;
    }

    /**
     * Zapisuje zawartość etykiety do pliku prywatnego.
     *
     * @param string $checkoutFormId UUID zamówienia (lub klucz techniczny)
     * @param string $content Binarna zawartość pliku
     * @param string $format Format (PDF lub ZPL)
     *
     * @return string Pełna ścieżka do zapisanego pliku
     */
    public function save(string $checkoutFormId, string $content, string $format): string
    {
        $this->ensureDirectory();

        $ext = $this->resolveExtension($format);
        $filename = 'label_' . $checkoutFormId . '.' . $ext;
        $path = $this->dir . $filename;

        file_put_contents($path, $content);

        return $path;
    }

    /**
     * Zwraca pełną ścieżkę do etykiety, jeśli istnieje.
     */
    public function getPath(string $checkoutFormId, string $format): ?string
    {
        $ext = $this->resolveExtension($format);
        $path = $this->dir . 'label_' . $checkoutFormId . '.' . $ext;

        if (!is_file($path)) {
            return null;
        }

        return $path;
    }

    /**
     * MIME typu dla danego formatu etykiety.
     */
    public function getMimeType(string $format): string
    {
        return (strtoupper($format) === LabelConfig::FORMAT_ZPL) ? 'application/zpl' : 'application/pdf';
    }

    private function resolveExtension(string $format): string
    {
        return (strtoupper($format) === LabelConfig::FORMAT_ZPL) ? 'zpl' : 'pdf';
    }

    /**
     * Tworzy katalog prywatny na etykiety, jeśli nie istnieje.
     */
    private function ensureDirectory(): void
    {
        if (!is_dir($this->dir)) {
            mkdir($this->dir, 0755, true);
        }
    }
}
