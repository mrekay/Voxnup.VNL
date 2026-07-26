<?php

http_response_code(404);

$meta = [
    'title' => lang('ERR_404_TITLE'),
    'description' => '',
    'keywords' => ''
];

$page_config = [
    'layout' => 'focus',
    'public' => true,
];

require view('static/header');
require view('404');
require view('static/footer');
