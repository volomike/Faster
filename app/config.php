<?

/**
* This file is an array that defines the static parameters used by this site.
* 
* You need to have at least the parameters ERROR_REPORTING, DISPLAY_ERRORS, COOKIE_PREFIX, and TIMEZONE.
* If you want to connect to a database, then you need DB_SERVER, DB_DATABASE, DB_USER, and DB_PASS.
* If connecting with SQLite, then you only need DB_DATABASE.
* 
* @package User-Sample-Application
* @param int ERROR_REPORTING The error level you wish to use for the project.
* @param string DISPLAY_ERRORS Whether you want to display errors to a log (Off) or to the screen or browser (On).
* @param string COOKIE_PREFIX A key that gets used (followed by underscore) before all your cookie labels.
* @param string TIMEZONE Your timezone for the webserver, such as "America/New_York", or use "UTC".
* @param string DB_SERVER The database hostname.
* @param string DB_DATABASE The database name.
* @param string DB_USER The user account for the database.
* @param string DB_PASS The password for the user account for the database.
* @param string DB_PORT The database port.
* @return array Returns an array of configuration values.
*/
return array(
	'ERROR_REPORTING' => E_ALL,
	'DISPLAY_ERRORS' => 'Off',
	'COOKIE_PREFIX' => 'mvc',
	'TIMEZONE' => 'America/New_York',
	'DB_SERVER' => 'localhost',
	'DB_DATABASE' => 'app/database/test.sqlite',
	//'DB_DATABASE' => 'test',
	//'DB_PORT' => 3306, 
	'DB_USER' => 'testuser',
	'DB_PASS' => 'testpass'
);

