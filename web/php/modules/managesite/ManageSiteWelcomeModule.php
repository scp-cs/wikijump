<?php
use DB\SiteTagPeer;
use DB\MemberPeer;

class ManageSiteWelcomeModule extends ManageSiteBaseModule
{

    public function build($runData)
    {

        $site = $runData->getTemp("site");

        $fsettings = $site->getForumSettings();

        $tips = array();

        if (!$fsettings) {
            $tips['forum'] = true;
        }

        // site tags

        $c = new Criteria();
        $c->add("site_id", $site->getSiteId());
        $t = SiteTagPeer::instance()->selectOne($c);

        if (!$t) {
            $tips['tags'] = true;
        }

        // count members... ???
        $c = new Criteria();
        $c->add("site_id", $site->getSiteId());
        $co = MemberPeer::instance()->selectCount($c);

        if ($co<4) {
            $tips['invite'] = true;
        }

        if (count($tips)>0) {
            $runData->contextAdd("tips", $tips);
        }

        $runData->contextAdd('site', $site);
    }
}
