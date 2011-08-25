<?php
/**
 * Table Definition for fave
 */
require_once INSTALLDIR.'/classes/Memcached_DataObject.php';

class Fave extends Memcached_DataObject
{
    ###START_AUTOCODE
    /* the code below is auto generated do not remove the above tag */

    public $__table = 'fave';                            // table name
    public $notice_id;                       // int(4)  primary_key not_null
    public $user_id;                         // int(4)  primary_key not_null
    public $modified;                        // timestamp()   not_null default_CURRENT_TIMESTAMP

    /* Static get */
    function staticGet($k,$v=null)
    { return Memcached_DataObject::staticGet('Fave',$k,$v); }

    /* the code above is auto generated do not remove the tag below */
    ###END_AUTOCODE

    /**
     * Save a favorite record.
     * @fixme post-author notification should be moved here
     *
     * @param Profile $profile the local or remote user who likes
     * @param Notice $notice the notice that is liked
     * @return mixed false on failure, or Fave record on success
     */
    static function addNew(Profile $profile, Notice $notice) {

        $fave = null;

        if (Event::handle('StartFavorNotice', array($profile, $notice, &$fave))) {

            $fave = new Fave();

            $fave->user_id   = $profile->id;
            $fave->notice_id = $notice->id;

            if (!$fave->insert()) {
                common_log_db_error($fave, 'INSERT', __FILE__);
                return false;
            }
            self::blow('fave:by_notice:%d', $fave->notice_id);

            Event::handle('EndFavorNotice', array($profile, $notice));
        }

        return $fave;
    }

    function delete()
    {
        $profile = Profile::staticGet('id', $this->user_id);
        $notice  = Notice::staticGet('id', $this->notice_id);

        $result = null;

        if (Event::handle('StartDisfavorNotice', array($profile, $notice, &$result))) {

            $result = parent::delete();
            self::blow('fave:by_notice:%d', $this->notice_id);

            if ($result) {
                Event::handle('EndDisfavorNotice', array($profile, $notice));
            }
        }

        return $result;
    }

    function pkeyGet($kv)
    {
        return Memcached_DataObject::pkeyGet('Fave', $kv);
    }

    function stream($user_id, $offset=0, $limit=NOTICES_PER_PAGE, $own=false, $since_id=0, $max_id=0)
    {
        $stream = new FaveNoticeStream($user_id, $own);

        return $stream->getNotices($offset, $limit, $since_id, $max_id);
    }

    function idStream($user_id, $offset=0, $limit=NOTICES_PER_PAGE, $own=false, $since_id=0, $max_id=0)
    {
        $stream = new FaveNoticeStream($user_id, $own);

        return $stream->getNoticeIds($offset, $limit, $since_id, $max_id);
    }

    function asActivity()
    {
        $notice  = Notice::staticGet('id', $this->notice_id);
        $profile = Profile::staticGet('id', $this->user_id);

        $act = new Activity();

        $act->verb = ActivityVerb::FAVORITE;

        // FIXME: rationalize this with URL below

        $act->id   = TagURI::mint('favor:%d:%d:%s',
                                  $profile->id,
                                  $notice->id,
                                  common_date_iso8601($this->modified));

        $act->time    = strtotime($this->modified);
        // TRANS: Activity title when marking a notice as favorite.
        $act->title   = _("Favor");
        // TRANS: Ntofication given when a user marks a notice as favorite.
        // TRANS: %1$s is a user nickname or full name, %2$s is a notice URI.
        $act->content = sprintf(_('%1$s marked notice %2$s as a favorite.'),
                               $profile->getBestName(),
                               $notice->uri);

        $act->actor     = ActivityObject::fromProfile($profile);
        $act->objects[] = ActivityObject::fromNotice($notice);

        $url = common_local_url('AtomPubShowFavorite',
                                          array('profile' => $this->user_id,
                                                'notice'  => $this->notice_id));

        $act->selfLink = $url;
        $act->editLink = $url;

        return $act;
    }

    /**
     * Fetch a stream of favorites by profile
     *
     * @param integer $profileId Profile that faved
     * @param integer $offset    Offset from last
     * @param integer $limit     Number to get
     *
     * @return mixed stream of faves, use fetch() to iterate
     *
     * @todo Cache results
     * @todo integrate with Fave::stream()
     */

    static function byProfile($profileId, $offset, $limit)
    {
        $fav = new Fave();

        $fav->user_id = $profileId;

        $fav->orderBy('modified DESC');

        $fav->limit($offset, $limit);

        $fav->find();

        return $fav;
    }

    /**
     * Grab a list of profile who have favored this notice.
     *
     * @return ArrayWrapper masquerading as a Fave
     */
    static function byNotice($noticeId)
    {
        $c = self::memcache();
        $key = Cache::key('fave:by_notice:' . $noticeId);

        $wrapper = $c->get($key);
        if (!$wrapper) {
            // @fixme caching & scalability!
            $fave = new Fave();
            $fave->notice_id = $noticeId;
            $fave->find();

            $list = array();
            while ($fave->fetch()) {
                $list[] = clone($fave);
            }
            $wrapper = new ArrayWrapper($list);
            $c->set($key, $wrapper);
        }
        return $wrapper;
    }
}
