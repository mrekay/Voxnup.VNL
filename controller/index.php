<?php
$meta = [
    'title'       => "",
    'description' => '',
    'keywords'    => '',
    'header'      => "",
];

$page_config = [
    'topbar'=>true,
    'leftsidebar'=>false,
    'contentheader'=>false
];


require view('static/header');
require view('index');
require view('static/footer');
