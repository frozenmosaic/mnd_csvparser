<?php
namespace Parser;

include '/Users/VyHuynh/Desktop/MenuDrive/vendor/parsecsv/php-parsecsv/parsecsv.lib.php';

// include dirname('/vendor/autoload.php');

class Parser {

    public $csvfile_name = 'csv-format.csv';
    public $parseCSV;

    public $dbo;

    public $csv_cols;
    public $csv_data;
    public $result;

    public $table_group = 'cs_menugroup';

    public function __construct() {
        $this->parseCSV = new \parseCSV($this->csvfile_name);

        $this->csv_cols = array('Group', 'Category', 'Item');
    }

    public function run() {
        $this->createResultArrays();
        $this->getCSVData();

        $this->connectDtb();

        $this->processData();
        $this->insertCategories();
    }

    public function createResultArrays() {
         foreach ($this->csv_cols as $value) {
            $this->result[$value] = array();
        }
    }

    public function connectDtb() {
        // connect to dtb
        try {
        $this->dbo = new \PDO("mysql:host=localhost;dbname=menudrive", "root", "vy");            
        } 
        catch (PDOException $e) {
            $e->getMessage();
        }

    }

    /**
     * Parse CSV out of CSV files and store raw CSV data in class field $csv_data
     * @return [void] 
     */
    public function getCSVData() {
        $this->csv_data = $this->parseCSV->data;
        // print_r($this->csv_data);
    }

    /**
    * Processing CSV data and store data in field $result
    * Assuming that CSV data will have format Group - Category - Item
    * @return [void]       
    */
    public function processData() {
        $count = count($this->csv_data); // number of lines
        
        // loop through each row
        for ($i = 0; $i < $count; $i++) {
            $row = $this->csv_data[$i]; // array of values for each row

            foreach ($this->csv_cols as $value) {
                if (!(empty($row[$value]))) {
                    $arr = $this->result[$value];
                    $arr[] = $row[$value];
                    $this->result[$value] = $arr;
                }

            }
        }
    }

    // public function insertData() {
    //     // $this->insertGroups();
    //     $this->insertCategories();
    // }

    public function insertGroups() {

        // prepare statement
        $stmt = $this->dbo->prepare("INSERT INTO `cs_menugroup` (`menugroupid`, `locationid`, `menugroupname`) VALUES (:menugroupid, :locationid, :menugroupname)");
        // `description`, `sequenceorder`, `status`, `no_of_different_items`, `is_dedicated`, `is_default`, `is_visible`
        $stmt->bindParam(':menugroupid', $menugroupid);
        $stmt->bindParam(':menugroupname', $menugroupname);
        $stmt->bindParam(':locationid', $locationid);
       
        // prepare parameters

        $groups = $this->result['Group'];
        for ($i = 0; $i < count($groups); $i++) {
            $menugroupid = $i+1;
            $locationid = $i+1;
            $menugroupname = $groups[$i];

            // execute statement
            $stmt->execute();
        }
    }

    public function insertCategories() {
        // prepare statement
        
        codecept_debug('running insertCategories');
        $stmt = $this->dbo->prepare("INSERT INTO `cs_menucategory` (`catid`, `menugroupid`, `categoryname`) VALUES (:catid, :menugroupid, :categoryname)");

        // prepare parameters
        $cats = $this->result['Category'];
        for ($i = 0; $i < count($cats); $i++) {
            $params = array();
            $params['menugroupid'] = $i+1;
            $params['catid'] = $i+1;
            $params['categoryname'] = $cats[$i];

            // execute statement
            $stmt->execute($params);
            print_r('inserted one value***');
        }
    }
}
