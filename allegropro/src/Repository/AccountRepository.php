<?php
namespace AllegroPro\Repository;

use Db;
use DbQuery;
use DateTime;
use Tools;

class AccountRepository
{
    private string $table;

    public function __construct()
    {
        $this->table = _DB_PREFIX_ . 'allegropro_account';
    }

    public function all(): array
    {
        $q = new DbQuery();
        $q->select('*')->from('allegropro_account')->orderBy('id_allegropro_account DESC');
        return Db::getInstance()->executeS($q) ?: [];
    }

    public function get(int $id): ?array
    {
        $q = new DbQuery();
        $q->select('*')->from('allegropro_account')->where('id_allegropro_account='.(int)$id);
        $row = Db::getInstance()->getRow($q);
        return $row ?: null;
    }

    public function create(string $label, bool $sandbox, bool $active, bool $isDefault): int
    {
        $now = date('Y-m-d H:i:s');
        if ($isDefault) {
            Db::getInstance()->execute('UPDATE `'.$this->table.'` SET is_default=0');
        }
        Db::getInstance()->insert('allegropro_account', [
            'label' => pSQL($label),
            'sandbox' => (int)$sandbox,
            'active' => (int)$active,
            'is_default' => (int)$isDefault,
            'created_at' => pSQL($now),
            'updated_at' => pSQL($now),
        ]);
        return (int) Db::getInstance()->Insert_ID();
    }

    public function update(int $id, array $data): bool
    {
        $data['updated_at'] = pSQL(date('Y-m-d H:i:s'));
        if (isset($data['is_default']) && (int)$data['is_default'] === 1) {
            Db::getInstance()->execute('UPDATE `'.$this->table.'` SET is_default=0');
        }
        return Db::getInstance()->update('allegropro_account', $data, 'id_allegropro_account='.(int)$id);
    }

    public function delete(int $id): bool
    {
        return Db::getInstance()->delete('allegropro_account', 'id_allegropro_account='.(int)$id);
    }

    public function setOauthState(int $id, string $state): bool
    {
        return $this->update($id, ['oauth_state' => pSQL($state)]);
    }

    public function findByOauthState(string $state): ?array
    {
        $q = new DbQuery();
        $q->select('*')->from('allegropro_account')->where("oauth_state='".pSQL($state)."'");
        $row = Db::getInstance()->getRow($q);
        return $row ?: null;
    }

    public function storeTokens(int $id, string $accessToken, string $refreshToken, ?int $expiresInSec): bool
    {
        $expiresAt = null;
        if ($expiresInSec) {
            $expiresAt = date('Y-m-d H:i:s', time() + $expiresInSec - 30);
        }
        return $this->update($id, [
            'access_token' => pSQL($accessToken, true),
            'refresh_token' => pSQL($refreshToken, true),
            'token_expires_at' => $expiresAt ? pSQL($expiresAt) : null,
        ]);
    }
}
