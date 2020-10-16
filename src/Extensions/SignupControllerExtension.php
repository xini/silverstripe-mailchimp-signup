<?php

namespace Innoweb\MailChimpSignup\Extensions;

use DrewM\MailChimp\MailChimp;
use Innoweb\MailChimpSignup\MailchimpDataLoader;
use Innoweb\MailChimpSignup\Forms\SignupPageValidator;
use SilverStripe\Core\Extension;
use SilverStripe\Core\Config\Config;
use SilverStripe\Forms\CheckboxSetField;
use SilverStripe\Forms\CompositeField;
use SilverStripe\Forms\DateField;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\EmailField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\NumericField;
use SilverStripe\Forms\OptionsetField;
use SilverStripe\Forms\TextField;
use SilverStripe\SpamProtection\Extension\FormSpamProtectionExtension;
use SilverStripe\View\Requirements;
use BadMethodCallException;
use SilverStripe\Core\Config\Configurable;
use Dynamic\CountryDropdownField\Fields\CountryDropdownField;
use SilverStripe\ORM\FieldType\DBDate;

class SignupControllerExtension extends Extension
{
    use Configurable;

    private static $allowed_actions = [
        'Form',
        'success'
    ];

    private static $block_default_jquery_and_validate = false;
    private static $block_form_validation = false;

