<div class="typography">
    <h1>$Title</h1>
	$Content
	<% if $FilteredCampaigns %>
        <ul class="mailchimp-campaigns">
			<% loop $FilteredCampaigns %>
                <li>
                    <h2><a href="$Link" target="_blank" rel="noopener noreferrer">$Subject</a></h2>
                    <p class="meta"><% _t('Innoweb\\MailChimpSignup\\Pages\\CampaignListPage.Sent', 'Sent') %>: $SentDate.Nice</p>
                </li>
			<% end_loop %>
        </ul>
	<% else %>
        <p class="message info">
			<% _t('Innoweb\\MailChimpSignup\\Pages\\CampaignListPage.NoCampaignsFound', 'No campaigns found.') %>
        </p>
	<% end_if %>
</div>