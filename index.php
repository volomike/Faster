<?php

/**
* Faster -- A Minimalist PHP MVC Framework
* 
* This is a single file MVC framework, combining bootstrap, framework, and front controller
* into one file. 
*
* @package Faster-Framework-API
* @author Volo, LLC
* @link http://volosites.com/
* @version 1.01
*/

// SPEED UP PHP BY TURNING OFF UNNECESSARY ASP TAG PARSING
ini_set('asp_tags','0');

// TURN ON SHORT OPEN TAGS
ini_set('short_open_tags','On');

// SOMETIMES IN VERY RARE CASES WE MAY ARRIVE HERE BY REDIRECT_QUERY STRING.
// IT MESSES UP OUR $_GET AND WE HAVE TO REPAIR IT.
if (isset($_SERVER['REDIRECT_QUERY_STRING'])) {
	$_GET = array();
	$_SERVER['QUERY_STRING'] = $_SERVER['REDIRECT_QUERY_STRING'];
	parse_str(preg_replace('/&(\w+)(&|$)/', '&$1=$2', strtr($_SERVER['QUERY_STRING'], ';', '&')), $_GET);
}

// boot our framework

$mvc = new MVC();
$mvc->core = new MVC_Core();
$mvc->view = new MVC_View();
$mvc->request = new MVC_Request();
$mvc->model = new MVC_Model();
$mvc->data = new MVC_Data();

// Remember, MVC_Core does not inherit from MVC. This is because we want to restrict $this only to
// variables ($this->VARIABLE) and core things like baseurl(), etc.
$mvc->view->core = $mvc->core;
$mvc->view->_setRequest($mvc->request);

// inherits from MVC and therefore gets full $this objects for the framework
$mvc->request->core = $mvc->core;
$mvc->request->view = $mvc->view;
$mvc->request->request = $mvc->request;
$mvc->request->model = $mvc->model;
$mvc->request->data = $mvc->data;

// inherits from MVC and therefore gets full $this objects for the framework
$mvc->model->core = $mvc->core;
$mvc->model->view = $mvc->view;
$mvc->model->request = $mvc->request;
$mvc->model->model = $mvc->model;
$mvc->model->data = $mvc->data;

// does not inherit from MVC because it only needs the core object for pathing reasons
$mvc->data->_setCore($mvc->core);

// set our page timer
$mvc->core->_setPageLoadStart();

// let our request object figure out the incoming URL
$mvc->request->_setGroup($mvc->request->getGroup());
$mvc->request->_setAction($mvc->request->getAction());

// SET OUR TIMEZONE STUFF
try {
	$sTimeZone = @ $mvc->core->config['TIMEZONE'];
	$sTimeZone = (empty($sTimeZone)) ? 'GMT' : $sTimeZone;
	if (function_exists('date_default_timezone_set')) {
		date_default_timezone_set($sTimeZone);
	}
	else {
		putenv('TZ=' .$sTimeZone);
	}
	ini_set('date.timezone', $sTimeZone);
} catch(Exception $e) {}

// display errors or log them instead
$sDisplayErrors = @ $mvc->core->config['DISPLAY_ERRORS'];
$sDisplayErrors = (empty($sDisplayErrors)) ? 'Off' : $sDisplayErrors;
ini_set('display_errors',$sDisplayErrors);

// set our error reporting
$sErrReporting = @ $mvc->core->config['ERROR_REPORTING'];
$sErrReporting = (empty($sErrReporting)) ? E_ALL : $sErrReporting;
error_reporting($sErrReporting);

// define our FRAMEWORK_LOADED constant, used by files to keep out prying eyes
/**
* Our constant to identify that the framework has been loaded, and therefore avoid issues with
* prying eyes directly on application folder scripts.
*
*/
define('FRAMEWORK_LOADED',TRUE);

// handle our front controller task
$bStopWhenRouted = TRUE;
$mvc->request->dispatchRoute('',$bStopWhenRouted);

/**
* MVC Class
*
* Serves up the entire framework off the $this variable, as in $this->core, $this->request, 
* $this->model, and $this->data.
*
* @package Faster-Framework-API
*/
class MVC {
	/**
	* Gets mapped to MVC_Core
	*/
	public $core;
	/**
	* Gets mapped to MVC_Request; synonym for controller for faster typing
	*/
	public $request;
	/**
	* Gets mapped to MVC_Model
	*/
	public $model;
	/**
	* Gets mapped to MVC_View
	*/
	public $view;
	/**
	* Gets mapped to MVC_Data
	*/
	public $data;

} // end class MVC

/**
* This class handles our controller details and loads our page controllers with the framework
* objects hanging off of the $this object.
*
* @package Faster-Framework-API
*/
class MVC_Request extends MVC {
	/**
	* Private variable storing our group, parsed from the url.
	*/
	private $_request_group;
	/**
	* Private variable storing our action, parsed from the url.
	*/
	private $_request_action;
	/**
	* Private variable storing our interpreted params past the group and action parts of the url.
	*/
	private $_params;
	
	/**
	* Set the Group. Note that this is public only for when the framework is loaded, but isn't
	* meant to be part of the API.
	* 
	* @ignore
	* @param string $sGroup Passes in a group portion of the URL when framework is loaded
	*/
	public function _setGroup($sGroup) {
		$this->_request_group = $sGroup;
	}
	
