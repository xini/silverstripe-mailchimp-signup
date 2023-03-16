# SilverStripe MailChimp Signup Page

[![Version](http://img.shields.io/packagist/v/innoweb/silverstripe-mailchimp-signup.svg?style=flat-square)](https://packagist.org/packages/innoweb/silverstripe-mailchimp-signup)
[![License](http://img.shields.io/packagist/l/innoweb/silverstripe-mailchimp-signup.svg?style=flat-square)](license.md)

## Overview

Adds page type for a MailChimp signup form. Form fields are read automatically from the MailChimp list.

The submissions need to be confirmed by the user, they receives a confirmation email from MailChimp.

## Requirements

* SilverStripe CMS 5
* [symbiote/silverstripe-multivaluefield ^6](https://packagist.org/packages/symbiote/silverstripe-multivaluefield)
* [drewm/mailchimp-api ~2.0](https://packagist.org/packages/drewm/mailchimp-api)

Note: this version is compatible with SilverStripe 5. 
For SilverStripe 4, please see the [4 release line](https://github.com/xini/silverstripe-mailchimp-signup/tree/4).
For SilverStripe 3, please see the [3.x release line](https://github.com/xini/silverstripe-mailchimp-signup/tree/3.0).

## Installation

Install the module using composer:
```
composer require innoweb/silverstripe-mailchimp-signup dev-master
```

Then run dev/build.

## Configuration

To disable SSL verfication (e.g. for your local dev environment) you can add the following to your `_config.php` file:

```
Config::inst()->update('DrewM\MailChimp\MailChimp', 'verify_ssl', false);
```

### MailChimpSignupPage

The page type has a 'MailChimp' tab where the MailChimp API Key and the AudienceID can be configured. 

Once the page is saved it will automatically read the fields from the MailChimp list and display a generated signup form based on these fields.

You can block the loading of jQuery and jQuery validate by using the following config option:

```
Innoweb\MailChimpSignup\Pages\SignupPageController:
  block_default_jquery_and_validate: true
```

You can block the loading of the form validation script by using the following config option:

```
Innoweb\MailChimpSignup\Pages\SignupPageController:
  block_form_validation: true
```

If you keep this enabled, you need to have jQuery/validate enabled as well or load these libraries by other means before this form validation script.

The forms fields loaded from MailChimp are cached for 5 minutes by default. You can change the cache time in the config:

```
Innoweb\MailChimpSignup\MailchimpDataLoader:
  field_cache_seconds: 300
```

Whenever the signup page is published, the cache is deleted.


### MailChimpCampaignListPage 

The page type has a 'MailChimp' tab where the MailChimp API Key, as well as the campaign filters and limitscan be configured.

The following configuration options are available:

```
Innoweb\MailChimpSignup\Pages\CampaignListPage:
  auto_update: true
  update_interval: 3600
```

If `auto_update` is enabled, the campaigns are read from MailChimp when the page is displayed, using the `update_interval` (seconds) as a limit.
The campaigns are always updated when the page is saved.

## License

BSD 3-Clause License, see [License](license.md)
