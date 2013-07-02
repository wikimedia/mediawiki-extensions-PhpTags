(function ($) {

	$().ready(function () {
                $('.foxway_debug_loopbody').hide();
                $('.foxway_debug_loophead').click(function () {
					$(this).next().toggle();
                });
            });

})(window.jQuery);
