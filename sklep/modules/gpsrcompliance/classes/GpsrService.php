<?php
if (!defined('_PS_VERSION_')) { exit; }

class GpsrService
{
    public static function getProducers()
    { return Db::getInstance()->executeS('SELECT * FROM '._DB_PREFIX_.'gpsr_producer WHERE active=1 ORDER BY name ASC') ?: []; }
    public static function getPersons()
    { return Db::getInstance()->executeS('SELECT * FROM '._DB_PREFIX_.'gpsr_person WHERE active=1 ORDER BY name ASC') ?: []; }

    public function getProducersOptions()
    { $o = [['id'=>0,'name'=>'— wybierz —']]; foreach (self::getProducers() as $p) { $o[] = ['id'=>(int)$p['id_gpsr_producer'],'name'=>$p['name']]; } return $o; }
    public function getPersonsOptions()
    { $o = [['id'=>0,'name'=>'— wybierz —']]; foreach (self::getPersons() as $p) { $o[] = ['id'=>(int)$p['id_gpsr_person'],'name'=>$p['name']]; } return $o; }

    public function getBrandRecord($id_manufacturer)
    {
        return Db::getInstance()->getRow('SELECT * FROM '._DB_PREFIX_.'gpsr_brand_map WHERE id_manufacturer='.(int)$id_manufacturer) ?: null;
    }
    public static function saveBrandRecord($id_manufacturer, $id_gpsr_producer, $id_gpsr_person)
    {
        $exists = Db::getInstance()->getValue('SELECT 1 FROM '._DB_PREFIX_.'gpsr_brand_map WHERE id_manufacturer='.(int)$id_manufacturer);
        $data = ['id_manufacturer'=>(int)$id_manufacturer,'id_gpsr_producer'=>$id_gpsr_producer?:null,'id_gpsr_person'=>$id_gpsr_person?:null];
        if ($exists) { Db::getInstance()->update('gpsr_brand_map', $data, 'id_manufacturer='.(int)$id_manufacturer); }
        else { Db::getInstance()->insert('gpsr_brand_map', $data, false, true, Db::REPLACE); }
    }

    public function getProductRecord($id_product)
    { return Db::getInstance()->getRow('SELECT * FROM '._DB_PREFIX_.'gpsr_product WHERE id_product='.(int)$id_product) ?: null; }
    public static function saveProductRecord($id_product, $id_gpsr_producer, $id_gpsr_person, $extra_info)
    {
        $exists = Db::getInstance()->getValue('SELECT 1 FROM '._DB_PREFIX_.'gpsr_product WHERE id_product='.(int)$id_product);
        $data = ['id_product'=>(int)$id_product,'id_gpsr_producer'=>$id_gpsr_producer?:null,'id_gpsr_person'=>$id_gpsr_person?:null,'extra_info'=>pSQL($extra_info, true)];
        if ($exists) { Db::getInstance()->update('gpsr_product',$data,'id_product='.(int)$id_product); }
        else { Db::getInstance()->insert('gpsr_product',$data,false,true,Db::REPLACE); }
    }

