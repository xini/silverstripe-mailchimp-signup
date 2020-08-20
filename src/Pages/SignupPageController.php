<?php

namespace Innoweb\MailChimpSignup\Pages;

use Innoweb\MailChimpSignup\Extensions\SignupControllerExtension;
use PageController;

class SignupPageController extends PageController {

    private static $extensions = [
        SignupControllerExtension::class
    ];

}
