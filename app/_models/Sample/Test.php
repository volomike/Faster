<?php defined('FRAMEWORK_LOADED') or die('No direct script access.'); ?>
<?php
/**
* A sample model class.
*
* Note that this class extends Faster so that the $this parameter gets populated with our core, request,
* model, view, and data objects.
*
* @package User-Sample-Application
*/

/**
* Test class, which extends Faster. This is typically how you would build a model class in this framework.
*
* @package User-Sample-Application
*/
class Test extends Faster {

/**
* A sample class method
* 
* @param string $sParam1 Something to query the names table's first names by.
* @return string The first matching name.
*/
public function tester($sParam1){
	$PDO = $this->data->sqlite();
	//$PDO = $this->data->mysql();
	$sSQL = "select * from names where first_name = '$sParam1'";
	$st = $PDO->prepare($sSQL);
	$st->execute();
	$rsRows = $st->fetchAll();
	foreach($rsRows as $rwRow){break;}
	$sFirstName = @ $rwRow['first_name'];
	$sLastName = @ $rwRow['last_name'];
	$sResult = "$sFirstName $sLastName";
	$sResult = trim($sResult);
	if (empty($sResult)) {
		$sResult = FALSE;
	}
	return $sResult;
}

} // end class


