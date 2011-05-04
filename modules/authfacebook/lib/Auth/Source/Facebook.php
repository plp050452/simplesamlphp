<?php

/**
 * Authenticate using Facebook Platform.
 *
 * @author Andreas Åkre Solberg, UNINETT AS.
 * @package simpleSAMLphp
 * @version $Id$
 */
class sspmod_authfacebook_Auth_Source_Facebook extends SimpleSAML_Auth_Source {


	/**
	 * The string used to identify our states.
	 */
	const STAGE_INIT = 'facebook:init';


	/**
	 * The key of the AuthId field in the state.
	 */
	const AUTHID = 'facebook:AuthId';


	/**
	 * Facebook App ID or API Key
	 */
	private $api_key;


	/**
	 * Facebook App Secret
	 */
	private $secret;


	/**
	 * Which additional data permissions to request from user
	 */
	private $req_perms;


	/**
	 * Constructor for this authentication source.
	 *
	 * @param array $info  Information about this authentication source.
	 * @param array $config  Configuration.
	 */
	public function __construct($info, $config) {
		assert('is_array($info)');
		assert('is_array($config)');

		/* Call the parent constructor first, as required by the interface. */
		parent::__construct($info, $config);

		$cfgParse = SimpleSAML_Configuration::loadFromArray($config, 'authsources[' . var_export($this->authId, TRUE) . ']');
		
		$this->api_key = $cfgParse->getString('api_key');
		$this->secret = $cfgParse->getString('secret');
		$this->req_perms = $cfgParse->getString('req_perms', NULL);

		require_once(dirname(dirname(dirname(dirname(__FILE__)))) . '/extlibinc/facebook.php');
	}


	/**
	 * Log-in using Facebook platform
	 *
	 * @param array &$state  Information about the current authentication.
	 */
	public function authenticate(&$state) {
		assert('is_array($state)');

		/* We are going to need the authId in order to retrieve this authentication source later. */
		$state[self::AUTHID] = $this->authId;
		
		$stateID = SimpleSAML_Auth_State::saveState($state, self::STAGE_INIT);
		
		SimpleSAML_Logger::debug('facebook auth state id = ' . $stateID);
		
		$linkback = SimpleSAML_Module::getModuleURL('authfacebook/linkback.php');
		$linkback_next = $linkback . '?next=' . urlencode($stateID);
		$linkback_cancel = $linkback . '?cancel=' . urlencode($stateID);
		$fb_login_params = array('next' => $linkback_next, 'cancel_url' => $linkback_cancel, 'req_perms' => $this->req_perms);

		$facebook = new Facebook(array('appId' => $this->api_key, 'secret' => $this->secret, 'cookie' => false));

		$fb_session = $facebook->getSession();

		if (isset($fb_session)) {
			try {
				$uid = $facebook->getUser();
				if (isset($uid)) {
					$info = $facebook->api("/me");
				}
			} catch (FacebookApiException $e) {
				if ($e->getType() != 'OAuthException') {
					throw new SimpleSAML_Error_AuthSource($this->authId, 'Error getting user profile.', $e);
				}
			}
		}

		if (!isset($info)) {
			$url = $facebook->getLoginUrl($fb_login_params);
			SimpleSAML_Utilities::redirect($url);
			assert('FALSE');
		}
		
		$attributes = array();
		foreach($info AS $key => $value) {
			if (is_string($value) && !empty($value)) {
				$attributes['facebook.' . $key] = array((string)$value);
			}
		}

		if (array_key_exists('username', $info)) {
			$attributes['facebook_user'] = array($info['username'] . '@facebook.com');
		} else {
			$attributes['facebook_user'] = array($uid . '@facebook.com');
		}

		$attributes['facebook_targetedID'] = array('http://facebook.com!' . $uid);
		$attributes['facebook_cn'] = array($info['name']);

		SimpleSAML_Logger::debug('Facebook Returned Attributes: '. implode(", ", array_keys($attributes)));

		$state['Attributes'] = $attributes;
	}
	

}

?>