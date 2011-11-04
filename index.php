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
* @version 1.0363
*/

// SPEED UP PHP BY TURNING OFF UNNECESSARY ASP TAG PARSING
ini_set('asp_tags','0');

// FIX MAGIC QUOTES IF TURNED ON
if (get_magic_quotes_gpc()) {
    $process = array(&$_GET, &$_POST, &$_COOKIE, &$_REQUEST);
    while (list($key, $val) = each($process)) {
        foreach ($val as $k => $v) {
            unset($process[$key][$k]);
            if (is_array($v)) {
                $process[$key][stripslashes($k)] = $v;
                $process[] = &$process[$key][stripslashes($k)];
            } else {
                $process[$key][stripslashes($k)] = stripslashes($v);
            }
        }
    }
    unset($process);
}

// FIXES A PROBLEM WHERE fgets() AND file() MAY NOT READ LINEWRAPS ON MAC OR WINDOWS FILES PROPERLY.
// BSD UNIX AND UNIX AND LINUX ALL USE \n FOR LINE WRAPS.
// MACS, EVEN THOUGH BASED ON BSD UNIX, USES \r FOR LINE WRAPS IN MOST GUI-BASED APPS.
// WINDOWS ABOUT 99% OF THE TIME USES \r\n FOR LINE WRAPS IN FILES.
ini_set('auto_detect_line_endings','1');

// TURN ON SHORT OPEN TAGS
ini_set('short_open_tags','On');

// SET SESSION TIMEOUT
ini_set('session.cookie_lifetime','3600'); //an hour
ini_set('session.gc_maxlifetime','3600'); 

// SOMETIMES IN VERY RARE CASES WE MAY ARRIVE HERE BY REDIRECT_QUERY STRING.
// IT MESSES UP OUR $_GET AND WE HAVE TO REPAIR IT.
if (isset($_SERVER['REDIRECT_QUERY_STRING'])) {
	$_GET = array();
	$_SERVER['QUERY_STRING'] = $_SERVER['REDIRECT_QUERY_STRING'];
	parse_str(preg_replace('/&(\w+)(&|$)/', '&$1=$2', strtr($_SERVER['QUERY_STRING'], ';', '&')), $_GET);
}

// boot our framework

$mvc = new Faster();
$mvc->core = new Faster_Core();
$mvc->view = new Faster_View();
$mvc->request = new Faster_Request();
$mvc->model = new Faster_Model();
$mvc->data = new Faster_Data();

// Remember, Faster_Core does not inherit from Faster. This is because we want to restrict $this only to
// variables ($this->VARIABLE) and core things like baseurl(), etc.
$mvc->view->core = $mvc->core;
$mvc->view->_setRequest($mvc->request);

// inherits from Faster and therefore gets full $this objects for the framework
$mvc->request->core = $mvc->core;
$mvc->request->view = $mvc->view;
$mvc->request->request = $mvc->request;
$mvc->request->model = $mvc->model;
$mvc->request->data = $mvc->data;

// inherits from Faster and therefore gets full $this objects for the framework
$mvc->model->core = $mvc->core;
$mvc->model->view = $mvc->view;
$mvc->model->request = $mvc->request;
$mvc->model->model = $mvc->model;
$mvc->model->data = $mvc->data;

// does not inherit from Faster because it only needs the core object for pathing reasons
$mvc->data->_setCore($mvc->core);

// set our page timer
$mvc->core->_setPageLoadStart();

