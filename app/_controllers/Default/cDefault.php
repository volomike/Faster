<?php defined('FRAMEWORK_LOADED') or die('No direct script access.'); ?>
<?
/**
* This is the default controller for when nothing is added on the end of the URL.
* 
* Here you may use the $this object to access the request (synonym for "controller"), model, view,
* core, and data objects and their class methods. Typically you would begin with getting values from
* $this->request, then passing things into the models that you load with $this->model, and then set
* vars with $this->view->setVar() or $this->view->setVars(), followed by optionally displaying that
* content with $this->view->display(). Note if a file path is specified in display, it will use
* that. Otherwise, it will assume almost the same path as the controller.
*
* Note that controller files must be in a folder and must begin with the lowercase letter "c". This
* is for two reasons: (1) so we don't confuse them with a parameter, and (2) so when we have a text
* editor open with multiple tabs, we can easily delineate our controllers files.
* 
* Note that models do not need a file prefix like "c". These are not added there because most coders
* will be "living" in the models area of a project, and therefore don't need to type this all the
* time. Besides, controllers and views use a prefix.
*
* Note that view files must be in a folder and must begin with the lowercase letter "v". This helps
* us delineate these files in a tabbed text editor so as not to be confused with other files.
*
* So, somme sample urls could be mapped with routes like so:
* 
* http://example.com/this-is-my-article = application/controllers/Defalt/cDefault.php
* http://example.com/articles/this-is-my-article = application/controllers/Articles/cDefault.php
* http://example.com/my-tickets/add-one = application/controllers/MyTickets/cAddOne.php
* 
* @package User-Sample-Application
*/

// TURN OFF ACCEPTING URL PARAMETERS ON THIS PAGE
// REDIRECT TO A 404 INSTEAD.
// uncomment the following line to enable it
// $this->request->blockDefaultURLParams();

$asParams = $this->request->getParams();
$sParam1 = @ $asParams[0];

$test = $this->model->load('Sample/Test');
$sResult = $test->tester($sParam1);

$this->view->setVar('RESULT',$sResult);
$this->view->display();





