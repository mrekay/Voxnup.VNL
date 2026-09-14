<?php

$meta = [
    'title'       => 'Tarih ve Saat',
    'upper_title' => 'VNL Theme El Kitabı',
    'description' => 'VNL Date: tarih, saat, ay ve aralık seçici',
    'keywords'    => 'vnl theme, tarih, saat, datepicker, timepicker, flatpickr',
];

$page_config = [
    'topbar'        => true,
    'leftsidebar'   => true,
    'contentheader' => true,
    'public'        => true,
];

require view('static/header');
require view('docs/theme-dates');
require view('static/footer');
