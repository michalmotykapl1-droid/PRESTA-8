<?php
namespace AllegroPro\Repository;

use Db;

class IssueChatRepository
{
    private string $table;

    public function __construct()
    {
        $this->table = _DB_PREFIX_ . 'allegropro_issue_chat';
    }

    /**
     * MAX(created_at_allegro) jako UNIX timestamp dla danego (konto + issue)
     * (do delta sync chatu)
     */
    public function getIssueMaxCreatedTs(int $accountId, string $issueId, ?string $cutoffMysql = null): int
    {
        $where = 'id_allegropro_account=' . (int)$accountId . " AND issue_id='" . pSQL($issueId) . "'";
        if ($cutoffMysql) {
            $where .= " AND created_at_allegro IS NOT NULL AND created_at_allegro >= '" . pSQL($cutoffMysql) . "'";
        }
        $val = Db::getInstance()->getValue(
            'SELECT UNIX_TIMESTAMP(MAX(created_at_allegro)) FROM `' . pSQL($this->table) . '` WHERE ' . $where
        );
        return (int)($val ?: 0);
    }

    /**
     * Upsert pojedynczej wiadomości z /sale/issues/{issueId}/chat
     */
    public function upsertFromApi(int $accountId, string $issueId, array $msg): bool
    {
        $uid = (string)($msg['id'] ?? ($msg['messageId'] ?? ($msg['uid'] ?? '')));
        if ($uid === '') {
            return false;
        }

        $createdIso = (string)($msg['createdAt'] ?? ($msg['createdAtDateTime'] ?? ($msg['created_at'] ?? '')));
        $createdAt = $this->isoToMysql($createdIso);

        $authorRole = null;
        $authorLogin = null;
        if (!empty($msg['author']) && is_array($msg['author'])) {
            $authorRole = $msg['author']['role'] ?? null;
            $authorLogin = $msg['author']['login'] ?? null;
        }

        $text = null;
        if (isset($msg['text'])) {
            $text = (string)$msg['text'];
        } elseif (isset($msg['message'])) {
            $text = (string)$msg['message'];
        } elseif (isset($msg['body'])) {
            $text = (string)$msg['body'];
        }

        $attachments = null;
        $hasAttachments = 0;
        if (!empty($msg['attachments']) && is_array($msg['attachments'])) {
            $attachments = $msg['attachments'];
            $hasAttachments = 1;
        }
        if (!empty($msg['additionalAttachments']) && is_array($msg['additionalAttachments'])) {
            $attachments = $msg['additionalAttachments'];
            $hasAttachments = 1;
        }
        if (isset($msg['hasAdditionalAttachments'])) {
            $hasAttachments = (int)((bool)$msg['hasAdditionalAttachments']);
        }

        $payload = json_encode($msg, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $attJson = $attachments ? json_encode($attachments, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null;

        $now = date('Y-m-d H:i:s');

        // Uwaga: tabela w bazie może nie mieć kolumny author_login (nie wymuszamy migracji)
        // Login doklejamy przy listowaniu na podstawie payload_json.
        $sql = 'INSERT INTO `' . pSQL($this->table) . '` (
                    id_allegropro_account, issue_id, msg_uid, created_at_allegro,
                    author_role, text, has_attachments, attachments_json, payload_json,
                    is_synced, synced_at, created_at, updated_at
                ) VALUES (
                    ' . (int)$accountId . ',
                    \'' . pSQL($issueId) . '\',
                    \'' . pSQL($uid) . '\',
                    ' . ($createdAt ? ('\'' . pSQL($createdAt) . '\'') : 'NULL') . ',
                    ' . ($authorRole === null ? 'NULL' : ('\'' . pSQL((string)$authorRole) . '\'')) . ',
                    ' . ($text === null ? 'NULL' : ('\'' . pSQL($text, true) . '\'')) . ',
                    ' . (int)$hasAttachments . ',
                    ' . ($attJson ? ('\'' . pSQL($attJson, true) . '\'') : 'NULL') . ',
                    ' . ($payload ? ('\'' . pSQL($payload, true) . '\'') : 'NULL') . ',
                    1,
                    \'' . pSQL($now) . '\',
                    \'' . pSQL($now) . '\',
                    \'' . pSQL($now) . '\'
                )
                ON DUPLICATE KEY UPDATE
                    created_at_allegro = VALUES(created_at_allegro),
                    author_role = VALUES(author_role),
                    text = VALUES(text),
                    has_attachments = VALUES(has_attachments),
                    attachments_json = VALUES(attachments_json),
                    payload_json = VALUES(payload_json),
                    updated_at = VALUES(updated_at),
                    is_synced = 1,
                    synced_at = IF(synced_at IS NULL, VALUES(synced_at), synced_at)';

        return (bool)Db::getInstance()->execute($sql);
    }

    public function listByIssue(int $accountId, string $issueId, int $limit = 200, int $offset = 0): array
    {
        $limit = max(1, min(1000, (int)$limit));
        $offset = max(0, (int)$offset);

        $rows = Db::getInstance()->executeS('SELECT
                msg_uid,
                created_at_allegro,
                author_role,
                text,
                has_attachments,
                attachments_json,
                payload_json
            FROM `' . pSQL($this->table) . '`
            WHERE id_allegropro_account=' . (int)$accountId . " AND issue_id='" . pSQL($issueId) . "'"
            . ' ORDER BY created_at_allegro ASC, id_allegropro_issue_chat ASC'
            . ' LIMIT ' . (int)$limit . ' OFFSET ' . (int)$offset) ?: [];

        // author_login wyciągamy z payload_json (bez migracji DB)
        foreach ($rows as &$r) {
            $r['author_login'] = null;
            if (!empty($r['payload_json'])) {
                $p = json_decode((string)$r['payload_json'], true);
                if (is_array($p) && isset($p['author']) && is_array($p['author'])) {
                    $r['author_login'] = $p['author']['login'] ?? null;
                }
            }
            unset($r['payload_json']);
        }
        unset($r);

        return $rows;
    }

    private function isoToMysql(?string $iso): ?string
    {
        $iso = $iso !== null ? trim((string)$iso) : '';
        if ($iso === '') {
            return null;
        }
        $ts = strtotime($iso);
        if (!$ts) {
            return null;
        }
        return date('Y-m-d H:i:s', $ts);
    }
}
