<?php

$theme_path = $assets_address . '/v1/';
$image_path = $assets_address . '/images';
?>
<!-- Basic -->
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">

<!-- Mobile Metas -->
<meta name="viewport" content="width=device-width, initial-scale=1">

<!-- Site Metas -->
<title><?php echo $meta['title']; ?></title>
<meta name="keywords" content="<?php echo $meta['keywords']; ?>">
<meta name="description" content="<?php echo $meta['description']; ?>">
<meta name="author" content="">

<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="icon" href="<?= $image_path ?>/icon.png" type="image/x-icon">
<link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Inter:300,400,500,600,700" />
<link href="<?=$theme_path?>plugins/custom/datatables/datatables.bundle.css" rel="stylesheet" type="text/css" />
<link href="<?=$theme_path?>plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css" />
<link href="<?=$theme_path?>css/style.bundle.css" rel="stylesheet" type="text/css" />
<style>
    .loading-overlay{
        display: -ms-flexbox;
        display: flex;
        position: absolute;
        left: 0;
        top: 0;
        bottom: 0;
        right: 0;
        margin: -1px;
        z-index: 1052;
        -ms-flex-pack: center;
        justify-content: center;
        -ms-flex-align: center;
        align-items: center;
        background-color: rgba(0,0,0,.7);
        color: #666f76;
        border-radius: .3rem;
    }

    .modal-dialog .loading-overlay{

        background-color: rgba(0,0,0,.9) !important;
    }



    .loading-overlay .fa-spin{
        font-size: 4em !important;
        font-weight: 900 !important;
        color: whitesmoke;
    }
</style>