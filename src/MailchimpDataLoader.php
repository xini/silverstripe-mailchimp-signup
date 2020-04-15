<?php

namespace Innoweb\MailChimpSignup;

use DrewM\MailChimp\MailChimp;
use Innoweb\MailChimpSignup\Pages\SignupPage;
use Psr\SimpleCache\CacheInterface;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Injector\Injector;

class MailchimpDataLoader
{
    private static $instances = [];

    private $merge_fields = null;
    private $categories = null;
    private $uses_email_type_options = null;
    private $api_key = null;
    private $list_id = null;

    public static function getInstance($APIKey, $ListID)
    {
        $instanceKey = md5($APIKey.'--'.$ListID);
        if (!in_array($instanceKey, self::$instances)) {
            self::$instances[$instanceKey] = new MailchimpDataLoader($APIKey, $ListID);
        }
        return self::$instances[$instanceKey];
    }

    private function __construct($APIKey, $ListID)
    {
        $this->api_key = $APIKey;
        $this->list_id = $ListID;
    }

    private function loadMergeFields($APIKey, $ListID)
    {
        $cache = Injector::inst()->get(CacheInterface::class . '.MailchimpFieldCache');
        $cacheKey = 'MergeFields'.md5($APIKey.$ListID);

        if (!$cache->has($cacheKey)) {

            // initialize
            $mailChimp = new MailChimp($APIKey);
            $mailChimp->verify_ssl = Config::inst()->get(MailChimp::class, 'verify_ssl');

            // Get list data
            $listInfo = $mailChimp->get(sprintf(
                'lists/%s/merge-fields?count=999',
                $ListID
            ));

            if (!$listInfo || !isset($listInfo['merge_fields'])) {
                $message = "Form fields could not be loaded.";
                if ($listInfo && isset($listInfo['status']) && isset($listInfo['error']) && isset($listInfo['title'])) {
                    $message .= ' ('.$listInfo['status'].': '.$listInfo['title'].': '.$listInfo['error'].')';
                }
                if ($mailChimp->getLastError()) {
                    $message .= ' (last error: '.$mailChimp->getLastError().')';
                }
                if ($mailChimp->getLastResponse()) {
                    $message .= ' (last response: '.print_r($mailChimp->getLastResponse(), true).')';
                }
                if ($mailChimp->getLastRequest()) {
                    $message .= ' (last reguest: '.print_r($mailChimp->getLastRequest(), true).')';
                }
                user_error($message, E_USER_WARNING);

                return false;
            }

            // store to cache
            $cache->set($cacheKey, $listInfo, Config::inst()->get(SignupPage::class, 'field_cache_seconds'));

        } else {
            $listInfo = $cache->get($cacheKey);
        }

        return $listInfo;
    }

    private function loadCategories($APIKey, $ListID)
    {
        $cache = Injector::inst()->get(CacheInterface::class . '.MailchimpFieldCache');
        $cacheKey = 'Categories'.md5($APIKey.$ListID);

        if (!$cache->has($cacheKey)) {

            // initialize
            $mailChimp = new MailChimp($APIKey);
            $mailChimp->verify_ssl = Config::inst()->get(MailChimp::class, 'verify_ssl');

            $groupInfo = $mailChimp->get(sprintf(
                'lists/%s/interest-categories?count=999',
                $ListID
            ));

            if ($groupInfo && isset($groupInfo['categories'])) {
                $categories = [];

                foreach ($groupInfo['categories'] as $group) {

                    // get options
                    $groupOptions = $mailChimp->get(sprintf(
                        'lists/%s/interest-categories/%s/interests?count=999',
                        $ListID,
                        $group['id']
                    ));

                    $group['options'] = $groupOptions;

                    $categories[] = $group;

                }

                // store to cache
                $cache->set($cacheKey, $categories, Config::inst()->get(SignupPage::class, 'field_cache_seconds'));

            } else {
                $categories = false;
            }

        } else {
            $categories = $cache->get($cacheKey);
        }

        return $categories;
    }

    private function loadListConfig($APIKey, $ListID)
    {
        $cache = Injector::inst()->get(CacheInterface::class . '.MailchimpFieldCache');
        $cacheKey = 'ListConfig'.md5($APIKey.$ListID);

        if (!$cache->has($cacheKey)) {

            // initialize
            $mailChimp = new MailChimp($APIKey);
            $mailChimp->verify_ssl = Config::inst()->get(MailChimp::class, 'verify_ssl');

            $listInfo = $mailChimp->get(sprintf(
                'lists/%s?fields=email_type_option',
                $ListID
            ));

            if ($listInfo && isset($listInfo['email_type_option'])) {

                $config = [];

                $config['email_type_option'] = $listInfo['email_type_option'];


                // store to cache
                $cache->set($cacheKey, $config, Config::inst()->get(SignupPage::class, 'field_cache_seconds'));

            } else {
                $config = false;
            }

        } else {
            $config = $cache->get($cacheKey);
        }

        return $config;
    }

    public function getMergeFields()
    {
        if ($this->merge_fields === null) {
            $this->merge_fields = $this->loadMergeFields($this->api_key, $this->list_id);
        }
        return $this->merge_fields;
    }

    public function getCategories()
    {
        if ($this->categories === null) {
            $this->categories = $this->loadCategories($this->api_key, $this->list_id);
        }
        return $this->categories;
    }

    public function getUsesEmailTypeOptions()
    {
        if ($this->uses_email_type_options === null) {
            $this->uses_email_type_options = $this->loadListConfig($this->api_key, $this->list_id);
        }
        if ($this->uses_email_type_options && isset($this->uses_email_type_options['email_type_option'])) {
            return $this->uses_email_type_options['email_type_option'];
        }
        return $this->uses_email_type_options;
    }
}