<?php
use DrewM\MailChimp\MailChimp;

class MailChimpSignupPage extends Page {
    
    private static $singular_name = "MailChimp Signup Form";
    private static $plural_name = "MailChimp Signup Forms";
    private static $description = "Signup form for a MailChimp mailing list.";
    private static $icon = "mailchimp-signup/images/treeicons/page-mailchimp.png";
    
    private static $db = array(
        'APIKey' => 'Varchar(255)',
        'ListID' => 'Varchar(255)',
        'ContentSuccess' => 'Text',
        'ContentError' => 'Text'
    );
    
    private static $defaults = array(
        'ShowInMenus' => true,
        'ShowInSearch' => true,
        'ProvideComments' => false,
        'Priority' => '',
        'ContentSuccess' => 'The subscription was successful. You will receive a confirmation email shortly.',
        'ContentError' => 'Unfortunately an error occurred during your subscription. Please try again.'
    );
    
    public function getCMSFields() {
        
        $fields = parent::getCMSFields();

        $fields->addFieldToTab(
            "Root.MailChimp",
            new TextField('APIKey', _t("MailChimpSignupPage.APIKEY", 'API Key'))
        );
        $fields->addFieldToTab(
            "Root.MailChimp",
            new TextField('ListID', _t("MailChimpSignupPage.LISTID", 'List ID'))
        );
        $fields->addFieldToTab(
            "Root.MailChimp",
            new TextareaField('ContentSuccess', _t("MailChimpSignupPage.CONTENTSUCCESS", 'Text for successful submission'))
        );
        $fields->addFieldToTab(
            "Root.MailChimp",
            new TextareaField('ContentError', _t("MailChimpSignupPage.CONTENTERROR", 'Text for unsuccessful submission'))
        );
        
        return $fields;
    }
    
}