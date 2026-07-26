<?php

use Illuminate\Database\Capsule\Manager as Capsule;

class ConfigDefinitions
{

    private $testMode = false;
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
    private $version = "2.4.0";
    private $vnl_version = "2.4.0"; // burayı sisteminizde değiştirmeyin. burası vnl'in takibini yapmak için var.
    private $login_except_pages = ["app","login","register","logout","index","getting-started","routing","request-handling","docs/overview","overview","docs/getting-started","docs/theme","docs/routing","docs/request-handling","docs/modals","docs/session","docs/config-definitions","docs/models","docs/validators","docs/languages","docs/sdtable","docs/excel-export","docs/modules","docs/view-includes","docs/extensions","docs/stripe"];

    public function __construct()
    {
        switch ($this->testMode) {
            case true:
                $this->db_info = [
                    'driver' => 'mysql',
                    'host' => 'localhost',
                    'database' => '',
                    'username' => 'root',
                    'password' => 'password',
                    'charset' => 'utf8mb4',
                    'collation' => 'utf8mb4_general_ci',
                    'prefix' => '',
                ];

                $this->master_db = "";
                $this->cookie_prefix = "";
                $this->salt_text = "";
                $this->site_url = "";
                $this->master_site_url = "";
                $this->assets_url = "/assets";
                $this->product_id = "";
                break;
            case false:
                $this->db_info = [
                    'driver' => 'mysql',
                    'host' => '',
                    'database' => '',
                    'username' => '',
                    'password' => '',
                    'charset' => 'utf8',
                    'collation' => 'utf8_unicode_ci',
                    'prefix' => '',
                ];

                $this->master_db = "";
                $this->cookie_prefix = "vnl";
                $this->salt_text = "vnl54.?**-";
                $this->site_url = "vnl.voxnup.com";
                $this->master_site_url = "vnl.voxnup.com";
                $this->assets_url = "/assets";
                $this->product_id = 0;

        }

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
