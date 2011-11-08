<?php defined('FRAMEWORK_LOADED') or die('No direct script access.'); ?>
<?php 
/**
* A sample view template
* Note usage of $this-> parameters on this page.
* 
* @package User-Sample-Application
*/

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd"> 
<html xmlns="http://www.w3.org/1999/xhtml"> 
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" /> 
<meta http-equiv="X-UA-Compatible" content="IE=7" /> 
<title>Faster</title>
<style type="text/css">
HTML,BODY {
margin:0;
padding:0;
font-family:'Liberation Sans', Helvetica, Arial, sans, sans-serif;
font-size:13px;
}
HTML {
background:#00638d;
}
#overlay {
background: 
-webkit-radial-gradient(rgba(127, 127, 127, 0.3), rgba(127, 127, 127, 0.3) 35%, rgba(0, 0, 0, 0.4));
background:
-moz-radial-gradient(rgba(127, 127, 127, 0.3), rgba(127, 127, 127, 0.3) 35%, rgba(0, 0, 0, 0.4));
overflow:hidden;
-o-radial-gradient(rgba(127, 127, 127, 0.3), rgba(127, 127, 127, 0.3) 35%, rgba(0, 0, 0, 0.4));
overflow:hidden;
}
#content {
margin:20px auto;
width:960px;
background:#eaf4f8;
background-color:#EEE;
-webkit-border-radius:6px;
-moz-border-radius:6px;
border-radius:6px;
box-shadow:1px 1px 6px rgba(0,0,0,0.6);
-moz-box-shadow:1px 1px 6px rgba(0,0,0,0.6);
-webkit-box-shadow:1px 1px 6px rgba(0,0,0,0.6);
min-height:1000px;
height:auto !important;
height:1000px;
}
#header {
overflow:hidden;
border-radius:5px 5px 0 0;
-moz-border-radius:5px 5px 0 0;
-webkit-border-radius:5px 5px 0 0;
background:#111;
color:#FFF;
font-size:22px;
font-weight:bold;
padding:5px;
}
#content P {
padding-left:20px;
padding-right:20px;
}
#footer {
padding-top:20px;
padding-bottom:20px;
font-size:11px;
color:#AAA;
text-align:center;
position:absolute;
bottom:-360px;
margin-left:40px;
}
</style>
</head>
<body>
<div id="overlay">
	<div id="content">

		<div id="header">
		Faster -- A Minimalist PHP MVC Framework v.1.0367
		</div><!-- #header -->

		<p>&nbsp;</p>

		<p><a target="_blank" href="_docs/api-viewer/">API Viewer</a></p>

		<p>&nbsp;</p>

		<p>Short Framework Test:</p>
		<p>RESULT = <?= $this->RESULT ?></p>

		<? if (!$this->RESULT): ?>
		<p>Try adding /Mike on the end of the URL so that you pass a parameter to the database.</p>
		<? endif; ?>

		<p>BASE PATH = <?= $this->core->base() ?></p>
		<p>BASE URL = <?= $this->core->baseurl() ?></p>
		<p>PAGE LOADING TIME = <?= $this->core->page_load_time() ?> seconds</p>

		<div id="footer">
		USA Copyright &copy; 2011, Volo, LLC (http://volosites.com/) All rights reserved. BSD License.
		</div><!-- #footer -->

	</div><!-- #content -->
</div><!-- #overlay -->
</body>
</html>

