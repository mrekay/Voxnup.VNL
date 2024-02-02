<script>
    $('#btn_search_file').click(function(){
        $('#file-not-found-result').hide();
        $('#btn_search_file .indicator-progress').fadeIn();

        var form = $('form[name=search-file-form]');
        var formData = form.serialize();
        $.ajax({
            type    : 'POST',
            dataType: 'json',
            url     : 'app/project/checkAccessCode',
            data    : formData,
            customErrMessage:true,
            success : function (cevap) {
                $('#btn_search_file .indicator-progress').hide();
                if (cevap.data){

                    window.location = cevap.data.url;

                }


            }, error: function (err) {
                $('#file-not-found-result').html(err.responseJSON.message);
                $('#file-not-found-result').fadeIn();
                $('#btn_search_file .indicator-progress').hide();
            }
        });
    })
</script>