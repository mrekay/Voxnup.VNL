<?php
/*!
 * ============================================================
 *  Voxnup Not Laravel (VNL) — yayın imzası (Ed25519)
 *  release imzalar, update doğrular.
 *  Authors: Voxnup Authors
 * ============================================================
 *
 * Neden: 3.x güncelleyicisi arşivi yalnız arşivin KENDİ MANIFEST'ine göre
 * sınıyordu. MANIFEST arşivin içinde geldiği için bu bozulmayı yakalar,
 * kurcalamayı yakalamaz — arşivi değiştiren MANIFEST'i de değiştirir.
 * İmza arşivin baytlarına atılır ve açık anahtar KURULU çekirdekte
 * (vnl/KEYS) durur; indirilen arşiv kendi doğrulayıcısını getiremez.
 *
 * Biçimler (satır tabanlı, '#' yorum):
 *
 *   vnl/KEYS                 ed25519 <anahtar-kimliği> <açık-anahtar-base64> [not]
 *   vnl-X.Y.Z.tar.gz.sig     ed25519 <anahtar-kimliği> <imza-base64>
 *
 * Anahtar kimliği açık anahtarın sha256'sının ilk 16 onaltılık hanesidir.
 * KEYS'te birden çok satır olabilir: anahtar değiştirirken yeni sürüm hem
 * eski hem yeni anahtarı taşır, bir sonraki sürüm yenisiyle imzalanır.
 */

final class VnlSignature
{
    public static function available(): bool
    {
        return function_exists('sodium_crypto_sign_detached')
            && function_exists('sodium_crypto_sign_verify_detached');
    }

    public static function keyId(string $publicKey): string
    {
        return substr(hash('sha256', $publicKey), 0, 16);
    }

    /**
     * Yeni anahtar çifti. 'secret' 32 baytlık tohumdur (base64) — VNL_RELEASE_KEY
     * olarak saklanır; 'line' KEYS'e yazılacak satırdır.
     *
     * @return array{secret:string,public:string,id:string,line:string}
     */
    public static function generate(): array
    {
        self::requireSodium();

        $seed   = random_bytes(SODIUM_CRYPTO_SIGN_SEEDBYTES);
        $public = sodium_crypto_sign_publickey(sodium_crypto_sign_seed_keypair($seed));
        $id     = self::keyId($public);

        return [
            'secret' => base64_encode($seed),
            'public' => base64_encode($public),
            'id'     => $id,
            'line'   => 'ed25519 ' . $id . ' ' . base64_encode($public),
        ];
    }

    /**
     * Gizli anahtarı (base64: 32 baytlık tohum ya da 64 baytlık anahtar)
     * çözer. @return array{secret:string,public:string,id:string}
     */
    public static function secretKey(string $encoded): array
    {
        self::requireSodium();

        $raw = base64_decode(trim($encoded), true);

        if ($raw !== false && strlen($raw) === SODIUM_CRYPTO_SIGN_SEEDBYTES) {
            $pair = sodium_crypto_sign_seed_keypair($raw);
        } elseif ($raw !== false && strlen($raw) === SODIUM_CRYPTO_SIGN_SECRETKEYBYTES) {
            $pair = sodium_crypto_sign_keypair_from_secretkey_and_publickey($raw, sodium_crypto_sign_publickey_from_secretkey($raw));
        } else {
            throw new RuntimeException('Gizli anahtar okunamadi: base64 kodlu 32 baytlik tohum (ya da 64 baytlik anahtar) bekleniyor.');
        }

        $public = sodium_crypto_sign_publickey($pair);

        return [
            'secret' => sodium_crypto_sign_secretkey($pair),
            'public' => $public,
            'id'     => self::keyId($public),
        ];
    }

    /** İmza dosyasının içeriği. */
    public static function sign(string $data, string $encodedSecret): string
    {
        $key = self::secretKey($encodedSecret);

        return 'ed25519 ' . $key['id'] . ' ' . base64_encode(sodium_crypto_sign_detached($data, $key['secret'])) . "\n";
    }

    /**
     * KEYS içeriğini okur. Kimliği açık anahtarla uyuşmayan ya da bozuk satır
     * yok sayılır — elle yazılmış bir kimlik başka bir anahtarı taklit edemez.
     *
     * @return array<string,string> kimlik => ham açık anahtar
     */
    public static function parseKeys(string $content): array
    {
        $keys = [];

        foreach (preg_split('/\R/', $content) as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#') {
                continue;
            }

            if (!preg_match('/^ed25519\s+([0-9a-f]{16})\s+([A-Za-z0-9+\/=]+)(?:\s|$)/', $line, $m)) {
                continue;
            }

            $public = base64_decode($m[2], true);
            if ($public === false || strlen($public) !== 32 || self::keyId($public) !== $m[1]) {
                continue;
            }

            $keys[$m[1]] = $public;
        }

        return $keys;
    }

    /** @return array{id:string,signature:string}|null */
    public static function parseSignature(string $content): ?array
    {
        foreach (preg_split('/\R/', $content) as $line) {
            $line = trim($line);

            if (preg_match('/^ed25519\s+([0-9a-f]{16})\s+([A-Za-z0-9+\/=]+)$/', $line, $m)) {
                $signature = base64_decode($m[2], true);

                if ($signature !== false && strlen($signature) === 64) {
                    return ['id' => $m[1], 'signature' => $signature];
                }
            }
        }

        return null;
    }

    /**
     * Doğrular; geçmezse sebebiyle birlikte istisna atar.
     *
     * @param array<string,string> $keys parseKeys() çıktısı
     * @return string imzalayan anahtarın kimliği
     */
    public static function verify(string $data, string $signatureFile, array $keys): string
    {
        self::requireSodium();

        $sig = self::parseSignature($signatureFile);
        if ($sig === null) {
            throw new VnlSignatureInvalid('Imza dosyasi okunamadi (bicim: "ed25519 <kimlik> <base64>").');
        }

        if (!isset($keys[$sig['id']])) {
            throw new VnlSignatureInvalid(
                "Arsiv {$sig['id']} anahtariyla imzalanmis; bu anahtar kurulu cekirdegin vnl/KEYS dosyasinda yok."
            );
        }

        if (!sodium_crypto_sign_verify_detached($sig['signature'], $data, $keys[$sig['id']])) {
            throw new VnlSignatureInvalid('IMZA GECERSIZ: arsiv imzalandiktan sonra degistirilmis ya da imza baska bir arsive ait.');
        }

        return $sig['id'];
    }

    private static function requireSodium(): void
    {
        if (!self::available()) {
            throw new RuntimeException('PHP sodium eklentisi yok; imza islenemiyor.');
        }
    }
}

/** İmza var ama geçmiyor. --insecure bunu AŞAMAZ. */
final class VnlSignatureInvalid extends RuntimeException
{
}
