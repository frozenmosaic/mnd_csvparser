<?php
require __DIR__ . '/vendor/autoload.php';

class CSVParser
{
	public $data;

    public function __construct()
    {

    }

    public static function process() {
    	$this->getRawData();
    	$this->insertToDtb();
    	$this->insertStatement();
    }

    // get data
    public function getRawData()
    {

        $csv  = new parseCSV('csv-format.csv');
        $data = $csv->data;

        $this->data = $data;
    }

    public function insertToDtb()
    {
        // connect to dtb
        try {
            $dbo = new PDO('mysql:host=localhost;dbname=test', "root", "");
        } catch (PDOException $e) {
            print_r($e->getMessage());
        }

    }

    private function insertStatement()
    {
        // prepare statement
        $stmt = $dbo->prepare("INSERT INTO `menugroup`(`id`, `name`) VALUES (:id,:name)");
        // $stmt->bindParam(':menugroupid', $menugroupid);
        // $stmt->bindParam(':menugroupname', $menugroupname);
        // $stmt->bindParam(':locationid', $locationid);
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':name', $name);

        // prepare parameters
        $id = 1;
        $name = $this->data[0]['group'];

        // execute statement
        $stmt->execute();
        print_r('inserted');
    }

    private function processData($data)
    {

    }

}