	/**
	* Set the Action. Note that this is public only for when the framework is loaded, but isn't
	* meant to be part of the API.
	* 
	* @ignore
	* @param string $sAction Passes in an action portion of the URL when framework is loaded
	*/
	public function _setAction($sAction) {
		$this->_request_action = $sAction;
	}
	
	/**
	* Redirect the user's browser to another location.
	*
	* @param string $sPath Where to redirect the user.
	* @param bool $bTemp Defaults to 1; designates whether this is a permanent or temporary redirection.
	*/
	public function redirectRoute($sPath, $bTemp = 1) {
		$sPath = trim($sPath);
		if (($bTemp == 1) and ($sPath != '')) {
		    header('HTTP/1.1 302 Moved Temporarily');
		    header("Location: $sPath");
		    exit;
		}
		if ($sPath != '') {
		    header('HTTP/1.1 301 Moved Permanently');
		    header("Location: $sPath");
		    exit;
		}
	}

	/**
	* Identify whether someone posted a form to the site.
	* 
	* @return array The $_POST array
	*/
	public function isPosted() {
		return (!empty($_POST));
	}

	/**
	* Returns a session variable.
	* 
	* @param string $sVar The session variable key
	* @return string The session variable value
	*/
	public function getSessionVar($sVar) {
		@ session_start();
		$sResult = @ $_SESSION[strtoupper($sVar)];
		return $sResult;
	}

	/**
	* Sets a session variable.
	*
	* @param string $sVar The session variable key to set
	* @param string $sVal The session variable value to set
	*/
	public function setSessionVar($sVar, $sVal) {
		@ session_start();
		$_SESSION[strtoupper($sVar)] = $sVal;
	}

