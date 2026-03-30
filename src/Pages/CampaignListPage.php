<?php

namespace Innoweb\MailChimpSignup\Pages;

use DateTime;
use DrewM\MailChimp\MailChimp;
use Innoweb\MailChimpSignup\Model\Campaign;
use Psr\Log\LoggerInterface;
use SilverStripe\Core\Config\Config;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\NumericField;
use SilverStripe\Forms\Tab;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordEditor;
use SilverStripe\Forms\GridField\GridFieldSortableHeader;
use SilverStripe\ORM\FieldType\DBDatetime;
use Symbiote\MultiValueField\Fields\MultiValueDropdownField;
use Page;

class CampaignListPage extends Page {

    private static $singular_name = 'MailChimp Campaign List Page';

    private static $plural_name = 'MailChimp Campaign List Pages';

    private static $description = 'Page listing selected MailChimp campaigns.';

    private static $icon = 'innoweb/silverstripe-mailchimp-signup:client/images/treeicons/page-mailchimp.png';

    private static $table_name = 'MailChimpSignupCampaignListPage';

    private static $db = [
        'APIKey'                =>  'Varchar(255)',
        'ListIDs'               =>  'MultiValueField',
        'HideSentToSegments'    =>  'Boolean',
        'LastUpdated'           =>  'Datetime',
        'Limit'                 =>  'Int'
    ];

    private static $has_many = [
        'Campaigns'     =>  Campaign::class
    ];

    private static $defaults = [
        'Limit'         =>  10
    ];

    private static $dependencies = [
        'Logger' => '%$' . LoggerInterface::class,
    ];

    private static array $scaffold_cms_fields_settings = [
        'ignoreFields' => [
            'LastUpdated',
        ],
        'ignoreRelations' => [
            'Campaigns',
        ],
    ];

    protected $logger;

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        $apiKeyField = $fields->dataFieldByName('APIKey');
        if ($apiKeyField) {
            $fields->removeByName('APIKey');
        }

        $listField = $fields->dataFieldByName('ListIDs');
        if ($listField) {
            $fields->removeByName('ListIDs');
        }

        $segmentField = $fields->dataFieldByName('HideSentToSegments');
        if ($segmentField) {
            $fields->removeByName('HideSentToSegments');
        }

        $limitField = $fields->dataFieldByName('Limit');
        if ($limitField) {
            $fields->removeByName('Limit');
        }

        $campConf = GridFieldConfig_RecordEditor::create(20);
        $campConf
            ->getComponentByType(GridFieldSortableHeader::class)
            ->setFieldSorting([
                'Title'         =>  'Title',
                'SentDate.Nice' =>  'SentDate',
                'Status'        =>  'Status'
            ]);

        $tab = Tab::create(
            'Root.Campaigns',
            _t('Innoweb\\MailChimpSignup\\Model\\CampaignListPage.Campaigns', 'Campaigns'),
            GridField::create(
                'Campaigns',
                'Campaigns',
                $this->Campaigns(),
                $campConf,
                $this
            )
        );

        $fields->insertBefore('Main', $tab);

        $apiKeyField = $apiKeyField
            ?? TextField::create(
                'APIKey',
                _t('Innoweb\\MailChimpSignup\\Model\\CampaignList.APIKEY', 'API Key')
            );
        $apiKeyField->setTitle(
            _t('Innoweb\\MailChimpSignup\\Model\\CampaignList.APIKEY', 'API Key')
        );
        $fields->addFieldToTab('Root.Mailchimp', $apiKeyField);

        if (!($this->APIKey)) {
            $fields->addFieldToTab(
                'Root.Mailchimp',
                LiteralField::create(
                    'APIKeyInfo',
                    '<p>'
                    . _t(
                        'Innoweb\\MailChimpSignup\\Model\\CampaignListPage.AddAPIKeyAndSave',
                        'Add API key and save to page to filter the campaigns.'
                    )
                    . '</p>'
                )
            );

        } else {

            // get API
            $mailChimp = new MailChimp($this->APIKey);
            $mailChimp->verify_ssl = Config::inst()->get(MailChimp::class, 'verify_ssl');

            // get lists
            $lists = $mailChimp->get('lists');
            if ($lists && isset($lists['lists'])) {

                // build list source array
                $listSource = [];
                $counter = count($lists['lists']);
                for ($pos = 0; $pos < $counter; $pos++) {
                    $listSource[$lists['lists'][$pos]['id']] = $lists['lists'][$pos]['name'];
                }

                if (!($listField instanceof MultiValueDropdownField)) {
                    $listField = MultiValueDropdownField::create('ListIDs');
                }
                $listField->setSource($listSource);
                $listField->setTitle(
                    _t(
                        'Innoweb\\MailChimpSignup\\Model\\CampaignListPage.LimitByLists',
                        'Only show campaigns sent to the following audiences'
                    )
                );

                $segmentField = $segmentField
                    ?? CheckboxField::create('HideSentToSegments');
                $segmentField->setTitle(
                    _t(
                        'Innoweb\\MailChimpSignup\\Model\\CampaignListPage.HideSentToSegments',
                        'Hide campaigns sent to segments of an audience'
                    )
                );

                $limitField = $limitField
                    ?? NumericField::create('Limit');
                $limitField->setTitle(
                    _t(
                        'Innoweb\\MailChimpSignup\\Model\\CampaignListPage.Limit',
                        'Limit campaigns shown (0 = all)'
                    )
                );

                $fields->addFieldsToTab(
                    'Root.Mailchimp',
                    [
                        $listField,
                        $segmentField,
                        $limitField,
                    ]
                );

            } else {
                $message = "An error occurred.";
                if ($lists && isset($lists['status']) && isset($lists['title']) && isset($lists['detail'])) {
                    $message .= ' ('.$lists['status'].': '.$lists['title'].': '.$lists['detail'].')';
                } elseif ($mailChimp->getLastError()) {
                    $message .= ' (last error: '.$mailChimp->getLastError().')';
                }

                $fields->addFieldToTab(
                    'Root.Mailchimp',
                    LiteralField::create('APIKeyInfo', '<p>'.$message.'</p>')
                );
            }
        }

