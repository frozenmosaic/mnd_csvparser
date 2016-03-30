<?php 
include 'test_superclass.php';

class B extends Par {

	public function __construct($a) {
		parent::__construct($a);
	}
}