	/**
	* Returns the IP address of this user
	* 
	* @return string IP Address
	*/
	public function getIP() {
		$sTest = '';
		if (isset($_SERVER['HTTP_X_FORWARD_FOR'])) {
			$sTest = $_SERVER['HTTP_X_FORWARD_FOR'];
		}
		if ($sTest) {
			$s = $sTest;
		} else {
			$s = $_SERVER['REMOTE_ADDR'];
		}
		$s = strip_tags($s);
		if (function_exists('filter_var')) {
			$s = filter_var($s,FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
		}
		return $s;
	}

	/**
	* Returns the User Agent string of this user
	* 
	* @return string User Agent string
	*/
	public function getUserAgent() {
		$s = $_SERVER['HTTP_USER_AGENT'];
		$s = strip_tags($s);
		if (function_exists('filter_var')) {
			$s = filter_var($s,FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
		}
		return $s;
	}

	/**
	* Returns the referring page to the current web page.
	* 
	* @return string The referring page
	*/
	public function getReferrer() {
		$s = @ $_SERVER['HTTP_REFERER'];
		$s = strip_tags($s);
		if (function_exists('filter_var')) {	
			$s = filter_var($s,FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
		}
		return $s;
	}
	
	/**
	* Another version of getReferrer, in case misspelled
	* 
	* @return string The referring page
	*/
	public function getReferer() {
		return $this->getReferrer();
	}

	/**
	* Returns the actual base file path of the site
	* 
	* @return string Base file path
	*/
	public function getPath(){
		$sPath = str_replace('\\', '/', substr(dirname(__FILE__), strlen($_SERVER['DOCUMENT_ROOT'])));
		$s = $_SERVER['REQUEST_URI'];
		$sTest = substr($s, 0, strlen($sPath));
		if ($sTest == $sPath) {
			$s = substr($s, strlen($sPath));
		}
		return $s;
	}

	/**
	* Returns a guess of a group from the URL, which comes right after the base URL.
	* It is not entirely accurate and cannot be trusted yet because the value may be a parameter,
	* instead. Therefore, this routine is used by another class method, which is why it is marked
	* private and not meant for the outside world.
	* 
	* @return string Guessed group parameter of the URL
	*/
	private function _getGuessGroup(){
		$s = $this->getPath();
		$s = ltrim($s, '/');
		$s = rtrim($s, '/');
		$s = str_replace('-',' ',$s);
		$s = ucwords(strtolower($s));
		$s = str_replace(' ','',$s);
		$s = strrev($s);
		$s = basename($s);
		$s = strrev($s);
		$s = (empty($s)) ? 'Default' : $s;
		return $s;
	}

	/**
	* Returns a guess of the action part of the URL, which comes right after the detected group
	* parameter. It is not entirel yaccurate and cannot be trusted yet because the value may be
	* a parameter, instead. Therefore, this routine is used by another class method, which is why
	* it is marked private and not meant for the outside world.
	* 
	* @return string Guessed action parameter of the URL
	*/
	private function _getGuessAction(){
		$s = $this->getPath();
		$s = ltrim($s, '/');
		$s = rtrim($s, '/');
		$s = str_replace('-',' ',$s);
		$s = ucwords(strtolower($s));
		$s = str_replace(' ','',$s);
		if (strpos(' ' . $s,'/') === FALSE) {
			$s = (empty($s)) ? 'Default' : $s;
			return $s;
		}
		$asParts = explode('/',$s);
		$s = @ $asParts[1];
		$s = ucfirst($s);
		$s = (empty($s)) ? 'Default' : $s;
		return $s;
	}

	/**
	* Returns the actual group paramter of the URL, which comes right after the base part of the
	* URL. Has been checked to see if this location exists with the controllers.
	*
	* @return string Actual group parameter of the URL.
	*/
	public function getGroup(){
		if (isset($this->_request_group)) {
			return $this->_request_group;
		}
		$sGGroup = $this->_getGuessGroup();
		$sGAction = $this->_getGuessAction();
		$F = $this->core->base();
		$sTestPath1 = $F . '/app/_controllers/' . $sGGroup . '/c' . $sGAction . '.php';
		$sTestPath2 = $F . '/app/_controllers/' . $sGGroup . '/cDefault.php';
		if ((file_exists($sTestPath1)) or (file_exists($sTestPath2))) {
			$this->_request_group = $sGGroup;
			return $sGGroup;
		}
		$this->_request_group = 'Default';
		return 'Default';
	}

	/**
	* Returns the actual action paramter of the URL, which comes right after the group part of the
	* URL. Has been checked to see if this location exists with the controllers.
	*
	* @return string Actual action parameter of the URL.
	*/
	public function getAction(){
		if (isset($this->_request_action)) {
			return $this->_request_action;
		}
		$sGGroup = $this->_getGuessGroup();
		$sGAction = $this->_getGuessAction();
		$F = $this->core->base();
		$sTestPath1 = $F . '/app/_controllers/' . $sGGroup . '/c' . $sGAction . '.php';
		$sTestPath2 = $F . '/app/_controllers/' . $sGGroup . '/cDefault.php';
		if ((file_exists($sTestPath1)) or (file_exists($sTestPath2))) {
			if (file_exists($sTestPath1)) {
				$this->_request_action = $sGAction;
				return $sGAction;
			} else {
				$this->_request_action = 'Default';
				return 'Default';
			}
		}
		$this->_request_action = 'Default';
		return 'Default';
	}

	/**
	* Returns the parameters of the URL that come after the group and action. Has been checked
	* to prove that these parameters are not part of the controller path.
	*
	* @return string Parameters of the URL, if present, beyond the action parameter of the URL.
	*/
	public function getParams(){
		if (isset($this->_params)) {
			return $this->_params;
		}
		$F = $this->core->base();
		$sGroup = $this->getGroup();
		$sAction = $this->getAction();
		$s = $this->getPath();
		if (strpos(' ' . $s, '?') > 0) {
			$nPos = strpos($s, '?');		
			$s = substr($s, 0, $nPos);
		}
		$s = ltrim($s, '/');
		$s = rtrim($s, '/');
		$asParts = explode('/', $s);
		$sPossibleGroup = @ $asParts[0];
		$sPossibleAction = @ $asParts[1];
		$sPossibleGroup = str_replace('-',' ',$sPossibleGroup);
		$sPossibleGroup = ucwords(strtolower($sPossibleGroup));
		$sPossibleGroup = str_replace(' ','',$sPossibleGroup);
		$sPossibleGroup = ucfirst($sPossibleGroup);
		$sPossibleAction = str_replace('-',' ',$sPossibleAction);
		$sPossibleAction = ucwords(strtolower($sPossibleAction));
		$sPossibleAction = str_replace(' ','',$sPossibleAction);
		$sPossisPossibleActionbleGroup = ucfirst($sPossibleAction);
		if (file_exists($F . '/app/_controllers/' . $sPossibleGroup)) {
			array_shift($asParts);
		}
		if (file_exists($F . '/app/_controllers/' . $sPossibleGroup . '/c' . $sPossibleAction . '.php')) {
			array_shift($asParts);
		}
		$this->_params = $asParts;
		return $asParts;
	}

	/**
	* Returns the $_GET -- added to keep the framework consistent.
	* 
	* @return array The $_GET parameters
	*/
	public function getVars(){
		return $_GET;
	}

	/**
	* Returns the $_POST -- added to keep the framework consistent.
	* 
	* @return array The $_POST parameters
	*/
	public function getPostedVars(){
		return $_POST;
	}

	/**
	* Returns the $_SERVER -- added to keep the framework consistent.
	* 
	* @return array The $_SERVER parameters
	*/
	public function getServerVars(){
		return $_SERVER;
	}

	/**
	* Looks at the incoming URL, or the $sWhichController variable, and routes the site workflow
	* to that controller. Can also be used for 404 handling. As well, can also be used to dispatch
	* a route but not stop (exit(0)) when done. An example of possible URLs and their routing are:
	*
	* <code>
	* http://example.com/ = app/_controllers/Default/cDefault.php
	* http://example.com/my-blog-post = app/_controllers/Default/cDefault.php
	* http://example.com/articles/my-blog-post = app/_controllers/Articles/cDefault.php
	* http://example.com/membership-system/get-user = app/_controllers/MembershipSystem/cGetUser.php
	* http://example.com/members/get-user/400 = app/_controllers/Members/cGetUser.php
	* http://example.com/members/get-user/400/500 = app/_controllers/Members/cGetUser.php
	* </code>
	* 
	* Therefore, the typical URL is either in the format:
	* 
	* <code>
	* http://example.com/
	* http://example.com/{PARAMETER 1..n}
	* http://example.com/{GROUP}/{PARAMETER 1...n}
	* http://example.com/{GROUP}/{ACTION}
	* http://example.com/{GROUP}/{ACTION}/{PARAMETER 1...n}
	* </code>
	*
	* The Default controller group is a folder called Default.
	* The Default controller action is a file called cDefault.php.
	* The actual controller file begins with a 'c' prefix because it helps us in tabbed text editors
	* so as not to confuse these with models or views.
	* All dashes in the group and action slots cause the group or action to be ProperCased with 
	* dashes removed, but this is not applied to the parameters that follow that.
	* It is possible to have a group like Articles, but then map to cDefault.php for action in order to
	* absorb the parameters that may follow.
	* When nothing comes after the base of the site, like http://example.com/, the site defaults to
	* using the controller path app/_controllers/Default/cDefault.php
	*
	* @param string $sWhichController By default, the front controller leaves this empty and therefore
	* parses the URL for that value. However, this can be overridden. Pass a value of 404 here and the
	* framework will try to send 404 headers and try to load either a 404.php or 404.html file, if found
	* or merely stop with the 404 headers.
	* @param string $bStopWhenRouted By default, the front controller stops code execution after the
	* routing, which means it runs through the controller code and then stops. However, this can be
	* overridden.
	*/
	public function dispatchRoute($sWhichController = '', $bStopWhenRouted = TRUE){
		$F = $this->core->base();
		if (($sWhichController == 404) or ($sWhichController == '404')) {
			if (!headers_sent()) {
				header('HTTP/1.1 404 Not Found');
				header('Status: 404 Not Found');
			}
			if (file_exists($F . '/404.php')) {
				require_once($F . '/404.php');
			} else {
				@ include($F . '/404.html');
			}
			die();
		}
		if (empty($sWhichController)) {
			$sGroup = $this->getGroup();
			$sAction = $this->getAction();
			if (!file_exists($F . '/app/_controllers')) {
				trigger_error('Your folder layout is missing a app/_controllers folder', E_USER_ERROR);
			}
			if (!file_exists($F . '/app/_controllers/Default')) {
				trigger_error('Your folder layout is missing a app/_controllers/Default controller folder', E_USER_ERROR);
			}
			if (!file_exists($F . '/app/_controllers/Default/cDefault.php')) {
				trigger_error('Your folder layout is missing a app/_controllers/Default/cDefault.php controller file', E_USER_ERROR);
			}
			$sPath = $F . '/app/_controllers/' . $sGroup . '/c' . $sAction . '.php';
			if (!file_exists($sPath)) {
				trigger_error('Your folder layout is missing a "' . $sPath . '" controller file', E_USER_ERROR);
			}
			require_once($sPath);
		} else {
			$s = $sWhichController;
			$s = str_replace('.php','',$s);
			$s = ltrim($s, '/');
			$s = rtrim($s, '/');		
			$s .= '.x';
			$sBase = basename($s);
			$s = str_replace('/' . $sBase . '.x','/c' . $sBase . '.x');
			$s = str_replace('.x','',$s);
			require_once($F . '/app/_controllers/' . $s . '.php');
		}
		if ($bStopWhenRouted) {
			exit(0);
		}
	}

	/**
	* Returns an unencrypted cookie value.
	* 
	* @param string $sLabel The key of that cookie value
	* @return string The value of that cookie by key
	*/
	public function readCookie($sLabel) {
		$sCookiePrefix = @ '_' . $this->core->config['COOKIE_PREFIX'];
		$sLabel = $sCookiePrefix . strtolower($sLabel);
		if (isset($_COOKIE[$sLabel])) {
			return $_COOKIE[$sLabel];
		} else {
			return '';
		}
	}

	/**
	* Returns an unencrypted cookie value that was encrypted.
	*
	* @param string $sLabel The key of that cookie value
	* @return string The value of that cookie by key
	*/
	public function readEncryptedCookie($sLabel) {
		$sValue = $this->readCookie($sLabel);
		$sValue = $this->_decryptData($sValue);
		return $sValue;
	}

	/**
	* Writes a cookie value. This is a session cookie, not persistent.
	* 
	* @param string $sLabel The key of that cookie value
	* @param string $sValue The value of that cookie by key
	*/
	public function writeCookie($sLabel, $sValue) {
		$sCookiePrefix = @ '_' . $this->core->config['COOKIE_PREFIX'];
		$sPath = '/';
		if ($sValue == '') {
		    $this->deleteCookie($sLabel);
		} else {
		   	setcookie(strtolower($sCookiePrefix . $sLabel), $sValue, 0, $sPath);
		}
	}

	/**
	* Writes a cookie value. This is a persistent, not session, cookie. The persistence is for
	* 365 days.
	* 
	* @param string $sLabel The key of that cookie value
	* @param string $sValue The value of that cookie by key
	*/
	public function writePersistentCookie($sLabel, $sValue) {
		$sCookiePrefix = @ '_' . $this->core->config['COOKIE_PREFIX'];
		$sPath = '/';
		if (!headers_sent()) {
			header ('Cache-control: private'); // IE 6 Fix.
		}
		setcookie(strtolower($sCookiePrefix . $sLabel), $sValue, time()+60*60*24*365, $sPath);
	}

	/**
	* Encrypts and writes an encrypted cookie value. This is a session cookie, not persistent.
	* 
	* @param string $sLabel The key of that cookie value
	* @param string $sValue The value of that cookie by key
	*/
	public function writeEncryptedCookie($sLabel, $sValue) {
		$sValue = $this->_encryptData($sValue);
		$this->writeCookie($sLabel, $sValue);
	}

	/**
	* Encrypts and writes an encrypted cookie value. This is a persistent cookie.
	* 
	* @param string $sLabel The key of that cookie value
	* @param string $sValue The value of that cookie by key
	*/
	public function writeEncryptedPersistentCookie($sLabel, $sValue) {
		$sValue = $this->_encryptData($sValue);
		$this->writePersistentCookie($sLabel, $sValue);
	}

	/**
	* Given a key, this will delete a cookie.
	* 
	* @param string $sLabel the key of that cookie
	*/
	public function deleteCookie($sLabel) {
		$sCookiePrefix = @ '_' . $this->core->config['COOKIE_PREFIX'];
		$sPath = '/';
		$sLabel = strtolower($sLabel);
		setcookie ($sCookiePrefix . $sLabel, ' ', 0, $sPath);
		setcookie ($sCookiePrefix . $sLabel, '', time() - 3600, $sPath);
	}

	/**
	* This private class method decrypts our data.
	* 
	* @param string $sData String of encrypted data.
	* @return string Unencrypted data.
	*/
	private function _decryptData($sData){
		$sCookiePrefix = @ $this->core->config['COOKIE_PREFIX'];
		$sPrivateKey = md5($sCookiePrefix);
		if (trim($sData) == '') {
		    return '';
		}
		// REVERSE THE PRIVATE KEY
		$sPrivateKey = strrev($sPrivateKey);

		// URL DECODE THE DATA
		$sData = urldecode($sData);

		// BASE64 DECODE THE DATA
		$sData = base64_decode($sData);

		if (function_exists('mcrypt_encrypt')) {

		    $iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB);
		    $iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
		    $sData = mcrypt_decrypt(MCRYPT_RIJNDAEL_256,$sPrivateKey,$sData,
		        MCRYPT_MODE_ECB, $iv);

		} else {

		    // XOR EACH CHAR BY EACH CHAR IN A PRIVATE KEY (PRIVATE KEY REPEATED
		    // OVER AND OVER AGAIN)
		    $j=0; $nLen = strlen($sData);
		    for($i = 0; $i < $nLen; $i++){
		        $sData[$i] = chr(ord($sData[$i]) ^ ord($sPrivateKey[$j]));
		        $j++;
		        $j = ($j >= strlen($sPrivateKey)) ? 0 : $j;
		    }
		    // REVERSE OUR DATA
		    $sData = strrev($sData);

		}

		// RETURN OUR DATA
		return trim($sData);
	}

	/**
	* This private class method encrypts our data.
	* 
	* @param string $sData String of unencrypted data.
	* @return string Encrypted data.
	*/
	private function _encryptData($sData){
		$sCookiePrefix = @ $this->core->config['COOKIE_PREFIX'];
		$sPrivateKey = md5($sCookiePrefix);
		if (trim($sData) == '') {
		    return '';
		}
		// REVERSE THE PRIVATE KEY
		$sPrivateKey = strrev($sPrivateKey);

		if (function_exists('mcrypt_encrypt')) {

		    $iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB);
		    $iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
		    $sData = mcrypt_encrypt(MCRYPT_RIJNDAEL_256,$sPrivateKey,$sData,
		        MCRYPT_MODE_ECB,$iv);

		} else {

		    // REVERSE OUR DATA
		    $sData = strrev($sData);
		    // XOR EACH CHAR BY EACH CHAR IN A PRIVATE KEY (PRIVATE KEY REPEATED
		    // OVER AND OVER AGAIN)
		    $j=0; $nLen = strlen($sData);
		    for($i = 0; $i < $nLen; $i++){
		        $sData[$i] = chr(ord($sData[$i]) ^ ord($sPrivateKey[$j]));
		        $j++;
		        $j = ($j >= strlen($sPrivateKey)) ? 0 : $j;
		    }

		}

		// BASE64 OUR DATA
		$sData = trim(base64_encode($sData));

		// FIX THE = PROBLEM IN THAT RESULT
		$sData = str_replace('=','',$sData);

		// URL ENCODE THE DATA
		$sData = urlencode($sData);

		// RETURN THE DATA
		return $sData;
	}	
} // end class MVC_Request

/**
* A class for loading model objects and enabling them with a powerful $this object, just like our
* controllers have.
*
* @package Faster-Framework-API
*/
class MVC_Model extends MVC {
	/**
	* Loads a model script to be executed and to return an object variable.
	* 
	* @param string $sModelName The model path, such as 'Test', or 'SampleSystem/Test'. This translates
	* to app/_models/Test.php and app/_models/SampleSystem/Test.php, for example. Note that models do not have
	* an 'm' prefix before the filename because we really only needed in tabbed text editors to delineate
	* files which are controllers or views, which is why controllers have the "c" prefix, while views
	* have the "v" prefix.
	*/
	public function load($sModelName) {
		$F = $this->core->base();
		if (strpos(' ' . $sModelName,'/')>0) {
			$sBaseName = basename($sModelName);
			$sPath = dirname($sModelName);
			$sPath = rtrim($sPath, '/') . '/';
			$sModelPath = $sPath . $sBaseName . '.php';
			$sModelName = basename($sModelName);
		} else {
			$sModelPath = $sModelName . '.php';
		}
		if (!file_exists($F . '/app/_models')) {
			trigger_error('Your folder layout is missing a app/_models folder',E_USER_ERROR);
		}
		$sPath = $F . '/app/_models/' . $sModelPath;
		if (!file_exists($sPath)) {
			trigger_error('Your folder layout is missing a "' . $sPath . '" models file',E_USER_ERROR);
		}
		require_once($sPath);
		$o = new $sModelName();
		$o->core = $this->core;
		$o->request = $this;
		$o->model = $this;
		$o->view = $this->view;
		$o->data = $this->data;		
		return $o;
	}	
}


/**
* This class is for common class methods for things not dealing with requests, views, data, or
* models.
*
* @package Faster-Framework-API
*/
class MVC_Core {

