<script>
    $('.go-back').click(function(){
        returnProjectList();
    })

    $('.btn-create-new-project').click(function(){
        $.ajax({
            type    : 'POST',
            dataType: 'json',
            url     : 'app/project/saveData',
            data    : {
                name:$('#inputName').val(),
                desc:$('#inputDescription').val(),
                pdate:$('#inputDate').val(),
            },
            success : function (response) {
                showModalMessage(response.message);
                returnProjectList();
            }, error: function (err) {
            }
        });
    });

    function returnProjectList(){
        loadApps({
            url:"project-list",
            cache_key:"project_list"
        })
    }


</script>