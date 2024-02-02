<script>
    function loadDefaultFileImage() {
        $('#symbol-file-ico').attr('src', "/assets/images/files/file.svg")
    }

    $('.btn-download-rev').click(function () {
        startCdDL();
        var counter = 5;
        var interval = setInterval(function() {
            $('.dl-time-left-span').text(counter);
            counter--;
            if (counter < 0) {
                clearInterval(interval);
                dlDirect();
                $('.dl-direct-span').show();
            }
        }, 1000);
    })

    function startCdDL() {
        $('.btn-download-rev').hide();
        $('.dl-cd-text-dv').fadeIn();
    }

    var ra = 2;

    function dlDirect(){

        if (ra <= 0) {
            window.location.reload();
            return;
        }
        ra -=1;

        $.ajax({
            url: 'app/view_file/downloadFile',
            type: 'POST',
            data: { fileAccessKey: '<?=$file_access_code?>' },
            xhrFields: {
                responseType: 'blob'
            },
            success: function(data) {
                var a = document.createElement('a');
                var url = window.URL.createObjectURL(data);
                a.href = url;
                a.download = '<?=$file_inspecting["file_visible_name"]?>';
                document.body.append(a);
                a.click();
                window.URL.revokeObjectURL(url);
                a.remove();
            }
        });
    }

    $('.btn-view-file').click(function(){
        var currentUrl = window.location.href;
        var newUrl = currentUrl + '&fileMode=1';

        window.open(newUrl, '_blank');
    })

</script>