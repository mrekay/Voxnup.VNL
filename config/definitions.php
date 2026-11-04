<?php

use Illuminate\Database\Capsule\Manager as Capsule;

class ConfigDefinitions
{

    private $app_name = "VNL";
    private $app_logo = "VNL.png";
    private $app_logo_path = "/assets/media/logos/";
    private $db_info;
    private $master_db;
    private $cookie_prefix;
    private $salt_text;
    private $site_url;
    private $app_url;
    private $master_site_url;
    private $assets_url;
    private $cdn_url;
    private $cdn_path;
    private $product_id;

    /** PROJENİN sürümü. Burası sizindir; istediğiniz gibi kullanın. */
    private $version = "3.0.0";

    /**
     * VNL Theme — uygulama geneli varsayılan davranışlar.
     * Sistemden sisteme değişir (ERP mini-hover isterken pazarlama sitesi
     * tam açık ister); sayfalar $page_config ile her zaman ezebilir.
     *
     *   leftsidebar : true (tam açık) | 'mini' (sabit dar) | 'mini-hover' (dar + hover'da genişler)
     *   submenu     : 'flyout' (sağda panel) | 'accordion' (alt alta liste)
     *   skin        : null (varsayılan kimlik) | 'dracula' | vnl/assets/theme/css/skins/ altındaki herhangi bir skin adı
     */
    private $theme_defaults = [
        'leftsidebar' => true,
        'submenu'     => 'flyout',
        'skin'        => null,
    ];
    /**
     * Girişsiz açılabilen sayfalar: 'sayfa' ya da 'grup/sayfa'.
     *
     * 3.4.0: varsayılan yalnız giriş akışıdır. 'app' action dağıtıcısıdır ve
     * kendi kimlik denetimini yapar (PUBLIC_METHODS dışında giriş ister).
     * 3.3'te 'index', 'register', 'overview' ve bütün docs/* sayfaları da
     * buradaydı — yani şablondan türeyen her proje açılış sayfasını ve
     * dokümanı herkese açık getiriyordu. Doküman sayfaları .env'de
     * VNL_PUBLIC_DOCS=true iken aşağıda eklenir.
     *
     * Eklentiler kendi sayfalarını boot.php'de vnl_public_page() ile açar.
     */
    private $login_except_pages = ["app", "login", "logout"];

    /**
     * Girişe atılan kullanıcının istediği adresi taşıyan parametre (3.5.0):
     * "donus" → /login?donus=%2Fsatis. Boş dizge kapalıdır. Çekirdek yalnız
     * adresi taşır; kullanımı (giriş formu, action) projenindir: /docs/config-definitions.
     */
    private $login_return_param = "";

    /** VNL_PUBLIC_DOCS=true iken girişsiz açılan doküman sayfaları. */
    private $public_docs_pages = ["docs/overview","docs/getting-started","docs/architecture","docs/versioning","docs/theme","docs/theme-layout","docs/theme-sidebar","docs/theme-design","docs/theme-components","docs/theme-forms","docs/theme-dates","docs/theme-icons","docs/theme-modals","docs/theme-js","docs/theme-migration","docs/routing","docs/templating","docs/request-handling","docs/errors","docs/environment","docs/modals","docs/session","docs/config-definitions","docs/models","docs/validators","docs/languages","docs/vnltable","docs/vnltable-grid","docs/vnltable-workbench","docs/sdtable","docs/excel-export","docs/modules","docs/view-includes","docs/extensions","docs/stripe","docs/security","docs/cli-queue"];

