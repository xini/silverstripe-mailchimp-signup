<?php

declare(strict_types=1);

namespace Innoweb\MailChimpSignup\Pages;

use Innoweb\MailChimpSignup\Extensions\SignupDataExtension;
use Page;

class SignupPage extends Page
{
    private static string $singular_name = 'MailChimp Signup Form';

    private static string $plural_name = 'MailChimp Signup Forms';

    private static string $class_description = 'Signup form for a MailChimp mailing list.';

    private static string $table_name = 'MailChimpSignupPage';

    private static string $cms_icon_class = 'font-icon-block-form';

    private static array $extensions = [
        SignupDataExtension::class
    ];

    private static array $defaults = [
        'ShowInMenus' =>  true,
        'ShowInSearch' =>  true,
        'ProvideComments' =>  false,
        'Priority' =>  '',
    ];
}
