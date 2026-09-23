<?php
/*!
 * ============================================================
 *  Voxnup Not Laravel (VNL) — Cron İfadesi
 *  Beş alanlı cron ifadesinin verilen dakikada tutup tutmadığı.
 *  Authors: Voxnup Authors
 * ============================================================
 */

/**
 * Cron ifadesi eşleştirici (schedule:run kullanır).
 *
 *   dakika saat gün ay haftanın-günü
 *   VnlCron::isDue('*\/5 * * * *');            // şu an beşin katı dakika mı
 *   VnlCron::isDue('0 3 * * 1-5', $timestamp);
 *
 * Desteklenen: '*', 'n', 'a-b', '*\/n', 'a-b/n', 'a,b,c' ve kısaltmalar
 * @yearly @annually @monthly @weekly @daily @midnight @hourly.
 * Haftanın günü 0–7 (0 ve 7 pazar). Ay ve gün adları desteklenmez.
 *
 * Standart cron kuralı: gün (ayın günü) ve haftanın günü İKİSİ DE
 * kısıtlıysa, ikisinden BİRİ tutması yeterlidir.
 */
class VnlCron
{
    private const ALIASES = [
        '@yearly'   => '0 0 1 1 *',
        '@annually' => '0 0 1 1 *',
        '@monthly'  => '0 0 1 * *',
        '@weekly'   => '0 0 * * 0',
        '@daily'    => '0 0 * * *',
        '@midnight' => '0 0 * * *',
        '@hourly'   => '0 * * * *',
    ];

    /** [alt, üst] sınırlar: dakika, saat, gün, ay, haftanın günü */
    private const RANGES = [[0, 59], [0, 23], [1, 31], [1, 12], [0, 7]];

    public static function isDue(string $expression, ?int $timestamp = null): bool
    {
        $fields = self::parse($expression);
        $ts     = $timestamp ?? time();

        $values = [
            (int)date('i', $ts),
            (int)date('G', $ts),
            (int)date('j', $ts),
            (int)date('n', $ts),
            (int)date('w', $ts),
        ];

        if (!self::matches($fields[0], $values[0], 0)
            || !self::matches($fields[1], $values[1], 1)
            || !self::matches($fields[3], $values[3], 3)) {
            return false;
        }

        $domRestricted = $fields[2] !== '*';
        $dowRestricted = $fields[4] !== '*';

        $dom = self::matches($fields[2], $values[2], 2);
        $dow = self::matches($fields[4], $values[4], 4) || ($values[4] === 0 && self::matches($fields[4], 7, 4));

        if ($domRestricted && $dowRestricted) {
            return $dom || $dow;
        }

        return $dom && $dow;
    }

    /** İfade geçerli mi? (config/schedule.php doğrulaması için) */
    public static function isValid(string $expression): bool
    {
        try {
            $fields = self::parse($expression);

            /* -1 hiçbir parçayla eşleşmez: her parça sonuna kadar
               ayrıştırılır, geçersiz olan istisna fırlatır. */
            foreach ($fields as $i => $field) {
                self::matches($field, -1, $i);
            }

            return true;
        } catch (InvalidArgumentException $e) {
            return false;
        }
    }

    /* ---------------------------------------------------------------- */

    private static function parse(string $expression): array
    {
        $expression = trim($expression);
        $expression = self::ALIASES[strtolower($expression)] ?? $expression;

        $fields = preg_split('/\s+/', $expression);

        if (!is_array($fields) || count($fields) !== 5) {
            throw new InvalidArgumentException('Cron ifadesi beş alan olmalı: ' . $expression);
        }

        return $fields;
    }

    private static function matches(string $field, int $value, int $index): bool
    {
        [$min, $max] = self::RANGES[$index];

        foreach (explode(',', $field) as $part) {
            if ($part === '') {
                throw new InvalidArgumentException('Boş cron parçası.');
            }

            $step = 1;
            if (str_contains($part, '/')) {
                [$part, $stepText] = explode('/', $part, 2);

                if (!ctype_digit($stepText) || (int)$stepText < 1) {
                    throw new InvalidArgumentException('Geçersiz cron adımı: ' . $stepText);
                }
                $step = (int)$stepText;
            }

            if ($part === '*') {
                $from = $min;
                $to   = $max;
            } elseif (preg_match('/^(\d+)-(\d+)$/', $part, $m)) {
                $from = (int)$m[1];
                $to   = (int)$m[2];
            } elseif (ctype_digit($part)) {
                $from = (int)$part;
                $to   = $step > 1 ? $max : $from;
            } else {
                throw new InvalidArgumentException('Geçersiz cron parçası: ' . $part);
            }

            if ($from < $min || $to > $max || $from > $to) {
                throw new InvalidArgumentException('Cron değeri aralık dışında: ' . $part);
            }

            if ($value >= $from && $value <= $to && ($value - $from) % $step === 0) {
                return true;
            }
        }

        return false;
    }
}
