<?php

$meta = [
    'title' => '',
    'description' => '',
    'keywords' => ''
];

$page_config = [
    'footer'=>false,
    'topbarSecond'=>false,
];

if($CurrentUser->id>0){
    header('Location:' . SITE_URL . '/');
}

//require view('static/header');
require view('login');
//require_once view('includes/static/script-standard');
//require view('static/footer');