<?php

use Illuminate\Database\Capsule\Manager as Capsule;

class ConfigDefinitions
{

    private $testMode = false;
    private $db_info;
    private $master_db;
    private $cookie_prefix;
    private $salt_text;
    private $site_url;
    private $master_site_url;
    private $assets_url;
    private $product_id;

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
                    'charset' => 'utf8',
                    'collation' => 'utf8_unicode_ci',
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
                $this->cookie_prefix = "";
                $this->salt_text = "";
                $this->site_url = "";
                $this->master_site_url = "";
                $this->assets_url = "/assets";
                $this->product_id = "";

        }

    }


    /**
     * @return string[]
     */
    public function getDBInfo() : array
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
        return SITE_HTTP. $this->site_url;
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