	/**
	* Gets mapped to app/site.php file, where we store an array of settings.
	*/
	public $config;
	
	/**
	* Private variable to store the page load start time
	*/
	private $_page_load_start_time;

	/**
	* Load our configuration file, app/site.php, so that the $config public variable is accessible with
	* the array of returned values.
	*/
	public function __construct(){
		$this->config = $this->getConfig();
	}
	
	/**
	* Sets our microtime() for the Page Load Time. Is not meant to be exposed publicly as part of the framework API.
	*
	* @ignore
	*/
	public function _setPageLoadStart(){
		$asMicro = explode(' ',microtime());
		$nStartTime = $asMicro[0] + $asMicro[1];
		$this->_page_load_start_time = $nStartTime;
	}

	/**
	* Loads our configuration file.
	*
	* @return array An array of settings from app/site.php
	*/
	public function getConfig() {
		$F = $this->base();
		return include $F . '/app/site.php';
	}

	/**
	* Returns our base URL of a site.
	* 
	* @return string The Base URL
	*/
	public function baseurl() {
		static $sRoot;
		if (!isset($sRoot)) {
			$sProtocol = empty($_SERVER['HTTPS'])? 'http' : 'https';
			$sServerName = $_SERVER['SERVER_NAME'];
			$sPort = $_SERVER['SERVER_PORT']=='80'? '' : ':' . $_SERVER['SERVER_PORT'];
			$sPath = str_replace('\\', '/', substr(dirname(__FILE__), strlen($_SERVER['DOCUMENT_ROOT'])));
			$sPath = $sProtocol . '://' . $sServerName . $sPort . $sPath;
			$sRoot = rtrim($sPath, '/');
		}
		return $sRoot;
	}
	
