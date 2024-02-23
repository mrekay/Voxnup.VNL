<?php

global $req;

header('Content-Type: application/json; charset=utf-8');
if (!route(1) || !route(2)) {
    returnAjaxError('redirect', '', array('url' => '401'));
}
$action = route(2);

$action_path = getDocumentRoot() . 'actions/action_' . route(1) . '.php';
require getDocumentRoot()."actions/Auth.php";

if (!file_exists($action_path)) {
    try {
        throwErrorMessage("Sayfa Bulunamadı1", 400);
    }catch (Exception $e){
        echo $e->getMessage();
        die();
    }
}

try {
    require_once $action_path;
    $class_name = 'action_'.route(1);
    $action_class = new $class_name;
    if (method_exists($action_class,$action)){
        call_user_func([$action_class, $action],$req);
    }else{
        returnErrorMessage("Sayfa Bulunamadı", 400);
    }
} catch (Exception $e) {
    $trace = $e->getTrace();
    $last_call = $trace[0];

    if ($last_call["function"] == "throwErrorMessage"){
        returnErrorMessage($e->getMessage());
    }else{
        returnErrorMessage($e->getMessage(),500);
    }
}
