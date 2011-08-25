<?php
/**
 * StatusNet - the distributed open-source microblogging tool
 * Copyright (C) 2011, StatusNet, Inc.
 *
 * Notice-list representation of an event
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
 * @category  Event
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
 * Notice-list representation of an event
 *
 * @category  General
 * @package   StatusNet
 * @author    Evan Prodromou <evan@status.net>
 * @copyright 2011 StatusNet, Inc.
 * @license   http://www.fsf.org/licensing/licenses/agpl-3.0.html AGPL 3.0
 * @link      http://status.net/
 */
class EventListItem extends NoticeListItemAdapter
{
    function showNotice()
    {
        $this->nli->out->elementStart('div', 'entry-title');
        $this->nli->showAuthor();
        $this->showContent();
        $this->nli->out->elementEnd('div');
    }

    function showContent()
    {
        $notice = $this->nli->notice;
        $out    = $this->nli->out;

        $profile = $notice->getProfile();
        $event   = Happening::fromNotice($notice);

        if (empty($event)) {
            // TRANS: Content for a deleted RSVP list item (RSVP stands for "please respond").
            $out->element('p', null, _m('Deleted.'));
            return;
        }

        $out->elementStart('div', 'vevent event'); // VEVENT IN

        $out->elementStart('h3');  // VEVENT/H3 IN

        if (!empty($event->url)) {
            $out->element('a',
                          array('href' => $event->url,
                                'class' => 'event-title entry-title summary'),
                          $event->title);
        } else {
            $out->text($event->title);
        }

        $out->elementEnd('h3'); // VEVENT/H3 OUT

        $startDate = strftime("%x", strtotime($event->start_time));
        $startTime = strftime("%R", strtotime($event->start_time));

        $endDate = strftime("%x", strtotime($event->end_time));
        $endTime = strftime("%R", strtotime($event->end_time));

        // FIXME: better dates

        $out->elementStart('div', 'event-times'); // VEVENT/EVENT-TIMES IN

        // TRANS: Field label for event description.
        $out->element('strong', null, _m('Time:'));

        $out->element('abbr', array('class' => 'dtstart',
                                    'title' => common_date_iso8601($event->start_time)),
                      $startDate . ' ' . $startTime);
        $out->text(' - ');
        if ($startDate == $endDate) {
            $out->element('span', array('class' => 'dtend',
                                        'title' => common_date_iso8601($event->end_time)),
                          $endTime);
        } else {
            $out->element('span', array('class' => 'dtend',
                                        'title' => common_date_iso8601($event->end_time)),
                          $endDate . ' ' . $endTime);
        }

        $out->elementEnd('div'); // VEVENT/EVENT-TIMES OUT

        if (!empty($event->location)) {
            $out->elementStart('div', 'event-location');
            // TRANS: Field label for event description.
            $out->element('strong', null, _m('Location:'));
            $out->element('span', 'location', $event->location);
            $out->elementEnd('div');
        }

        if (!empty($event->description)) {
            $out->elementStart('div', 'event-description');
            // TRANS: Field label for event description.
            $out->element('strong', null, _m('Description:'));
            $out->element('span', 'description', $event->description);
            $out->elementEnd('div');
        }

        $rsvps = $event->getRSVPs();

        $out->elementStart('div', 'event-rsvps');
        // TRANS: Field label for event description.
        $out->element('strong', null, _m('Attending:'));
        $out->element('span', 'event-rsvps',
                      // TRANS: RSVP counts.
                      // TRANS: %1$d, %2$d and %3$d are numbers of RSVPs.
                      sprintf(_m('Yes: %1$d No: %2$d Maybe: %3$d'),
                              count($rsvps[RSVP::POSITIVE]),
                              count($rsvps[RSVP::NEGATIVE]),
                              count($rsvps[RSVP::POSSIBLE])));
        $out->elementEnd('div');

        $user = common_current_user();

        if (!empty($user)) {
            $rsvp = $event->getRSVP($user->getProfile());

            if (empty($rsvp)) {
                $form = new RSVPForm($event, $out);
            } else {
                $form = new CancelRSVPForm($rsvp, $out);
            }

            $form->show();
        }

        $out->elementEnd('div'); // vevent out
    }
}