    public function Form()
    {
        $this->getOwner()->invokeWithExtensions('onBeforeSignupForm');

        // Get list data
        $listInfo = $this->getOwner()->getMailchimpMergeFields();

        // create field list and validator
        $fields = FieldList::create();

        // create validator with default email field
        $validator = SignupPageValidator::create('EMAIL');

        $emailAdded = false;

        if ($listInfo) {
            $fieldData = $listInfo['merge_fields'];

            // sort fields
            $sortedData = $this->getOwner()->sortArray($fieldData, 'display_order', SORT_ASC);

            for ($pos = 1; $pos <= count($sortedData); $pos++) {

                // get field data
                $field = $sortedData[$pos-1];
                $newField = null;

                // check if email needs to be added
                if ($pos != $field['display_order'] && !$emailAdded) {
                    $newField = EmailField::create(
                        'EMAIL',
                        _t('Innoweb\\MailChimpSignup\\Extension\\SignupControllerExtension.EmailAddress', 'Email Address'),
                        null,
                        255
                    );
                    $fields->push($newField);
                    $emailAdded = true;
                }

                if ($field['public']) {

                    // add field validation
                    if ($field['required']) {
                        $validator->addRequiredField($field['tag']);
                    }

                    // add field
                    switch ($field['type']) {

                        case 'text':
                            if ($field['tag'] == 'EMAIL') {
                                $newField = EmailField::create(
                                    $field['tag'],
                                    $field['name'],
                                    $field['default_value'],
                                    255
                                );
                                $emailAdded = true;
                            } else {
                                $newField = TextField::create(
                                    $field['tag'],
                                    $field['name'],
                                    $field['default_value'],
                                    255
                                );
                            }
                            break;

                        case 'number':
                            $newField = NumericField::create(
                                $field['tag'],
                                $field['name'],
                                $field['default_value'],
                                255
                            );
                            break;

                        case 'date':
                        case 'birthday':
                            $newField = DateField::create(
                                $field['tag'],
                                $field['name'],
                                $field['default_value'],
                                255
                            );
                            break;

                        case 'phone':
                            $newField = TextField::create(
                                $field['tag'],
                                $field['name'],
                                $field['default_value'],
                                255
                            )->setAttribute('type', 'tel');
                            break;

                        case 'imageurl':
                        case 'url':
                            $newField = TextField::create(
                                $field['tag'],
                                $field['name'],
                                $field['default_value'],
                                255
                            )->setAttribute('type', 'url');
                            break;

                        case 'dropdown':

                            // set value and key to value
                            $optionSet = [];
                            foreach ($field['options']['choices'] as $opt) {
                                $optionSet[$opt] = $opt;
                            }
                            $newField = DropdownField::create(
                                $field['tag'],
                                $field['name'],
                                $optionSet
                            );
                            break;

                        case 'radio':

                            // set value and key to value
                            $optionSet = [];
                            foreach ($field['options']['choices'] as $opt) {
                                $optionSet[$opt] = $opt;
                            }
                            $newField = OptionsetField::create(
                                $field['tag'],
                                $field['name'],
                                $optionSet
                            );
                            break;

                        case 'zip':
                            $newField = TextField::create(
                                $field['tag'],
                                $field['name'],
                                $field['default_value'],
                                10
                            );
                            break;

                        case 'address':
                            $newField = CompositeField::create(
                                TextField::create(
                                    $field['tag'].'_addr1',
                                    _t("Innoweb\\MailChimpSignup\\Extension\\SignupControllerExtension.AddressLine1", 'Street Address'),
                                    null,
                                    255
                                ),
                                TextField::create(
                                    $field['tag'].'_addr2',
                                    _t("Innoweb\\MailChimpSignup\\Extension\\SignupControllerExtension.AddressLine2", 'Unit/Floor'),
                                    null,
                                    255
                                ),
                                TextField::create(
                                    $field['tag'].'_city',
                                    _t("Innoweb\\MailChimpSignup\\Extension\\SignupControllerExtension.City", 'City'),
                                    null,
                                    50
                                ),
                                TextField::create(
                                    $field['tag'].'_state',
                                    _t("Innoweb\\MailChimpSignup\\Extension\\SignupControllerExtension.State", 'State/Province'),
                                    null,
                                    50
                                ),
                                TextField::create(
                                    $field['tag'].'_zip',
                                    _t("Innoweb\\MailChimpSignup\\Extension\\SignupControllerExtension.ZipCode", 'Zip/Post Code'),
                                    null,
                                    20
                                ),
                                CountryDropdownField::create(
                                    $field['tag'].'_country',
                                    _t("Innoweb\\MailChimpSignup\\Extension\\SignupControllerExtension.Country", 'Country')
                                )->setEmptyString(_t("Innoweb\\MailChimpSignup\\Extension\\SignupControllerExtension.SELECT", '- select -'))
                            )->setLegend($field['name'])->setTag('fieldset');
                            // add required fields
                            if ($field['required']) {
                                $validator->addRequiredField($field['tag'].'_addr1');
                                $validator->addRequiredField($field['tag'].'_city');
                                $validator->addRequiredField($field['tag'].'_state');
                                $validator->addRequiredField($field['tag'].'_zip');
                                $validator->addRequiredField($field['tag'].'_country');
                            } else {
                                $validator->addAddressField($field['tag']);
                            }
                            break;

                        default:
                            $newField = LiteralField::create(
                                $field['tag'],
                                'ERROR: UNSUPPORTED FIELDTYPE ('
                                    . $field['type']
                                    . ') -> '
                                    . $field['tag']
                                    . ' '
                                    . $field['name']
                            );
                    }

                    $fields->push($newField);

                    // add description to field
                    if ($field['help_text']) {
                        $newField->setRightTitle($field['help_text']);
                    }
                }
            }
        } else {
            return false;
        }

        // check again if email needs to be added
        if (!$emailAdded) {
            $newField = EmailField::create(
                'EMAIL',
                _t('Innoweb\\MailChimpSignup\\Extension\\SignupControllerExtension.EmailAddress', 'Email Address'),
                null,
                255
            );
            $fields->push($newField);
        }

        // add group fields
        $groupInfo = $this->getOwner()->getMailchimpCategories();
        if ($groupInfo) {
            foreach ($groupInfo as $group) {

                // get options
                $options = [];
                $groupOptions = $group['options'];

                if ($groupOptions && isset($groupOptions['interests'])) {
                    $sortedOptions = $this->getOwner()->sortArray($groupOptions['interests'], 'display_order', SORT_ASC);
                    foreach ($sortedOptions as $option) {
                        $options[$option['id']] = $option['name'];
                    }
                }

                // add field
                switch ($group['type']) {
                    case 'radio':
                        $fields->push(
                            OptionsetField::create(
                                'groupings_'.$group['id'],
                                $group['title'],
                                $options
                            )
                        );
                        break;

                    case 'checkboxes':
                        $fields->push(
                            CheckboxSetField::create(
                                'groupings_'.$group['id'],
                                $group['title'],
                                $options
                            )
                        );
                        break;

                    case 'dropdown':
                        $fields->push(
                            DropdownField::create(
                                'groupings_'.$group['id'],
                                $group['title'],
                                $options
                            )
                        );
                        break;

                    case 'hidden':
                        //skip it
                        break;

                    default:
                        $fields->push(
                        LiteralField::create(
                            $group['id'],
                            'ERROR: UNSUPPORTED GROUP TYPE ('
                                . $group['form_field']
                                . ') -> '
                                . $group['id']
                                . ' '
                                . $group['title']
                            )
                        );
                }
            }
        }

        $usesEmailTypeOptions = $this->getOwner()->getUsesEmailTypeOptions();
        if ($usesEmailTypeOptions) {
            $fields->push(
                OptionsetField::create(
                    'EMAILTYPE',
                    _t('Innoweb\\MailChimpSignup\\Extension\\SignupControllerExtension.PREFERREDFORMAT', 'Preferred Format'),
                    [
                        'html' => _t('Innoweb\\MailChimpSignup\\Extension\\SignupControllerExtension.FORMATHTML', 'HTML'),
                        'text' => _t('Innoweb\\MailChimpSignup\\Extension\\SignupControllerExtension.FORMATTEXT', 'Plain-text')
                    ],
                    'html'
                )
            );
            $validator->addRequiredField('EMAILTYPE');
        }

        $actions = FieldList::create(
            FormAction::create('subscribe', _t('Innoweb\\MailChimpSignup\\Extension\\SignupControllerExtension.SUBSCRIBE', 'Subscribe'))
        );

        $form = Form::create(
            $this->getOwner(),
            'Form',
            $fields,
            $actions,
            $validator
        );

        $form->setHTMLID('mailchimp-signup-form');

        // Retrieve potentially saved data and populate form field if values were present in session variables
        try {
            $session = $this->getOwner()->getRequest()->getSession();
            $sessionData = $session->get('FormInfo.' . $form->FormName() . '.data');
            if (is_array($sessionData)) {
                $form->loadDataFrom($sessionData);
            }
        } catch (BadMethodCallException $e) {
            // no session available
        }

        // load jquery and validate
        if (!$this->getOwner()->config()->get('block_default_jquery_and_validate') && !$this->config()->get('block_default_jquery_and_validate')) {
            Requirements::javascript('innoweb/silverstripe-mailchimp-signup: client/dist/javascript/jquery.min.js');
            Requirements::javascript('innoweb/silverstripe-mailchimp-signup: client/dist/javascript/jquery-validate.min.js');
        }
        // load validation script
        if (!$this->getOwner()->config()->get('block_form_validation') && !$this->config()->get('block_form_validation')) {
            Requirements::javascript('innoweb/silverstripe-mailchimp-signup: client/dist/javascript/mailchimp-validation.min.js');
        }

        // enable spam protection if available
        if (class_exists(FormSpamProtectionExtension::class)) {
            $form = $form->enableSpamProtection();
        }

        // Re-initiate the form error set up with the new HTMLID and Spam Protection field (if applies).
        $form->restoreFormState();

        $this->getOwner()->invokeWithExtensions('updateSignupForm', $form);

        return $form;
    }

