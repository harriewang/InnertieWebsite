<?php
/*
 * StatusNet - the distributed open-source microblogging tool
 * Copyright (C) 2010, StatusNet, Inc.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

if (!defined('STATUSNET') && !defined('LACONICA')) {
    exit(1);
}

class User_urlshortener_prefs extends Memcached_DataObject
{
    ###START_AUTOCODE
    /* the code below is auto generated do not remove the above tag */

    public $__table = 'user_urlshortener_prefs';         // table name
    public $user_id;                         // int(4)  primary_key not_null
    public $urlshorteningservice;            // varchar(50)   default_ur1.ca
    public $maxurllength;                    // int(4)   not_null
    public $maxnoticelength;                 // int(4)   not_null
    public $created;                         // datetime   not_null default_0000-00-00%2000%3A00%3A00
    public $modified;                        // timestamp   not_null default_CURRENT_TIMESTAMP

    /* Static get */
    function staticGet($k,$v=NULL) { return Memcached_DataObject::staticGet('User_urlshortener_prefs',$k,$v); }

    /* the code above is auto generated do not remove the tag below */
    ###END_AUTOCODE

    function sequenceKey()
    {
        return array(false, false, false);
    }

    static function maxUrlLength($user)
    {
        $def = common_config('url', 'maxlength');

        $prefs = self::getPrefs($user);

        if (empty($prefs)) {
            return $def;
        } else {
            return $prefs->maxurllength;
        }
    }

    static function maxNoticeLength($user)
    {
        $def = common_config('url', 'maxnoticelength');

        if ($def == -1) {
            $def = Notice::maxContent();
        }

        $prefs = self::getPrefs($user);

        if (empty($prefs)) {
            return $def;
        } else {
            return $prefs->maxnoticelength;
        }
    }

    static function urlShorteningService($user)
    {
        $def = common_config('url', 'shortener');

        $prefs = self::getPrefs($user);

        if (empty($prefs)) {
            if (!empty($user)) {
                return $user->urlshorteningservice;
            } else {
                return $def;
            }
        } else {
            return $prefs->urlshorteningservice;
        }
    }

    static function getPrefs($user)
    {
        if (empty($user)) {
            return null;
        }

        $prefs = User_urlshortener_prefs::staticGet('user_id', $user->id);

        return $prefs;
    }
}
