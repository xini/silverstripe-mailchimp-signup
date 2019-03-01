<?php

namespace Innoweb\MailChimpSignup\Pages;

use Innoweb\MailChimpSignup\Model\Campaign;
use PageController;

class CampaignListPageController extends PageController {

    public function init()
    {
        parent::init();

        // update campaigns
        $page = $this->dataRecord;
        $page->updateCampaigns();
    }

    public function getFilteredCampaigns()
    {
        // get page
        $page = $this->dataRecord;

        // setup filter
        $filter = [
            'Hidden'    =>  false,
            'PageID'    =>  $page->ID
        ];
        $listIDs = $page->dbObject('ListIDs')->getValue();
        if ($listIDs && is_array($listIDs) && count($listIDs) > 0) {
            $filter['ListID'] = $listIDs;
        }
        if ($page->HideSentToSegments) {
            $filter['SentToSegment'] = false;
        }

        // load campaigns
        $campaigns = Campaign::get()->filter($filter);

        // limit results
        if ($page->Limit && $page->Limit > 0) {
            $campaigns = $campaigns->limit($page->Limit);
        }

        // return
        return $campaigns;
    }
}