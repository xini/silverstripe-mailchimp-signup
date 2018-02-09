<div class="typography">
    <h1>$Title</h1>
	$Content
	<% if $Form %>
		$Form
	<% else %>
        <p class="message warning">
			<% _t('Innoweb\\MailChimpSignup\\Pages\\SignupPage.FORMNOTLOADED', 'Unfortunately the signup form could not be loaded.') %>
        </p>
	<% end_if %>
</div>