(function ($) {

	$('.foxway_runtime').tipsy({
		html: true,
		title: function () {
			return jQuery.parseJSON(this.getAttribute('data'));
		}
	});

})(window.jQuery);
