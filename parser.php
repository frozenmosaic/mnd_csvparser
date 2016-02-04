<?php
// namespace Parser;

require __DIR__ . '/vendor/autoload.php';

class Parser {

    public $csv_cols;
    public $csv_data;

    public $result;

    public function __construct() {
        $this->csv_cols = array('Group', 'Category', 'Item');

        foreach ($this->csv_cols as $value) {
            $this->result[$value] = array();
        }
    }

    public function getCSVData() {
        $csv  = new parseCSV('csv-format.csv');
        $this->csv_data = $csv->data;
        print_r($this->csv_data);
    }

    /**
    * Processing CSV data
    * Assuming that CSV data will have format Group - Category - Item
    * @param  [type] $data [array of data from CSV file]
    * @return [type]       [description]
    */
    public function processData() {
        $count = count($this->csv_data); // number of lines
        
        // loop through each row
        for ($i = 0; $i < $count; $i++) {
            $row = $this->csv_data[$i]; // array of values for each row

            foreach ($this->csv_cols as $value) {
                print_r($row[$value]);
                if (!empty($row[$value])) {
                    $arr = $this->result[$value];
                    print_r($row[$value]);
                    print_r($arr);
                    $arr[] = 'abc';
                }

            }
        }
    }

    public static function test() {
        $parser = new Parser();
        $parser->getCSVData();
        $parser->processData();
        print_r($parser->result);
    }

    public function process() {
        // connect to dtb
        $dbo = new PDO("mysql:host=localhost;dbname=menudrive", "root", "vy");
        
        // prepare statement
        $stmt = $dbo->prepare("INSERT INTO `cs_menugroup`(`menugroupid`, `locationid`, `menugroupname`) VALUES (:menugroupid, :locationid, :menugroupname)");
        // `description`, `sequenceorder`, `status`, `no_of_different_items`, `is_dedicated`, `is_default`, `is_visible`
        $stmt->bindParam(':menugroupid', $menugroupid);
        $stmt->bindParam(':menugroupname', $menugroupname);
        $stmt->bindParam(':locationid', $locationid);
       
        // prepare parameters
        $menugroupid = 1;
        $menugroupname = $data[0]['Group'];
        $locationid = 1;
        
        // execute statement
        // $stmt->execute();
    }
}

// $parser = new Parser();
// $parser->test();