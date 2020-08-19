<?php

namespace Innoweb\MailChimpSignup\Forms;

use SilverStripe\Forms\RequiredFields;

class SignupPageValidator extends RequiredFields
{
    /**
     * List of address fields
     *
     * @var array
     */
    protected $addresses;

    /**
     * Adds a address field to addresses stack.
     *
     * @param string $field
     *
     * @return $this
     */
    public function addAddressField($field)
    {
        $this->addresses[$field] = $field;

        return $this;
    }

    /**
     * Ensures address fields are validated
     */
    public function php($data)
    {
        $valid = parent::php($data);

        // check addresses
        if (count($this->addresses) > 0) {
            foreach ($this->addresses as $addressField) {
                // check if any of the address fields have data set
                if (
                    (isset($data[$addressField.'_addr1']) && strlen($data[$addressField.'_addr1']) > 0)
                    || (isset($data[$addressField.'_addr2']) && strlen($data[$addressField.'_addr2']) > 0)
                    || (isset($data[$addressField.'_city']) && strlen($data[$addressField.'_city']) > 0)
                    || (isset($data[$addressField.'_state']) && strlen($data[$addressField.'_state']) > 0)
                    || (isset($data[$addressField.'_zip']) && strlen($data[$addressField.'_zip']) > 0)
                    || (isset($data[$addressField.'_country']) && strlen($data[$addressField.'_country']) > 0)
                ) {
                    // if any of the dependent fields are empty, add an error message
                    if (!isset($data[$addressField.'_addr1']) || strlen($data[$addressField.'_addr1']) < 1) {
                        $this->validationError(
                            $addressField.'_addr1',
                            _t(
                                'SignupPageValidator.AddressFieldIsRequired',
                                'This field is required to form a complete address.'
                            ),
                            "required"
                        );
                        $valid = false;
                    }
                    if (!isset($data[$addressField.'_city']) || strlen($data[$addressField.'_city']) < 1) {
                        $this->validationError(
                            $addressField.'_city',
                            _t(
                                'SignupPageValidator.AddressFieldIsRequired',
                                'This field is required to form a complete address.'
                            ),
                            "required"
                        );
                        $valid = false;
                    }
                    if (!isset($data[$addressField.'_state']) || strlen($data[$addressField.'_state']) < 1) {
                        $this->validationError(
                            $addressField.'_state',
                            _t(
                                'SignupPageValidator.AddressFieldIsRequired',
                                'This field is required to form a complete address.'
                            ),
                            "required"
                        );
                        $valid = false;
                    }
                    if (!isset($data[$addressField.'_zip']) || strlen($data[$addressField.'_zip']) < 1) {
                        $this->validationError(
                            $addressField.'_zip',
                            _t(
                                'SignupPageValidator.AddressFieldIsRequired',
                                'This field is required to form a complete address.'
                            ),
                            "required"
                        );
                        $valid = false;
                    }
                    if (!isset($data[$addressField.'_country']) || strlen($data[$addressField.'_country']) < 1) {
                        $this->validationError(
                            $addressField.'_country',
                            _t(
                                'SignupPageValidator.AddressFieldIsRequired',
                                'This field is required to form a complete address.'
                            ),
                            "required"
                        );
                        $valid = false;
                    }
                }
            }
        }

        return $valid;
    }

}