	/**
	* Returns our base file path of a site.
	*
	* @return string The Base File Path
	*/
	public function base() {
		static $sPath;
		if (!isset($sPath)) {
			$sPath = dirname(__FILE__);
			$sPath = rtrim($sPath, '/');
		}
		return $sPath;
	}
	
	/**
	* Returns the total page load time.
	*
	* @return int Total time in seconds
	*/
	public function page_load_time(){
		$asMicro = explode(' ',microtime());
		$nEndTime = $asMicro[0] + $asMicro[1];
		$nTotalTime = ($nEndTime - $this->_page_load_start_time);
		return $nTotalTime;
	}

} // end class MVC_Core

/**
* This class handles our view details.
*
* @package Faster-Framework-API
*/
class MVC_View {

	
	/**
	* Gets mapped to MVC_Core so that this functionality is exposed to the View.
	*/
	public $core;
	/**
	* Private variable where we store our request object when the framework loads
	*/
	private $_request;

	/**
	* Private variable where we store variables that can be utilized in Views with $this->VARIABLE
	*/
	private $_asVars = array();

	/**
	* Constant used by some of the class methods to indicate no desire to alter variable.
	*/
	const ENCODE_DEFAULT = 0;
	/**
	* Constant used by some of the class methods to indicate we need to use htmlentities() on the variable.
	*/
	const ENCODE_HTML = 1;
	/**
	* Constant used by some of the class methods to indicate we need to use urlencode() on the variable.
	*/
	const ENCODE_URL = 2;
	
