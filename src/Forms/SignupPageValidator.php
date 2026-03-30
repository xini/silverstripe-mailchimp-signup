<?php

namespace Innoweb\MailChimpSignup\Forms;

use SilverStripe\Forms\Validation\RequiredFieldsValidator;
use Override;

class SignupPageValidator extends RequiredFieldsValidator
{
    /**
     * List of address fields
     *
     * @var array
     */
    protected $addresses = [];

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
    #[Override]
    public function php($data)
    {
        $valid = parent::php($data);

        // check addresses
        if (count($this->addresses) > 0) {
            foreach ($this->addresses as $addressField) {
                // check if any of the address fields have data set
                if (
                    (isset($data[$addressField.'_addr1']) && (string) $data[$addressField.'_addr1'] !== '')
                    || (isset($data[$addressField.'_addr2']) && (string) $data[$addressField.'_addr2'] !== '')
                    || (isset($data[$addressField.'_city']) && (string) $data[$addressField.'_city'] !== '')
                    || (isset($data[$addressField.'_state']) && (string) $data[$addressField.'_state'] !== '')
                    || (isset($data[$addressField.'_zip']) && (string) $data[$addressField.'_zip'] !== '')
                    || (isset($data[$addressField.'_country']) && (string) $data[$addressField.'_country'] !== '')
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