        return $fields;
    }

    public function onAfterWrite()
    {
        parent::onAfterWrite();
        $this->updateCampaigns(true);
    }

    public function onAfterDelete()
    {
        parent::onAfterDelete();

        $missing = $this->Campaigns();
        if ($missing && $missing->exists()) {
            foreach ($missing as $gone) {
                $gone->delete();
            }
        }
    }

    public function updateCampaigns($onWrite = false)
    {
        if ($this->APIKey && ($onWrite || Config::inst()->get(CampaignListPage::class, 'auto_update'))) {
            $lastUpdated = $this->LastUpdated;
            $updateInterval = Config::inst()->get(CampaignListPage::class, 'update_interval');
            if ($lastUpdated) {
                // get next time we should update
                $nextUpdateTime = strtotime($lastUpdated . ' +' . $updateInterval . ' seconds');
            }

            // If we haven't auto-updated before (fresh install), or an update is due, do update
            if ($onWrite || !isset($nextUpdateTime) || $nextUpdateTime < time()) {

                $this->loadCampaigns();

                // Save the time the update was performed
                $this->LastUpdated = DBDatetime::now()->value;
                if (!$onWrite) {
                    $this->write();
                }
            }
        }
    }

    protected function loadCampaigns()
    {
        $mailChimp = new MailChimp($this->APIKey);
        $mailChimp->verify_ssl = Config::inst()->get(MailChimp::class, 'verify_ssl');

        // save all existing IDs to delete non-existing ones
        $campaignIDs = [];

        // get all sent campaigns
        $campaigns = $mailChimp->get(
            'campaigns',
            [
                'status'    =>  'sent',
                'type'      =>  'regular',
                'count'     =>  999,
                'fields'    =>  'campaigns.id,campaigns.settings.title,campaigns.settings.subject_line,'
                    . 'campaigns.archive_url,campaigns.send_time,campaigns.recipients.list_id,'
                    . 'campaigns.recipients.segment_opts'
            ]
        );

        if ($campaigns && isset($campaigns['campaigns'])) {
            foreach($campaigns['campaigns'] as $campaign) {

                $mcID = $this->processCampaign($campaign);

                if ($mcID) {
                    $campaignIDs[] = $mcID;
                }
            }
        } else {
            $message = 'An error occurred.';
            if ($campaigns && isset($campaigns['status']) && isset($campaigns['title']) && isset($campaigns['detail'])) {
                $message .= ' ('.$campaigns['status'].': '.$campaigns['title'].': '.$campaigns['detail'].')';
            }

            if ($mailChimp->getLastError()) {
                $message .= ' (last error: ' . $mailChimp->getLastError() . ')';
            }

            if ($mailChimp->getLastResponse()) {
                $message .= ' (last response: ' . $mailChimp->getLastResponse() . ')';
            }

            if ($mailChimp->getLastRequest()) {
                $message .= ' (last request: ' . $mailChimp->getLastRequest() . ')';
            }

            $this->logger->warning($message);
        }

        // delete campaigns that don't exist anymore
        if ($campaignIDs !== []) {
            $missing = $this->Campaigns()->exclude('MailChimpID', $campaignIDs);
            if ($missing && $missing->exists()) {
                foreach ($missing as $gone) {
                    $gone->delete();
                }
            }
        }
    }

    protected function processCampaign($campaignData = [])
    {
        if ($campaignData && count($campaignData) > 0 && isset($campaignData['id'])) {

            // check if campaign already exists
            $campaign = Campaign::get()
                ->filter([
                    'PageID' => $this->ID,
                    'MailChimpID' => $campaignData['id'],
                ])
                ->first();

            if ($campaign && $campaign->exists()) {

                // title can be updated after the campaign was sent
                if (isset($campaignData['settings']) && $campaignData['settings']['title']) {
                    $campaign->Title = $campaignData['settings']['title'];
                    $campaign->write();
                }

            } else {

                // get send date
                $sentDate = null;
                if (isset($campaignData['send_time'])) {
                    $date = DateTime::createFromFormat(DateTime::ATOM, $campaignData['send_time']);
                    if ($date !== false) {
                        $sentDate = $date->format('Y-m-d H:i:s');
                    }
                }

                // create campaign object
                $campaign = Campaign::create();
                $campaign->PageID = $this->ID;
                $campaign->MailChimpID = $campaignData['id'] ?? 0;
                $campaign->Title = (isset($campaignData['settings']) && isset($campaignData['settings']['title'])) ? $campaignData['settings']['title'] : '';
                $campaign->Subject = (isset($campaignData['settings']) && isset($campaignData['settings']['subject_line'])) ? $campaignData['settings']['subject_line'] : '';
                $campaign->SentDate =  $sentDate;
                $campaign->URL = $campaignData['archive_url'] ?? null;
                $campaign->ListID = (isset($campaignData['recipients']) && isset($campaignData['recipients']['list_id'])) ? $campaignData['recipients']['list_id'] : '';
                $campaign->SentToSegment = isset($campaignData['recipients']) && isset($campaignData['recipients']['segment_opts']);
                $campaign->write();
            }

            return $campaign->MailChimpID;
        }

        return null;
    }

    /**
     * @param LoggerInterface $logger
     * @return $this
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
        return $this;
    }
}
