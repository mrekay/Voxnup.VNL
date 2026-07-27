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
    private $version = "3.0.0";
    private $vnl_version = "3.0.0"; // burayı sisteminizde değiştirmeyin. burası vnl'in takibini yapmak için var.

    /**
     * VNL Theme — uygulama geneli varsayılan davranışlar.
     * Sistemden sisteme değişir (ERP mini-hover isterken pazarlama sitesi
     * tam açık ister); sayfalar $page_config ile her zaman ezebilir.
     *
     *   leftsidebar : true (tam açık) | 'mini' (sabit dar) | 'mini-hover' (dar + hover'da genişler)
     *   submenu     : 'flyout' (sağda panel) | 'accordion' (alt alta liste)
     *   skin        : null (varsayılan kimlik) | 'dracula' | theme/scss/skins/ altındaki herhangi bir skin adı
     */
    private $theme_defaults = [
        'leftsidebar' => true,
        'submenu'     => 'flyout',
        'skin'        => null,
    ];
    private $login_except_pages = ["app","login","register","logout","index","getting-started","routing","request-handling","docs/overview","overview","docs/getting-started","docs/theme","docs/theme-layout","docs/theme-sidebar","docs/theme-design","docs/theme-components","docs/theme-forms","docs/theme-icons","docs/theme-js","docs/theme-migration","docs/routing","docs/request-handling","docs/modals","docs/session","docs/config-definitions","docs/models","docs/validators","docs/languages","docs/vnltable","docs/sdtable","docs/excel-export","docs/modules","docs/view-includes","docs/extensions","docs/stripe"];

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
        $this->db_info = [
            'driver'    => (string)VnlEnv::get('DB_DRIVER', 'mysql'),
            'host'      => (string)VnlEnv::get('DB_HOST', 'localhost'),
            'database'  => (string)VnlEnv::get('DB_DATABASE', ''),
            'username'  => (string)VnlEnv::get('DB_USERNAME', ''),
            'password'  => (string)VnlEnv::get('DB_PASSWORD', ''),
            'charset'   => (string)VnlEnv::get('DB_CHARSET', 'utf8'),
            'collation' => (string)VnlEnv::get('DB_COLLATION', 'utf8_unicode_ci'),
            'prefix'    => (string)VnlEnv::get('DB_PREFIX', ''),
        ];

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
    public function vnl_version(): string
    {
        return $this->vnl_version;
    }

    public function getLoginExceptPages() : array{
        return $this->login_except_pages;
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
