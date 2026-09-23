<?php

http_response_code(401);

$meta = [
    'title' => lang('ERR_401_TITLE'),
    'description' => '',
    'keywords' => ''
];

$page_config = [
    'layout' => 'focus',
];

require view('static/header');
require view('401');
require view('static/footer');
