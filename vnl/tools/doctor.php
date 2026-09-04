<?php
/*!
 * ============================================================
 *  Voxnup Not Laravel (VNL) — doctor komutu
 *  Kurulumu bu çekirdeğin beklentisine karşı sınar.
 *  Authors: Voxnup Authors
 * ============================================================
 *
 * Neden gerekli: güncelleme yalnızca vnl/ dizinini değiştirir, projenin
 * dosyalarına dokunamaz. Ama bazı sürümler proje tarafında da bir şey
 * bekler — 3.0'ın index.php'si VNL_ROOT tanımlamak zorundadır, 2.x'ten
 * gelen index.php çekirdeği hiç yükleyemez. CHANGELOG bunu yazar ama
 * okunacağının garantisi yoktur; bu komut aynı şeyi sorar.
 */

return static function (array $args): int {
    if (in_array('--help', $args, true) || in_array('-h', $args, true)) {
        fwrite(STDOUT, <<<TXT

  Kurulumu bu cekirdegin beklentisine karsi sinar.

    php vnl.php doctor

  Guncelleme sonrasi calistirin: vnl/ yenilendiginde projenin
  tohumlanmis dosyalari (index.php, config/, view/static/ ...) eski
  kalmis olabilir.


TXT
        );

        return 0;
    }

    $checks = [];

    /** @return array{0:string,1:string,2:string} durum, baslik, aciklama */
    $check = static function (string $title, bool $ok, string $remedy, bool $fatal = true): array {
        return [$ok ? 'ok' : ($fatal ? 'fail' : 'warn'), $title, $ok ? '' : $remedy];
    };

    /* ---- Giriş noktası ---- */
    $index = @file_get_contents(VNL_ROOT . '/index.php');
    $checks[] = $check(
        'index.php var',
        is_string($index) && $index !== '',
        'Kokte index.php bulunamadi.'
    );
    $checks[] = $check(
        "index.php VNL_ROOT tanimliyor",
        is_string($index) && str_contains($index, 'VNL_ROOT'),
        "VNL 3.0'da on denetleyici VNL_ROOT'u sabitler. 2.x'ten gelen bir\n"
        . "        index.php cekirdegi yukleyemez. Sablondaki index.php ile degistirin."
    );
    $checks[] = $check(
        'index.php vnl/boot.php yukluyor',
        is_string($index) && str_contains($index, 'vnl/boot.php'),
        "Onyukleme artik libs/config.php degil vnl/boot.php."
    );

    /* ---- Yapılandırma ---- */
    $checks[] = $check(
        'config/definitions.php var',
        is_file(VNL_ROOT . '/config/definitions.php'),
        'Eskiden libs/config/config_definitions.php idi; config/definitions.php olmali.'
    );
    $checks[] = $check(
        'config/database.php var',
        is_file(VNL_ROOT . '/config/database.php'),
        'Eskiden libs/config/config_db.php idi; config/database.php olmali.'
    );
    $checks[] = $check(
        '.env var',
        is_file(VNL_ROOT . '/.env') || getenv('DB_DATABASE') !== false,
        '.env.example dosyasini .env olarak kopyalayip doldurun.',
        false
    );

    /* ---- Proje tarafında olması gerekenler ---- */
    $checks[] = $check(
        'languages/ dolu',
        glob(VNL_ROOT . '/languages/*.json') !== [],
        "Dil dosyalari VNL 3.0'da projeye ait: languages/tr.json, languages/en.json."
    );
    $checks[] = $check(
        'view/static/ duzen parcalari var',
        is_file(VNL_ROOT . '/view/static/header.php') && is_file(VNL_ROOT . '/view/static/footer.php'),
        'Cekirdek gorunum tasimaz; duzen parcalari projede olmali.'
    );
    $checks[] = $check(
        'controller/404.php var',
        is_file(VNL_ROOT . '/controller/404.php'),
        'Hata sayfalari projeye aittir; sablondan kopyalayin.'
    );

    $head = @file_get_contents(VNL_ROOT . '/view/includes/static/head-standard.php');
    $checks[] = $check(
        'head-standard.php cekirdek varlik listesini kullaniyor',
        is_string($head) && str_contains($head, 'vnl_core_styles'),
        "Varlik listesi elle yazilmis. Cekirdege yeni bir CSS eklendiginde\n"
        . "        yuklenmez. vnl_core_styles() donguse cevirin.",
        false
    );

    /* ---- Proje tarafındaki bilinen güvenlik tuzakları ----
       Bu iki dosya da projenindir: guncelleme onlara dokunamaz, dolayisiyla
       VNL 3.2'deki duzeltmeler mevcut kurulumlara kendiliginden ulasmaz.
       Bosluk burada kapatilir. */

    /* Yorumlar ayiklanir: duzeltilmis dosya eski hatayi aciklamak icin
       ayni ifadeyi YORUM icinde taşıyor, denetim ona takilmasin. */
    $auth = @file_get_contents(VNL_ROOT . '/actions/Auth.php');
    $authCode = '';

    if (is_string($auth)) {
        foreach (@token_get_all($auth) as $token) {
            $authCode .= is_array($token)
                ? (in_array($token[0], [T_COMMENT, T_DOC_COMMENT], true) ? ' ' : $token[1])
                : $token;
        }
    }

    $checks[] = $check(
        'Auth::CheckLogin() gercekten denetliyor',
        !is_string($auth) || !preg_match('/empty\s*\(\s*self::User\(\)\s*\)/', $authCode),
        "GUVENLIK: `!empty(self::User())` bir giris denetimi degildir.\n"
        . "        \$CurrentUser kosulsuz kurulur ve PHP'de nesne asla empty olmaz;\n"
        . "        denetim HER ZAMAN true doner, yani hic denetlemez. Olcut\n"
        . "        vnl_logged_in() olmali. Sablondaki actions/Auth.php ile degistirin."
    );

    $sidebar = @file_get_contents(VNL_ROOT . '/view/static/leftSidebar.php');
    $checks[] = $check(
        'Kenar cubugu yetki denetimi canli',
        !is_string($sidebar) || !preg_match('/^\s*\$pagePermission\s*=\s*true\s*;/m', $sidebar),
        "Menu yetki denetimi kisa devre edilmis (\$pagePermission = true).\n"
        . "        'permission' anahtari yazan menu ogeleri herkese gorunur.\n"
        . "        VNL 3.2 bunu vnl_can() kancasina bagladi; sablondaki\n"
        . "        view/static/leftSidebar.php ile degistirin.",
        false
    );

    /* ---- Eski sürümden kalıntı ---- */
    $checks[] = $check(
        'libs/ kalintisi yok',
        !is_dir(VNL_ROOT . '/libs'),
        "libs/ VNL 3.0'da dagitildi. Icinde kendi kodunuz yoksa silin."
    );
    $checks[] = $check(
        'assets/vnl/ kalintisi yok',
        !is_dir(VNL_ROOT . '/assets/vnl'),
        "Tema ciktisi artik vnl/assets/theme/. Eski dizin bayat dosya servis eder.",
        false
    );

    /* ---- Çekirdek ---- */
    $checks[] = $check('vnl/VERSION var', is_file(VNL_PATH . '/VERSION'), 'Cekirdek eksik ya da bozuk.');
    $checks[] = $check(
        'vnl/MANIFEST var',
        is_file(VNL_PATH . '/MANIFEST'),
        "Bu cekirdek yayin arsivinden degil depodan gelmis olabilir;\n"
        . "        'php vnl.php update --verify' calismaz.",
        false
    );

    /* ---- Çalışma ortamı ---- */
    $checks[] = $check(
        'PHP 8.2+',
        PHP_VERSION_ID >= 80200,
        'VNL 3.0 PHP 8.2 ister; kurulu surum ' . PHP_VERSION . '.'
    );

    foreach (['mbstring' => true, 'json' => true, 'pdo_mysql' => true, 'openssl' => true, 'zlib' => false] as $ext => $fatal) {
        $checks[] = $check(
            "eklenti: {$ext}",
            extension_loaded($ext),
            $ext === 'zlib'
                ? 'Guncelleyici sikistirilmis arsivi acamaz; sikistirilmamis .tar kullanin.'
                : "Bu eklenti olmadan VNL calismaz.",
            $fatal
        );
    }

    /* ---- Rapor ---- */
    $fails = 0;
    $warns = 0;

    fwrite(STDOUT, "\n  VNL kurulum denetimi\n\n");

    foreach ($checks as [$state, $title, $remedy]) {
        $mark = ['ok' => '[+]', 'warn' => '[!]', 'fail' => '[X]'][$state];
        fwrite(STDOUT, "    {$mark} {$title}\n");

        if ($remedy !== '') {
            fwrite(STDOUT, "        {$remedy}\n");
        }

        if ($state === 'fail') $fails++;
        if ($state === 'warn') $warns++;
    }

    fwrite(STDOUT, "\n  " . (count($checks) - $fails - $warns) . ' gecti, '
        . $warns . ' uyari, ' . $fails . " sorun\n\n");

    return $fails > 0 ? 1 : 0;
};