    public function getMailchimpMergeFields()
    {
        if (!$this->getOwner()->data()->APIKey || !$this->getOwner()->data()->ListID) {
            user_error("MailChimp API key or list ID is missing", E_USER_WARNING);
            return false;
        }

        $loader = MailchimpDataLoader::getInstance($this->getOwner()->data()->APIKey, $this->getOwner()->data()->ListID);
        $fields = $loader->getMergeFields();

        $this->getOwner()->invokeWithExtensions('updateMailchimpMergeFields', $fields);

        return $fields;
    }

    public function getMailchimpCategories()
    {
        if (!$this->getOwner()->data()->APIKey || !$this->getOwner()->data()->ListID) {
            user_error("MailChimp API key or list ID is missing", E_USER_WARNING);
            return false;
        }

        $loader = MailchimpDataLoader::getInstance($this->getOwner()->data()->APIKey, $this->getOwner()->data()->ListID);
        $categories = $loader->getCategories();

        $this->getOwner()->invokeWithExtensions('updateMailchimpCategories', $categories);

        return $categories;
    }

    public function getUsesEmailTypeOptions()
    {
        if (!$this->getOwner()->data()->APIKey || !$this->getOwner()->data()->ListID) {
            user_error("MailChimp API key or list ID is missing", E_USER_WARNING);
            return false;
        }

        $loader = MailchimpDataLoader::getInstance($this->getOwner()->data()->APIKey, $this->getOwner()->data()->ListID);
        $flag = $loader->getUsesEmailTypeOptions();

        $this->getOwner()->invokeWithExtensions('updateUsesEmailTypeOptions', $flag);

        return $flag;
    }

