(function($) {
	$(document).ready(function() {
		if (typeof mailchimp_validation_options !== 'undefined') {
			$('#mailchimp-signup-form').validate(
				mailchimp_validation_options
			);
		}
	});
}(jQuery));
