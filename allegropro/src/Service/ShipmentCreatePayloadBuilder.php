<?php
namespace AllegroPro\Service;

use Configuration;

class ShipmentCreatePayloadBuilder
{
    public function resolvePackageDimensions(array $params): array
    {
        $inputWeight = null;
        if (isset($params['weight']) && $params['weight'] !== '' && is_numeric($params['weight'])) {
            $inputWeight = (float)$params['weight'];
            if ($inputWeight <= 0) {
                $inputWeight = null;
            }
        }

        if (!empty($params['size_code'])) {
            switch ($params['size_code']) {
                // Allegro API akceptuje tylko: DOX|PACKAGE|PALLET|OTHER.
                // Dla gabarytów A/B/C (np. paczkomaty) przekazujemy PACKAGE + wymiary.
                // UWAGA: waga powinna pochodzić z pola formularza (użytkownik ją wpisuje). Wcześniej była na sztywno 25kg.
                case 'A': return ['height' => 8,  'width' => 38, 'length' => 64, 'weight' => $inputWeight ?? 1.0, 'type' => 'PACKAGE'];
                case 'B': return ['height' => 19, 'width' => 38, 'length' => 64, 'weight' => $inputWeight ?? 1.0, 'type' => 'PACKAGE'];
                case 'C': return ['height' => 41, 'width' => 38, 'length' => 64, 'weight' => $inputWeight ?? 1.0, 'type' => 'PACKAGE'];
            }
        }

        if ($inputWeight !== null) {
            $def = Config::pkgDefaults();
            return [
                'height' => $def['height'], 'width' => $def['width'], 'length' => $def['length'],
                'weight' => $inputWeight,
                'type' => 'PACKAGE'
            ];
        }

        return Config::pkgDefaults();
    }

    public function buildPayload($methodId, array $order, array $dims, LabelConfig $config): array
    {
        $senderPhone = Configuration::get('PS_SHOP_PHONE');
        $senderPhone = preg_replace('/[^0-9+]/', '', (string)$senderPhone);
        if (preg_match('/^[0-9]{9}$/', $senderPhone)) {
            $senderPhone = '+48' . $senderPhone;
        }

        if (empty($senderPhone)) {
            $senderPhone = '+48000000000';
        }

        $sender = [
            'name' => $this->limitText(Configuration::get('PS_SHOP_NAME'), 30),
            'street' => Configuration::get('PS_SHOP_ADDR1'),
            'city' => Configuration::get('PS_SHOP_CITY'),
            'postalCode' => Configuration::get('PS_SHOP_CODE'),
            'countryCode' => 'PL',
            'email' => Configuration::get('PS_SHOP_EMAIL'),
            'phone' => $senderPhone
        ];

        $addr = $order['delivery']['address'];
        $receiver = [
            'name' => trim(($addr['firstName'] ?? '') . ' ' . ($addr['lastName'] ?? '')),
            'street' => $addr['street'],
            'city' => $addr['city'],
            'postalCode' => $addr['zipCode'],
            'countryCode' => $addr['countryCode'],
            'email' => $order['buyer']['email'],
            'phone' => $addr['phoneNumber'] ?? $order['buyer']['phoneNumber']
        ];

        $receiverPhone = (string)($receiver['phone'] ?? '');
        $receiverPhone = preg_replace('/[^0-9+]/', '', $receiverPhone);
        if (strlen($receiverPhone) == 9) {
            $receiverPhone = '+48' . $receiverPhone;
        }
        $receiver['phone'] = $receiverPhone;

        if (!empty($addr['companyName'])) {
            $receiver['company'] = $addr['companyName'];
        }

        if (!empty($order['delivery']['pickupPoint']['id'])) {
            $rawPoint = trim($order['delivery']['pickupPoint']['id']);
            $parts = preg_split('/\s+/', $rawPoint);
            $lastPart = end($parts);
            $cleanPoint = preg_replace('/[^A-Z0-9-]/', '', strtoupper($lastPart));

            if (!empty($cleanPoint)) {
                // Allegro WZA oczekuje tutaj stringa (np. "WCL01BAPP"), nie obiektu.
                $receiver['point'] = $cleanPoint;
            }
        }

        $wgtVal = number_format((float)($dims['weight'] ?? 1), 3, '.', '');
        $finalType = $dims['type'] ?? 'PACKAGE';

        return [
            'deliveryMethodId' => $methodId,
            'sender' => $sender,
            'receiver' => $receiver,
            'labelFormat' => $config->getFileFormat(),
            'packages' => [[
                'type' => $finalType,
                'weight' => [
                    'value' => (string)$wgtVal,
                    'unit' => 'KILOGRAMS'
                ],
                // Allegro WZA oczekuje pól length/width/height bezpośrednio w paczce (nie w obiekcie "dimensions").
                // W przeciwnym wypadku zwraca błąd: input.packages[0].length (brak wymaganych pól).
                'length' => [
                    'value' => (string)(int)($dims['length'] ?? 10),
                    'unit' => 'CENTIMETER'
                ],
                'width' => [
                    'value' => (string)(int)($dims['width'] ?? 10),
                    'unit' => 'CENTIMETER'
                ],
                'height' => [
                    'value' => (string)(int)($dims['height'] ?? 10),
                    'unit' => 'CENTIMETER'
                ],
                'content' => 'Towary handlowe'
            ]]
        ];
    }

    private function limitText($text, $maxLen)
    {
        $text = (string)$text;
        $text = trim(preg_replace('/\s+/u', ' ', $text));
        if ($maxLen > 0 && function_exists('mb_strlen') && function_exists('mb_substr')) {
            if (mb_strlen($text, 'UTF-8') > $maxLen) {
                $text = mb_substr($text, 0, $maxLen, 'UTF-8');
            }
        } else {
            if ($maxLen > 0 && strlen($text) > $maxLen) {
                $text = substr($text, 0, $maxLen);
            }
        }
        return $text;
    }
}
