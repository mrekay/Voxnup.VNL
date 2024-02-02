<?php

class ConfigDefinitions
{

    private $testMode = false;
    private $db;
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
                $this->db = [
                    'name' => 'i',
                    'host' => '',
                    'port' => '3306',
                    'user' => 'root',
                    'password' => '',
                ];

                $this->master_db = "";
                $this->cookie_prefix = "";
                $this->salt_text = "";
                $this->site_url = "";
                $this->master_site_url = "";
                $this->assets_url = "";
                $this->product_id = "";
                break;
            case false:
                $this->db = [
                    'name' => 'i',
                    'host' => '',
                    'port' => '3306',
                    'user' => 'root',
                    'password' => '',
                ];

                $this->master_db = "";
                $this->cookie_prefix = "";
                $this->salt_text = "";
                $this->site_url = "";
                $this->master_site_url = "";
                $this->assets_url = "";
                $this->product_id = "";

        }

    }


    /**
     * @return string[]
     */
    public function getDb(): array
    {
        return $this->db;
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
        return $this->site_url;
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
