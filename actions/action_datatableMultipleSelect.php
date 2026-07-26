<?php
define('PHPEXCELMODE',true);
class action_datatableMultipleSelect
{
    public function createSession($req)
    {
        if (!$req->postHas('selected_list') || empty($req->post('selected_list'))) {
            $dt_message = 'Seçim yapılmamış';
            returnErrorMessage($dt_message);
        }
        else {
            $tmp_session_id = 'dt_s_' . rand();
            $selected_list = json_encode($req->post('selected_list'));
            Session::set($tmp_session_id, $selected_list);
            $dt_data['id'] = $tmp_session_id;

            $dt_message = lang('SUC_GENERAL');
            returnSuccessMessage($dt_message, $dt_data);
        }
    }

    function selectAllChange($req)
    {
        // HATA ÇÖZÜMÜ: Parametre olarak gelen $req nesnesini Global scope'a tanıtıyoruz.
        // Böylece çağrılan getStockList fonksiyonu "global $req" dediğinde null hatası almayacak.
        $GLOBALS['req'] = $req;

        global $CurrentUser, $CurrentCompany, $configDefinitions, $documentRoot;

        $default_action = trim($req->post('default_action'));
        $action_url = explode('/', $default_action);
        $action_temp = end($action_url);

        // URL parsing işlemleri
        $action_path_parts = explode('?', $action_temp);
        $get_temp = isset($action_path_parts[1]) ? $action_path_parts[1] : '';
        $get = [];

        if (!empty($get_temp)) {
            foreach (explode('&', $get_temp) as $item) {
                $parts = explode('=', $item);
                if (count($parts) == 2) {
                    $get[$parts[0]] = $parts[1];
                }
            }
        }

        if (count($action_url) < 3) {
            returnErrorMessage("Geçersiz işlem adresi.");
            return;
        }

        $action = $action_url[1];
        $function = $action_url[2];

        // POST verilerini yedekle ve manipüle et
        $post = $_POST;
        $post['length'] = -1; // Tüm kayıtları çekmek için

        ob_start();
        $_GET = array_merge($_GET, $get);
        $_POST = $post;

        $class_name = 'action_' . $action;
        $action_file_path = $documentRoot . 'actions/' . $class_name . '.php';

        if (!file_exists($action_file_path)) {
            ob_end_clean();
            returnErrorMessage("Dosya bulunamadı: " . $class_name);
            return;
        }

        require_once $action_file_path;

        if (!class_exists($class_name)) {
            ob_end_clean();
            returnErrorMessage("Sınıf bulunamadı: " . $class_name);
            return;
        }

        $action_class = new $class_name;

        if (method_exists($action_class, $function)) {
            try {
                $reflection = new ReflectionMethod($class_name, $function);
                $params = $reflection->getParameters();
                $args = [];

                foreach ($params as $param) {
                    $paramName = $param->getName();

                    // Reflection ile parametre eşleştirme
                    if (isset($_GET[$paramName])) {
                        $args[] = $_GET[$paramName];
                    }
                    elseif (isset($_POST[$paramName])) {
                        $args[] = $_POST[$paramName];
                    }
                    elseif ($param->isOptional()) {
                        $args[] = $param->getDefaultValue();
                    }
                    else {
                        // Eğer req parametresi isteniyorsa onu ver, yoksa null
                        if($paramName == 'req') {
                            $args[] = $req;
                        } else {
                            $args[] = null;
                        }
                    }
                }

                call_user_func_array([$action_class, $function], $args);

            } catch (Exception $e) {
                ob_end_clean();
                returnErrorMessage("Fonksiyon hatası: " . $e->getMessage());
                return;
            }
        } else {
            ob_end_clean();
            returnErrorMessage("Metod bulunamadı: " . $function);
            return;
        }

        $content = ob_get_clean();
        $jsonObj = (array)json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $jsonArray = [];
        } else {
            $jsonArray = isset($jsonObj['data']) ? (array)$jsonObj['data'] : [];
        }

        $allItemSelected = intval($req->post('dt-allItemSelected'));
        $select = $req->postHas('dt-selectItem') && !empty($req->post('dt-selectItem')) ? $req->post('dt-selectItem') : array();

        if ($jsonArray) {
            if ($allItemSelected == 1) {
                foreach ($jsonArray as $item) {
                    if (isset($item['id']) && !in_array($item['id'], $select)) {
                        $select[] = $item['id'];
                    }
                }
            } else {
                $tmp_unselect = array();
                foreach ($jsonArray as $item) {
                    if (isset($item['id'])) {
                        $tmp_unselect[] = $item['id'];
                    }
                }
                $select = array_diff($select, $tmp_unselect);
            }

            $select = array_values($select);
            $dt_data['selected_list'] = $select;
            $dt_message = lang('SUC_GENERAL');
            $json_data = array('table_id' => $req->post('table_id'),'message' => $dt_message, 'select_result' => "ok", 'data' => $dt_data);
            echo json_encode($json_data);
            die;
        } else {
            returnErrorMessage(lang('ERR_GENERAL'));
        }
    }

    public function datatableEmptyData($req){
        $jsondata = array(
            "recordsTotal"    => 0,
            "recordsFiltered" => 0,
            "data"            => []
        );
        returnSuccessDataOnly( $jsondata );
    }
}