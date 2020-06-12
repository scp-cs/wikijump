<?php
/**
 * Wikidot - free wiki collaboration software
 * Copyright (c) 2008, Wikidot Inc.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * For more information about licensing visit:
 * http://www.wikidot.org/license
 *
 * @category Wikidot
 * @package Wikidot
 * @version $Id$
 * @copyright Copyright (c) 2008, Wikidot Inc.
 * @license http://www.gnu.org/licenses/agpl-3.0.html GNU Affero General Public License
 */


use DB\CategoryPeer;

class WikiCategoriesModule extends SmartyModule
{

    public function build($runData)
    {
        // get categories for the site

        $siteId = $runData->getTemp("site")->getSiteId();

        $c = new Criteria();
        $c->add("site_id", $siteId);
        $c->addOrderAscending("replace(name, '_', '00000000')");

        $cats = CategoryPeer::instance()->select($c);

        $runData->contextAdd("categories", $cats);
    }
}