    public function subscribe($data, $form)
    {
        $result = $this->getOwner()->doSubscription($data);
        $session = $this->getOwner()->getRequest()->getSession();

        if (isset($result['type']) && $result['type'] == 'good') {

            // clear session data
            $sessionData = $session->get('FormInfo.' . $form->FormName() . '.data');
            if (is_array($sessionData)) {
                $session->clear('FormInfo.' . $form->FormName() . '.data');
            }

            // redirect
            $successLink = $this->getOwner()->join_links($this->getOwner()->Link(), 'success');
            if ($this->getOwner()->hasMethod('getSuccessLink')) {
                $successLink = $this->getOwner()->getSuccessLink();
            }
            return $this->getOwner()->redirect($successLink);

        } else {

            // Store the submitted data in case the user needs to try again
            $session->set('FormInfo.' . $form->FormName() . '.data', $data);

            // add error message
            $form->sessionMessage($result['message'], $result['type']);

            // redirect back
            if ($this->getOwner()->hasMethod('getErrorLink')) {
                return $this->getOwner()->redirect($this->getOwner()->getErrorLink());
            }
            return $this->getOwner()->redirectBack();
        }
    }

    public function success()
    {
        return $this->getOwner();
    }

    public function doSubscription($data)
    {
        $returnData = [
            'type'      =>  'error',
            'message'   =>  $this->getOwner()->data()->ContentError
        ];

        // initialize
        $mailChimp = new MailChimp($this->getOwner()->data()->APIKey);
        $mailChimp->verify_ssl = Config::inst()->get(MailChimp::class, 'verify_ssl');

        $memberInfo = $mailChimp->get(sprintf(
            'lists/%s/members/%s',
            $this->getOwner()->data()->ListID,
            md5(strtolower($data['EMAIL']))
        ));
        $memberFound = $mailChimp->success();

        if ($memberFound && $memberInfo && isset($memberInfo['status']) && $memberInfo['status'] == 'subscribed') {

            // The e-mail address has already subscribed, provide feedback
            $returnData['type'] = 'warning';
            $returnData['message'] = _t(
                'Innoweb\\MailChimpSignup\\Extension\\SignupControllerExtension.DUPLICATE',
                'This email address is already subscribed to this list.'
            );

        } else {

            // gather member data for submission

            $mergeVars = [];
            // get list data
            $listInfo = $this->getOwner()->getMailchimpMergeFields();
            if ($listInfo) {
                $fieldData = $listInfo['merge_fields'];
                foreach ($fieldData as $field) {
                    if ($field['public']) {
                        if ($field['type'] == 'address') {
                            // if field type is address, get data from multiple addess fields
                            $addressData = array();
                            if (isset($data[$field['tag'].'_addr1']) && strlen($data[$field['tag'].'_addr1']) > 0) {
                                $addressData['addr1'] = $data[$field['tag'].'_addr1'];
                            }
                            if (isset($data[$field['tag'].'_addr2']) && strlen($data[$field['tag'].'_addr2']) > 0) {
                                $addressData['addr2'] = $data[$field['tag'].'_addr2'];
                            }
                            if (isset($data[$field['tag'].'_city']) && strlen($data[$field['tag'].'_city']) > 0) {
                                $addressData['city'] = $data[$field['tag'].'_city'];
                            }
                            if (isset($data[$field['tag'].'_state']) && strlen($data[$field['tag'].'_state']) > 0) {
                                $addressData['state'] = $data[$field['tag'].'_state'];
                            }
                            if (isset($data[$field['tag'].'_zip']) && strlen($data[$field['tag'].'_zip']) > 0) {
                                $addressData['zip'] = $data[$field['tag'].'_zip'];
                            }
                            if (isset($data[$field['tag'].'_country']) && strlen($data[$field['tag'].'_country']) > 0) {
                                $addressData['country'] = $data[$field['tag'].'_country'];
                            }
                            if (count($addressData) > 0) {
                                $mergeVars[$field['tag']] = $addressData;
                            }
                        } else if ($field['type'] == 'date') {
                            $formattedDate = '';
                            if (isset($data[$field['tag']]) && strlen($data[$field['tag']]) > 0) {
                                $formattedDate = DBDate::create()->setValue($data[$field['tag']])->Format('yyyy-MM-dd 00:00:00');
                            }
                            $mergeVars[$field['tag']] = $formattedDate;
                        } else if ($field['type'] == 'birthday') {
                            $formattedDate = '';
                            if (isset($data[$field['tag']]) && strlen($data[$field['tag']]) > 0) {
                                $formattedDate = DBDate::create()->setValue($data[$field['tag']])->Format('MM/dd');
                            }
                            $mergeVars[$field['tag']] = $formattedDate;
                        } else if (isset($data[$field['tag']])) {
                            // add value from field
                            $mergeVars[$field['tag']] = $data[$field['tag']];
                        }
                    }
                }
            }

            // same for groups
            $aGroups = [];
            $groupInfo = $this->getOwner()->getMailchimpCategories();
            if ($groupInfo) {
                foreach ($groupInfo as $group) {

                    // get options
                    $groupOptions = $group['options'];

                    if ($groupOptions && isset($groupOptions['interests'])) {
                        // get submitted data
                        if (is_array($data['groupings_' . $group['id']])) {
                            $submittedGroups = $data['groupings_' . $group['id']];
                        } else {
                            $submittedGroups = [$data['groupings_' . $group['id']]];
                        }
                        // init group array
                        foreach ($groupOptions['interests'] as $option) {
                            if (in_array($option['id'], $submittedGroups)) {
                                $aGroups[$option['id']] = true;
                            } else {
                                $aGroups[$option['id']] = false;
                            }
                        }
                    }
                }
            }

            // build submission data
            $submissionData = [
                'email_address' => $data['EMAIL'],
                'status' => $this->getOwner()->data()->RequireEmailConfirmation ? 'pending' : 'subscribed',
            ];
            if (count($mergeVars)) {
                $submissionData['merge_fields'] = $mergeVars;
            }
            if (count($aGroups)) {
                $submissionData['interests'] = $aGroups;
            }

            if (!$memberFound) {

                // no on list, new subscription

                $mailChimp->post(
                    sprintf(
                        'lists/%s/members',
                        $this->getOwner()->data()->ListID
                    ),
                    $submissionData
                );

            } else {

                // update existing record

                $mailChimp->patch(
                    sprintf(
                        'lists/%s/members/%s',
                        $this->getOwner()->data()->ListID,
                        md5(strtolower($data['EMAIL']))
                    ),
                    $submissionData
                );
            }

            // check if update/adding successful
            if ($mailChimp->success()) {

                // set message
                $returnData['type'] = 'good';
                $returnData['message'] = $this->getOwner()->data()->ContentSuccess;

            } else {
                user_error('Last Error: ' . print_r($mailChimp->getLastError(), true), E_USER_WARNING);
                user_error('Last Request: ' . print_r($mailChimp->getLastRequest(), true), E_USER_WARNING);
                user_error('Last Response: ' . print_r($mailChimp->getLastResponse(), true), E_USER_WARNING);
            }
        }

        return $returnData;
    }

    public function sortArray()
    {
        $args = func_get_args();
        $data = array_shift($args);
        foreach ($args as $n => $field) {
            if (is_string($field)) {
                $tmp = [];
                foreach ($data as $key => $row) {
                    $tmp[$key] = $row[$field];
                }
                $args[$n] = $tmp;
            }
        }
        $args[] = &$data;
        call_user_func_array('array_multisort', $args);
        return array_pop($args);
    }

}