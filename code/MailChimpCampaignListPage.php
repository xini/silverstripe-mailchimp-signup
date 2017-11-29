<?php
use DrewM\MailChimp\MailChimp;

class MailChimpCampaignListPage extends Page {
    
    private static $singular_name = "MailChimp Campaign List Page";
    private static $plural_name = "MailChimp Campaign List Pages";
    private static $description = "Page listing selected MailChimp campaigns.";
    private static $icon = "mailchimp-signup/images/treeicons/page-mailchimp.png";
    
    private static $db = array(
        'APIKey' => 'Varchar(255)',
        'ListIDs' => 'MultiValueField',
        'HideSentToSegments' => 'Boolean',
        'LastUpdated' => 'SS_Datetime',
        'Limit' => 'Int',
    );
    
    private static $has_many = array(
        'Campaigns' => 'MailChimpCampaign',
    );
    
    private static $default = array(
        'Limit' => '10',
    );
    
    public function getCMSFields() {
        
        $fields = parent::getCMSFields();
        
        $campConf = GridFieldConfig_RecordEditor::create(20);
        $campConf->getComponentByType('GridFieldSortableHeader')->setFieldSorting(array('Title' => 'Title', 'SentDate.Nice' => 'SentDate', 'Status' => 'Status'));
        $tab = new Tab("Root.Campaigns", _t('MailChimpCampaignListPage.Campaigns', 'Campaigns'), new GridField('Campaigns', 'Campaigns', $this->Campaigns(), $campConf, $this));
        $fields->insertBefore('Main', $tab);

        $fields->addFieldToTab(
            "Root.MailChimp",
            TextField::create('APIKey', _t("MailChimpCampaignListPage.APIKEY", 'API Key'))
        );
        
        if (!($this->APIKey)) {
            
            $fields->addFieldToTab(
                "Root.MailChimp",
                LiteralField::create('APIKeyInfo', '<p>'._t("MailChimpCampaignListPage.AddAPIKeyAndSave", 'Add API key and save to page to filter the campaigns.').'</p>')
            );
            
        } else {
            
            // get API
            $MailChimp = new MailChimp($this->APIKey);
            $MailChimp->verify_ssl = Config::inst()->get('MailChimp', 'verify_ssl');
            
            // get lists
            $lists = $MailChimp->get('lists');
            if ($lists && isset($lists['lists'])) {
                // build list source array
                $listSource = array();
                for ($pos = 0; $pos < count($lists['lists']); $pos++) {
                    $listSource[$lists['lists'][$pos]['id']] = $lists['lists'][$pos]['name'];
                }
                // add list filter
                $fields->addFieldToTab(
                    "Root.MailChimp",
                    MultiValueDropdownField::create('ListIDs', _t('MailChimpCampaignListPage.LimitByLists', 'Only show campaigns sent to the following lists'), $listSource)
                );
                // add segment checkbox
                $fields->addFieldToTab(
                    "Root.MailChimp",
                    CheckboxField::create('HideSentToSegments', _t('MailChimpCampaignListPage.HideSentToSegments', 'Hide campaigns sent to segments of a list'))
                );
                // add segment checkbox
                $fields->addFieldToTab(
                    "Root.MailChimp",
                    NumericField::create('Limit', _t('MailChimpCampaignListPage.Limit', 'Limit campaigns shown (0 = all)'))
                );
            } else  {
                $message = "An error occurred.";
                if ($lists && isset($lists['status']) && isset($lists['title']) && isset($lists['detail'])) {
                    $message .= ' ('.$lists['status'].': '.$lists['title'].': '.$lists['detail'].')';
                } else if ($MailChimp->getLastError()) {
                    $message .= ' (last error: '.$MailChimp->getLastError().')';
                }
                $fields->addFieldToTab(
                    "Root.MailChimp",
                    LiteralField::create('APIKeyInfo', '<p>'.$message.'</p>')
                );
            }
        }
        
        return $fields;
    }
    
    public function onAfterWrite() {
        parent::onAfterWrite();
        
        $this->updateCampaigns(true);
    }
    
    public function onAfterDelete() {
        parent::onAfterDelete();
        
        $missing = $this->Campaigns();
        if ($missing && $missing->exists()) {
            foreach ($missing as $gone) {
                $gone->delete();
            }
        }
    }
    
    public function updateCampaigns($onWrite = false) {
    
        if ($this->APIKey) {
    
            if ($onWrite || Config::inst()->get('MailChimpCampaignListPage', 'auto_update')) {
    
                $lastUpdated = $this->LastUpdated;
                $updateInterval = Config::inst()->get('MailChimpCampaignListPage', 'update_interval');
    
                if ($lastUpdated) {
                    // get next time we should update
                    $nextUpdateTime = strtotime($lastUpdated . ' +' . $updateInterval . ' seconds');
                }
    
                // If we haven't auto-updated before (fresh install), or an update is due, do update
                if ($onWrite || !isset($nextUpdateTime) || $nextUpdateTime < time()) {
    
                    $this->loadCampaigns();
    
                    // Save the time the update was performed
                    $this->LastUpdated = SS_Datetime::now()->value;
                    if (!$onWrite) {
                        $this->write();
                    }
                }
            }
    
        }
    
    }
    
