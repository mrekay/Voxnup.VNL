<?php
$meta = [
    'title'       => "VNL Başarıyla kuruldu - " .$cDefinitions->version(),
    'description' => '',
    'keywords'    => '',
    'header'      => "",
];

$page_config = [
    'topbar'=>true,
    'leftsidebar'=>true,
    'contentheader'=>true
];


require view('static/header');
require view('index');
require view('static/footer');