// let our request object figure out the incoming URL
$sTest = @ $argv[1];
$bCLI = (!empty($sTest));
if ($bCLI) {
	$sPath = $sTest;
	$sPath = str_replace('--PATH','',$sPath);
	$sPath = str_replace('--path','',$sPath);
	$sPath = str_replace('--','',$sPath);
	$sPath = str_replace('.php','',$sPath);
	$sPath = str_replace('.PHP','',$sPath);
	$sPath = str_replace('=','',$sPath);
	$sPath = str_replace('"','',$sPath);
	$sPath = str_replace("'",'',$sPath);
	$sPath = str_replace('\\','/',$sPath);
	$sPath = str_replace(' ','',$sPath);
	$mvc->request->_setGroup($mvc->request->polishGroup($sPath));
	$mvc->request->_setAction($mvc->request->polishAction($sPath));
} else {
	$mvc->request->_setGroup($mvc->request->getGroup());
	$mvc->request->_setAction($mvc->request->getAction());
}

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

$sBase = $mvc->core->base();
/**
* Our constant to provide a quick way to identify a header path.
*
*/
define('HEADER',$sBase . '/app/_views/HEADER.php');
/**
* Our constant to provide a quick way to identify a footer path.
*
*/
define('FOOTER',$sBase . '/app/_views/FOOTER.php');

// handle our front controller task
$bStopWhenRouted = TRUE;
$mvc->request->dispatchRoute('',$bStopWhenRouted);

/**
* Faster Class
*
* Serves up the entire framework off the $this variable, as in $this->core, $this->request, 
* $this->model, and $this->data.
*
* @package Faster-Framework-API
*/
class Faster {
	/**
	* Gets mapped to Faster_Core
	*/
	public $core;
	/**
	* Gets mapped to Faster_Request; synonym for controller for faster typing
	*/
	public $request;
	/**
	* Gets mapped to Faster_Model
	*/
	public $model;
	/**
	* Gets mapped to Faster_View
	*/
	public $view;
	/**
	* Gets mapped to Faster_Data
	*/
	public $data;

} // end class Faster

