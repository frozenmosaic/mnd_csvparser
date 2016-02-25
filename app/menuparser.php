<?php
namespace Parser;

include '/Users/VyHuynh/Sites/MenuDrive/vendor/parsecsv/php-parsecsv/parsecsv.lib.php';

class MenuParser
{
    private $type;

    private $parseCSV;
    private $parseCSV_mod;

    private $dbo;

    private $location_id = 842;

    public $menu_errors = array();
    public $mod_errors  = array();
    public $errors_code = array();
    public $errors_msg  = array(
        1 => 'Empty Data',
        2 => 'Empty Important Values',
    );
    public $csv_data;
    public $csv_mod;

    private $inserted_group   = array(); // used for both menu groups and modifier groups
    private $inserted_cat     = array();
    private $inserted_item    = array();
    private $inserted_size    = array();
    private $inserted_moditem = array();

    private $group_id    = array();
    private $cat_id      = array();
    private $item_id     = array();
    private $size_id     = array();
    private $modgroup_id = array();

    public function __construct($file = null)
    {

        $this->parseCSV = new \parseCSV($file);
        $this->run();

    }

    public function run() // parse only menu

    {

        $this->getCSVData();
        $this->connectDtb();
        if ($this->validate()) {
            $this->insert();
            print_r("Successfully inserted menu. <br/>");
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

    /**
     * Parse CSV out of CSV files and
     * store raw CSV data in class field $csv_data
     * @return [void]
     */
    public function getCSVData()
    {
        $this->csv_data = $this->parseCSV->data;
    }

    public function validate()
    {
        $valid  = true;
        $data   = $this->csv_data;
        $errors = $this->menu_errors;

        // remove empty rows and check for empty values in: [menu] group, category, item
        $numRows   = count($data);
        $emptyRows = 0;
        foreach ($data as $index => $row) {
            $emptyElems  = 0;
            $temp_errors = array();
            foreach ($row as $key => $elem) {
                if (empty($elem)) {
                    $emptyElems++;
                    if ($key == 'Group' || $key == 'Category' || $key == 'Item') {
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
        $this->menu_errors = $errors;

        return $valid;
    }

    public function printErrors()
    {
        print_r("For Menu CSV File: <br/>");
        foreach ($this->menu_errors as $key => $value) {
            foreach ($value as $elem) {
                print_r("Empty value " . $elem . " for row " . ($key + 1) . ". <br/>");
            }
        }
    }

    /**
     * Insert data into database using raw csv data, row by row
     * @return [void]
     */
    public function insert()
    {
        $count = count($this->csv_data);

        for ($i = 0; $i < $count; $i++) {
            $row = $this->csv_data[$i]; // array of values for each csv data row

            // insert menu group
            $group = $row['Group'];
            if (!in_array($group, $this->inserted_group)) {

                $stmt = $this->dbo->prepare(
                    "INSERT INTO `cs_menugroup`
                        (
                        `menugroupname`,
                        `locationid`
                        )
                    VALUES (
                        :name,
                        :locationid
                        )"
                );
                $stmt->bindParam(':name', $group);
                $stmt->bindParam(':locationid', $this->location_id);

                $stmt->execute();

                $this->inserted_group[] = $group;

                $this->group_id[$group] = $this->dbo->lastInsertId();

            }

            // insert menu category
            $cat  = $row['Category'];
            $size = $row['Size'];
            if (!in_array($cat, $this->inserted_cat)) {

                $query =
                    "INSERT INTO `cs_menucategory`
                        (
                        `menugroupid`,
                        `categoryname`,
                        `size`
                        )
                    VALUES
                        (
                        :groupid,
                        :name,
                        :size
                        )"
                ;

                $stmt = $this->dbo->prepare($query);
                $stmt->bindParam(':name', $cat);

                $id = $this->group_id[$group];
                $stmt->bindParam(':groupid', $id);
                $stmt->bindParam(':size', $size);

                $stmt->execute();

                $this->inserted_cat[] = $cat;
                $this->cat_id[$cat]   = $this->dbo->lastInsertId();

            }

            // insert menu item
            $item = $row['Item'];
            if (!in_array($item, $this->inserted_item)) {
                $stmt = $this->dbo->prepare(
                    "INSERT INTO `cs_menuitem`
                    (
                    `catid`,
                    `itemname`
                    )
                VALUES
                    (
                    :catid,
                    :itemname
                    )
                ");
                $stmt->bindParam(':itemname', $item);
                $catid = $this->cat_id[$cat];
                $stmt->bindParam(':catid', $catid);

                $stmt->execute();

                $this->inserted_item[] = $item;
                $this->item_id[$item]  = $this->dbo->lastInsertId();
            }

            // insert category size
            $size     = $row['Size'];
            $sizename = !empty($row['Size Names']) ? $row['Size Names'] : 'size1';
            $cat      = $row['Category'];
            $cat_id   = $this->cat_id[$cat];
            $str      = $sizename . $cat;

            if (!in_array($str, $this->inserted_size)) {
                $stmt = $this->dbo->prepare(
                    "INSERT INTO `cs_categorysize`
                                (
                                `categoryid`,
                                `sizename`
                                )
                            VALUES
                                (
                                :catid,
                                :sizename
                                )
                            ");
                $stmt->bindParam(':catid', $cat_id);

                $stmt->bindParam(':sizename', $sizename);

                $stmt->execute();

                $this->inserted_size[] = $str;
                $this->size_id[$str]   = $this->dbo->lastInsertId();
            }

            // insert price
            $query =
                "INSERT INTO `cs_price`
                (
                `itemid`,
                `sizeid`,
                `price`
                )
            VALUES
                (
                :itemid,
                :sizeid,
                :price
                )
            ";

            $item   = $row['Item'];
            $itemid = $this->item_id[$item];

            $size     = $row['Size'];
            $sizename = $row['Size Names'];
            $cat      = $row['Category'];

            if ($size == 1) {
                $str = 'size1' . $cat;
            } elseif (empty($size)) {
                if (empty($sizename)) {
                    $str = 'size1' . $cat;
                } else {
                    $str = $sizename . $cat;
                }
            } elseif ($size > 1) {
                $str = $sizename . $cat;
            }

            $sizeid = $this->size_id[$str];
            $price  = $row['Price'];

            $stmt = $this->dbo->prepare($query);
            $stmt->bindParam(':itemid', $itemid);
            $stmt->bindParam(':sizeid', $sizeid);
            $stmt->bindParam(':price', $price);

            $stmt->execute();
        }

        $this->inserted_group = array();
        $this->inserted_cat   = array();
        $this->inserted_item  = array();
        $this->inserted_size  = array();

    }

}
