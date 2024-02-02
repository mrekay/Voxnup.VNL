<?php
if (!route(1) || !route(2)) {
	returnAjaxError('redirect', '', array('url' => '401'));
}
$action = route(2);

$action_path = $documentRoot . 'actions/action_' . route(1) . '.php';

if (!file_exists($action_path)) {
	try {
		returnErrorMessage("Sayfa Bulunamadı", 400);
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
		call_user_func([$action_class, $action]);
	}else{
		require_once $documentRoot.'libs/static/pages/function_cannot_load.php';
	}
} catch (Exception $e) {
	$trace = $e->getTrace();
	$last_call = $trace[0];
	if ($last_call = "returnErrorMessage"){
		echo json_encode(json_decode($e->getMessage()));
	}else{
		require_once $documentRoot.'libs/static/pages/function_load_error.php';
	}
}
