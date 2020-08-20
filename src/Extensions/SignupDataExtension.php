<?php

namespace Innoweb\MailChimpSignup\Extensions;

use Psr\SimpleCache\CacheInterface;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\FieldGroup;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\RequiredFields;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\TextareaField;
use SilverStripe\ORM\DataExtension;

class SignupDataExtension extends DataExtension
{
    private static $db = [
        'APIKey' =>  'Varchar(255)',
        'ListID' =>  'Varchar(255)',
        'ContentSuccess' =>  'Text',
        'ContentError' =>  'Text',
        'RequireEmailConfirmation' => 'Boolean',
    ];

    private static $defaults = [
        'ContentSuccess' =>  'The subscription was successful. You will receive a confirmation email shortly.',
        'ContentError' =>  'Unfortunately an error occurred during your subscription. Please try again.',
        'RequireEmailConfirmation' => true,
    ];

    public function updateCMSFields(FieldList $fields)
    {
        $fields->removeByName([
            'APIKey',
            'ListID',
            'RequireEmailConfirmation',
            'ContentSuccess',
            'ContentError']
        );
        $fields->addFieldsToTab(
            'Root.Mailchimp',
            [
                TextField::create(
                    'APIKey',
                    _t('Innoweb\\MailChimpSignup\\Extensions\\SignupDataExtension.APIKEY', 'API Key')
                ),
                TextField::create(
                    'ListID',
                    _t('Innoweb\\MailChimpSignup\\Extensions\\SignupDataExtension.LISTID', 'Audience ID')
                ),
                FieldGroup::create(
                    CheckboxField::create('RequireEmailConfirmation', '')
                )->setTitle(_t('Innoweb\\MailChimpSignup\\Extensions\\SignupDataExtension.RequireEmailConfirmation', 'Require Email Confirmation')),
                TextareaField::create(
                    'ContentSuccess',
                    _t('Innoweb\\MailChimpSignup\\Extensions\\SignupDataExtension.CONTENTSUCCESS', 'Text for successful submission')
                ),
                TextareaField::create(
                    'ContentError',
                    _t('Innoweb\\MailChimpSignup\\Extensions\\SignupDataExtension.CONTENTERROR', 'Text for unsuccessful submission')
                )
            ]
        );
    }

    public function getCMSValidator()
    {
        return RequiredFields::create('APIKey', 'ListID');
    }

    public function onAfterWrite()
    {
        // clear form field cache
        $cache = Injector::inst()->get(CacheInterface::class . '.MailchimpFieldCache');
        $cache->clear();
    }

    public function onAfterPublish()
    {
        // clear form field cache
        $cache = Injector::inst()->get(CacheInterface::class . '.MailchimpFieldCache');
        $cache->clear();
    }
}