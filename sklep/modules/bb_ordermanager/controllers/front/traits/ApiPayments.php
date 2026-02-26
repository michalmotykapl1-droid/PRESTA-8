<?php
trait ApiPayments
{
    protected function getPublicLink() { $id_order = (int)Tools::getValue('id_order'); if (!$id_order) throw new Exception("Brak ID"); $db = Db::getInstance(); $order = $db->getRow('SELECT reference, secure_key FROM `' . _DB_PREFIX_ . 'orders` WHERE id_order = ' . $id_order); $token = md5($order['reference'] . $order['secure_key']); $shopUrl = Tools::getShopDomainSsl(true) . __PS_BASE_URI__; $link = $shopUrl . 'index.php?fc=module&module=bb_ordermanager&controller=info&id_order=' . $id_order . '&token=' . $token; echo json_encode(['success' => true, 'link' => $link]); die(); }
    
    protected function updatePayment()
    {
        $id_order = (int)Tools::getValue('id_order');
        $amount = (float)Tools::getValue('amount');
        if (!$id_order) {
            throw new Exception("Brak ID");
        }

        $db = Db::getInstance();

        $old = (float)$db->getValue('SELECT total_paid_real FROM `' . _DB_PREFIX_ . 'orders` WHERE id_order = ' . (int)$id_order);

        $db->update('orders', ['total_paid_real' => $amount], 'id_order = ' . (int)$id_order);

        // Log audytowy
        $msg = 'PŁATNOŚĆ: Zmieniono "Wpłacono" na ' . number_format($amount, 2, '.', '') . ' (było ' . number_format($old, 2, '.', '') . ')';
        $this->addSystemLog(
            $id_order,
            $msg,
            'PAYMENT_UPDATE',
            [
                'field' => 'total_paid_real',
                'old' => (float) $old,
                'new' => (float) $amount,
            ]
        );

        echo json_encode(['success' => true]);
        die();
    }
    
    protected function generatePaymentLink()
    {
        $id_order = (int)Tools::getValue('id_order');
        $customAmount = (float)Tools::getValue('amount');
        if (!$id_order) {
            throw new Exception("Brak ID");
        }

        $db = Db::getInstance();
        $order = $db->getRow('SELECT id_order, reference, total_paid, total_paid_real, id_currency, id_customer, id_cart FROM `' . _DB_PREFIX_ . 'orders` WHERE id_order = ' . (int)$id_order);
        if (!$order) {
            throw new Exception("Nie znaleziono");
        }

        $customer = $db->getRow('SELECT email, firstname, lastname FROM `' . _DB_PREFIX_ . 'customer` WHERE id_customer = ' . (int)$order['id_customer']);

        if ($customAmount > 0) {
            $amount = $customAmount;
        } else {
            $to_pay = (float)$order['total_paid'];
            $paid = (float)$order['total_paid_real'];
            $amount = $to_pay - $paid;
        }

        if ($amount <= 0.01) {
            throw new Exception("Kwota > 0.01");
        }

        $merchantId = (int)Configuration::get('P24_MERCHANT_ID');
        $crcKey = Configuration::get('P24_SALT');
        $apiKey = Configuration::get('P24_API_KEY');
        $isTest = (int)Configuration::get('P24_TEST_MODE') === 1;
        if (!$merchantId || !$crcKey) {
            throw new Exception("Brak Config");
        }

        $baseUrl = $isTest ? 'https://sandbox.przelewy24.pl' : 'https://secure.przelewy24.pl';
        $registerUrl = $baseUrl . '/api/v1/transaction/register';

        $sessionId = 'doplata_' . (int)$order['id_order'] . '_' . time();
        $amountInt = (int)round($amount * 100);
        $currency = 'PLN';
        $description = 'Dopłata ' . $order['reference'];
        $email = $customer['email'];
        $shopUrl = Tools::getShopDomainSsl(true) . __PS_BASE_URI__;

        $signString = '{"sessionId":"' . $sessionId . '","merchantId":' . $merchantId . ',"amount":' . $amountInt . ',"currency":"' . $currency . '","crc":"' . $crcKey . '"}';
        $sign = hash('sha384', $signString);

        $postData = [
            'merchantId' => $merchantId,
            'posId' => $merchantId,
            'sessionId' => $sessionId,
            'amount' => $amountInt,
            'currency' => $currency,
            'description' => $description,
            'email' => $email,
            'client' => $customer['firstname'] . ' ' . $customer['lastname'],
            'country' => 'PL',
            'language' => 'pl',
            'urlReturn' => $shopUrl,
            'urlStatus' => $shopUrl,
            'sign' => $sign
        ];

        $ch = curl_init($registerUrl);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Basic ' . base64_encode($merchantId . ':' . $apiKey)
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $res = json_decode($response, true);

        if ($httpCode == 200 && isset($res['data']['token'])) {
            $token = $res['data']['token'];
            $paymentLink = $baseUrl . '/trnRequest/' . $token;

            // Log audytowy
            $this->addSystemLog(
                $id_order,
                'PŁATNOŚĆ: Wygenerowano link dopłaty P24 na kwotę ' . number_format($amount, 2, '.', ''),
                'PAYMENT_LINK',
                [
                    'provider' => 'P24',
                    'amount' => (float) $amount,
                    'session_id' => (string) $sessionId,
                ]
            );

            if (ob_get_length()) {
                ob_clean();
            }
            echo json_encode([
                'success' => true,
                'link' => $paymentLink,
                'amount' => $amount
            ]);
            die();
        }

        throw new Exception("Błąd P24");
    }
}