<?php
/**
 * ALLEGRO PRO - Korespondencja (Wiadomości + Dyskusje/Reklamacje)
 *
 * ETAP 1/2:
 * - lewy przycisk w BO: "Korespondencja" (jeden TAB)
 * - klik ma otwierać NOWE okno/kartę i ładować "app" na froncie (czytelny full-screen)
 * - bez synchronizacji i bez zapisu do bazy (to dodamy w kolejnych etapach)
 */

class AdminAllegroProCorrespondenceController extends ModuleAdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->bootstrap = true;
    }

    public function initContent()
    {
        parent::initContent();

        // Zapewnij aktualny schemat (kolumny/tabele) bez reinstalacji modułu
        if (isset($this->module) && method_exists($this->module, 'ensureDbSchema')) {
            $this->module->ensureDbSchema();
        }

        // Zapewnij brakujące TAB-y + HOOK-i (po wgraniu plików bez reinstall)
        if (isset($this->module) && method_exists($this->module, 'ensureTabs')) {
            $this->module->ensureTabs();
        }

        // BO -> FO "bridge" (krótki TTL) + redirect do czytelnego widoku "aplikacji"
        if (!isset($this->module) || !method_exists($this->module, 'generateBoBridgeParams')) {
            // fallback: pokaż prostą informację
            $this->content = '<div class="alert alert-danger">Brak metody generateBoBridgeParams() w module AllegroPro.</div>';
            return;
        }

        $params = $this->module->generateBoBridgeParams((int)$this->context->employee->id, 43200);
        $url = $this->context->link->getModuleLink($this->module->name, 'correspondenceapp', $params, true);

        // Przekierowanie na "frontową" aplikację
        Tools::redirect($url);
    }
}
