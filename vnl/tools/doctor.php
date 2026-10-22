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

/* .env okunur (APP_DEBUG, APP_ENCRYPTION_KEY denetimleri); uygulama açılmaz. */
require_once VNL_PATH . '/env.php';

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

    /* ---- 3.4.0: proje tarafındaki güvenlik dosyaları ----
       Aşağıdakilerin hepsi PROJENİN dosyalarıdır; güncelleme onları
       değiştiremez. 3.3'ten gelen bir kurulumda şablondaki karşılıklarıyla
       değiştirilmeleri gerekir (CHANGELOG 3.4.0 → Yükseltme). */

    /** PHP dosyasının yorumsuz hali (yorumdaki eski örnekler denetimi yanıltmasın). */
    $codeOf = static function (string $relative): ?string {
        $source = @file_get_contents(VNL_ROOT . '/' . $relative);

        if (!is_string($source)) {
            return null;
        }

        $code = '';
        foreach (@token_get_all($source) as $token) {
            $code .= is_array($token)
                ? (in_array($token[0], [T_COMMENT, T_DOC_COMMENT], true) ? ' ' : $token[1])
                : $token;
        }

        return $code;
    };

    $appCode = $codeOf('controller/app.php');
    $checks[] = $check(
        'controller/app.php 3.4 dagiticisini kullaniyor',
        $appCode !== null && str_contains($appCode, 'VnlDispatcher::handle'),
        "GUVENLIK: eski dagitici her HTTP yontemini kabul eder, CSRF denetlemez,\n"
        . "        girissiz istegi action'a ulastirir ve __construct dahil her metodu\n"
        . "        cagirir. Sablondaki controller/app.php ile degistirin\n"
        . "        (tek satir: VnlDispatcher::handle(\$req);)."
    );

    $loginCode = $codeOf('actions/action_login.php');
    $checks[] = $check(
        'action_login PUBLIC_METHODS tanimliyor',
        $loginCode === null || str_contains($loginCode, 'PUBLIC_METHODS'),
        "3.4 dagiticisi girisi varsayilan olarak zorunlu kilar; PUBLIC_METHODS\n"
        . "        olmadan giris formu 401 alir. Sablondaki actions/action_login.php\n"
        . "        ile degistirin (hiz siniri, TOTP ve POST cikis da onunla gelir)."
    );

    $checks[] = $check(
        'head-standard.php CSRF jetonunu basiyor',
        is_string($head) && str_contains($head, 'csrf-token'),
        "<meta name=\"csrf-token\"> yok: vnl.js ve VNL Table jeton gonderemez,\n"
        . "        her POST 403 VNL_CSRF alir. Ekleyin:\n"
        . "        <meta name=\"csrf-token\" content=\"<?= vnl_e(vnl_csrf_token()) ?>\">"
    );

    $hasPermissionHook = false;
    foreach (glob(VNL_ROOT . '/helpers/*.php') ?: [] as $helperFile) {
        if (preg_match('/function\s+vnl_project_permission\s*\(/', (string)@file_get_contents($helperFile))) {
            $hasPermissionHook = true;
            break;
        }
    }
    $checks[] = $check(
        'Yetki kancasi (vnl_project_permission) tanimli',
        $hasPermissionHook,
        "Kanca yoksa yetki kapilari (Auth::CheckPermission, Auth::Can,\n"
        . "        vnl_require_permission) KIMSEYE izin vermez, menu ise herkese\n"
        . "        acik kalir. Sablondaki helpers/permissions.php'yi kopyalayin.",
        false
    );

    $definitionsCode = $codeOf('config/definitions.php');
    $checks[] = $check(
        "login_except_pages 'index' icermiyor",
        $definitionsCode === null || !preg_match('/\$login_except_pages\s*=\s*\[[^\]]*["\']index["\']/', $definitionsCode),
        "Acilis sayfasi girissiz aciliyor. 3.4 varsayilani [\"app\", \"login\", \"logout\"];\n"
        . "        docs sayfalari VNL_PUBLIC_DOCS=true ile acilir.",
        false
    );

    $navbar = $codeOf('view/static/topNavbar.php');
    $checks[] = $check(
        'Cikis GET baglantisi degil',
        $navbar === null || !preg_match('~href=["\']/logout["\']~', $navbar),
        "Ust cubuktaki cikis <a href=\"/logout\">: 3.4'te GET /logout yalniz onay\n"
        . "        sayfasidir. Dugmeye data-vnl-logout verin (POST + CSRF).",
        false
    );

    $logoutView = $codeOf('view/logout.php');
    $checks[] = $check(
        'view/logout.php durum degistirmiyor',
        $logoutView === null || !str_contains($logoutView, 'LogOut('),
        "GUVENLIK: GET /logout oturumu kapatiyor; baska bir sitedeki <img> bile\n"
        . "        kullaniciyi cikarabilir. Sablondaki controller/logout.php ve\n"
        . "        view/logout.php ile degistirin.",
        false
    );

    $checks[] = $check(
        'APP_DEBUG kapali',
        VnlEnv::get('APP_DEBUG', false) !== true,
        "APP_DEBUG=true: hata sayfasi kaynak kod, yol ve yigin izi gosterir.\n"
        . "        Canlida false olmali.",
        false
    );

    $checks[] = $check(
        'APP_ENCRYPTION_KEY tanimli',
        (string)VnlEnv::get('APP_ENCRYPTION_KEY', '') !== '',
        "Iki adimli dogrulama sirlari ve safeEncrypt() bu anahtari ister.\n"
        . "        php -r \"echo base64_encode(random_bytes(32));\"",
        false
    );

    /* Yorum satırları sayılmaz: yeni dosya eski deseni açıklamada anıyor. */
    $htaccess = @file_get_contents(VNL_ROOT . '/.htaccess');
    $htaccessRules = is_string($htaccess) ? preg_replace('/^\s*#.*$/m', '', $htaccess) : null;
    $checks[] = $check(
        '.htaccess yonlendirme kurali guncel',
        !is_string($htaccessRules) || !str_contains($htaccessRules, '[a-zA-z0-9-_/]'),
        "Eski desen noktali adresleri (/domains/view/ornek.com) index.php'ye\n"
        . "        ulastirmiyor ve config/, vnl/, files/ dizinlerini korumuyor.\n"
        . "        Sablondaki .htaccess ile degistirin (nginx icin nginx.conf.example).",
        false
    );

    $checks[] = $check(
        '.htaccess Authorization basligini iletiyor',
        !is_string($htaccessRules) || str_contains($htaccessRules, 'HTTP_AUTHORIZATION'),
        "Apache + PHP-FPM (mod_proxy_fcgi) Authorization basligini PHP'ye\n"
        . "        iletmez: Bearer API jetonlari 401 VNL_AUTH alir. Sablondaki\n"
        . "        .htaccess'teki RewriteRule .* - [E=HTTP_AUTHORIZATION:...] satirini\n"
        . "        ekleyin ya da vhost'ta CGIPassAuth On (Apache 2.4.13+).",
        false
    );

    $checks[] = $check(
        'Web kokunde eski gunluk dizini yok',
        !is_dir(VNL_ROOT . '/mysql-log') && !is_dir(VNL_ROOT . '/event-log'),
        "mysql-log/ ve event-log/ 3.3'e kadar web kokunde 0777 ile aciliyordu.\n"
        . "        Gunlukler artik LOG_PATH (varsayilan files/logs). Eskileri tasiyip silin.",
        false
    );

    $checks[] = $check(
        'eklenti: pcntl (kuyruk iscisi)',
        extension_loaded('pcntl') || DIRECTORY_SEPARATOR === '\\',
        "queue:work SIGTERM'u yakalayamaz, eldeki is yarida kalir (--stale sonra\n"
        . "        kurtarir). pcntl TEK BASINA yetmez: systemd biriminde KillMode=mixed\n"
        . "        (yoksa SIGTERM isin alt sureclerini de oldurur) ve en uzun isten uzun\n"
        . "        TimeoutStopSec gerekir — bkz. /docs/cli-queue.",
        false
    );

    /* queue:work birimleri: KillMode=control-group (varsayılan) SIGTERM'i
       işin alt süreçlerine de gönderir; iş yarıda kalır. */
    $unitsWithoutMixed = [];
    foreach (glob('/etc/systemd/system/*.service') ?: [] as $unitFile) {
        $unit = (string)@file_get_contents($unitFile);

        if (str_contains($unit, 'queue:work') && !preg_match('/^\s*KillMode\s*=\s*mixed\s*$/mi', $unit)) {
            $unitsWithoutMixed[] = basename($unitFile);
        }
    }
    $checks[] = $check(
        'queue:work systemd birimleri KillMode=mixed',
        $unitsWithoutMixed === [],
        implode(', ', $unitsWithoutMixed) . ": KillMode=mixed yok. systemctl stop/restart\n"
        . "        SIGTERM'i isin alt sureclerine (useradd, certbot, rsync) de gonderir.\n"
        . "        [Service] altina KillMode=mixed ve TimeoutStopSec=<en uzun is> ekleyin.",
        false
    );

    /* ---- Veritabanı: bekleyen çekirdek migration'ları (en iyi çaba) ----
       Uygulama açılmaz; migrate gibi .env + config/definitions.php okunup
       doğrudan PDO açılır. Bağlanılamazsa yalnız uyarı. Özellikle 0007:
       uygulanmadan iki adımlı giriş kapalıdır (fail closed). */
    $pendingCore = null;
    $dbProblem   = null;

    if (!extension_loaded('pdo_mysql')) {
        $dbProblem = 'pdo_mysql yok';
    } elseif (!is_file(VNL_ROOT . '/config/definitions.php')) {
        $dbProblem = 'config/definitions.php yok';
    } else {
        try {
            require_once VNL_ROOT . '/config/definitions.php';
            $dbInfo = (new ConfigDefinitions())->getDBInfo();

            if ((string)($dbInfo['database'] ?? '') === '') {
                throw new RuntimeException('DB_DATABASE bos');
            }

            $socket = (string)($dbInfo['unix_socket'] ?? '');
            $dsn    = ($socket !== ''
                    ? 'mysql:unix_socket=' . $socket
                    : 'mysql:host=' . $dbInfo['host'] . ((int)($dbInfo['port'] ?? 0) > 0 ? ';port=' . (int)$dbInfo['port'] : ''))
                . ';dbname=' . $dbInfo['database'] . ';charset=utf8mb4';

            $pdo = new PDO($dsn, (string)$dbInfo['username'], (string)$dbInfo['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 3,
            ]);

            $applied = $pdo->query('SELECT `number` FROM `' . ($dbInfo['prefix'] ?? '') . "tbl_vnl_migrations` WHERE `source` = 'vnl'")
                ->fetchAll(PDO::FETCH_COLUMN);

            $pendingCore = [];
            foreach (glob(VNL_PATH . '/migrations/*.sql') ?: [] as $migrationFile) {
                if (preg_match('/^(\d{4})_/', basename($migrationFile), $mm) && !in_array($mm[1], $applied, true)) {
                    $pendingCore[] = basename($migrationFile);
                }
            }
        } catch (Throwable $e) {
            $dbProblem = $e->getMessage();
        }
    }

    $checks[] = $pendingCore === null
        ? $check('Cekirdek migrationlari (veritabani)', false,
            "Denetlenemedi: {$dbProblem}\n        Elle: php vnl.php migrate --status", false)
        : $check('Cekirdek migrationlari uygulanmis', $pendingCore === [],
            'Bekleyen: ' . implode(', ', $pendingCore) . "\n"
            . "        php vnl.php migrate --run" . (in_array('0007_vnl_totp_replay.sql', $pendingCore, true)
                ? "\n        0007 uygulanmadan iki adimli dogrulamali kullanicilar giris YAPAMAZ."
                : ''));

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

    /* PHP 2026-10-21 güvenlik yayını: CVE-2026-109618 ve CVE-2026-109619,
       ikisi de uzaktan kod çalıştırma. Her dalın yamalı ilk sürümü; 8.2'den
       eski dallar yama almadı, listede olmayan yeni dallar yamalı doğdu. */
    $phpPatched = [80200 => 80235, 80300 => 80336, 80400 => 80427, 80500 => 80512];
    $phpBranch  = intdiv(PHP_VERSION_ID, 100) * 100;
    $checks[] = $check(
        'PHP guvenlik yamasi (CVE-2026-109618, CVE-2026-109619)',
        $phpBranch >= 80200 && PHP_VERSION_ID >= ($phpPatched[$phpBranch] ?? 0),
        'PHP ' . PHP_VERSION . " uzaktan kod calistirma acigi tasiyor. En az\n"
        . "        8.2.35 / 8.3.36 / 8.4.27 / 8.5.12'ye guncelleyin. Bu denetim komut\n"
        . "        satirindaki PHP'yi olcer; web sunucusu (FPM, mod_php) ayri bir\n"
        . "        kurulumsa onun surumunu da denetleyin (phpinfo())."
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
