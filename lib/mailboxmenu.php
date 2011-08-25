<?php
/**
 * StatusNet - the distributed open-source microblogging tool
 * Copyright (C) 2011, StatusNet, Inc.
 *
 * Private mailboxes menu
 *
 * PHP version 5
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
 *
 * @category  Cache
 * @package   StatusNet
 * @author    Evan Prodromou <evan@status.net>
 * @copyright 2011 StatusNet, Inc.
 * @license   http://www.fsf.org/licensing/licenses/agpl-3.0.html AGPL 3.0
 * @link      http://status.net/
 */

if (!defined('STATUSNET')) {
    // This check helps protect against security problems;
    // your code file can't be executed directly from the web.
    exit(1);
}

/**
 * Menu of existing mailboxes
 *
 * @category  General
 * @package   StatusNet
 * @author    Evan Prodromou <evan@status.net>
 * @copyright 2011 StatusNet, Inc.
 * @license   http://www.fsf.org/licensing/licenses/agpl-3.0.html AGPL 3.0
 * @link      http://status.net/
 */
class MailboxMenu extends Menu
{
    function show()
    {
        $cur = common_current_user();
        $nickname = $cur->nickname;

        $this->out->elementStart('ul', array('class' => 'nav'));

        $this->item('inbox',
                    array('nickname' => $nickname),
                    // TRANS: Menu item in mailbox menu. Leads to incoming private messages.
                    _m('MENU','Inbox'),
                    // TRANS: Menu item title in mailbox menu. Leads to incoming private messages.
                    _('Your incoming messages.'));

        $this->item('outbox',
                    array('nickname' => $nickname),
                    // TRANS: Menu item in mailbox menu. Leads to outgoing private messages.
                    _m('MENU','Outbox'),
                    // TRANS: Menu item title in mailbox menu. Leads to outgoing private messages.
                    _('Your sent messages.'));

        $this->out->elementEnd('ul');
    }
}
