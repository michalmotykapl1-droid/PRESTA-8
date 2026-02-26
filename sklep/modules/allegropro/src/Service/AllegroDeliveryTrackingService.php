<?php
namespace AllegroPro\Service;

/**
 * Pobiera historię śledzenia z publicznej strony Allegro Delivery:
 * https://allegro.pl/allegrodelivery/sledzenie-paczki?numer=...
 *
 * Uwaga: to nie jest oficjalny endpoint API, więc parsowanie jest defensywne.
 */
class AllegroDeliveryTrackingService
{
    /** @var HttpClient */
    private $http;

    public function __construct(HttpClient $http)
    {
        $this->http = $http;
    }

    /**
     * @return array{success:bool, number?:string, url?:string, current?:array|null, events?:array, message?:string}
     */
    public function fetch(string $number): array
    {
        $number = trim($number);
        $url = 'https://allegro.pl/allegrodelivery/sledzenie-paczki?numer=' . urlencode($number);

        $resp = $this->http->request('GET', $url, [
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AllegroPro/1.0',
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Accept-Language' => 'pl-PL,pl;q=0.9,en;q=0.8',
            'Accept-Encoding' => 'identity',
        ]);

        if (empty($resp['ok']) || (int)$resp['code'] < 200 || (int)$resp['code'] >= 300) {
            return [
                'success' => false,
                'message' => 'Nie udało się pobrać strony śledzenia (HTTP ' . (int)$resp['code'] . ').',
                'number' => $number,
                'url' => $url,
            ];
        }

        $html = (string)($resp['body'] ?? '');
        $events = $this->parseEvents($html);

        $current = null;
        if (!empty($events)) {
            $current = $events[0];
        }

        return [
            'success' => true,
            'number' => $number,
            'url' => $url,
            'current' => $current,
            'events' => $events,
        ];
    }

