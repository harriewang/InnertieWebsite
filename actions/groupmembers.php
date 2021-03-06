<?php
/**
 * StatusNet, the distributed open-source microblogging tool
 *
 * List of group members
 *
 * PHP version 5
 *
 * LICENCE: This program is free software: you can redistribute it and/or modify
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
 *
 * @category  Group
 * @package   StatusNet
 * @author    Evan Prodromou <evan@status.net>
 * @copyright 2008-2009 StatusNet, Inc.
 * @license   http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License version 3.0
 * @link      http://status.net/
 */

if (!defined('STATUSNET') && !defined('LACONICA')) {
    exit(1);
}

require_once(INSTALLDIR.'/lib/profilelist.php');
require_once INSTALLDIR.'/lib/publicgroupnav.php';

/**
 * List of group members
 *
 * @category Group
 * @package  StatusNet
 * @author   Evan Prodromou <evan@status.net>
 * @license  http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License version 3.0
 * @link     http://status.net/
 */
class GroupmembersAction extends GroupAction
{
    var $page = null;

    function isReadOnly($args)
    {
        return true;
    }

    function prepare($args)
    {
        parent::prepare($args);
        $this->page = ($this->arg('page')) ? ($this->arg('page')+0) : 1;

        $nickname_arg = $this->arg('nickname');
        $nickname = common_canonical_nickname($nickname_arg);

        // Permanent redirect on non-canonical nickname

        if ($nickname_arg != $nickname) {
            $args = array('nickname' => $nickname);
            if ($this->page != 1) {
                $args['page'] = $this->page;
            }
            common_redirect(common_local_url('groupmembers', $args), 301);
            return false;
        }

        if (!$nickname) {
            // TRANS: Client error displayed when trying to view group members without providing a group nickname.
            $this->clientError(_('No nickname.'), 404);
            return false;
        }

        $local = Local_group::staticGet('nickname', $nickname);

        if (!$local) {
            // TRANS: Client error displayed when trying to view group members for a non-existing group.
            $this->clientError(_('No such group.'), 404);
            return false;
        }

        $this->group = User_group::staticGet('id', $local->group_id);

        if (!$this->group) {
            // TRANS: Client error displayed when trying to view group members for an object that is not a group.
            $this->clientError(_('No such group.'), 404);
            return false;
        }

        return true;
    }

    function title()
    {
        if ($this->page == 1) {
            // TRANS: Title of the page showing group members.
            // TRANS: %s is the name of the group.
            return sprintf(_('%s group members'),
                           $this->group->nickname);
        } else {
            // TRANS: Title of the page showing group members.
            // TRANS: %1$s is the name of the group, %2$d is the page number of the members list.
            return sprintf(_('%1$s group members, page %2$d'),
                           $this->group->nickname,
                           $this->page);
        }
    }

    function handle($args)
    {
        parent::handle($args);
        $this->showPage();
    }

    function showPageNotice()
    {
        $this->element('p', 'instructions',
                       // TRANS: Page notice for group members page.
                       _('A list of the users in this group.'));
    }

    function showObjectNav()
    {
        $nav = new GroupNav($this, $this->group);
        $nav->show();
    }

    function showContent()
    {
        $offset = ($this->page-1) * PROFILES_PER_PAGE;
        $limit =  PROFILES_PER_PAGE + 1;

        $cnt = 0;

        $members = $this->group->getMembers($offset, $limit);

        if ($members) {
            $member_list = new GroupMemberList($members, $this->group, $this);
            $cnt = $member_list->show();
        }

        $members->free();

        $this->pagination($this->page > 1, $cnt > PROFILES_PER_PAGE,
                          $this->page, 'groupmembers',
                          array('nickname' => $this->group->nickname));
    }
}
