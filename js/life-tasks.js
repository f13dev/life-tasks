(function($) {
    $(document).on('change','#life-tasks-user-select', function() {
        $(this).closest("form").submit();
    });
})(jQuery);