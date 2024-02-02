</div>
<!--end::Content wrapper-->

<?php if ($page_config['footer'] != false) { ?>
    <!--begin::Footer-->
    <div id="kt_app_footer" class="app-footer">
        <!--begin::Footer container-->
        <div class="app-container container-fluid d-flex flex-column flex-md-row flex-center flex-md-stack py-3">
            <!--begin::Copyright-->
            <div class="text-dark order-2 order-md-1">
                <span class="text-muted fw-semibold me-1">2023&copy;</span>
                <a href="https://keenthemes.com" target="_blank" class="text-gray-800 text-hover-primary">Keenthemes</a>
            </div>
            <!--end::Copyright-->
            <!--begin::Menu-->
            <ul class="menu menu-gray-600 menu-hover-primary fw-semibold order-1">
                <li class="menu-item">
                    <a href="https://keenthemes.com" target="_blank" class="menu-link px-2">About</a>
                </li>
                <li class="menu-item">
                    <a href="https://devs.keenthemes.com" target="_blank" class="menu-link px-2">Support</a>
                </li>
                <li class="menu-item">
                    <a href="https://1.envato.market/EA4JP" target="_blank" class="menu-link px-2">Purchase</a>
                </li>
            </ul>
            <!--end::Menu-->
        </div>
        <!--end::Footer container-->
    </div>
    <!--end::Footer-->
<?php } ?>

</div>
<!--end:::Main-->
</div>
<!--end::Wrapper-->
</div>
<!--end::Page-->
</div>
<!--end::App-->
<?php
require_once view('static/modals');
if (file_exists(view('includes/footer/script-' . route(0))))
	require_once view('includes/footer/script-' . route(0));

if (isset($js_files) && !empty($js_files)) {
	foreach ($js_files as $item) {
		if (file_exists(view('includes/footer/' . $item)))
			require_once view('includes/footer/' . $item);
	}
}
?>


</body>
<!--end::Body-->
</html>
