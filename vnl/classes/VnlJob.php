<?php
/*!
 * ============================================================
 *  Voxnup Not Laravel (VNL) — Kuyruk İşi
 *  Çalışan işin işleyiciye verilen tanıtıcısı.
 *  Authors: Voxnup Authors
 * ============================================================
 */

/**
 * İşleyicinin ikinci argümanı:
 *
 *   return function (array $payload, VnlJob $job): void {
 *       $job->progress(50, 'Yapılandırma yazıldı');
 *       $job->log('nginx -t: ok');
 *   };
 *
 * progress() ve log() her çağrıda veritabanına yazar — ilerleme ekranı
 * (Queue::status) işi çalışırken izleyebilir. Çıktı MEDIUMTEXT'tir;
 * log() satırları sona eklenir.
 */
class VnlJob
{
    private array $row;

    public function __construct(array $row)
    {
        $this->row = $row;
    }

    public function id(): int
    {
        return (int)$this->row['id'];
    }

    public function type(): string
    {
        return (string)$this->row['type'];
    }

    public function queue(): string
    {
        return (string)$this->row['queue'];
    }

    public function payload(): array
    {
        $payload = json_decode((string)($this->row['payload'] ?? ''), true);

        return is_array($payload) ? $payload : [];
    }

    /** Bu çalıştırma dahil kaçıncı deneme. */
    public function attempts(): int
    {
        return (int)$this->row['attempts'];
    }

    public function maxAttempts(): int
    {
        return (int)$this->row['max_attempts'];
    }

    /** İşi kuyruğa yazan kullanıcı (web'den geldiyse). */
    public function createdBy(): ?int
    {
        return isset($this->row['created_by']) ? (int)$this->row['created_by'] : null;
    }

    /** İlerleme yüzdesi (0–100); not verilirse çıktıya da bir satır düşer. */
    public function progress(int $percent, string $note = ''): void
    {
        $percent = max(0, min(100, $percent));

        Database::table(Queue::TABLE)->where('id', $this->id())->update([
            'progress'   => $percent,
            'updated_at' => Queue::now(),
        ]);

        if ($note !== '') {
            $this->log('[' . $percent . '%] ' . $note);
        }
    }

    /** Çıktıya bir satır ekler (zaman damgalı). */
    public function log(string $line): void
    {
        $table = Queue::rawTable();

        Database::connection()->update(
            "UPDATE `{$table}` SET `output` = CONCAT(COALESCE(`output`, ''), ?), `updated_at` = NOW() WHERE `id` = ?",
            ['[' . date('H:i:s') . '] ' . rtrim($line, "\r\n") . "\n", $this->id()]
        );
    }

    /** Satırın ham hali (tanılama için). */
    public function toArray(): array
    {
        return $this->row;
    }
}