	/**
	* Private class method to set our request object when the framework loads. It's not designed to
	* be used by people using the framework.
	* 
	* @ignore
	* @param object $request The request object
	*/
	public function _setRequest($request){
		$this->_request = $request;
	}
	
	/**
	* Injects a variable into a view. The view can then access it via $this->VARIABLE.
	*
	* Note that View variables must be in UPPERCASE or an error is triggered.
	* 
	* @param string $sKey The key we want to set for our variable.
	* @param string $sVal The value we want to store.
	* @param int $nType A selector from the ENCODE_* constants of this class which lets us do some
	* encoding on the variable either through htmlentities() or urlencode(), or not at all.
	*/	
	public function setVar($sKey, $sVal, $nType = self::ENCODE_DEFAULT){
		$sUpper = strtoupper($sKey);
		if ($sUpper != $sKey) {
			trigger_error('View variables must be in uppercase',E_USER_ERROR);
		}
		switch ($nType) {
			case self::ENCODE_HTML: // show the html
				$sVal = htmlentities($sVal);
				break;
			case self::ENCODE_URL: // prepare for urls
				$sVal = urlencode($sVal);
				break;
			default: // 0, default, as is, unaltered
				break;
		
		}		
		$this->_asVars[$sUpper] = $sVal;
	}
	
