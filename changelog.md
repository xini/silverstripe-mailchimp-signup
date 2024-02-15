# Changelog

All notable changes to this project will be documented in this file.

This project adheres to [Semantic Versioning](http://semver.org/).

## [5.1.1]

* fix issue when interests are displayed as checkboxes and are submitted empty

## [5.1.0]

* don't log errors if they come from Mailchimp spam filter
* use SS logger instead of user_error

## [5.0.1]

* update jquery

## [5.0.0]

* upgrade to Silverstripe 5

## [4.7.10]

* build frontend

## [4.7.9]

* update npm dependencies

## [4.7.8]

* update npm dependencies

## [4.7.7]

* update npm dependencies

## [4.7.6]

* update npm dependencies

## [4.7.5]

* fix loading of campaigns on campaign list pages

## [4.7.4]

* update js dependencies

## [4.7.3]

* Fix reading of options for radio buttons and dropdowns
* Fix format for date and birthday fields
* Replace country text field with CountryDropdownField

## [4.7.2]

* Fix form validation

## [4.7.1]

* Fix missing config trait 
* fix CMS field label

## [4.7.0]

* Update jQuery to 3.5.1 and jQuery Validation to 1.19.2
* add support for Mailchimp address fields 
* move Mailchimp signup functionality to extensions so that this can be used for other objects than pages, e.g. elemental
* add customisation hooks for success and error urls

## [4.6.3]

* Use title case in Subscribe button for consistency with other form field labels

## [4.6.2]

* add missing upgrade configs

## [4.6.1]

* update jQuery to 3.5.0

## [4.6.0]

* improve data loader calls
* add support for email format radio buttons

## [4.5.0]

* implement singleton data loader for form fields

## [4.4.0]

* add cache for forms fields loaded from Mailchimp (default 5 mins)

## [4.3.1]

* fix composer expose

## [4.3.0]

* upgrade front-end build to gulp 4
* replace jQuery and validate with current npm versions
* add config options to block js requirements
* add SS extension hooks to signup form method
* rename MailChimp 'Lists' to 'Audiences' in front-end

## [4.2.7]

* update campaign CMS fields to make it clearer what can be changed
* add rel="noopener noreferrer" to links in default template

## [4.2.6]

* fix campaign summary fields

## [4.2.5]

* fix session retrieval

## [4.2.4]

* update jQuery validate library to 1.19.0 to fix 'for' label for error messages

## [4.2.3]

* fix spam protection

## [4.2.2]

* fix error handling

## [4.2.1]

* fix initiation of form errors, use restoreFormState() instead of the SS3 setupFormErrors()

## [4.2.0]

* add hook to update CMS fields
* fix form error setup
* fix missing use statements

## [4.1.2]

* add missing namespace

## [4.1.1]

* fix typo

## [4.1.0]

* add option to omit email verification process on subscription
* update form generator to generate more specific field types according to data type

## [4.0.1]

* fix loading limits of list fields and properties

## [4.0.0]

* module upgraded for SilverStripe 4.x compatibility
* add error message in case API key or ListID are missing

## [3.0.2]

* update multivaluefield requirement to latest SS3 version

## [3.0.1]

* fix filtering of campaign lists

## [3.0.0]

* change verify_ssl config to MailChimp class (from MailChimpSignupPage)
* add campaign listing page

## [2.4.1]

* fix typo in verify_ssl config
* fix API urls
* fix form error message
* fix js validation
* update readme

## [2.4.0]

* public release

## [2.3.1]

* add text field types

## [2.3.0]

* decouple js validation options for async loading of libraries

## [2.2.0]

* add config option to disable sll verification, improve logging

## [2.1.4]

* add warning message if MC form could not be loaded

## [2.1.3]

* fix display of email field if no merge fields defined

## [2.1.2]

* fix signup data when merge fields or interests are empty

## [2.1.1]

* fix position of email field

## [2.1.0]

* move subscription handling into separate method to make it available to other classes

## [2.0.2]

* add spam protection to signup form

## [2.0.1]

* remove debug output

## [2.0.0]

* change dependencies to version 3 of mailchimp api
* update page icon

## [1.1.2]

* fix dependency

## [1.1.1]

* fix success message and dependencies

## [1.1.0]

* add success action to allow goal tracking

## [1.0.0]

* initial release
