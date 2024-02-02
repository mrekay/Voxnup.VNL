<script>

    var _loadArchive = 0;

    $(document).on('click', '.create-project', function () {

        loadApps({
            url: "create-project",
            cache_key: "create-project",
            load_function: function () {
            }
        })


    })

    function reloadProjects() {
        showSpinner();
        $('.btn-refresh-icon').addClass('refreshing');
        $.ajax({
            type: 'POST',
            dataType: 'json',
            url: 'app/project/getList2',
            data: {
                "archive": _loadArchive,
                "search":$('#search-on-project-input').val()
            },
            success: function (response) {
                $('.project-list-area').html("");
                setTimeout(function () {
                    $('.btn-refresh-icon').removeClass('refreshing');
                }, 500);

                $.each(response.data, function (k, v) {

                    var outerDiv = $('<div></div>').addClass('col-md-6 col-xl-4 ');

                    var cardDiv = $('<div></div>').addClass('card border-hover-primary project-card').attr("projectKey", v.project_key).appendTo(outerDiv);

                    var cardHeader = $('<div></div>').addClass('card-header border-0 pt-9').appendTo(cardDiv);

                    var cardTitle = $('<div></div>').addClass('card-title m-0').appendTo(cardHeader);
                    var avatarDiv = $('<div></div>').addClass('symbol symbol-50px bg-light').appendTo(cardTitle);
                    $('<i></i>').addClass('fas fa-box p-3').appendTo(avatarDiv);

                    var cardToolbar = $('<div></div>').addClass('card-toolbar project-card-dropdown').appendTo(cardHeader);
                    var dropdownButton = $('<button></button>')
                        .addClass('btn btn-sm btn-light-primary dropdown-toggle menu-item')
                        .attr("data-kt-menu-trigger", "click")
                        .attr('type', 'button')
                        .attr('data-bs-toggle', 'dropdown')
                        .attr('aria-expanded', 'false')
                        .append($('<i class="fas fa-ellipsis-v p-0">'))
                        .appendTo(cardToolbar);

                    // Dropdown menü içeriği
                    var dropdownMenu = $('<ul></ul>')
                        .addClass('dropdown-menu')
                        .appendTo(cardToolbar);

                    // Örnek menü öğeleri
                    $(`<li><a class="dropdown-item di-archive-project" projectID="${v.project_key}" href="#">Archive</a></li>`).appendTo(dropdownMenu);
                    $(`<li><a class="dropdown-item di-delete-project" projectID="${v.project_key}" href="#">Delete Project</a></li>`).appendTo(dropdownMenu);

                    var cardBody = $('<div></div>').addClass('card-body p-9').appendTo(cardDiv);
                    $('<div></div>').addClass('fs-3 fw-bold text-dark').text(v.project_name).appendTo(cardBody);
                    $('<p></p>').addClass('text-gray-400 fw-semibold fs-5 mt-1 mb-7').text(v.project_description).appendTo(cardBody);

                    var infoDiv = $('<div></div>').addClass('d-flex flex-wrap mb-5').appendTo(cardBody);
                    var dueDiv = $('<div></div>').addClass('border border-gray-300 border-dashed rounded min-w-125px py-3 px-4 me-7 mb-3').appendTo(infoDiv);
                    $('<div></div>').addClass('fs-6 text-gray-800 fw-bold').text(v.project_date).appendTo(dueDiv);
                    $('<div></div>').addClass('fw-semibold text-gray-400').text('Project Date').appendTo(dueDiv);

                    var budgetDiv = $('<div></div>').addClass('border border-gray-300 border-dashed rounded min-w-125px py-3 px-4 mb-3').appendTo(infoDiv);
                    $('<div></div>').addClass('fs-6 text-gray-800 fw-bold').text(v.revision_id).appendTo(budgetDiv);
                    $('<div></div>').addClass('fw-semibold text-gray-400').text('Revisions').appendTo(budgetDiv);

                    $('.project-list-area').append(outerDiv);

                });

                hideSpinner();
            }, error: function (err) {
                hideSpinner();
            }
        });
    }


    $(document).on('click', '.di-archive-project', function () {
        showSpinner();
        $.ajax({
            type: 'POST',
            dataType: 'json',
            url: 'app/project/archiveProject',
            data: {
                projID: $(this).attr('projectID')
            },
            success: function (response) {
                hideSpinner();
                reloadProjects();
            }, error: function (err) {
                hideSpinner();
                reloadProjects();
            }
        });
    });

    $(document).on('click', '.di-delete-project', function () {
        Swal.fire({
            title: "Are you sure?",
            text: "You won't be able to revert this!",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: "Yes, delete it!"
        }).then((result) => {
            if (result.isConfirmed) {
                showSpinner();

                $.ajax({
                    type: 'POST',
                    dataType: 'json',
                    url: 'app/project/deleteData',
                    async: false,
                    data: {
                        dID: $(this).attr('projectID'),
                    },
                    success: function (response) {
                        Swal.fire({
                            title: "Deleted!",
                            text: "Your file has been deleted.",
                            icon: "success"
                        });
                        reloadProjects();
                        hideSpinner();
                    }
                });

            }
        });
    });

    function toggleArchive() {
        _loadArchive = _loadArchive == 1 ? 0 : 1;
        if (_loadArchive == 1){
            $('.btn-archive-icon').removeClass("fa-box").addClass("fa-box-open")
        }else{
            $('.btn-archive-icon').removeClass("fa-box-open").addClass("fa-box")
        }
        reloadProjects();
    }

    $('#projectList').on('click', '.project-card', function (e) {
        if ($(e.target).closest(".project-card-dropdown").length) {
            return;
        }
        openProject($(this).attr("projectKey"))
    })

    $("#search-on-project-input").on("keypress", function(e) {
        console.log(e)
        if(e.which == 13) {
            reloadProjects();
        }
    });


</script>