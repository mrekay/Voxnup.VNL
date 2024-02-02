

<!-- Content Header (Page header) -->
<?php if ($page_config['contentheader'] != false){?>
<div class="content-header">
	<div class="container-fluid">
		<div class="row mb-2">
			<div class="col-sm-6">
				<h1 class="m-0"><?=$meta['title']; ?></h1>
			</div><!-- /.col -->
			<div class="col-sm-6">
				<ol class="breadcrumb float-sm-right">
					<li class="breadcrumb-item"><a href="#">Anasayfa</a></li>
					<li class="breadcrumb-item active"><?=$meta['title']; ?></li>
				</ol>
			</div><!-- /.col -->
		</div><!-- /.row -->
	</div><!-- /.container-fluid -->
</div>
<?php } ?>
<!-- /.content-header -->
