(function($) {
    $(document).on('change','#life-tasks-user-select', function() {
        $(this).closest("form").submit();
    });

    $(document).ajaxComplete(function() {
        remove_success();
    });

    function remove_success() {
        var success = $('#f13-life-tasks-success');
        if ($(success).length) {
            let yay = new Audio(plugin_url+'audio/yay.mp3');
            yay.currentTime = 1.2;
            yay.pause();
            yay.play();
            timer = setInterval(remove, 2600);
            function remove() {
                $('#f13-life-tasks-success').fadeOut(800, function() { 
                    $('#f13-life-tasks-success').remove(); 
                });
            }
        }
    }

    $(document).ready(function() {
        remove_success();
    });
})(jQuery);