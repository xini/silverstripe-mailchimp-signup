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
        $this->merge_fields = $this->loadMergeFields($APIKey, $ListID);
        $this->categories = $this->loadCategories($APIKey, $ListID);
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
    
    public function getMergeFields() {
        return $this->merge_fields;
    }
    
    public function getCategories() {
        return $this->categories;
    }
    
}