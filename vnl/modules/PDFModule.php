<?php

class PDFModule
{
    static function CreatePDF($orientation, $printurl, $title, $prefix, $printfilename, $queue_id = 0)
    {


        $url = 'http://' . $_SERVER['HTTP_HOST'] . '/pdf/printurl.php';
        $printurl .= '&queue_id=' . $queue_id;

        $data = array(
            'orientation' => $orientation, //Landscape||Portrait
            'url' => $printurl,
            'title' => $title,
            'prefix' => $prefix,
            'printfilename' => $printfilename
        );
        $options = array(
            'http' => array(
                'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                'method' => 'POST',
                'content' => http_build_query($data),
                'timeout' => 800,  //1200 Seconds is 20 Minutes
            )
        );

        $context = stream_context_create($options);
        $result = file_get_contents($url, false, $context);


        return $result;
    }

}