/**
* This class handles our controller details and loads our page controllers with the framework
* objects hanging off of the $this object.
*
* @package Faster-Framework-API
*/
class Faster_Request extends Faster {
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
	* Redirect the user's browser to another location. If 404 location, then show a 404 condition.
	*
	* @param string $sPath Where to redirect the user.
	* @param bool $bTemp Defaults to 1; designates whether this is a permanent or temporary redirection.
	*/
	public function redirectRoute($sPath, $bTemp = 1) {
		if ($sPath == 404) {
			$this->dispatchRoute(404);
			exit;
		}
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
	* @return bool Whether someone posetd a form to the site via $_POST or $_FILES
	*/
	public function isPosted() {
		if ((empty($_POST)) and (empty($_FILES))) {
			return FALSE;
		}
		return TRUE;
	}

	/**
	* Returns a session variable.
	* 
	* @param string $sVar The session variable key
	* @return string The session variable value
	*/
	public function getSessionVar($sVar) {
		@ session_set_cookie_params(0, '/');
		@ session_start();
		$sResult = @ $_SESSION[session_id() . '_' . strtoupper($sVar)];
		return $sResult;
	}

	/**
	* Sets a session variable.
	*
	* @param string $sVar The session variable key to set
	* @param string $sVal The session variable value to set
	*/
	public function setSessionVar($sVar, $sVal) {
		@ session_set_cookie_params(0, '/');
		@ session_start();
		$_SESSION[session_id() . '_' . strtoupper($sVar)] = $sVal;
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
		$sPath = str_replace('index.php','',$_SERVER['SCRIPT_NAME']);
		$s = @ $_SERVER['REQUEST_URI'];
		$sTest = substr($s, 0, strlen($sPath));
		if ($sTest == $sPath) {
			$s = substr($s, strlen($sPath));
		}
		if (empty($s)) {
			$s = dirname(__FILE__);
		}
		return $s;
	}
	
	/**
	* Converts the Group (aka "controller") portion of the URL from dashes into the form that is
	* used by this framework in its ProperCase format.
	*
	* @param string $s The path. This is parsed from getPath() unless a URL is passed to it.
	* @return string Group portion of the URL
	*/
	public function polishGroup($s) {
		$s = ltrim($s, '/');
		$s = rtrim($s, '/');
		$s = str_replace('-',' ',$s);
		$s = ucwords(strtolower($s));
		$s = str_replace(' ','',$s);
		$s = strrev($s);
		$s = basename($s);
		$s = strrev($s);
		return $s;
	}
	
	/**
	* Converts the Action portion of the URL from dashes into the form that is
	* used by this framework in its ProperCase format.
	*
	* @param string $s The path. This is parsed from getPath() unless a URL is passed to it.
	* @return string Action portion of the URL
	*/
	public function polishAction($s){
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
		if (substr($s,-4) == '.php') {
			$s = str_replace('.php','',$s);
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
		return $this->polishGroup($s);
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
		return $this->polishAction($s);
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
	
	public function getParam($nIndex) {
		$asParams = $this->getParams();
		$sVal = @ $asParams[$nIndex];
		return $sVal;
	}

	/**
	* Returns the parameters of the URL that come after the group and action. Has been checked
	* to prove that these parameters are not part of the controller path.
	*
	* @return array An Array of string parameters of the URL, if present, beyond the action parameter of the URL.
	*/
	public function getParams(){
		if (isset($this->_params)) {
			return $this->_params;
		}
		
		global $argv;
		$sTest = @ $argv[1];
		$bCLI = (!empty($sTest));
		
		if ($bCLI) {
			$asParams = $argv;
			array_shift($asParams);
			array_shift($asParams);
			foreach($asParams as $sKey => $sVal) {
				if (substr($sVal, 0, 2) == '--') {
					$sVal = preg_replace('/^--/','',$sVal);
					$asParams[$sKey] = $sVal;
				}
				if ((substr($sVal, 0, 1) == '"') and (substr($sVal, -1, 1) == '"')) {
					$sVal = ltrim($sVal, '"');
					$sVal = rtrim($sVal, '"');
					$asParams[$sKey] = $sVal;
				} else if ((substr($sVal, 0, 1) == "'") and (substr($sVal, -1, 1) == "'")) {
					$sVal = ltrim($sVal, "'");
					$sVal = rtrim($sVal, "'");
					$asParams[$sKey] = $sVal;
				}
			}
			$this->_params = $asParams;
			return $asParams;			
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
		foreach($_GET as $sKey => $sVal){
			$sVal = urldecode($sVal);
			$_GET[$sKey] = trim($sVal);
		}
		return $_GET;
	}

	/**
	* Returns the $_POST -- added to keep the framework consistent.
	* 
	* @return array The $_POST parameters
	*/
	public function getPostedVars(){
		foreach($_POST as $sKey => $sVal){
			$_POST[$sKey] = trim($sVal);
		}
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
	* Returns the $_GET by key param
	* 
	* @param string $sKey The key
	* @param boolean $bStripTags Whether to apply strip_tags(). Defaults to TRUE.
	* @return string The value
	*/
	public function getVar($sKey, $bStripTags = TRUE){
		$sVal = @ $_GET[$sKey];
		if ($bStripTags) {
			$sVal = strip_tags($sVal);
		}
		$sVal = urldecode($sVal);
		$sVal = trim($sVal);
		return $sVal;
	}

	/**
	* Returns the $_POST by key param
	* 
	* @param string $sKey The key
	* @param boolean $bStripTags Whether to apply strip_tags(). Defaults to TRUE.
	* @return string The value
	*/
	public function getPostedVar($sKey, $bStripTags = TRUE){
		$sVal = @ $_POST[$sKey];
		if ($bStripTags) {
			$sVal = strip_tags($sVal);
		}
		$sVal = trim($sVal);
		return $sVal;
	}

	/**
	* Returns the $_SERVER by key param
	* 
	* @param string $sKey The key
	* @return string The value
	*/
	public function getServerVar($sKey){
		$sVal = @ $_SERVER[$sKey];
		return $sVal;
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
	* Note underscores in variables in this function are so that the controller doesn't inherit
	* variables. (I mean, it does, but it would have to use underscore vars.) This is actually a
	* failsafe in case this function gets edited. We will do unset() on all variables possible.
	*
	* Note also an alternative action path. Instead of cDefault.php, you can name it the same as the
	* group path. So, if you have /about for the URL, then you could use About/cAbout.php for the
	* controller path. This helps when working with multiple files at once in a text editor, where
	* you won't confuse all the cDefault.php files together.
	*
	* @param string $_sWhichController By default, the front controller leaves this empty and therefore
	* parses the URL for that value. However, this can be overridden. Pass a value of 404 here and the
	* framework will try to send 404 headers and try to load either a 404.php or 404.html file, if found
	* or merely stop with the 404 headers.
	* @param string $_bStopWhenRouted By default, the front controller stops code execution after the
	* routing, which means it runs through the controller code and then stops. However, this can be
	* overridden.
	*/
	public function dispatchRoute($_sWhichController = '', $_bStopWhenRouted = TRUE){
		$_F = $this->core->base();
		if ($_sWhichController == 404) {
			if (!headers_sent()) {
				header('HTTP/1.1 404 Not Found');
				header('Status: 404 Not Found');
			}
			if (file_exists($_F . '/404.php')) {
				unset($_sWhichController);
				unset($_bStopWhenRouted);
				require_once($_F . '/404.php');
			} else {
				@ include($_F . '/404.html');
			}
			die();
		}
		if (empty($_sWhichController)) {
			$_sGroup = $this->getGroup();
			$_sAction = $this->getAction();
			if (!file_exists($_F . '/app/_controllers')) {
				trigger_error('Your folder layout is missing a app/_controllers folder', E_USER_ERROR);
			}
			if (!file_exists($_F . '/app/_controllers/Default')) {
				trigger_error('Your folder layout is missing a app/_controllers/Default controller folder', E_USER_ERROR);
			}
			if (!file_exists($_F . '/app/_controllers/Default/cDefault.php')) {
				trigger_error('Your folder layout is missing a app/_controllers/Default/cDefault.php controller file', E_USER_ERROR);
			}
			$_sPath = $_F . '/app/_controllers/' . $_sGroup . '/c' . $_sAction . '.php';
			if (!file_exists($_sPath)) {
				$_sPath = $_F . '/app/_controllers/' . $_sGroup . '/c' . $_sGroup . '.php';
				if (!file_exists($_sPath)) {
					trigger_error('Your folder layout is missing a "' . $_sPath . '" controller file', E_USER_ERROR);
				}
			}
			unset($_sWhichController);
			unset($_F);
			unset($_sGroup);
			unset($_sAction);			
			require_once($_sPath);
		} else {
			$_s = $_sWhichController;
			$_s = str_replace('.php','',$_s);
			$_s = ltrim($_s, '/');
			$_s = rtrim($_s, '/');		
			$_s .= '.x';
			$_sBase = basename($_s);
			$_s = str_replace('/' . $_sBase . '.x','/c' . $_sBase . '.x');
			$_s = str_replace('.x','',$_s);
			unset($_sWhichController);
			require_once($_F . '/app/_controllers/' . $_s . '.php');
		}
		if ($_bStopWhenRouted) {
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
} // end class Faster_Request

/**
* A class for loading model objects and enabling them with a powerful $this object, just like our
* controllers have.
*
* @package Faster-Framework-API
*/
class Faster_Model extends Faster {
	/**
	* Loads a model script to be executed and to return an object variable.
	*
	* Note all variables use underscores so as not to pass them to the model class file. This is
	* just a precautionary failsafe. We do an unset() on all variables we can before the
	* require_once() call.
	* 
	* @param string $_sModelName The model path, such as 'Test', or 'SampleSystem/Test'. This translates
	* to app/_models/Test.php and app/_models/SampleSystem/Test.php, for example. Note that models do not have
	* an 'm' prefix before the filename because we really only needed in tabbed text editors to delineate
	* files which are controllers or views, which is why controllers have the "c" prefix, while views
	* have the "v" prefix.
	*/
	public function load($_sModelName) {
		$_F = $this->core->base();
		if (strpos(' ' . $_sModelName,'/')>0) {
			$_sBaseName = basename($_sModelName);
			$_sPath = dirname($_sModelName);
			$_sPath = rtrim($_sPath, '/') . '/';
			$_sModelPath = $_sPath . $_sBaseName . '.php';
			$_sModelName = basename($_sModelName);
		} else {
			$_sModelPath = $_sModelName . '.php';
		}
		if (!file_exists($_F . '/app/_models')) {
			trigger_error('Your folder layout is missing a app/_models folder',E_USER_ERROR);
		}
		$_sPath = $_F . '/app/_models/' . $_sModelPath;
		if (!file_exists($_sPath)) {
			trigger_error('Your folder layout is missing a "' . $_sPath . '" models file',E_USER_ERROR);
		}
		unset($_sBaseName);
		unset($_sModelPath);
		unset($_F);
		require_once($_sPath);
		$_o = new $_sModelName();
		$_o->core = $this->core;
		$_o->request = $this;
		$_o->model = $this;
		$_o->view = $this->view;
		$_o->data = $this->data;		
		return $_o;
	}	
	
}


/**
* This class is for common class methods for things not dealing with requests, views, data, or
* models.
*
* @package Faster-Framework-API
*/
class Faster_Core {

	/**
	* Gets mapped to app/config.php file, where we store an array of settings.
	*/
	public $config;
	
	/**
	* Private variable to store the page load start time
	*/
	private $_page_load_start_time;

	/**
	* Load our configuration file, app/config.php, so that the $config public variable is accessible with
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
	* @return array An array of settings from app/config.php
	*/
	public function getConfig() {
		$F = $this->base();
		return include $F . '/app/config.php';
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
			$sPath = str_replace('index.php','',$_SERVER['SCRIPT_NAME']);
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
	* Returns our max session lifetime in seconds.
	*
	* @return int The max session lifetime in seconds
	*/
	public function sessiontimeout() {
		static $nSecs;
		if (!isset($nSecs)) {
			$nSecs = ini_get('session.gc_maxlifetime');
		}
		return $nSecs;
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

} // end class Faster_Core

/**
* This class handles our view details.
*
* @package Faster-Framework-API
*/
class Faster_View {

	
	/**
	* Gets mapped to Faster_Core so that this functionality is exposed to the View.
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
	* encoding on the variable either through htmlentities() or urlencode(), or not at all. The
	* default is ENCODE_DEFAULT (unaltered)
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
	* Injects a variable into a view. The view can then access it via $this->VARIABLE.
	*
	* Note that View variables must be in UPPERCASE or an error is triggered.
	* Note the difference with setVar() is that setVarH() assumes default ENCODE_HTML
	* 
	* @param string $sKey The key we want to set for our variable.
	* @param string $sVal The value we want to store.
	*/	
	public function setVarH($sKey, $sVal){
		$sUpper = strtoupper($sKey);
		if ($sUpper != $sKey) {
			trigger_error('View variables must be in uppercase',E_USER_ERROR);
		}
		$sVal = htmlentities($sVal);
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
	* Note all variables use underscores so as not to pass them to the view file. This is
	* just a precautionary failsafe. We do an unset() on all variables we can before the
	* require_once() call.
	*
	* Note that the view path naturally mimics the controller path if you leave $_sFile empty.
	*
	* Note the alternative syntax for the Action parameter of the path. For instance if you have
	* /about for the URL, then this can map to either About/vDefault.php --OR-- About/vAbout.php.
	* This helps in text editors by limiting the confusion with all the vDefault.php files.
	* 
	* @param string $_sFile A specified file path to the view. If not specified, it will assume the
	* same path as the controller path, but with "v" instead of "c" on the final file prefix.
	* @param bool $_bDrawImmediately Defines whether to cache the view until all elements are drawn
	* to the browser, and then show it, or show it as it is built.
	*/
	public function render($_sFile = '', $_bDrawImmediately = FALSE) {
		$_F = $this->core->base();	
		if (empty($_sFile)) {
			$_sFile = $this->_request->getGroup() . '/' . $this->_request->getAction();
		}
		$_sFile = strrev($_sFile);
		$_sFile = str_replace('/','~',$_sFile);
		$_sFile = preg_replace('/~/','v~',$_sFile,1);
		$_sFile = str_replace('~','/',$_sFile);
		$_sFile = strrev($_sFile);
		if (!$_bDrawImmediately) {
			ob_start();
		}
		if (!file_exists($_F . '/app/_views')) {
			trigger_error('Your folder layout is missing a app/_views folder',E_USER_ERROR);
		}
		$_sPath = $_F . '/app/_views/' . $_sFile . '.php';
		if (!file_exists($_sPath)) {
			trigger_error('Your folder layout is missing a "' . $_sPath . '" views file',E_USER_ERROR);
		}
		if (file_exists($_sPath)) {
			unset($_F);
			unset($_sFile);
			require_once($_sPath);
		} else {
			$_sFile = $this->_request->getGroup() . '/' . $this->_request->getGroup();
			$_sFile = strrev($_sFile);
			$_sFile = str_replace('/','~',$_sFile);
			$_sFile = preg_replace('/~/','v~',$_sFile,1);
			$_sFile = str_replace('~','/',$_sFile);
			$_sFile = strrev($_sFile);
			$_sPath = $_F . '/app/_views/' . $_sFile . '.php';
			unset($_F);
			unset($_sFile);
			require_once($_sPath);
		}
		if (!$_bDrawImmediately) {
			$_sOut = ob_get_contents();
			ob_end_clean();
			return $_sOut;
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

} // end class Faster_View

/**
* A simplistic data class that helps us with PDO with MySQL or SQLite. This class is intentionally
* small and simple.
*
* @package Faster-Framework-API
*/
class Faster_Data {

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
			$DSN = sprintf('mysql:dbname=%s;host=%s;port=%s', $this->_core->config['DB_DATABASE'], $this->_core->config['DB_SERVER'], $sPort);
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
				$PDO->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
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
	* WARNING: This only provides numbers up to 47,775,744 (~ 47 million). If you need more than this,
	* then create your own function. (BTW, 9x9x9x16x16x16x16 = 47,775,744.)
	*
	* The SQL for creating this table in MySQL would be:
	*
	* CREATE TABLE IF NOT EXISTS `ids` (
	* `id` char(8) COLLATE utf8_unicode_ci NOT NULL,
	* `group` varchar(80) COLLATE utf8_unicode_ci NOT NULL,
	* `dt_created` datetime NOT NULL,
	* PRIMARY KEY (`id`,`group`)
	* ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
	*
	* ...and for SQLite would be:
	*
	* CREATE TABLE IF NOT EXISTS `ids` (
	* `id` TEXT PRIMARY KEY,
	* `group` TEXT NOT NULL,
	* `dt_created` TEXT NOT NULL
	* );
	*
	* @param object $PDO The current PDO object
	* @param string $sTable The table by which we need this new unique ID value.
	* @return string A unique ID for our record. It is in the format 999-ABCD and is fixed at 8
	* characters. The dash helps us identify the record faster, visually, if viewing the records
	* in a table.
	*/
	public function getNewID($PDO, $sTable, $nSize = 8) {
		$sKey = mt_rand(111,999) . '-';
		$sKey .= dechex(mt_rand(1111111111,9999999999));
		$sKey = substr($sKey, 0, $nSize); // failsafe
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
			trigger_error("The SQLSTATE ($SQLSTATE) error code happened in getNewID() of the Faster_Data class.", E_USER_ERROR);
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

} // end class Faster_Data


