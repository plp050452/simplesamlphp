<?php


require_once('../../www/_include.php');

require_once((isset($SIMPLESAML_INCPREFIX)?$SIMPLESAML_INCPREFIX:'') . 'SimpleSAML/Utilities.php');
require_once((isset($SIMPLESAML_INCPREFIX)?$SIMPLESAML_INCPREFIX:'') . 'SimpleSAML/Session.php');
require_once((isset($SIMPLESAML_INCPREFIX)?$SIMPLESAML_INCPREFIX:'') . 'SimpleSAML/Metadata/MetaDataStorageHandler.php');
require_once((isset($SIMPLESAML_INCPREFIX)?$SIMPLESAML_INCPREFIX:'') . 'SimpleSAML/XHTML/Template.php');
require_once((isset($SIMPLESAML_INCPREFIX)?$SIMPLESAML_INCPREFIX:'') . 'SimpleSAML/Logger.php');
require_once((isset($SIMPLESAML_INCPREFIX)?$SIMPLESAML_INCPREFIX:'') . 'SimpleSAML/Auth/LDAP.php');

$config = SimpleSAML_Configuration::getInstance();
$metadata = SimpleSAML_Metadata_MetaDataStorageHandler::getMetadataHandler();
$session = SimpleSAML_Session::getInstance(true);

SimpleSAML_Logger::info('AUTH  - ldap: Accessing auth endpoint login');

SimpleSAML_Configuration::init($configdir, 'ldapconfig', 'ldap.php');
$ldapconfig = SimpleSAML_Configuration::getInstance('ldapconfig');


$error = null;
$attributes = array();
$username = null;

if (empty($session))
	SimpleSAML_Utilities::fatalError($session->getTrackID(), 'NOSESSION');

/* Load the RelayState argument. The RelayState argument contains the address
 * we should redirect the user to after a successful authentication.
 */
if (!array_key_exists('RelayState', $_REQUEST)) {
	SimpleSAML_Utilities::fatalError($session->getTrackID(), 'NORELAYSTATE');
}

$relaystate = $_REQUEST['RelayState'];


if (isset($_POST['username'])) {


	try {
	
		/* Validate and sanitize form data. */
	
		/* First, make sure that the password field is included. */
		if (!array_key_exists('password', $_POST)) {
			$error = 'error_nopassword'; 
			continue;
		}
	
		$username = $_POST['username'];
		$password = $_POST['password'];
	
		/* Escape any characters with a special meaning in LDAP. The following
		 * characters have a special meaning (according to RFC 2253):
		 * ',', '+', '"', '\', '<', '>', ';', '*'
		 * These characters are escaped by prefixing them with '\'.
		 */
		$ldapusername = addcslashes($username, ',+"\\<>;*');
	
	
		/*
		 * Connecting to LDAP.
		 */
		$ldap = new SimpleSAML_Auth_LDAP($ldapconfig->getValue('auth.ldap.hostname'),
                                         $ldapconfig->getValue('auth.ldap.enable_tls'));
	
		
		
		
		/** 
		 * Insert the LDAP username into the pattern configured in the 'auth.ldap.dnpattern' option.
		 */
		$dn = str_replace('%username%', $ldapusername, $ldapconfig->getValue('auth.ldap.dnpattern'));
	
		
		/*
		 * Do LDAP bind using DN found from the the dnpattern
		 */
		if (!$ldap->bind($dn, $password)) {
			SimpleSAML_Logger::info('AUTH - ldap: '. $username . ' failed to authenticate. DN=' . $dn);
			throw new Exception('error_wrongpassword');
		}

		/*
		 * Retrieve attributes from LDAP
		 */
		$attributes = $ldap->getAttributes($dn, $ldapconfig->getValue('auth.ldap.attributes', null));

		SimpleSAML_Logger::info('AUTH - ldap: '. $ldapusername . ' successfully authenticated');
		
		$session->setAuthenticated(true, 'login');
		$session->setAttributes($attributes);
		
		$session->setNameID(array(
			'value' => SimpleSAML_Utilities::generateID(),
			'Format' => 'urn:oasis:names:tc:SAML:2.0:nameid-format:transient'));
			
		/**
		 * Create a statistics log entry for every successfull login attempt.
		 * Also log a specific attribute as set in the config: statistics.authlogattr
		 */
		$authlogattr = $config->getValue('statistics.authlogattr', null);
		if ($authlogattr && array_key_exists($authlogattr, $attributes)) 
			SimpleSAML_Logger::stats('AUTH-login OK ' . $attributes[$authlogattr][0]);
		else 
			SimpleSAML_Logger::stats('AUTH-login OK');
			

		$returnto = $_REQUEST['RelayState'];
		SimpleSAML_Utilities::redirect($returnto);	
		
		
	} catch (Exception $e) {
		SimpleSAML_Logger::error('AUTH - ldap: User: '.(isset($requestedUser) ? $requestedUser : 'na'). ':'. $e->getMessage());
		SimpleSAML_Logger::stats('AUTH-login Failed');
		$error = $e->getMessage();
	}
	
}


$t = new SimpleSAML_XHTML_Template($config, 'login.php', 'login.php');

$t->data['header'] = 'simpleSAMLphp: Enter username and password';
$t->data['relaystate'] = $relaystate;
$t->data['error'] = $error;
if (isset($error)) {
	$t->data['username'] = $username;
}

$t->show();


?>