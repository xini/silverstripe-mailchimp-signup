<?php

namespace Innoweb\MailChimpSignup\Pages;

use SilverStripe\Forms\TextField;
use SilverStripe\Forms\TextareaField;

use Page;

class SignupPage extends \Page {

    private static $singular_name = 'MailChimp Signup Form';
    private static $plural_name = 'MailChimp Signup Forms';
    private static $description = 'Signup form for a MailChimp mailing list.';
    private static $icon = 'innoweb/silverstripe-mailchimp-signup:client/images/treeicons/page-mailchimp.png';

    private static $table_name = 'MailChimpSignupPage';

    private static $db = [
        'APIKey'            =>  'Varchar(255)',
        'ListID'            =>  'Varchar(255)',
        'ContentSuccess'    =>  'Text',
        'ContentError'      =>  'Text'
    ];

    private static $defaults = [
        'ShowInMenus'       =>  true,
        'ShowInSearch'      =>  true,
        'ProvideComments'   =>  false,
        'Priority'          =>  '',
        'ContentSuccess'    =>  'The subscription was successful. You will receive a confirmation email shortly.',
        'ContentError'      =>  'Unfortunately an error occurred during your subscription. Please try again.'
    ];

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        $fields->addFieldsToTab(
            'Root.MailChimp',
            [
                TextField::create(
                    'APIKey',
                    _t('Innoweb\\MailChimpSignup\\Model\\SignupPage.APIKEY', 'API Key')
                ),
                TextField::create(
                    'ListID',
                    _t('Innoweb\\MailChimpSignup\\Model\\SignupPage.LISTID', 'List ID')
                ),
                TextareaField::create(
                    'ContentSuccess',
                    _t('Innoweb\\MailChimpSignup\\Model\\SignupPage.CONTENTSUCCESS', 'Text for successful submission')
                ),
                TextareaField::create(
                    'ContentError',
                    _t('Innoweb\\MailChimpSignup\\Model\\SignupPage.CONTENTERROR', 'Text for unsuccessful submission')
                )
            ]
        );

        return $fields;
    }
}