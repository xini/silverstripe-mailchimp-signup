<?php

class MailChimpSignupPage extends Page {
    
    private static $singular_name = "MailChimp Signup Form";
    private static $plural_name = "MailChimp Signup Forms";
    private static $description = "Signup form for a MailChimp mailing list.";
    private static $icon = "mailchimp-signup/images/treeicons/page-mailchimp.png";
    
    private static $extensions = array(
        'MailChimpSignupDataExtension',
    );
    
    private static $defaults = array(
        'ShowInMenus' => true,
        'ShowInSearch' => true,
        'ProvideComments' => false,
        'Priority' => '',
    );
    
    public function getCMSFields() {
        $fields = parent::getCMSFields();
        
        $this->extend('updateMailchimpCMSFields', $fields);
        
        return $fields;
    }
    
}

class MailChimpSignupPage_Controller extends Page_Controller {
    
    private static $extensions = array(
        'MailChimpSignupControllerExtension',
    );
    
}
