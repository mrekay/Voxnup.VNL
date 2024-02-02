<script>

    var page_cache = {};
    var page_loc = "project-manager?action=";
    const urlParams = new URLSearchParams(window.location.search);

    $('#page-load-pb-wrapper').parent().ready(function () {

        if (urlParams.get("action")){
            loadApps({
                url: urlParams.get("action"),
                data: {project: urlParams.get("project")}
            });
        }else{
            loadApps({
                url: "project-list",
                cache_key: "project_list",
                load_function: function () {
                    reloadProjects();
                }
            });
        }


    })

    function setPMProgressBar(value) {
        if (value <= 0) {
            $('#page-load-pb-wrapper').hide();
            return;
        }
        $('#page-load-pb').width(value + "%");
        $('#page-load-pb-wrapper').fadeIn();

        if (value >= 100) {
            setTimeout(function () {

                $('#page-load-pb-wrapper').hide();

            }, 800);
        }
    }

    function loadApps(optsIn) {

        if (!optsIn || !optsIn.url) return;

        setPMProgressBar(5);

        var opts_default = {
            type: 'GET',
            data_type: 'html',
            data: {},
            load_function: null,
        }

        var opts = $.extend({}, opts_default, optsIn);

        if (opts.cache_key && page_cache[opts.cache_key] && page_cache[opts.cache_key]["load_function"]) {
            loadFromCache(opts.cache_key);
            return;
        }

        $.ajax({
            url: "/app-projects/" + opts.url,
            type: opts.type,
            dataType: opts.data_type,
            data: opts.data,
            success: function (response) {
                // window.history.pushState(null,"Projects",  page_loc + opts.url);
                setPMProgressBar(100);

                if (opts.cache_key) {
                    page_cache[opts.cache_key] = {html: JSON.stringify(response), load_function: opts.load_function};
                }

                $('.app').html(response);
                if (opts.load_function)opts.load_function();


            },
            error: function (xhr, status, error) {
            }
        });

    }

    function loadFromCache(cache_key) {
        var page = page_cache[cache_key];
        $('.app').html(JSON.parse(page.html));
        page.load_function();
        setPMProgressBar(100);
    }

    function openProject(projectKey) {
        loadApps({
            url: "view-project",
            // cache_key: "view_project",
            load_function: function () {
                loadProjectData(projectKey);
            }
        });
    }


</script>