    public function resolve($id_product)
    {
        $id_product = (int)$id_product;
        $prod = null; $pers = null; $extra = '';

        if ($id_product > 0) {
            $rowp = $this->getProductRecord($id_product);
            if (is_array($rowp) && !empty($rowp)) {
                $extra = (string)$rowp['extra_info'];
                if (!empty($rowp['id_gpsr_producer'])) {
                    $prod = Db::getInstance()->getRow('SELECT * FROM '._DB_PREFIX_.'gpsr_producer WHERE id_gpsr_producer='.(int)$rowp['id_gpsr_producer']);
                }
                if (!empty($rowp['id_gpsr_person'])) {
                    $pers = Db::getInstance()->getRow('SELECT * FROM '._DB_PREFIX_.'gpsr_person WHERE id_gpsr_person='.(int)$rowp['id_gpsr_person']);
                }
            }

            if (!$prod || !$pers) {
                $brand = Db::getInstance()->getRow('
                    SELECT m.id_manufacturer, bm.id_gpsr_producer, bm.id_gpsr_person
                    FROM '._DB_PREFIX_.'product p
                    LEFT JOIN '._DB_PREFIX_.'manufacturer m ON (m.id_manufacturer=p.id_manufacturer)
                    LEFT JOIN '._DB_PREFIX_.'gpsr_brand_map bm ON (bm.id_manufacturer=m.id_manufacturer)
                    WHERE p.id_product='.(int)$id_product);
                if (is_array($brand) && !empty($brand)) {
                    if (!$prod && !empty($brand['id_gpsr_producer'])) {
                        $prod = Db::getInstance()->getRow('SELECT * FROM '._DB_PREFIX_.'gpsr_producer WHERE id_gpsr_producer='.(int)$brand['id_gpsr_producer']);
                    }
                    if (!$pers && !empty($brand['id_gpsr_person'])) {
                        $pers = Db::getInstance()->getRow('SELECT * FROM '._DB_PREFIX_.'gpsr_person WHERE id_gpsr_person='.(int)$brand['id_gpsr_person']);
                    }
                }
            }
        }

        if (!is_array($prod) || empty($prod)) {
            $prod = [
                'name'=> (string)Configuration::get('GPSR_RESP_NAME'),
                'address'=> (string)Configuration::get('GPSR_RESP_ADDRESS'),
                'email'=> (string)Configuration::get('GPSR_RESP_EMAIL'),
                'phone'=> (string)Configuration::get('GPSR_RESP_PHONE'),
                'country'=>'','postcode'=>'','city'=>'','info'=>'',
            ];
        }
        if (!is_array($pers) || empty($pers)) {
            $pers = $prod;
        }

        $text = self::renderText($id_product, $prod, $pers, $extra);
        return ['producer'=>$prod, 'person'=>$pers, 'text'=>$text];
    }

    public static function renderText($id_product, array $prod, array $pers, $extra)
    {
        $tpl = (string)Configuration::get('GPSR_TEMPLATE');
        $productRow = null;
        if ((int)$id_product > 0) {
            $productRow = Db::getInstance()->getRow('
                SELECT pl.name, p.reference, p.ean13, m.name AS brand
                FROM '._DB_PREFIX_.'product p
                LEFT JOIN '._DB_PREFIX_.'product_lang pl ON (pl.id_product=p.id_product AND pl.id_lang='.(int)Context::getContext()->language->id.')
                LEFT JOIN '._DB_PREFIX_.'manufacturer m ON (m.id_manufacturer=p.id_manufacturer)
                WHERE p.id_product='.(int)$id_product);
        }
        $repl = [
            '{RESP_NAME}' => $prod['name'] ?? '',
            '{RESP_ADDRESS}' => $prod['address'] ?? '',
            '{RESP_EMAIL}' => $prod['email'] ?? '',
            '{RESP_PHONE}' => $prod['phone'] ?? '',
            '{PRODUCT_NAME}' => ($productRow && isset($productRow['name'])) ? (string)$productRow['name'] : '',
            '{REFERENCE}' => ($productRow && isset($productRow['reference'])) ? (string)$productRow['reference'] : '',
            '{EAN}' => ($productRow && isset($productRow['ean13'])) ? (string)$productRow['ean13'] : '',
            '{BRAND}' => ($productRow && isset($productRow['brand'])) ? (string)$productRow['brand'] : '',
            '{DATE}' => date('Y-m-d'),
        ];
        $base = strtr($tpl, $repl);
        if (!empty($extra)) { $base .= "
".$extra; }
        return $base;
    }

    public static function buildSafetyParameters($id_category, $id_product, array $currentParameters = [])
    {
        $self = new self();
        $resolved = $self->resolve((int)$id_product);

        $selectParamId = (int)Configuration::get('GPSR_SAFETY_SELECT_PARAM_ID');
        $selectValueYesId = (int)Configuration::get('GPSR_SAFETY_SELECT_VALUE_YES_ID');
        $textParamId = (int)Configuration::get('GPSR_SAFETY_TEXT_PARAM_ID');

        $out = [];

        if ($selectParamId && $selectValueYesId) {
            $exists = false;
            foreach ($currentParameters as $p) { if ((int)$p['id'] === $selectParamId) { $exists = true; break; } }
            if (!$exists) { $out[] = ['id'=>$selectParamId, 'valuesIds'=>[$selectValueYesId]]; }
        }
        if ($textParamId) {
            $exists = false;
            foreach ($currentParameters as $p) { if ((int)$p['id'] === $textParamId) { $exists = true; break; } }
            if (!$exists) { $out[] = ['id'=>$textParamId, 'values'=>[$resolved['text']]]; }
        }
        return $out;
    }
}
