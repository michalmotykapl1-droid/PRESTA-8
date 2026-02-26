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
     * @param string[] $fallbackCarrierIds
     *
     * @return array{ok:bool, code:int, message?:string, carrierId?:string, waybill?:string, statuses?:array<int,array{occurredAt:string, code:string, description?:string}>}
     */
    public function fetch(array $account, string $carrierId, string $waybill, array $fallbackCarrierIds = []): array
    {
        $waybill = trim($waybill);
        if ($waybill === '') {
            return ['ok' => false, 'code' => 0, 'message' => 'Brak numeru przesyłki.'];
        }

        $carrierCandidates = [];
        $push = static function (array &$list, string $cid): void {
            $cid = strtoupper(trim($cid));
            if ($cid === '') {
                return;
            }
            if (!preg_match('/^[A-Z0-9_]{2,30}$/', $cid)) {
                return;
            }
            if (!in_array($cid, $list, true)) {
                $list[] = $cid;
            }
        };

        $push($carrierCandidates, $carrierId);
        foreach ($fallbackCarrierIds as $cid) {
            if (is_string($cid)) {
                $push($carrierCandidates, $cid);
            }
        }

        if (empty($carrierCandidates)) {
            return ['ok' => false, 'code' => 0, 'message' => 'Brak carrierId lub numeru przesyłki.'];
        }

        $errors = [];
        $lastEmpty = null;

        foreach ($carrierCandidates as $candidateCarrierId) {
            $path = '/order/carriers/' . rawurlencode($candidateCarrierId) . '/tracking';
            $res = $this->api->get($account, $path, ['waybill' => $waybill], 'application/vnd.allegro.public.v1+json');

            if (empty($res['ok'])) {
                $msg = 'Błąd Allegro API (' . (int)($res['code'] ?? 0) . ')';
                $raw = (string)($res['raw'] ?? '');
                if ($raw !== '') {
                    $msg .= ': ' . $raw;
                }
                $errors[] = '[' . $candidateCarrierId . '] ' . $msg;
                continue;
            }

            $json = $res['json'] ?? null;
            if (!is_array($json)) {
                return [
                    'ok' => false,
                    'code' => (int)($res['code'] ?? 0),
                    'message' => 'Nieprawidłowa odpowiedź JSON z Allegro.',
                ];
            }

            $waybills = $json['waybills'] ?? [];
            if (!is_array($waybills) || empty($waybills)) {
                $lastEmpty = [
                    'ok' => true,
                    'code' => (int)($res['code'] ?? 200),
                    'carrierId' => $candidateCarrierId,
                    'waybill' => $waybill,
                    'statuses' => [],
                    'message' => 'Brak danych trackingu (waybill nie rozpoznany lub brak statusów).',
                ];
                continue;
            }

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
                $lastEmpty = [
                    'ok' => true,
                    'code' => (int)($res['code'] ?? 200),
                    'carrierId' => $candidateCarrierId,
                    'waybill' => $waybill,
                    'statuses' => [],
                    'message' => 'Brak danych trackingu (pusta struktura waybills).',
                ];
                continue;
            }

            $details = $wb['trackingDetails'] ?? null;
            if (!is_array($details)) {
                $lastEmpty = [
                    'ok' => true,
                    'code' => (int)($res['code'] ?? 200),
                    'carrierId' => $candidateCarrierId,
                    'waybill' => $waybill,
                    'statuses' => [],
                    'message' => 'TrackingDetails = null (np. brak statusów albo paczka > 60 dni).',
                ];
                continue;
            }

            $statuses = $details['statuses'] ?? [];
            if (!is_array($statuses)) {
                $statuses = [];
            }

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

            if (!empty($clean)) {
                return [
                    'ok' => true,
                    'code' => (int)($res['code'] ?? 200),
                    'carrierId' => $candidateCarrierId,
                    'waybill' => $waybill,
                    'statuses' => $clean,
                ];
            }

            $lastEmpty = [
                'ok' => true,
                'code' => (int)($res['code'] ?? 200),
                'carrierId' => $candidateCarrierId,
                'waybill' => $waybill,
                'statuses' => [],
                'message' => 'Brak danych trackingu (brak statusów).',
            ];
        }

        if (is_array($lastEmpty)) {
            if (count($carrierCandidates) > 1) {
                $lastEmpty['message'] = (string)($lastEmpty['message'] ?? 'Brak danych trackingu.')
                    . ' Sprawdzono carrierId: ' . implode(', ', $carrierCandidates) . '.';
            }
            return $lastEmpty;
        }

        return [
            'ok' => false,
            'code' => 0,
            'message' => 'Nie udało się pobrać trackingu. ' . implode(' | ', $errors),
        ];
    }
}
