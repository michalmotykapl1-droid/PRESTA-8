<?php
namespace AllegroPro\Repository;

use Db;

class MsgMessageRepository
{
    private string $table;

    public function __construct()
    {
        $this->table = _DB_PREFIX_ . 'allegropro_msg_message';
    }

    /**
     * MAX(created_at_allegro) jako UNIX timestamp dla danego (konto + wątek).
     * Używane do przyrostowego pobierania messages dla konkretnego wątku.
     */
    public function getThreadMaxCreatedTs(int $accountId, string $threadId, ?string $cutoffMysql = null): int
    {
        $where = 'id_allegropro_account=' . (int)$accountId . " AND thread_id='" . pSQL($threadId) . "'";
        if ($cutoffMysql) {
            $where .= " AND created_at_allegro IS NOT NULL AND created_at_allegro >= '" . pSQL($cutoffMysql) . "'";
        }
        $val = Db::getInstance()->getValue(
            'SELECT UNIX_TIMESTAMP(MAX(created_at_allegro)) FROM `' . pSQL($this->table) . '` WHERE ' . $where
        );
        return (int)($val ?: 0);
    }

    /**
     * Upsert pojedynczej wiadomości z /messaging/threads/{threadId}/messages
     */
    public function upsertFromApi(int $accountId, string $threadId, array $msg): bool
    {
        $createdIso = (string)($msg['createdAt'] ?? ($msg['createdAtDateTime'] ?? ($msg['created_at'] ?? '')));
        $createdAt = $this->isoToMysql($createdIso);

        $authorLogin = null;
        $authorIsInterlocutor = 0;
        if (isset($msg['author']) && is_array($msg['author'])) {
            $authorLogin = $msg['author']['login'] ?? null;
            $authorIsInterlocutor = !empty($msg['author']['isInterlocutor']) ? 1 : 0;
        }

        $text = null;
        if (isset($msg['text'])) {
            $text = (string)$msg['text'];
        } elseif (isset($msg['body'])) {
            $text = (string)$msg['body'];
        }

        // ID wiadomości – standardowo Allegro zwraca msg.id.
        // Zdarza się jednak, że w niektórych przypadkach obiekt może nie mieć ID.
        // Wtedy tworzymy stabilny "syntetyczny" identyfikator, aby nie gubić treści w chacie.
        $messageId = (string)($msg['id'] ?? ($msg['messageId'] ?? ''));
        if ($messageId === '') {
            $seed = $threadId . '|' . ($createdIso ?: '') . '|' . ($authorLogin ?: '') . '|' . ($authorIsInterlocutor ? '1' : '0') . '|' . (string)($text ?? '');
            $messageId = 'synt_' . substr(sha1($seed), 0, 40);
        }

        // Załączniki
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

        $sql = 'INSERT INTO `' . pSQL($this->table) . '` (
                    id_allegropro_account, thread_id, message_id, created_at_allegro,
                    author_login, author_is_interlocutor, text, has_attachments, attachments_json,
                    payload_json, is_synced, synced_at, created_at, updated_at
                ) VALUES (
                    ' . (int)$accountId . ',
                    \'' . pSQL($threadId) . '\',
                    \'' . pSQL($messageId) . '\',
                    ' . ($createdAt ? ('\'' . pSQL($createdAt) . '\'') : 'NULL') . ',
                    ' . ($authorLogin === null ? 'NULL' : ('\'' . pSQL((string)$authorLogin) . '\'')) . ',
                    ' . (int)$authorIsInterlocutor . ',
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
                    author_login = VALUES(author_login),
                    author_is_interlocutor = VALUES(author_is_interlocutor),
                    text = VALUES(text),
                    has_attachments = VALUES(has_attachments),
                    attachments_json = VALUES(attachments_json),
                    payload_json = VALUES(payload_json),
                    updated_at = VALUES(updated_at),
                    is_synced = 1,
                    synced_at = IF(synced_at IS NULL, VALUES(synced_at), synced_at)';

        return (bool)Db::getInstance()->execute($sql);
    }

    public function listByThread(int $accountId, string $threadId, int $limit = 200, int $offset = 0): array
    {
        $limit = max(1, min(5000, (int)$limit));
        $offset = max(0, (int)$offset);

        $rows = Db::getInstance()->executeS('SELECT
                message_id,
                created_at_allegro,
                author_login,
                author_is_interlocutor,
                text,
                has_attachments,
                attachments_json
            FROM `' . pSQL($this->table) . '`
            WHERE id_allegropro_account=' . (int)$accountId . " AND thread_id='" . pSQL($threadId) . "'"
            . ' ORDER BY created_at_allegro ASC, id_allegropro_msg_message ASC'
            . ' LIMIT ' . (int)$limit . ' OFFSET ' . (int)$offset) ?: [];

        return $rows;
    }

    public function countByThread(int $accountId, string $threadId): int
    {
        $val = Db::getInstance()->getValue(
            'SELECT COUNT(*) FROM `' . pSQL($this->table) . '`'
            . ' WHERE id_allegropro_account=' . (int)$accountId
            . " AND thread_id='" . pSQL($threadId) . "'"
        );
        return (int)($val ?: 0);
    }

    /**
     * Zwraca ostatnie N wiadomości wątku (w kolejności rosnącej po dacie),
     * żeby UI zawsze pokazywał świeże wpisy (a nie "pierwsze" z historii).
     */
    public function listTailByThread(int $accountId, string $threadId, int $limit = 400): array
    {
        $limit = max(1, min(5000, (int)$limit));
        $total = $this->countByThread($accountId, $threadId);
        $offset = max(0, $total - $limit);
        return $this->listByThread($accountId, $threadId, $limit, $offset);
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