	/**
	* Is the inverse of setVar(). Is used to provide $this->VARIABLE functionality in the view for
	* accessing variables that were previously assigned with setVar().
	*
	* Note that View variables must be in UPPERCASE or an error is triggered.
	* 
	* @param string $sKey The key we want to use to access our variable.
	*/
	public function __get($sKey){
		$sUpper = strtoupper($sKey);
		if ($sUpper != $sKey) {
			trigger_error('View variables must be in uppercase',E_USER_ERROR);
		}
		return $this->_asVars[$sKey];
	}		

	/**
	* Injects a variable into a view. What it does, in reality, is create a global constant which
	* the view can read.
	* 
	* Note that View variables must be in UPPERCASE or an error is triggered.
	* 
	* @param string $sKey The key we want to set for our variable. Becomes the constant name.
	* @param string $sVal The value we want to store.
	* @param int $nType A selector from the ENCODE_* constants of this class which lets us do some
	* encoding on the variable either through htmlentities() or urlencode(), or not at all.
	*/
	public function setCVar($sKey, $sVal, $nType = self::ENCODE_DEFAULT) {
	
		$sUpper = strtoupper($sKey);
		if ($sUpper != $sKey) {
			trigger_error('View variables must be in uppercase',E_USER_ERROR);
		}
	
		switch ($nType) {
			case self::ENCODE_HTML: // show the html
				$sVal = htmlentities($sVal);
				break;
			case self::ENCODE_URL: // prepare for urls
				$sVal = urlencode($sVal);
				break;
			default: // 0, default, as is, unaltered
				break;
		
		}
		define($sUpper, $sVal);
	}
	
	/**
	* Does the same thing as setCVar(), but does so with an array of vars at once.
	*
	* @param array $asVars Array of key => value pairs.
	* @param int $nType A selector from the ENCODE_* constants of this class which lets us do some
	* encoding on the variable either through htmlentities() or urlencode(), or not at all.
	*/
	public function setCVars($asVars, $nType = self::ENCODE_DEFAULT) {
		foreach($asVars as $sVarName => $sVal){
			$this->setCVar($sVarName, $sVal, $nType);
		}
	}
	
	/**
	* Does the same thing as setVar(), but does so with an array of vars at once.
	*
	* @param array $asVars Array of key => value pairs.
	* @param int $nType A selector from the ENCODE_* constants of this class which lets us do some
	* encoding on the variable either through htmlentities() or urlencode(), or not at all.
	*/
	public function setVars($asVars, $nType = self::ENCODE_DEFAULT) {
		foreach($asVars as $sVarName => $sVal){
			$this->setCVar($sVarName, $sVal, $nType);
		}
	}	

	/**
	* Renders our view with variables intact, and then returns as a string. The view uses PHP
	* Alternative Syntax.
	* 
	* @param string $sFile A specified file path to the view. If not specified, it will assume the
	* same path as the controller path, but with "v" instead of "c" on the final file prefix.
	* @param bool $bDrawImmediately Defines whether to cache the view until all elements are drawn
	* to the browser, and then show it, or show it as it is built.
	*/
	public function render($sFile = '', $bDrawImmediately = FALSE) {
		$F = $this->core->base();	
		if (empty($sFile)) {
			$sFile = $this->_request->getGroup() . '/' . $this->_request->getAction();
		}
		$sFile = strrev($sFile);
		$sFile = str_replace('/','~',$sFile);
		$sFile = preg_replace('/~/','v~',$sFile,1);
		$sFile = str_replace('~','/',$sFile);
		$sFile = strrev($sFile);
		if (!$bDrawImmediately) {
			ob_start();
		}
		if (!file_exists($F . '/app/_views')) {
			trigger_error('Your folder layout is missing a app/_views folder',E_USER_ERROR);
		}
		$sPath = $F . '/app/_views/' . $sFile . '.php';
		if (!file_exists($sPath)) {
			trigger_error('Your folder layout is missing a "' . $sPath . '" views file',E_USER_ERROR);
		}
		require_once($sPath);
		if (!$bDrawImmediately) {
			$sOut = ob_get_contents();
			ob_end_clean();
			return $sOut;
		}
	}

	/**
	* Displays our view. See render() for more information. All this class method does is echo
	* the result of render().
	*
	* @param string $sFile A specified file path to the view. If not specified, it will assume the
	* same path as the controller path, but with "v" instead of "c" on the final file prefix.
	* @param bool $bDrawImmediately Defines whether to cache the view until all elements are drawn
	* to the browser, and then show it, or show it as it is built.
	*/
	public function display($sFile = '', $bDrawImmediately = FALSE) {
		echo $this->render($sFile, $bDrawImmediately);
	}

} // end class MVC_View

/**
* A simplistic data class that helps us with PDO with MySQL or SQLite. This class is intentionally
* small and simple.
*
* @package Faster-Framework-API
*/
class MVC_Data {

