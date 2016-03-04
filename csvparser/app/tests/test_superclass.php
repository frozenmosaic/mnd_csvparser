<?php 

class Par {

	private $a;
	public function __construct($a) {
		$this->a = $a;

		$this->run();
	}

	public function run() {
		$this->a += 1;

		print_r($this->a);
	}
}