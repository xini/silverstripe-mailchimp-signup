<?php
class MailChimpCampaign extends DataObject {
    
    private static $db = array(
        'MailChimpID' => 'Varchar(255)',
        'Title' => 'Varchar(255)',
        'Subject' => 'Varchar(255)',
        'SentDate' => 'SS_Datetime',
        'URL' => 'Varchar(1024)',
        'Hidden' => 'Boolean',
        'ListID' => 'Varchar(50)',
        'SentToSegment' => 'Boolean',
    );
    
    private static $has_one = array(
        "Page" => "MailChimpCampaignListPage",
    );
    
    private static $summary_fields = array(
        'Title' => 'Title',
        'Subject' => 'Subject',
        'SentDate.Nice' => 'Sent',
        'Status' => 'Status',
    );
    
    private static $casting = array(
        'Status' => 'Varchar(50)',
    );
    
    private static $default_sort = "SentDate DESC";
    
    public function getLink() {
        return $this->URL;
    }
    
    public function getStatus() {
        $page = $this->Page();
        if ($this->Hidden) {
            return _t('MailChimpCampaign.StatusHiddenManual', 'Hidden (manual)');
        } else if (
            ($this->SentToSegment && $page->HideSentToSegments) 
            || ($this->ListID && is_array($page->ListIDs) && in_array($this->ListID, $page->ListIDs))
        ) {
            return _t('MailChimpCampaign.StatusHiddenFilter', 'Hidden (filter)');
        } else {
            return _t('MailChimpCampaign.StatusVisible', 'Visible');
        }
    }
    
}