<?php

namespace Innoweb\MailChimpSignup\Pages;

use Page;
use Innoweb\MailChimpSignup\Extensions\SignupDataExtension;

class SignupPage extends Page {

    private static $singular_name = 'MailChimp Signup Form';

    private static $plural_name = 'MailChimp Signup Forms';

    private static $description = 'Signup form for a MailChimp mailing list.';

    private static $icon = 'innoweb/silverstripe-mailchimp-signup:client/images/treeicons/page-mailchimp.png';

    private static $table_name = 'MailChimpSignupPage';

    private static $extensions = [
        SignupDataExtension::class
    ];

    private static $defaults = [
        'ShowInMenus' =>  true,
        'ShowInSearch' =>  true,
        'ProvideComments' =>  false,
        'Priority' =>  '',
    ];

}