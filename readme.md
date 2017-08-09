# SilverStripe MailChimp Signup Page

[![Version](http://img.shields.io/packagist/v/innoweb/silverstripe-mailchimp-signup.svg?style=flat-square)](https://packagist.org/packages/innoweb/silverstripe-mailchimp-signup)
[![License](http://img.shields.io/packagist/l/innoweb/silverstripe-mailchimp-signup.svg?style=flat-square)](license.md)

## Overview

Adds page type for a MailChimp signup form. Form fields are read automatically from the MailChimp list.

## Requirements

* SilverStripe CMS ~3.2

## Installation

Install the module using composer:
```
composer require innoweb/silverstripe-mailchimp-signup dev-master
```
or download or git clone the module into a ‘mailchimp-signup’ directory in your webroot.

Then run dev/build.

## Configuration

The page type this module adds has a 'MailChimp' tab where the MailChimp API Key and the ListID can be configured. 

Once the page is saved it will automatically ready the fields from the MailChimp list and diesplay a generated signup form based on these fields. 

The submissions need to be confirmed by the user, they receives a confirmation email from MailChimp.

## License

BSD 3-Clause License, see [License](license.md)
