<?php
use Parser\Parser;

// include '/Users/VyHuynh/Desktop/MenuDrive/vendor/parsecsv/php-parsecsv/parsecsv.lib.php';

class Test extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
    }

    protected function tearDown()
    {
    }

    // tests
    public function test() {
        $parser = new Parser();

        $parser->run();
    }
}
