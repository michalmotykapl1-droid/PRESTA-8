<?php
namespace AllegroPro\Repository;

use Db;
use DbQuery;

/**
 * Czyta dane płatności kupującego zapisane z checkout-form.
 * Źródło: tabela {prefix}_allegropro_order_payment.
 */
class OrderPaymentRepository
{
    public function getByCheckoutFormId(string $checkoutFormId): ?array
    {
        $cf = trim($checkoutFormId);
        if ($cf === '') {
            return null;
        }

        // Uwaga:
        // - Db::getRow() dopina własne LIMIT 1, więc NIE dodajemy LIMIT ręcznie.
        // - PK w tej tabeli to id_allegropro_payment (nie id_allegropro_order_payment).
        $q = new DbQuery();
        $q->select('*')
            ->from('allegropro_order_payment', 'op')
            ->where("op.checkout_form_id = '" . pSQL($cf) . "'")
            ->orderBy('op.id_allegropro_payment DESC');

        $row = Db::getInstance()->getRow($q);
        return (is_array($row) && !empty($row)) ? $row : null;
    }
}