    /**
     * @return array<int, array{date:string,label:string}>
     */
    private function parseEvents(string $html): array
    {
        $events = [];

        // 1) NEXT_DATA (jeśli strona jest Next.js) - preferowane
        if (preg_match('/<script[^>]+id="__NEXT_DATA__"[^>]*>(.*?)<\/script>/s', $html, $m)) {
            $json = html_entity_decode($m[1], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $data = json_decode($json, true);
            if (is_array($data)) {
                $events = $this->extractBestEventList($data);
            }
        }

        // 2) Fallback: prosta analiza tekstu HTML
        if (empty($events)) {
            $events = $this->extractFromPlainText($html);
        }

        // Normalizacja i deduplikacja
        $events = $this->normalizeEvents($events);

        return $events;
    }

    /**
     * Szuka "najlepszej" listy zdarzeń w strukturze JSON.
     *
     * @param mixed $node
     * @return array<int, array{date:string,label:string}>
     */
    private function extractBestEventList($node): array
    {
        $best = [];
        $this->walkForEventLists($node, $best);
        return $best;
    }

    /**
     * @param mixed $node
     * @param array<int, array{date:string,label:string}> $best
     */
    private function walkForEventLists($node, array &$best): void
    {
        if (!is_array($node)) {
            return;
        }

        // lista (indeksowana)
        if ($this->isList($node)) {
            $tmp = [];
            foreach ($node as $item) {
                $ev = $this->eventFromItem($item);
                if ($ev) {
                    $tmp[] = $ev;
                }
            }

            if (count($tmp) >= 2 && count($tmp) > count($best)) {
                $best = $tmp;
            }
        }

        foreach ($node as $v) {
            $this->walkForEventLists($v, $best);
        }
    }

    /**
     * @param mixed $item
     * @return array{date:string,label:string}|null
     */
    private function eventFromItem($item): ?array
    {
        if (!is_array($item)) {
            return null;
        }

        $label = $this->pickString($item, [
            'label','text','description','title','name','message','statusText','statusDescription','statusLabel',
        ]);

        $date = $this->pickString($item, [
            'date','datetime','time','occurredAt','eventDate','eventTime','at','timestamp','createdAt','updatedAt',
        ]);

        // przypadek: osobno date/time
        if (($date === null || $date === '') && isset($item['date']) && isset($item['time']) && is_string($item['date']) && is_string($item['time'])) {
            $date = trim($item['date'] . ' ' . $item['time']);
        }

        if (!$label || !$date) {
            return null;
        }

        $label = $this->cleanText($label);
        $date = $this->cleanText($date);

        if (mb_strlen($label, 'UTF-8') < 3) {
            return null;
        }

        // ISO -> format PL (bezpiecznie)
        $date = $this->formatDateIfIso($date);

        return ['date' => $date, 'label' => $label];
    }

    /**
     * @param array $arr
     * @param array<int, string> $keys
     */
    private function pickString(array $arr, array $keys): ?string
    {
        foreach ($keys as $k) {
            if (isset($arr[$k]) && is_string($arr[$k]) && trim($arr[$k]) !== '') {
                return (string)$arr[$k];
            }
        }
        return null;
    }

    private function cleanText(string $s): string
    {
        $s = html_entity_decode($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $s = preg_replace('/\s+/u', ' ', $s);
        return trim((string)$s);
    }

    private function isList(array $arr): bool
    {
        // PHP < 8.1 kompatybilne
        $i = 0;
        foreach ($arr as $k => $_) {
            if ($k !== $i) return false;
            $i++;
        }
        return true;
    }

    /**
     * Fallback: po wycięciu tagów wyszukuje par (data -> opis).
     *
     * @return array<int, array{date:string,label:string}>
     */
    private function extractFromPlainText(string $html): array
    {
        // usuń skrypty/styl
        $html = preg_replace('~<script\b[^<]*(?:(?!</script>)<[^<]*)*</script>~is', ' ', $html);
        $html = preg_replace('~<style\b[^<]*(?:(?!</style>)<[^<]*)*</style>~is', ' ', $html);

        $text = strip_tags($html);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $text = preg_replace('/\s+/u', ' ', $text);
        $text = trim((string)$text);

        // wzorzec: "13 lut 2026, 11:34"
        $re = '/(\d{1,2}\s+\p{L}{3,12}\s+\d{4},\s*\d{2}:\d{2})/u';
        if (!preg_match_all($re, $text, $m, PREG_OFFSET_CAPTURE)) {
            return [];
        }

        $matches = $m[1];
        $events = [];
        $count = count($matches);

        for ($i=0; $i<$count; $i++) {
            $date = $matches[$i][0];
            $start = $matches[$i][1] + strlen($matches[$i][0]);
            $end = ($i+1 < $count) ? $matches[$i+1][1] : strlen($text);

            $chunk = trim(substr($text, $start, $end - $start));
            if ($chunk === '') continue;

            // weź pierwsze sensowne zdanie/opis
            // często zaczyna się od "Przesyłka ..."
            $label = $chunk;
            // obetnij, żeby nie brać całych akapitów
            if (mb_strlen($label, 'UTF-8') > 180) {
                $label = mb_substr($label, 0, 180, 'UTF-8') . '…';
            }

            $events[] = [
                'date' => $date,
                'label' => $label,
            ];
        }

        return $events;
    }

    /**
     * Deduplikacja + sort (jeśli wygląda na ISO, sortujemy malejąco).
     *
     * @param array<int, array{date:string,label:string}> $events
     * @return array<int, array{date:string,label:string}>
     */
    private function normalizeEvents(array $events): array
    {
        // uniq
        $seen = [];
        $out = [];
        foreach ($events as $ev) {
            if (!is_array($ev) || !isset($ev['date'], $ev['label'])) continue;
            $k = $ev['date'] . '|' . $ev['label'];
            if (isset($seen[$k])) continue;
            $seen[$k] = true;
            $out[] = ['date' => (string)$ev['date'], 'label' => (string)$ev['label']];
        }

        // Jeśli są daty ISO w polu dateIso, można by sortować, ale tu zostawiamy kolejność jak Allegro.
        return $out;
    }

    private function formatDateIfIso(string $date): string
    {
        // ISO 8601: 2026-02-13T08:20:00Z lub bez Z
        if (preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}(:\d{2})?/', $date)) {
            try {
                $dt = new \DateTime($date);
                return $dt->format('d.m.Y H:i');
            } catch (\Throwable $e) {
                return $date;
            }
        }
        return $date;
    }
}
