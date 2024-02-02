<!--begin::Root-->
<div class="d-flex flex-column flex-root" id="kt_app_root">
    <!--begin::Page bg image-->
    <style>body { background-image: url('assets/images/index/bg9.jpg'); } [data-bs-theme="dark"] body { background-image: url('assets/images/index/bg9-dark.jpg'); }</style>
    <!--end::Page bg image-->
    <!--begin::Authentication - Signup Welcome Message -->
    <div class="d-flex flex-column flex-center flex-column-fluid">
        <!--begin::Content-->
        <div class="d-flex flex-column flex-center text-center p-10">
            <!--begin::Wrapper-->
            <div class="card card-flush w-lg-650px py-5">
                <div class="card-body py-15 py-lg-20">
                    <!--begin::Logo-->
                    <div class="mb-13">
                        <a href="/" class="">
<!--                            <img alt="Logo" src="assets/images/logos/Subtract_dark.png" class="theme-dark-show h-40px me-5" />-->
<!--                            <img alt="Logo" src="assets/images/logos/Subtract_.png" class="theme-light-show h-40px me-5" />-->
                            <img alt="Logo" src="assets/images/logos/latestset_black.svg" class="theme-dark-show h-40px" />
                            <img alt="Logo" src="assets/images/logos/latestset.svg" class="theme-light-show h-40px" />
                        </a>
                    </div>
                    <!--end::Logo-->
                    <!--begin::Title-->
                    <h1 class="fw-bolder text-gray-900 mb-7"></h1>
                    <!--end::Title-->
                    <!--begin::Text-->
                    <div class="fw-semibold fs-6 text-gray-500 mb-7">Please enter the access code of the file you wish to view</div>
                    <!--end::Text-->
                    <!--begin::Form-->
                    <form class="w-md-350px mb-2 mx-auto" name="search-file-form">
                        <div class="fv-row text-start">
                            <div class="d-flex flex-column flex-md-row justify-content-center gap-3">
                                <!--end::Input=-->
                                <input type="text" placeholder="Enter Access Code" name="file-access-key" autocomplete="off" class="form-control" />
                                <!--end::Input=-->
                                <!--begin::Submit-->
                                <div class="btn btn-primary text-nowrap" id="btn_search_file">
                                    <!--begin::Indicator label-->
                                    <span class="indicator-label">Search</span>
                                    <!--end::Indicator label-->
                                    <!--begin::Indicator progress-->
                                    <span class="indicator-progress">Please wait...
											<span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                                    <!--end::Indicator progress-->
                                </div>
                                <!--end::Submit-->
                            </div>
                            <div class="col-12 text-danger mt-3" id="file-not-found-result" style="<?= $req->getHas("showErr") ?"": "display:none" ?>">
                                File Not Found
                            </div>
                        </div>
                    </form>
                    <!--end::Form-->
                    <!--begin::Illustration-->
                    <div class="mb-n5">
                        <img src="assets/images/index/chart-graph.png" class="mw-100 mh-300px theme-light-show" alt="" />
                        <img src="assets/images/index/chart-graph-dark.png" class="mw-100 mh-300px theme-dark-show" alt="" />
                    </div>
                    <!--end::Illustration-->
                </div>
            </div>
            <!--end::Wrapper-->
        </div>
        <!--end::Content-->
    </div>
    <!--end::Authentication - Signup Welcome Message-->
</div>
<!--end::Root-->