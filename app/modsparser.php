<?php
namespace Parser;

include '/Users/VyHuynh/Sites/MenuDrive/vendor/parsecsv/php-parsecsv/parsecsv.lib.php';

class ModsParser
{

    private $parseCSV;

    private $dbo;

    private $location_id = 842;

    public $errors  = array();
    public $errors_code = array();
    public $errors_msg  = array(
        1 => 'Empty Data',
        2 => 'Empty Important Values',
    );
    public $csv_data;

    private $inserted_group = array(); 
    private $inserted_item  = array();

    private $group_id    = array();
    private $item_id     = array();

    public function __construct($file)
    {
    	if (is_file($file)) {
            $this->parseCSV = new \parseCSV($file);
            $this->run();
        } else {
            print_r("Unable to open file.");
        }
    }

    public function getCSVData()
    {
        $this->csv_data = $this->parseCSV->data;
    }

    public function run()
    {
        $this->getCSVData();
        $this->connectDtb();

        if ($this->validate()) {
            $this->insert();
            print_r("Successfully inserted modifiers. <br/>");
        } else {
            $this->printErrors();
        }
    }

    public function connectDtb()
    {
        // connect to dtb
        try
        {
            $this->dbo = new \PDO("mysql:host=localhost;dbname=menudrive", "root", "vy");
        } catch (PDOException $e) {
            $e->getMessage();
        }
    }

    public function validate()
    {
        $valid  = true;
        $data   = $this->csv_data;
        $errors = $this->errors;

        // remove empty rows and check for empty values in: [mod] group, item
        $numRows   = count($data);
        $emptyRows = 0;
        foreach ($data as $index => $row) {
            $emptyElems  = 0;
            $temp_errors = array();
            foreach ($row as $key => $elem) {
                if (empty($elem)) {
                    $emptyElems++;
                    if ($key == 'Group' || $key == 'Item') {
                        $temp_errors[] = $key;
                    }
                }
            }

            if ($emptyElems == count($row)) {
                $emptyRows++;
                unset($data[$index]);
            } elseif ($emptyElems > 0 && count($temp_errors)) {
                $valid               = false;
                $this->errors_code[] = 2;
                $errors[$index]      = $temp_errors;
            }
        }

        if ($emptyRows == count($row)) {
            $valid               = false;
            $this->errors_code[] = 1;
        }

        $data = array_values($data);

        $this->csv_data    = $data;
        $this->errors = $errors;

        return $valid;

    }

    public function printErrors()
    {
        print_r("For Modifier CSV File: <br/>");
        foreach ($this->errors as $key => $value) {
            foreach ($value as $elem) {
                print_r("Empty value " . $elem . " for row " . ($key + 1) . ". <br/>");
            }
        }
    }

    public function insert()
    {
        $count        = count($this->csv_data);
        $topping_item = array();

        for ($i = 0; $i < $count; $i++) {
            $row   = $this->csv_data[$i];
            $name  = $row['Group'];
            $min   = $row['Min'];
            $max   = $row['Max'];
            $type  = $row['Type'];
            $item  = $row['Item'];
            $price = $row['Price'];
            $left  = $row['1st'];
            $whole = $row['2nd'];
            $right = $row['3rd'];

            $topping_item[$name] = $type;
            // format group type
            $type = strtolower($type);

            if ($type == "custom") {
                $type = "custom_topping";
            } elseif ($type == "pizza") {
                $type = "half_topping";
            } elseif ($type == "dropdown") {
                $type = "select";
            }

            // insert mod groups
            if (!in_array($name, $this->inserted_group)) {

                $stmt = $this->dbo->prepare(
                    "INSERT INTO `cs_toppinggroup`
                        (
                        `toppinggroupname`,
                        `mintop`,
                        `maxtop`,
                        `group_type`,
                        `locationid`
                        )
                    VALUES
                        (
                        :name,
                        :min,
                        :max,
                        :type,
                        :locationid
                        )
                ");

                $stmt->bindParam(':name', $name);
                $stmt->bindParam(':min', $min);
                $stmt->bindParam(':max', $max);
                $stmt->bindParam(':type', $type);
                $stmt->bindParam(':locationid', $this->location_id);

                $stmt->execute();
                $this->inserted_group[]   = $name;
                $this->group_id[$name] = $this->dbo->lastInsertId();
            }

            // insert mod items
            if (!in_array($item, $this->inserted_item)) {
                $groupid = $this->group_id[$name];
                $query   =
                    "INSERT INTO `cs_toppingitems`
                        (
                        `toppinggroupid`,
                        `toppingitemname`,
                        `toppingitemprice`,
                        `sequence`
                        )
                    VALUES
                        (
                        :groupid,
                        :name,
                        :price,
                        :sequence
                        )
                    ";

                $stmt = $this->dbo->prepare($query);

                $sequence = count($this->inserted_item) + 1;

                $stmt->bindParam(':groupid', $groupid);
                $stmt->bindParam(':name', $item);
                $stmt->bindParam(':price', $price);
                $stmt->bindParam(':sequence', $sequence);

                $stmt->execute();
                $this->inserted_item[] = $item;

            }

            if ($topping_item[$name] == 'Custom' || $topping_item[$name] = 'Pizza') {
                if (is_numeric($left) && is_numeric($whole) && is_numeric($right)) {
                    $query =
                        "UPDATE `cs_toppingitems`
                        SET `left_price` = $left,
                            `whole_price`= $whole,
                            `right_price` = $right
                        WHERE `toppingitemname` = '$item'
                                AND `toppinggroupid` = $groupid
                    ";

                    $stmt = $this->dbo->prepare($query);
                    $stmt->execute();
                }

            }

            if ($topping_item[$name] == 'Custom') {
                $query =
                    "INSERT INTO `cs_custom_topping_labels`
                            (
                            `topping_group_id`,
                            `topping_label`
                            )
                        VALUES
                            (
                            :groupid,
                            :label
                            )
                        ";

                $stmt = $this->dbo->prepare($query);

                $labels = array($left, $whole, $right);
                $stmt->bindParam(':groupid', $groupid);

                foreach ($labels as $value) {
                    $stmt->bindParam(':label', $value);
                    $stmt->execute();
                }

            }

        }
    }
}