    private function loadCampaigns() {
    
        // get API
        $MailChimp = new MailChimp($this->APIKey);
        $MailChimp->verify_ssl = Config::inst()->get('MailChimp', 'verify_ssl');
    
        // save all existing IDs to delete non existing ones
        $campaignIDs = array();
    
        // get all sent campaigns
        $campaigns = $MailChimp->get('campaigns', array(
            'status' => 'sent',
            'type' => 'regular',
            'count' => 999,
            'fields' => 'campaigns.id,campaigns.settings.title,campaigns.settings.subject_line,campaigns.archive_url,campaigns.send_time,campaigns.recipients.list_id,campaigns.recipients.segment_opts',
        ));
        if ($campaigns && isset($campaigns['campaigns'])) {
            foreach($campaigns['campaigns'] as $campaign) {
    
                $mcID = $this->processCampaign($campaign);
    
                if ($mcID) {
                    $campaignIDs[] = $mcID;
                }
    
            }
        } else {
            $message = "An error occurred.";
            if ($campaigns && isset($campaigns['status']) && isset($campaigns['title']) && isset($campaigns['detail'])) {
                $message .= ' ('.$campaigns['status'].': '.$campaigns['title'].': '.$campaigns['detail'].')';
            }
            if ($MailChimp->getLastError()) {
                $message .= ' (last error: '.$MailChimp->getLastError().')';
            }
            if ($MailChimp->getLastResponse()) {
                $message .= ' (last response: '.print_r($MailChimp->getLastResponse(), true).')';
            }
            if ($MailChimp->getLastRequest()) {
                $message .= ' (last reguest: '.print_r($MailChimp->getLastRequest(), true).')';
            }
            SS_Log::log($message, SS_Log::WARN);
        }
    
        // delete campaigns that don't exist anymore
        $missing = $this->Campaigns()->exclude(array('MailChimpID' => $campaignIDs));
        if ($missing && $missing->exists()) {
            foreach ($missing as $gone) {
                $gone->delete();
            }
        }
    
    }
    
    private function processCampaign($campaigndata = array()) {
        if ($campaigndata && count($campaigndata) > 0 && isset($campaigndata['id'])) {
    
            // check if campaign already exists
            $campaign = MailChimpCampaign::get()->filter(array('MailChimpID' => $campaigndata['id']))->first();
    
            if ($campaign && $campaign->exists()) {
    
                // title can be updated after the campaign was sent
                if (isset($campaigndata['settings']) && isset($campaigndata['settings']['title'])) {
                    $campaign->Title = $campaigndata['settings']['title'];
                    $campaign->write();
                }
    
            } else {
    
                // get send date
                $sentDate = null;
                if (isset($campaigndata['send_time'])) {
                    $date = DateTime::createFromFormat(DateTime::ATOM, $campaigndata['send_time']);
                    if ($date !== false) {
                        $sentDate = $date->format('Y-m-d H:i:s');
                    }
                }
    
                // create campaign object
                $campaign = MailChimpCampaign::create();
                $campaign->PageID = $this->ID;
                $campaign->MailChimpID = isset($campaigndata['id']) ? $campaigndata['id'] : 0;
                $campaign->Title = (isset($campaigndata['settings']) && isset($campaigndata['settings']['title'])) ? $campaigndata['settings']['title'] : '';
                $campaign->Subject = (isset($campaigndata['settings']) && isset($campaigndata['settings']['subject_line'])) ? $campaigndata['settings']['subject_line'] : '';
                $campaign->SentDate =  $sentDate;
                $campaign->URL = isset($campaigndata['archive_url']) ? $campaigndata['archive_url'] : null;
                $campaign->ListID = (isset($campaigndata['recipients']) && isset($campaigndata['recipients']['list_id'])) ? $campaigndata['recipients']['list_id'] : '';
                $campaign->SentToSegment = (isset($campaigndata['recipients']) && isset($campaigndata['recipients']['segment_opts'])) ? true : false;
                $campaign->write();
    
            }
    
            return $campaign->MailChimpID;
        }
    
        return null;
    }
    
    
}

class MailChimpCampaignListPage_Controller extends Page_Controller {
    
    public function init() {
        parent::init();
        
        // update campaigns
        $page = $this->dataRecord;
        $page->updateCampaigns();
        
    }
    
    public function getFilteredCampaigns() {
        // get page
        $page = $this->dataRecord;
        // setup filter
        $filter = array(
            'Hidden' => false,
            'PageID' => $page->ID,
        );
        $listIDs = $page->dbObject('ListIDs')->getValue();
        if ($listIDs && is_array($listIDs) && count($listIDs) > 0) {
            $filter['ListID'] = $listIDs;
        }
        if ($page->HideSentToSegments) {
            $filter['SentToSegment'] = false;
        }
        // load campaigns
        $campaigns = MailChimpCampaign::get()->filter($filter);
        // limit results
        if ($page->Limit && $page->Limit > 0) {
            $campaigns = $campaigns->limit($page->Limit);
        }
        // return
        return $campaigns;
    }
    
}
