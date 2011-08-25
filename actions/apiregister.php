<?php
//echo '<pre>';
//print_r($_POST);
//exit;
if (!defined('STATUSNET')) {
    exit(1);
}


class ApiRegisterAction extends ApiAction
{
    var $nickname    = null;
    var $email    = null;
    var $fullname    = null;
    var $homepage = null;
    var $bio    = null;
    var $location = null;

    /**
     * Take arguments for running
     *
     * @param array $args $_REQUEST args
     *
     * @return boolean success flag
     */
    function prepare($args)
    {
        parent::prepare($args);
		
		$this->nickname = Nickname::normalize($this->arg('nickname'));
        $this->email    = $this->arg('email');
        $this->fullname = $this->arg('fullname');
        $this->homepage = $this->arg('homepage');
        $this->bio      = $this->arg('bio');
        $this->location = $this->arg('location');
		// We don't trim these... whitespace is OK in a password!
        $this->password = $this->arg('password');
        $this->confirm  = $this->arg('confirm');

        return true;
    }

    /**
     * Handle the request
     *
     * Save the new group
     *
     * @param array $args $_REQUEST data (unused)
     *
     * @return void
     */
    function handle($args)
    {
        parent::handle($args);

        /*if ($_SERVER['REQUEST_METHOD'] != 'POST') {
             $this->clientError(
                 // TRANS: Client error. POST is a HTTP command. It should not be translated.
                 _('This method requires a POST.'),
                 200,
                 $this->format
             );
             return;
        }*/

        /*if (empty($this->user)) {
            // TRANS: Client error given when a user was not found (404).
            $this->clientError(_('No such user.'), 404, $this->format);
            return;
        }*/

        if ($this->validateParams() == false) {
            return;
        }

       $user = User::register(array('nickname' => $this->nickname,
                                                    'password' => $this->password,
                                                    'email' => $this->email,
                                                    'fullname' => $this->fullname,
                                                    'homepage' => $this->homepage,
                                                    'bio' => $this->bio,
                                                    'location' => $this->location,
                                                    'code' => $this->code));
		$profile = $user->getProfile();
        switch($this->format) {
        case 'xml':
            $this->showSingleXmlUser($profile);
            break;
        case 'json':
            $this->showSingleJsonUser($profile);
            break;
        default:
            $this->clientError(
                // TRANS: Client error given when an API method was not found (404).
                _('API method not found.'),
                404,
                $this->format
            );
            break;
        }
    }

    /**
     * Validate params for the new group
     *
     * @return void
     */
    function validateParams()
    {
        if ($this->nicknameExists($this->nickname)) {
            $this->clientError(
                // TRANS: Client error trying to create a group with a nickname this is already in use.
                _('Nickname already in use. Try another one.'),
                200,
                $this->format
            );
            return false;
        } else if (!User::allowed_nickname($this->nickname)) {
            $this->clientError(
                // TRANS: Client error in form for username creation.
                _('Not a valid nickname.'),
                200,
                $this->format
            );
            return false;

        } elseif (
            !is_null($this->homepage)
            && strlen($this->homepage) > 0
            && !Validate::uri(
                $this->homepage, array(
                    'allowed_schemes' =>
                    array('http', 'https')
                )
            )) {
            $this->clientError(
                // TRANS: Client error in form for group creation.
                _('Homepage is not a valid URL.'),
                200,
                $this->format
            );
            return false;
        } elseif (
            !is_null($this->fullname)
            && mb_strlen($this->fullname) > 255) {
                $this->clientError(
                    // TRANS: Client error in form for group creation.
                    _('Full name is too long (maximum 255 characters).'),
                    200,
                    $this->format
                );
            return false;
        } elseif (User_group::descriptionTooLong($this->description)) {
            $this->clientError(
                sprintf(
                    // TRANS: Client error shown when providing too long a description during group creation.
                    // TRANS: %d is the maximum number of allowed characters.
                    _m('Description is too long (maximum %d character).',
                      'Description is too long (maximum %d characters).',
                      User_group::maxDescription()),
                    User_group::maxDescription()
                ),
                200,
                $this->format
            );
            return false;
        } elseif (
            !is_null($this->location)
            && mb_strlen($this->location) > 255) {
                $this->clientError(
                    // TRANS: Client error shown when providing too long a location during group creation.
                    _('Location is too long (maximum 255 characters).'),
                    200,
                    $this->format
                );
            return false;
        }

        if (!empty($this->aliasstring)) {
            $this->aliases = array_map(
                'common_canonical_nickname',
                array_unique(preg_split('/[\s,]+/', $this->aliasstring))
            );
        } else {
            $this->aliases = array();
        }

        if (count($this->aliases) > common_config('group', 'maxaliases')) {
            $this->clientError(
                sprintf(
                    // TRANS: Client error shown when providing too many aliases during group creation.
                    // TRANS: %d is the maximum number of allowed aliases.
                    _m('Too many aliases! Maximum %d allowed.',
                       'Too many aliases! Maximum %d allowed.',
                       common_config('group', 'maxaliases')),
                    common_config('group', 'maxaliases')
                ),
                200,
                $this->format
            );
            return false;
        }

        foreach ($this->aliases as $alias) {

            if (!Nickname::isValid($alias)) {
                $this->clientError(
                    // TRANS: Client error shown when providing an invalid alias during group creation.
                    // TRANS: %s is the invalid alias.
                    sprintf(_('Invalid alias: "%s".'), $alias),
                    200,
                    $this->format
                );
                return false;
            }
            if ($this->groupNicknameExists($alias)) {
                $this->clientError(
                    sprintf(
                        // TRANS: Client error displayed when trying to use an alias during group creation that is already in use.
                        // TRANS: %s is the alias that is already in use.
                        _('Alias "%s" already in use. Try another one.'),
                        $alias
                    ),
                    200,
                    $this->format
                );
                return false;
            }

            // XXX assumes alphanum nicknames

            if (strcmp($alias, $this->nickname) == 0) {
                $this->clientError(
                    // TRANS: Client error displayed when trying to use an alias during group creation that is the same as the group's nickname.
                    _('Alias can\'t be the same as nickname.'),
                    200,
                    $this->format
                );
                return false;
            }
        }

        // Everything looks OK

        return true;
    }

    /**
     * Does the given nickname already exist?
     *
     * Checks a canonical nickname against the database.
     *
     * @param string $nickname nickname to check
     *
     * @return boolean true if the nickname already exists
     */
    function nicknameExists($nickname)
    {
        $user = User::staticGet('nickname', $nickname);
        return is_object($user);
    }
	
	/**
     * Does the given email address already exist?
     *
     * Checks a canonical email address against the database.
     *
     * @param string $email email address to check
     *
     * @return boolean true if the address already exists
     */
    function emailExists($email)
    {
        $email = common_canonical_email($email);
        if (!$email || strlen($email) == 0) {
            return false;
        }
        $user = User::staticGet('email', $email);
        return is_object($user);
    }
	
	function showSingleJsonUser($user)
    {
        $this->initDocument('json');
        $twitter_user = $this->twitterUserArray($user);
        $this->showJsonObjects($twitter_user);
        $this->endDocument('json');
    }

    function showSingleXmlUser($user)
    {
        $this->initDocument('xml');
        $twitter_user= $this->twitterUserArray($user);
        $this->showTwitterXmlGroup($twitter_user);
        $this->endDocument('xml');
    }
}
