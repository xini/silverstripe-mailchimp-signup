(function($) {
	$(document).ready(function() {
		if (mailchimp_validation_options) {
			$('#mailchimp-signup-form').validate(
				mailchimp_validation_options
			);
		}
	});
}(jQuery));
