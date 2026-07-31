<?php

/**
 * Proje kökü, sonunda ayraçla.
 *
 * Eskiden $_SERVER['DOCUMENT_ROOT'] okunuyordu; CLI'da o boştur ve yol
 * "/classes/Foo.php" gibi kökten başlayan yanlış bir şeye dönüşüyordu.
 * Bu yüzden çağıran yerlerde "DOCUMENT_ROOT boş mu" biçiminde CLI
 * yoklamaları türemişti. Artık ön denetleyici VNL_ROOT'u sabitliyor.
 */
function getDocumentRoot(): string
{
    return VNL_ROOT . '/';
}

/** Veriyi ekrana bas: _print($data); */
function _print($data, $jsonOutput = false)
{
    if (!$jsonOutput) {
        echo "<pre>";
        print_r($data);
        echo "</pre>";
    } else {
        ob_end_clean();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data);
        die();
    }
}

function foreign_key($class, $column)
{
    return ['class' => $class, 'column' => $class];
}
