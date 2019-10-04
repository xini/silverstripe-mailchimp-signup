<div class="typography">
	<h1>$Title</h1>
	$Content
	<% if $FilteredCampaigns %>
		<ul class="mailchimp-campaigns">
			<% loop $FilteredCampaigns %>
				<li>
					<h2><a href="$Link" target="_blank" rel="noopener noreferrer">$Subject</a></h2>
					<p class="meta"><% _t('MailChimpCampaignListPage.Sent', 'Sent') %>: $SentDate.Nice</p>
					<p><a href="$Link" target="_blank" rel="noopener noreferrer">$Link</a></p>
				</li>
			<% end_loop %>
		</ul>
	<% else %>
		<p class="message info">
			<% _t('MailChimpCampaignListPage.NoCampaignsFound', 'No campaigns found.') %>
		</p>
	<% end_if %>
</div>