<?php
namespace AllegroPro\Service;

use Tools;

class LabelStorage
{
    private string $dir;
    private string $dirUrl;

    public function __construct()
    {
        // Ścieżka fizyczna na serwerze: /modules/allegropro/labels/
        $this->dir = _PS_MODULE_DIR_ . 'allegropro/labels/';
        
        // Ścieżka URL (dla przeglądarki): https://twojsklep.pl/modules/allegropro/labels/
        $this->dirUrl = _PS_BASE_URL_ . __PS_BASE_URI__ . 'modules/allegropro/labels/';
    }

    /**
     * Zapisuje zawartość etykiety do pliku
     * @param string $checkoutFormId UUID zamówienia
     * @param string $content Binarna zawartość pliku
     * @param string $format Format (PDF lub ZPL) - używany do rozszerzenia
     * @return string Pełna ścieżka do zapisanego pliku
     */
    public function save(string $checkoutFormId, string $content, string $format): string
    {
        $this->ensureDirectory();

        $ext = (strtoupper($format) === LabelConfig::FORMAT_ZPL) ? 'zpl' : 'pdf';
        $filename = 'label_' . $checkoutFormId . '.' . $ext;
        $path = $this->dir . $filename;

        file_put_contents($path, $content);

        return $path;
    }

    /**
     * Sprawdza, czy etykieta w danym formacie już istnieje
     */
    public function exists(string $checkoutFormId, string $format): bool
    {
        $ext = (strtoupper($format) === LabelConfig::FORMAT_ZPL) ? 'zpl' : 'pdf';
        return file_exists($this->dir . 'label_' . $checkoutFormId . '.' . $ext);
    }

    /**
     * Zwraca URL do etykiety (do wyświetlenia w przycisku "Pobierz")
     */
    public function getUrl(string $checkoutFormId, string $format): ?string
    {
        if (!$this->exists($checkoutFormId, $format)) {
            return null;
        }
        
        $ext = (strtoupper($format) === LabelConfig::FORMAT_ZPL) ? 'zpl' : 'pdf';
        return $this->dirUrl . 'label_' . $checkoutFormId . '.' . $ext;
    }

    /**
     * Tworzy katalog labels jeśli nie istnieje i zabezpiecza go plikiem index.php
     */
    private function ensureDirectory(): void
    {
        if (!is_dir($this->dir)) {
            mkdir($this->dir, 0755, true);
        }

        // Dodajemy index.php dla bezpieczeństwa (żeby nikt nie mógł wylistować katalogu w przeglądarce)
        if (!file_exists($this->dir . 'index.php')) {
            file_put_contents($this->dir . 'index.php', '<?php header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); header("Last-Modified: ".gmdate("D, d M Y H:i:s")." GMT"); header("Cache-Control: no-store, no-cache, must-revalidate"); header("Cache-Control: post-check=0, pre-check=0", false); header("Pragma: no-cache"); header("Location: ../"); exit;');
        }
    }
}