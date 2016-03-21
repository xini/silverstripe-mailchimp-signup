<?php
require_once(BASE_PATH . '/vendor/drewm/mailchimp-api/src/Drewm/MailChimp.php');

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
	
	public function Success() {
		return isset($_REQUEST['success']) && $_REQUEST['success'] == "1";
	}
	
	public function Error() {
		return isset($_REQUEST['success']) && $_REQUEST['success'] == "0";
	}
	
}

class MailChimpSignupPage_Controller extends Page_Controller {
	
	private static $allowed_actions = array(
		'Form',
		'success',
	);
	
	public function Form() {
		
		if (!($this->APIKey)) { debug::show('Please, set API key first in CMS'); return false; }
		if (!($this->ListID)) { debug::show('Please, set list id first in CMS'); return false; }
		
		// validation requirements
		Requirements::javascript(THIRDPARTY_DIR.'/jquery/jquery.min.js');
		Requirements::javascript(THIRDPARTY_DIR.'/jquery-validate/jquery.validate.min.js');
		
		//initialize
		$MailChimp = new Drewm\MailChimp($this->APIKey);
		
		// Get list data
		$listInfo = $MailChimp->call('/lists/merge-vars', array(
						'id' => array($this->ListID),
					));
		$groupInfo = $MailChimp->call('/lists/interest-groupings', array(
						'id' => $this->ListID
					));
//		debug::log(print_r($listInfo, true));
//		debug::log(print_r($groupInfo, true));
		
		// create field list and validator
		$fields = new FieldList();
		$fields->push(new LiteralField('required', '<p class="info">'._t("MailChimpSignupPage.REQUIRED", 'Required fields are marked with an asterisk (*)').'</p>'));
		$validator = new RequiredFields();
		$jsValidation = array();
		
		if ($listInfo && isset($listInfo['data']) && isset($listInfo['data'][0]) && isset($listInfo['data'][0]['merge_vars'])) {
			$fielddata = $listInfo['data'][0]['merge_vars'];
			
			foreach($fielddata as $field) {
				
				if ($field['public'] && $field['show']) {
					
					//add field validation
					if ($field['req']) {
						$field['name'] .=' *';
						$validator->addRequiredField($field['tag']);
						$jsValidation[] = $field['tag'];
					}
					
					// add field
					switch ($field['field_type']) {
					
						case 'text':
							$fields->push( $newField = new TextField($field['tag'], $field['name'], $field['default'], 255));
						break;
						
						case 'e-mail':
						case 'email':
							$fields->push( $newField = new EmailField($field['tag'], $field['name'], $field['default'], 255));
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
							$fields->push( $newField = new LiteralField($field['tag'], 'ERROR: UNSUPPORTED FIELDTYPE ('.$field['field_type'].') -> ' . $field['tag'] .' '. $field['name']));
					}
					
					//add description to field
					if (isset($newField) && $field['helptext']) {$newField->setRightTitle($field['helptext']);}
				}
			}
		} else {
			$message = "Form fields could not be loaded.";
			if ($listInfo && isset($listInfo['status']) && $listInfo['status'] == 'error' && isset($listInfo['error'])) {
				$message = $listInfo['error'];
			}
			throw new Exception($message);
		}
		
		if ($groupInfo && !isset($groupInfo['status']) || (isset($groupInfo['status']) && $groupInfo['status'] != 'error')) {
			foreach($groupInfo as $group) {
				
				$groupOptions = array();
				foreach($group['groups'] as $groupValue) {
					$groupOptions[str_ireplace(',', '\,', $groupValue['name'])] = $groupValue['name'];
				}
				
				switch ($group['form_field']) {
					case 'radio':
						$fields->push( new OptionsetField('groupings_'.$group['id'], $group['name'], $groupOptions) );
					break;
					
					case 'checkboxes':
						$fields->push( new CheckboxSetField('groupings_'.$group['id'], $group['name'], $groupOptions) );
					break;
					
					case 'dropdown':
						$fields->push( new DropdownField('groupings_'.$group['id'], $group['name'], $groupOptions) );
					break;
					
					case 'hidden':
						//skip it
					break;
					
					default:
						$fields->push( new LiteralField($group['id'], 'ERROR: UNSUPPORTED GROUP TYPE ('.$group['form_field'].') -> ' . $group['id'] .' '. $group['name']));
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

	    return $form;
	}
 
	public function subscribe($data, $form) {
		
		//initialize
		$MailChimp = new Drewm\MailChimp($this->APIKey);
		
		$memberInfo = $MailChimp->call('/lists/member-info', array(
						'id' => $this->ListID,
						'emails' => array(array('email' => $data['EMAIL'])),
					));
		
		// Make sure the feedback variables exist just in case of a freak error
		$sFeedbackMsg = "";
		$sFeedbackType = "";
		
		$bRetryData = false;
		
		//debug::show(print_r($memberInfo, true));

		// get member data
		if ($memberInfo && isset($memberInfo['error'])) {
			$bRetryData = true;
			$sFeedbackType = "bad";
			$sFeedbackMsg = $this->ContentError." (Error ".$memberInfo['code'].": ".$memberInfo['error'].')';
		} else {
			
			// Only attempt to subscribe the user if the e-mail address is not found in the list
			$bDoSubscribe = true;
			if ($memberInfo && isset($memberInfo['data']) && count($memberInfo['data']) > 0 && isset($memberInfo['data'][0]['status'])) {
				if ($memberInfo['data'][0]['status'] == "subscribed") {
					// The e-mail address has already subscribed, provide feedback
					$sFeedbackType = "warning";
					$sFeedbackMsg = _t("MailChimpSignupPage.DUPLICATE", "This email address is already subscribed to this list.");
					$bDoSubscribe = false;
				}
			}

			//passed checks above (no errror, no existing user)
			if ($bDoSubscribe) {
				$mergeVars = array();
				
				//get list data
				$listInfo = $MailChimp->call('/lists/merge-vars', array(
								'id' => array($this->ListID),
							));
				$groupInfo = $MailChimp->call('/lists/interest-groupings', array(
								'id' => $this->ListID
							));

				//loop through input types and add them to merge arrray
				if ($listInfo && isset($listInfo['data']) && isset($listInfo['data'][0]) && isset($listInfo['data'][0]['merge_vars'])) {
					$fielddata = $listInfo['data'][0]['merge_vars'];
					foreach($fielddata as $field) {
						if ($field['public'] && $field['show']) {
							//add value from field
							if (isset($data[$field['tag']])) { $mergeVars[$field['tag']] = $data[$field['tag']]; }
						}
					}
				}
				
				// same for groups
				$aGroups = array();
				if ($groupInfo && !isset($groupInfo['status']) || (isset($groupInfo['status']) && $groupInfo['status'] != 'error')) {
					foreach($groupInfo as $group) {
						if ($group['form_field'] != 'hidden') {
						
							if (isset($data['groupings_'.$group['id']])) {
								//add checkbox groups
								if (is_array($data['groupings_'.$group['id']])) {
									$aGroups[] = array(
										'id' => $group['id'],
										'groups' => $data['groupings_'.$group['id']]
									);
								} else {
								//add regular groups
									$aGroups[] = array(
										'id' => $group['id'],
										'groups' => array($data['groupings_'.$group['id']])
									);
								}
							}
						}
					}
				}
				$mergeVars['groupings'] = $aGroups;	//add groups to mergevars

				//actual subscribe
				$result = $MailChimp->call('lists/subscribe', array(
			                'id'                => $this->ListID,
			                'email'             => array('email' => $data['EMAIL']),
			                'merge_vars'        => $mergeVars,
			            ));
				
				if (!$result || ($result && isset($result['status']) && $result['status'] == 'error')) {
					$bRetryData = true;
					$sFeedbackType = "error";
					$sFeedbackMsg = $this->ContentError;
					if ($result && isset($result['error'])) {
						$sFeedbackMsg .= " (Error ".$result['code'].": ".$result['error'].')';
					}
				} else {
					$sFeedbackType = "good";
					$sFeedbackMsg = $this->ContentSuccess;
				}
				
			}
		}
		
		if ($bRetryData) { // error
			// Store the submitted data in case the user needs to try again
			Session::set("FormInfo.".$form->FormName().".data", $data);
			// add error message
			$form->addErrorMessage("Form_SubscribeForm_message", $sFeedbackMsg, $sFeedbackType);
			// redirct back
			return $this->redirectBack();
		} else {
			$aSessData = Session::get("FormInfo.".$form->FormName().".data");
			if(is_array($aSessData)) {
				Session::clear("FormInfo.".$form->FormName().".data");
			}
			return $this->redirect($this->join_links($this->Link, 'success'));
		}

	}
	
	public function success() {
		return $this;
	}

}
