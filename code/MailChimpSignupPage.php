<?php
use DrewM\MailChimp\MailChimp;

class MailChimpSignupPage extends Page {
    
    static $db = array(
        'APIKey' => 'Varchar(255)',
        'ListID' => 'Varchar(255)',
        'ContentSuccess' => 'Text',
        'ContentError' => 'Text'
    );
    
    static $defaults = array(
        'ShowInMenus' => true,
        'ShowInSearch' => true,
        'ProvideComments' => false,
        'Priority' => '',
        'ContentSuccess' => 'The subscription was successful. You will receive a confirmation email shortly.',
        'ContentError' => 'Unfortunately an error occurred during your subscription. Please try again.'
    );
    
    function getCMSFields() {
        
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

class MailChimpSignupPage_Controller extends Page_Controller {
    
    private static $allowed_actions = array(
        'Form',
        'success',
    );
    
    public function Form() {
        
        if (!($this->APIKey)) { Debug::show('Please, set API key first in CMS'); return false; }
        if (!($this->ListID)) { Debug::show('Please, set list id first in CMS'); return false; }
        
        // validation requirements
        Requirements::javascript(THIRDPARTY_DIR.'/jquery/jquery.min.js');
        Requirements::javascript(THIRDPARTY_DIR.'/jquery-validate/jquery.validate.min.js');
        
        //initialize
        $MailChimp = new MailChimp($this->APIKey);
        
        // Get list data
        $listInfo = $MailChimp->get(sprintf(
            '/lists/%s/merge-fields', 
            $this->ListID
        ));
        $groupInfo = $MailChimp->get(sprintf(
            '/lists/%s/interest-categories',
            $this->ListID
        ));
//        Debug::log(print_r($listInfo, true));
//        Debug::log(print_r($groupInfo, true));
        
        // create field list and validator
        $fields = new FieldList();
        $fields->push(new LiteralField('required', '<p class="info">'._t("MailChimpSignupPage.REQUIRED", 'Required fields are marked with an asterisk (*)').'</p>'));
        $validator = new RequiredFields('EMAIL');
        $jsValidation = array();
        
        if ($listInfo && isset($listInfo['merge_fields'])) {
            $fielddata = $listInfo['merge_fields'];
            
            // sort fields
            $sorteddata = $this->sortArray($fielddata, 'display_order', SORT_ASC);
            
            $emailAdded = false;
            
            for ($pos = 1; $pos <= count($sorteddata); $pos++) {
                
                // get field data
                $field = $sorteddata[$pos-1];
                $newField = null;
                
                // check if email needs to be added
                if ($pos != $field['display_order'] && !$emailAdded) {
                    $fields->push( $newField = new EmailField('EMAIL', _t("MailChimpSignupPage.EmailAddress", 'Email Address'), null, 255));
                }
                
                if ($field['public']) {
                    
                    //add field validation
                    if ($field['required']) {
                        $validator->addRequiredField($field['tag']);
                        $jsValidation[] = $field['tag'];
                    }
                    
                    // add field
                    switch ($field['type']) {
                    
                        case 'text':
                            $fields->push( $newField = new TextField($field['tag'], $field['name'], $field['default_value'], 255));
                        break;
                                                
                        case 'dropdown':
                            
                            //set value and key to value
                            $optionSet = array();
                            foreach($field['choices'] as $opt) { $optionSet[$opt] = $opt; }
                            
                            $fields->push( $newField = new DropdownField($field['tag'], $field['name'], $optionSet) );
                        break;
                        
                        case 'radio':
                        
                            //set value and key to value
                            $optionSet = array();
                            foreach($field['choices'] as $opt) { $optionSet[$opt] = $opt; }
                            
                            $fields->push( $newField = new OptionsetField($field['tag'], $field['name'], $optionSet) );
                            break;
    
                        
                        default:
                            $fields->push( $newField = new LiteralField($field['tag'], 'ERROR: UNSUPPORTED FIELDTYPE ('.$field['type'].') -> ' . $field['tag'] .' '. $field['name']));
                    }
                    
                }
                
                //add description to field
                if (isset($newField) && $field['help_text']) {$newField->setRightTitle($field['help_text']);}
                
            }
        } else {
            $message = "Form fields could not be loaded.";
            if ($listInfo && isset($listInfo['status']) && isset($listInfo['error']) && isset($listInfo['title'])) {
                $message .= ' ('.$listInfo['status'].': '.$listInfo['title'].': '.$listInfo['error'].')';
            }
            throw new Exception($message);
        }
        
        if ($groupInfo && isset($groupInfo['categories'])) {
            foreach($groupInfo['categories'] as $group) {
                
                // get options
                $options = array();
                $groupOptions = $MailChimp->get(sprintf(
                    '/lists/%s/interest-categories/%s/interests', 
                    $this->ListID,
                    $group['id']
                ));
//                Debug::log(print_r($groupOptions, true));
                if ($groupOptions && isset($groupOptions['interests'])) {
                    $sortedOptions = $this->sortArray($groupOptions['interests'], 'display_order', SORT_ASC);
                    foreach ($sortedOptions as $option) {
                        $options[$option['id']] = $option['name']; 
                    }
                }
            
                // add field
                switch ($group['type']) {
                    case 'radio':
                        $fields->push( new OptionsetField('groupings_'.$group['id'], $group['title'], $options) );
                    break;
                    
                    case 'checkboxes':
                        $fields->push( new CheckboxSetField('groupings_'.$group['id'], $group['title'], $options) );
                    break;
                    
                    case 'dropdown':
                        $fields->push( new DropdownField('groupings_'.$group['id'], $group['title'], $options) );
                    break;
                    
                    case 'hidden':
                        //skip it
                    break;
                    
                    default:
                        $fields->push( new LiteralField($group['id'], 'ERROR: UNSUPPORTED GROUP TYPE ('.$group['form_field'].') -> ' . $group['id'] .' '. $group['title']));
                }
            }
        }
        
        $actions = new FieldList(
            new FormAction('subscribe', _t("MailChimpSignupPage.SUBSCRIBE", 'subscribe'))
        );

        $form = new Form($this, 'Form', $fields, $actions, $validator);

        // Retrieve potentially saved data and populate form fields if values were present in session variables
        $data = Session::get("FormInfo.".$form->FormName().".data");
        if(is_array($data)) {
            $form->loadDataFrom($data);
        }

        if (count($jsValidation) > 0 ) {
            $js = 'jQuery(document).ready(function() { $("#'.$form->FormName().'").validate({ rules: {';
            foreach ($jsValidation as $field) {
                $js .= $field.': { required: true },';
            }
            $js = rtrim($js, ",");
            $js .= '}});});';
            Requirements::customScript($js, 'formvalidator');
        }
        
        if (class_exists('SpamProtectorManager')) {
            $form->enableSpamProtection();
        }

        return $form;
    }
 
    public function subscribe($data, $form) {
        
        //initialize
        $MailChimp = new MailChimp($this->APIKey);
        
        $memberInfo = $MailChimp->get(sprintf(
            '/lists/%s/members/%s',
            $this->ListID,
            md5(strtolower($data['EMAIL']))
        ));
//        Debug::log(print_r($memberInfo, true));
        $memberFound = $MailChimp->success();
        
        $sFeedbackType = "error";
        $sFeedbackMsg = $this->ContentError;
        
        if ($memberFound && $memberInfo && isset($memberInfo['status']) && $memberInfo['status'] == "subscribed") {

            // The e-mail address has already subscribed, provide feedback
            $sFeedbackType = "warning";
            $sFeedbackMsg = _t("MailChimpSignupPage.DUPLICATE", "This email address is already subscribed to this list.");
            
        } else {
            
            // gather member data for submission
            
            //get list data
            $mergeVars = array();
            $listInfo = $MailChimp->get(sprintf(
                '/lists/%s/merge-fields',
                $this->ListID
            ));
            if ($listInfo && isset($listInfo['merge_fields'])) {
                $fielddata = $listInfo['merge_fields'];
                foreach($fielddata as $field) {
                    if ($field['public']) {
                        //add value from field
                        if (isset($data[$field['tag']])) { $mergeVars[$field['tag']] = $data[$field['tag']]; }
                    }
                }
            }
            
            // same for groups
            $aGroups = array();
            $groupInfo = $MailChimp->get(sprintf(
                '/lists/%s/interest-categories',
                $this->ListID
            ));
            if ($groupInfo && isset($groupInfo['categories'])) {
                foreach($groupInfo['categories'] as $group) {
                    // get options
                    $groupOptions = $MailChimp->get(sprintf(
                        '/lists/%s/interest-categories/%s/interests',
                        $this->ListID,
                        $group['id']
                    ));
//                    Debug::log(print_r($groupOptions, true));
                    if ($groupOptions && isset($groupOptions['interests'])) {
                        // get submitted data
                        if (is_array($data['groupings_'.$group['id']])) {
                            $sumbittedGroups = $data['groupings_'.$group['id']];
                        } else {
                            $sumbittedGroups = array($data['groupings_'.$group['id']]);
                        }
                        // init group array
                        foreach ($groupOptions['interests'] as $option) {
                            if (in_array($option['id'], $sumbittedGroups)) {
                                $aGroups[$option['id']] = true;
                            } else {
                                $aGroups[$option['id']] = false;
                            }
                        }
                    }
                }
            }
            
            // build submission data
            $submissionData = array(
                "email_address" => $data['EMAIL'],
                "status" => "pending",
                "merge_fields" => $mergeVars,
                "interests" => $aGroups,
            );
//            Debug::log(print_r($submissionData, true));
            
            if (!$memberFound) {
                
                // not on list, new subscription
                
                $MailChimp->post(
                    sprintf(
                        '/lists/%s/members',
                        $this->ListID
                    ),
                    $submissionData
                );
                
            } else {
                
                // update existing record
                
                $MailChimp->patch(
                    sprintf(
                        '/lists/%s/members/%s',
                        $this->ListID,
                        md5(strtolower($data['EMAIL']))
                    ),
                    $submissionData
                );
                
            }
            
            
            // check if update/adding successful
            if ($MailChimp->success()) {
                
                // set message
                $sFeedbackType = "good";
                $sFeedbackMsg = $this->ContentSuccess;
                
                // clear session data
                $aSessData = Session::get("FormInfo.".$form->FormName().".data");
                if(is_array($aSessData)) {
                    Session::clear("FormInfo.".$form->FormName().".data");
                }
                
                return $this->redirect($this->join_links($this->Link(), 'success'));
                
            } else {
                SS_Log::log(new Exception(print_r($MailChimp->getLastResponse(), true)), SS_Log::WARN);
            }
            
        }

        // Store the submitted data in case the user needs to try again
        Session::set("FormInfo.".$form->FormName().".data", $data);
        // add error message
        $form->addErrorMessage("Form_SubscribeForm_message", $sFeedbackMsg, $sFeedbackType);
        // redirct back
        return $this->redirectBack();
        
    }
    
    public function success() {
        return $this;
    }
    
    private function sortArray() {
        $args = func_get_args();
        $data = array_shift($args);
        foreach ($args as $n => $field) {
            if (is_string($field)) {
                $tmp = array();
                foreach ($data as $key => $row)
                    $tmp[$key] = $row[$field];
                $args[$n] = $tmp;
                }
        }
        $args[] = &$data;
        call_user_func_array('array_multisort', $args);
        return array_pop($args);
    }

}
