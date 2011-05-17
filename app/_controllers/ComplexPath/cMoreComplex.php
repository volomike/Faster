<?php defined('FRAMEWORK_LOADED') or die('No direct script access.'); ?>
<?
/**
* A sample, longer path. Visit the site with...
*
* http://{site path}/complex-path/more-complex/
*
* ...and it will come to this controller properly.
*
* Note that if you try http://{site path}/complex-path alone, it thinks you want to pass 
* "complex-path" to the root URL, so you end up seeing the homepage. But add cDefault.php to this
* folder, and it will send you there. That's how you can do an override of that.
* 
* @package User-Sample-Application
*/
die('You have arrived at the proper controller for that path.');

