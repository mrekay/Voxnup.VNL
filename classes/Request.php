<?php
/**
 * Created by PhpStorm.
 * User: ErkayTeknoloji
 * Date: 29.04.2019
 * Time: 13:08
 */

class Request
{
    function __construct()
    {
//print_r(json_decode(file_get_contents('php://input'), true));
        if (empty($_POST)) {
            try {
                $_POST = json_decode(file_get_contents('php://input'), true);
            } catch (Exception $e) {
                $_POST = array();
            }
        }
        $this->stripslashes_gpc();

    }

    function stripslashes_gpc()
    {
        $_GET = $this->stripslashes_deep($_GET);
        $_POST = $this->stripslashes_deep($_POST);
    }

    function stripslashes_deep(&$data)
    {
        if (empty($data)) return null;

        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $data[$key] = $this->stripslashes_deep($value);
            }
        } else {
            $data = htmlspecialchars($data);
        } // ters \ i kaldir

        return $data;
    }

    public function get($key)
    { // get ile gelen veriyi al
        return $this->_get($key, 'GET');
    }

    private function _get($key, $type = 'GET', $default = null)
    { // siteye gönderilen verinin tipini bul ve tipi $dataya aktar.
        switch (strtoupper($type)) {
            case 'GET':
                $data = $_GET;
                break;
            case 'POST':
                $data = $_POST;
                break;
        }

        return (isset($data[$key]) ? $data[$key] : $default); //  Veriyi $_POST["isim"] gibi $data["isim"] şeklinde geri gönder.
    }

    public function post($key)
    {// post ile gelen veriyi al
        return $this->_get($key, 'POST');
    }

    public function getHas($key)
    {
        return $this->has($key, 'GET');
    }

    private function has($key, $type = 'GET')
    {
        switch (strtoupper($type)) { // gelen verinin tip yazısı büyüt ve kontrol et.
            case 'GET':
                return isset($_GET[$key]); // tip get ise veriyi get metodu ile al ve gönder.
                break;
            case 'POST':
                return isset($_POST[$key]); // tip post ise veriyi post metodu ile al ve gönder.
                break;

        }

        return null;
    }

    public function postHas($key)
    {
        return $this->has($key, 'POST');
    }

    public function setPost($key, $value)
    {
        $this->_set($key, $value, 'POST');
    }

    private function _set($key, $value, $type = 'GET')
    {
        switch (strtoupper($type)) { // gelen verinin tip yazısı büyüt ve kontrol et.
            case 'GET':
                return $_GET[$key] = $value; // tip get ise veriyi get metodu ile al ve değeri ona aktarıp gönder.
                break;
            case 'POST':
                return $_POST[$key] = $value; // tip post ise veriyi post metodu ile al ve değeri ona aktarıp gönder.
                break;
        }

        return null;
    }

    //previously stripSlashes

    public function setGet($key, $value)
    {
        $this->_set($key, $value);
    }

    public function isPost() // post verisi var mı ?
    {
        return (strtoupper($_SERVER['REQUEST_METHOD']) == 'POST');
    }
}

?>
