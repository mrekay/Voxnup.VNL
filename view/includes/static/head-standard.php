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
<link rel="icon" href="<?=$cDefinitions->appLogoPath().'VNL.png'?>" type="image/x-icon">
<link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Inter:300,400,500,600,700" />
<link href="<?=$theme_path?>plugins/custom/datatables/datatables.bundle.css" rel="stylesheet" type="text/css" />
<link href="<?=$theme_path?>plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css" />
<link href="<?=$theme_path?>css/style.bundle.css" rel="stylesheet" type="text/css" />
