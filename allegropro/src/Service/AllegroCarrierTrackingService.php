<?php
namespace AllegroPro\Service;

/**
 * Pobiera historię trackingu z Allegro API:
 * GET /order/carriers/{carrierId}/tracking?waybill={waybill}
 *
 * Nie scrapujemy strony allegro.pl (często 403). Korzystamy z oficjalnego API.
 */
class AllegroCarrierTrackingService
{
    private AllegroApiClient $api;

    public function __construct(AllegroApiClient $api)
    {
        $this->api = $api;
    }

    /**
     * @return array{ok:bool, code:int, message?:string, carrierId?:string, waybill?:string, statuses?:array<int,array{occurredAt:string, code:string, description?:string}>}
     */
    public function fetch(array $account, string $carrierId, string $waybill): array
    {
        $carrierId = strtoupper(trim($carrierId));
        $waybill = trim($waybill);

        if ($carrierId === '' || $waybill === '') {
            return ['ok' => false, 'code' => 0, 'message' => 'Brak carrierId lub numeru przesyłki.'];
        }

        $path = '/order/carriers/' . rawurlencode($carrierId) . '/tracking';
        // UWAGA: AllegroApiClient używa http_build_query - dla pojedynczego waybill OK.
        $res = $this->api->get($account, $path, ['waybill' => $waybill], 'application/vnd.allegro.public.v1+json');

        if (empty($res['ok'])) {
            $msg = 'Błąd Allegro API (' . (int)($res['code'] ?? 0) . ')';
            $raw = (string)($res['raw'] ?? '');
            if ($raw !== '') {
                $msg .= ': ' . $raw;
            }
            return ['ok' => false, 'code' => (int)($res['code'] ?? 0), 'message' => $msg];
        }

        $json = $res['json'] ?? null;
        if (!is_array($json)) {
            return ['ok' => false, 'code' => (int)($res['code'] ?? 0), 'message' => 'Nieprawidłowa odpowiedź JSON z Allegro.'];
        }

        $waybills = $json['waybills'] ?? [];
        if (!is_array($waybills) || empty($waybills)) {
            return [
                'ok' => true,
                'code' => (int)($res['code'] ?? 200),
                'carrierId' => $carrierId,
                'waybill' => $waybill,
                'statuses' => [],
                'message' => 'Brak danych trackingu (waybill nie rozpoznany lub brak statusów).'
            ];
        }

        // API może zwrócić wiele waybills - bierzemy ten, o który prosiliśmy
        $wb = null;
        foreach ($waybills as $item) {
            if (is_array($item) && (string)($item['waybill'] ?? '') === $waybill) {
                $wb = $item;
                break;
            }
        }
        if (!is_array($wb)) {
            $wb = is_array($waybills[0] ?? null) ? $waybills[0] : null;
        }
        if (!is_array($wb)) {
            return [
                'ok' => true,
                'code' => (int)($res['code'] ?? 200),
                'carrierId' => $carrierId,
                'waybill' => $waybill,
                'statuses' => [],
                'message' => 'Brak danych trackingu (pusta struktura waybills).'
            ];
        }

        $details = $wb['trackingDetails'] ?? null;
        if (!is_array($details)) {
            return [
                'ok' => true,
                'code' => (int)($res['code'] ?? 200),
                'carrierId' => $carrierId,
                'waybill' => $waybill,
                'statuses' => [],
                'message' => 'TrackingDetails = null (np. brak statusów albo paczka > 60 dni).'
            ];
        }

        $statuses = $details['statuses'] ?? [];
        if (!is_array($statuses)) {
            $statuses = [];
        }

        // Uporządkuj po occurredAt rosnąco
        usort($statuses, function ($a, $b) {
            $ta = is_array($a) ? (string)($a['occurredAt'] ?? '') : '';
            $tb = is_array($b) ? (string)($b['occurredAt'] ?? '') : '';
            return strcmp($ta, $tb);
        });

        $clean = [];
        foreach ($statuses as $st) {
            if (!is_array($st)) {
                continue;
            }
            $occurredAt = (string)($st['occurredAt'] ?? '');
            $code = (string)($st['code'] ?? '');
            if ($occurredAt === '' && $code === '') {
                continue;
            }
            $row = ['occurredAt' => $occurredAt, 'code' => $code];
            if (isset($st['description']) && is_string($st['description']) && $st['description'] !== '') {
                $row['description'] = $st['description'];
            }
            $clean[] = $row;
        }

        return [
            'ok' => true,
            'code' => (int)($res['code'] ?? 200),
            'carrierId' => $carrierId,
            'waybill' => $waybill,
            'statuses' => $clean,
        ];
    }
}