    /**
     * Ortama göre değişen ve gizli olan her şey .env'den gelir; kopyası
     * .env.example'dadır. Sunucu seviyesinde tanımlı gerçek ortam
     * değişkeni .env'i ezer, böylece konteyner/CI yapılandırması çalışır.
     *
     * VNL 2.x'teki $testMode anahtarı kaldırıldı: iki sabit blok arasında
     * seçim yapmak yerine değerler doğrudan ortamdan okunuyor.
     */
    public function __construct()
    {
        /* 3.4.0: DB_PORT / DB_SOCKET ayrı anahtarlar ("host:port" PDO'da
           çalışmıyordu); varsayılan karakter kümesi utf8mb4 (utf8 = utf8mb3,
           emoji ve 4 baytlık karakterleri kesiyordu). */
        $this->db_info = [
            'driver'    => (string)VnlEnv::get('DB_DRIVER', 'mysql'),
            'host'      => (string)VnlEnv::get('DB_HOST', 'localhost'),
            'port'      => (int)VnlEnv::get('DB_PORT', 3306),
            'database'  => (string)VnlEnv::get('DB_DATABASE', ''),
            'username'  => (string)VnlEnv::get('DB_USERNAME', ''),
            'password'  => (string)VnlEnv::get('DB_PASSWORD', ''),
            'charset'   => (string)VnlEnv::get('DB_CHARSET', 'utf8mb4'),
            'collation' => (string)VnlEnv::get('DB_COLLATION', 'utf8mb4_unicode_ci'),
            'prefix'    => (string)VnlEnv::get('DB_PREFIX', ''),
        ];

        $socket = (string)VnlEnv::get('DB_SOCKET', '');
        if ($socket !== '') {
            $this->db_info['unix_socket'] = $socket;
        }

        if (VnlEnv::get('VNL_PUBLIC_DOCS', false) === true) {
            $this->login_except_pages = array_merge($this->login_except_pages, $this->public_docs_pages);
        }

        $this->master_db       = (string)VnlEnv::get('DB_MASTER', '');
        $this->cookie_prefix   = (string)VnlEnv::get('APP_COOKIE_PREFIX', 'vnl');
        $this->salt_text       = (string)VnlEnv::get('APP_SALT', '');
        $this->site_url        = (string)VnlEnv::get('APP_URL', '');
        $this->master_site_url = (string)VnlEnv::get('APP_MASTER_URL', '');
        $this->assets_url      = (string)VnlEnv::get('APP_ASSETS_URL', '/assets');
        $this->product_id      = (string)VnlEnv::get('APP_PRODUCT_ID', '0');
    }



    public function appName(): string
    {
        return $this->app_name;
    }
    public function appLogoPath(): string{
        return $this->app_logo_path;
    }
    public function appLogo(): string{
        return $this->appLogoPath(). $this->app_logo;
    }
    public function version(): string
    {
        return $this->version;
    }
    /**
     * ÇEKİRDEĞİN sürümü.
     *
     * VNL 3.0'a kadar bu değer de bu dosyada duruyor ve yanında "burayı
     * sisteminizde değiştirmeyin" yazıyordu — proje dosyasında duran bir
     * şeyi proje değiştirmesin diye rica etmek gerekiyordu. Artık kaynağı
     * vnl/VERSION: çekirdek güncellendiğinde kendiliğinden doğru olur,
     * proje ona hiç dokunmaz.
     */
    public function vnl_version(): string
    {
        static $version = null;

        if ($version === null) {
            $file    = VNL_PATH . '/VERSION';
            $version = is_file($file) ? trim((string)file_get_contents($file)) : '';
        }

        return $version;
    }

    public function getLoginExceptPages() : array{
        return $this->login_except_pages;
    }

    public function getLoginReturnParam(): string
    {
        return $this->login_return_param;
    }

    public function themeDefaults(): array
    {
        return $this->theme_defaults;
    }
    public function getDBInfo(): array
    {
        return $this->db_info;
    }

    public function getMasterDb(): string
    {
        return $this->master_db;
    }

    public function getCookiePrefix(): string
    {
        return $this->cookie_prefix;
    }

    public function getSaltText(): string
    {
        return $this->salt_text;
    }

    public function getSiteUrl(): string
    {
        return SITE_HTTP . $this->site_url;
    }

    public function getMasterSiteUrl(): string
    {
        return $this->master_site_url;
    }

    public function getAssetsUrl(): string
    {
        return $this->assets_url;
    }

    public function getProductId(): string
    {
        return $this->product_id;
    }

}
