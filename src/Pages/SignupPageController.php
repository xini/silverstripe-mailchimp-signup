<?php

declare(strict_types=1);

namespace Innoweb\MailChimpSignup\Pages;

use Innoweb\MailChimpSignup\Extensions\SignupControllerExtension;
use PageController;

class SignupPageController extends PageController
{

    private static array $extensions = [
        SignupControllerExtension::class
    ];
}