	/**
	* Private variable to store our core object, used by this class.
	*/
	private $_core;
	
	/**
	* Is used only at boot time of the framework in order to set the core object of the data class.
	* It is not meant to be exposed to be used by someone who wants to use the framework.
	*
	* @ignore
	* @param object $core Our core object
	*/
	public function _setCore($core){
		$this->_core = $core;
	}

	/**
	* Returns a PDO object variable for the given MySQL connection as specified in the $this->_core
	* ->config parameters for the given database.
	*
	* @return object The PDO object
	*/
	public function mysql(){
		static $PDO;
		if (!$PDO) {
			$sPort = @ $this->_core->config['DB_PORT'];
			$sPort = (empty($sPort)) ? '3306' : $sPort;
			$DSN = sprintf('mysql:dbname=%s;host=%s;port=$sPort', $this->_core->config['DB_DATABASE'], $this->_core->config['DB_SERVER'], $sPort);
			try {
				$PDO = new PDO($DSN, $this->_core->config['DB_USER'],$this->_core->config['DB_PASS']);
			} catch(PDOException $e) {
				$SQLSTATE = $this->get_ANSI_SQLSTATE_Code($e);
				if ($SQLSTATE == '42000') {
					trigger_error('Missing database "' . $this->_core->config['DB_DATABASE'] . '"', E_USER_ERROR);
				}
			}
			$PDO->setAttribute(PDO::MYSQL_ATTR_MAX_BUFFER_SIZE, 1024*1024*50);
			$PDO->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			$PDO->setAttribute(PDO::ATTR_PERSISTENT,TRUE);
			$PDO->setAttribute(PDO::ATTR_CURSOR, PDO::CURSOR_FWDONLY);
		}
		return $PDO;
	}

	/**
	* Returns a PDO object variable for the given SQLite connection as specified in the $this->_core
	* ->config parameters for the given database.
	*
	* @return object The PDO object
	*/
	public function sqlite(){
		static $PDO;
		if (!$PDO){
			$DSN = sprintf('sqlite:%s', $this->_core->config['DB_DATABASE']);
			try {
				$PDO = new PDO($DSN, $this->_core->config['DB_USER'],$this->_core->config['DB_PASS']);	
			} catch(PDOException $e) {
				$SQLSTATE = $this->get_ANSI_SQLSTATE_Code($e);
				if ($SQLSTATE == 'HY000') {
					trigger_error('The database file "' . $this->_core->config['DB_DATABASE']  . '" could not be found.',E_USER_ERROR);
				}
			}
		}
		return $PDO;
	}

	/**
	* A handy class method to give you a unique ID if you don't like a database default autonumbering.
	* Some people don't like database default autonumbering because programmers can mess up the data
	* integrity if they don't know what they are doing in transferring a database. By using these keys
	* instead, the data integrity remains intact.
	*
	* @param object $PDO The current PDO object
	* @param string $sTable The table by which we need this new unique ID value.
	* @return string A unique ID for our record. It is in the format 999-ABCD and is fixed at 8
	* characters. The dash helps us identify the record faster, visually, if viewing the records
	* in a table.
	*/
	public function getNewID($PDO, $sTable) {
		$sKey = mt_rand(111,999) . '-';
		$sKey .= dechex(mt_rand(11111,99999));
		$sKey = substr($sKey, 0, 8); // failsafe
		$sKey = strtoupper($sKey);
		$sTable = strtoupper($sTable);
		$sDate = gmdate('Y-m-d H:i:s');
		$sSQL = "INSERT INTO `ids` (`id`, `group`, `dt_created`) VALUES ('$sKey', '$sTable', '$sDate');";
		try {
			$PDO->exec($sSQL);
		} catch (PDOException $e) {
			$SQLSTATE = $this->get_ANSI_SQLSTATE_Code($e);
			if ($SQLSTATE == '42S02') {
				trigger_error('The "ids" table does not exist.', E_USER_ERROR);
			}
			if ($SQLSTATE == '23000') {
				return getNewID();
			}
			trigger_error("The SQLSTATE ($SQLSTATE) error code happened in getNewID() of the MVC_Data class.", E_USER_ERROR);
		}
		return $sKey;
	}

	/**
	* Takes a PDOException variable and converts it into an ANSI SQLSTATE code.
	* 
	* For SQLSTATE variables and their meanings, see the attached links.
	* @link http://dev.mysql.com/doc/refman/5.0/en/error-messages-server.html
	* @link http://www.php.net/manual/en/pdo.errorcode.php
	*
	* @param object $e PDOException object from a Try/Catch routine
	* @return string A translated PDOException as an ANSI SQLSTATE code.
	*/
	public function get_ANSI_SQLSTATE_Code($e) {
		$s = $e->__toString();
		$asItems = explode('[', $s);
		$s = @ $asItems[1];
		$asItems = explode(']', $s);
		$s = @ $asItems[0];
		return trim($s);
	}

} // end class MVC_Data


