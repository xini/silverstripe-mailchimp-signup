<?php

class MailChimpSignupDataExtension extends DataExtension {
    
    private static $db = array(
        'APIKey' => 'Varchar(255)',
        'ListID' => 'Varchar(255)',
        'ContentSuccess' => 'Text',
        'ContentError' => 'Text',
        'RequireEmailConfirmation' => 'Boolean'
    );
    
    private static $defaults = array(
        'ContentSuccess' => 'The subscription was successful. You will receive a confirmation email shortly.',
        'ContentError' => 'Unfortunately an error occurred during your subscription. Please try again.',
        'RequireEmailConfirmation' => true,
    );
    
    public function updateCMSFields(FieldList $fields) {
        $fields->addFieldsToTab(
            "Root.MailChimp",
            array(
                TextField::create('APIKey', _t("MailChimpSignupPage.APIKEY", 'API Key')),
                TextField::create('ListID', _t("MailChimpSignupPage.LISTID", 'Audience ID')),
                FieldGroup::create(
                    CheckboxField::create('RequireEmailConfirmation', '')
                )->setTitle('Require Email Confirmation'),
                TextareaField::create('ContentSuccess', _t("MailChimpSignupPage.CONTENTSUCCESS", 'Text for successful submission')),
                TextareaField::create('ContentError', _t("MailChimpSignupPage.CONTENTERROR", 'Text for unsuccessful submission'))
            )
            
        );
    }
    
}
