<?php
namespace Parser;

include '/Users/VyHuynh/Sites/MenuDrive/vendor/parsecsv/php-parsecsv/parsecsv.lib.php';

/**
 * Parent class for CSV Parser
 * A class to parse data in CSV format, validate data and insert data into database
 * @author Vy Huynh
 * @date Feb 25, 2016
 */
class CSVParser
{
    protected $type;

    protected $parseCSV;

    protected $dbo;
    protected $host     = "localhost";
    protected $dbname   = "menudrive";
    protected $user     = "root";
    protected $password = "vy";

    protected $location_id = 842;

    protected $allowed_topping_types = array(
        'radio',
        'checkbox',
        'dropdown',
        'quantity',
        'pizza',
        'custom',
    );
    public $errors      = array(); // format row index => value containing errors
    public $errors_code = array(); // record any occurances of errors
    public $errors_msg  = array(
        1 => 'Empty File',
        2 => 'Missing Important Values',
        3 => 'Invalid Topping Type',
    );

    public $csv_data;
    public $csv_mod;

    protected $inserted_group    = array(); // used for both menu groups and modifier groups
    protected $inserted_cat      = array();
    protected $inserted_item     = array();
    protected $inserted_sizename = array();

    protected $group_id = array();
    protected $cat_id   = array();
    protected $item_id  = array();
    protected $size_id  = array();

    public function __construct($file)
    {
        if (is_file($file)) {
            $this->parseCSV = new \parseCSV($file);
            $this->run();
        } else {
            print_r("Unable to open file.");
        }

    }

    /**
     * Execute required steps:
     * (1) get CSV raw data,
     * (2) connect to database,
     * (3) validate CSV data,
     * (4) proceed to insert data if CSV data is valid
     * (5) print out errors if any, or success message
     * @return [type] [description]
     */
    public function run()
    {
        $this->getCSVData();
        $this->connectDtb();
        if ($this->validate()) {
            $this->insert();
            print_r("Successfully inserted data. <br/>");
        } else {
            $this->printErrors();
        }

    }

    /**
     * Connect to database using information provided in class fields
     * @return [type] [description]
     */
    public function connectDtb()
    {
        // connect to dtb
        try
        {
            $connStr   = "mysql:host=" . $this->host . ";dbname=" . $this->dbname;
            $this->dbo = new \PDO($connStr, $this->user, $this->password);
        } catch (PDOException $e) {
            $e->getMessage();
        }

    }

    /**
     * Parse CSV out of CSV files and
     * store raw CSV data in class field $csv_data
     * @return [void]
     */
    public function getCSVData()
    {
        $this->csv_data = $this->parseCSV->data;
    }

    /**
     * Validate CSV Data by checking for: (1) empty file, and (2) missing important values
     * @return [type] [description]
     */
    public function validate()
    {
        $valid  = true;
        $data   = $this->csv_data;
        $errors = $this->errors;

        // remove empty rows and check for empty values in: [menu] group, category, item
        $numRows = count($data);
        if (!empty($data)) {
            foreach ($data as $rowIndex => $row) {
                $emptyElems = 0;
                $row_errors = array();
                foreach ($row as $key => $elem) {
                    trim($elem);

                    if (empty($elem)) {
                        $emptyElems++;
                        if ($key == 'Group') {
                            $row_errors[2][] = $key;
                        }
                        if ($key == 'Category') {
                            $row_errors[2][] = $key;
                        }
                        if ($key == 'Item') {
                            $row_errors[2][] = $key;
                        }
                    }

                    if ($key == 'Type') {
                        $elem = strtolower($elem);
                        if (!empty($elem)) {
                            if (!in_array($elem, $this->allowed_topping_types)) {
                                $row_errors[3]       = $key;
                                $this->errors_code[] = 3;
                            }

                        }
                    }
                }

                if ($emptyElems == count($row)) {
                    // remove empty rows
                    unset($data[$rowIndex]);
                } elseif ($emptyElems > 0 && count($row_errors)) {
                    $valid = false;
                    if (!in_array(2, $this->errors_code)) {
                        $this->errors_code[] = 2;
                    }
                    $errors[$rowIndex] = $row_errors;
                }
            }

            $data = array_values($data);
        } else {
            $valid               = false;
            $this->errors_code[] = 1;
        }

        $this->csv_data = $data;
        $this->errors   = $errors;

        return $valid;
    }

/**
 * Print out errors, if there exist any
 * @return [type] [description]
 */
    public function printErrors()
    {
        if (!empty($this->errors_code)) {
            foreach ($this->errors as $rowIndex => $rowErrorsArray) {
                foreach ($rowErrorsArray as $code => $invalidValue) {
                    print_r($this->errors_msg[$code] . ": <br/>");

                    if ($code == 2) {
                        foreach ($invalidValue as $missingValue) {
                            print_r("Empty value " . $missingValue . " for row " . ($rowIndex + 1) . ". <br/>");
                        }
                    }

                    if ($code == 3) {
                        print_r("Invalid topping type " . $invalidValue . " for row " . ($rowIndex + 1) . ". <br/>");
                    }

                }
            }

            print_r("Data not imported.");
        }

    }

/**
 * Insert data into database using raw csv data, row by row
 * @return [void]
 */
    public function insert()
    {}
}
