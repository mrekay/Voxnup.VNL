<?php if (($vnl_layout ?? 'app') === 'focus') { ?>
    </div><!-- /.vnl-focus -->
<?php } ?>
</main><!-- /.vnl-content -->

<?php if ($page_config['footer'] != false) { ?>
    <footer class="vnl-footer">
        <div class="app-container container-fluid d-flex flex-stack flex-wrap gap-2">
            <div>
                <span class="fw-semibold"><?= $cDefinitions->appName() ?></span>
                <span class="ms-1"><?= $cDefinitions->version() ?></span>
            </div>
            <div>&copy; <?= date('Y') ?> Voxnup</div>
        </div>
    </footer>
<?php } ?>

</div><!-- /.vnl-main -->

<?php
require_once view('static/modals');
if (file_exists(view('includes/footer/script-' . route(0))))
	require_once view('includes/footer/script-' . route(0));

/* Manuel JS include'ları: $page_config['js_files'] ve tarihsel $js_files
   globali birlikte desteklenir. */
$vnl_js_files = $page_config['js_files'] ?? ($js_files ?? array());
foreach ($vnl_js_files as $item) {
	if (file_exists(view('includes/footer/' . $item)))
		require_once view('includes/footer/' . $item);
}
?>

</body>
</html>
