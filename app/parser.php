<?php
namespace Parser;

include '/Users/VyHuynh/Sites/MenuDrive/vendor/parsecsv/php-parsecsv/parsecsv.lib.php';

// include dirname('/vendor/autoload.php');

class Parser
{

    private $csv_file       = 'csv-format.csv';
    private $modifiers_file = 'modifiers.csv';

    private $parseCSV;
    private $parseCSV_mod;

    private $dbo;

    private $csv_cols;
    public $csv_data;
    public $csv_mod;

    private $inserted_group = array(); // used for both menu groups and modifier groups
    private $inserted_cat   = array();
    private $inserted_item  = array();
    private $inserted_size  = array();

    private $group_id = array();
    private $cat_id   = array();
    private $item_id   = array();
    private $size_id   = array();

    private $modgroup_id = array();

    public function __construct()
    {
        $this->parseCSV     = new \parseCSV($this->csv_file);
        $this->parseCSV_mod = new \parseCSV($this->modifiers_file);

        $this->csv_cols = array('Group', 'Category', 'Item');
    }

    public function createResultArrays()
    {
        foreach ($this->csv_cols as $value) {
            $this->csv_processed[$value] = array();
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
     * Parse CSV out of CSV files and store raw CSV data in class field $csv_data
     * @return [void]
     */
    public function getCSVData()
    {
        $this->csv_data = $this->parseCSV->data;
        $this->csv_mod  = $this->parseCSV_mod->data;
    }

    public function run()
    {
        $this->createResultArrays();
        $this->getCSVData();
        $this->connectDtb();

        // $this->insertMenu();
        $this->insertMods();
    }

    public function insertMods()
    {
        $count = count($this->csv_mod);

        for ($i = 0; $i < $count; $i++) {
            $row = $this->csv_mod[$i];

            // insert mod groups
            if (!in_array($row['Name'], $this->inserted_group)) {


                $stmt = $this->dbo->prepare(
                    "INSERT INTO `cs_toppinggroup`
                        (
                        `toppinggroupname`,
                        `mintop`,
                        `maxtop`,
                        `group_type`
                        )
                    VALUES 
                        (
                        :name,
                        :min,
                        :max,
                        :type
                        )
                ");

                $name = $row['Name'];
                $min = $row['Min'];
                $max = $row['Max'];
                $type = $row['Type'];

                $stmt->bindParam(':name', $name);
                $stmt->bindParam(':min', $min);
                $stmt->bindParam(':max', $max);
                $stmt->bindParam(':type', $type);

                $stmt->execute();
                $this->inserted_group[] = $name;
                $this->modgroup_id[$name] = $this->dbo->lastInsertId();
            }

            // insert mod items
            
            $query = 
            "INSERT INTO `cs_toppingitems`
                (
                `toppingroupid`,
                `toppingitemname`,
                `toppingitemprice`,
                `left_price`,
                `whole_price`,
                `right_price`
                )
            VALUES 
                (
                :groupid,
                :name,
                :price,
                :left,
                :whole,
                :right
                )
            ";

            $stmt = $this->dbo->prepare($query);

            $groupid = $this->modgroup_id[$row['Name']];
            $name = $row['Item'];
            $price = $row['Price'];


            $stmt->bindParam(':groupid', $groupid);
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':price', $price);
            $stmt->bindParam(':left', $left);
            $stmt->bindParam(':right', $right);  
            $stmt->bindParam(':whole', $whole); 

            $stmt->execute();
        }
    }

    /**
     * Insert data into database using raw csv data, row by row
     * @return [void]
     */
    public function insertMenu()
    {
        $count = count($this->csv_data);

        for ($i = 0; $i < $count; $i++) {
            $row = $this->csv_data[$i]; // array of values for each csv data row

            // insert menu group
            $value = 'Group';
            if (!in_array($row[$value], $this->inserted_group)) {

                $stmt = $this->dbo->prepare(
                    "INSERT INTO `cs_menugroup`
                        (`menugroupname`)
                    VALUES (:name)"
                );
                $stmt->bindParam(':name', $row[$value]);

                $stmt->execute();

                $this->inserted_group[] = $row[$value];

                $this->group_id[$row[$value]] = $this->dbo->lastInsertId();

            }

            // insert menu category
            $value = 'Category';
            if (!in_array($row[$value], $this->inserted_cat)) {

                $stmt = $this->dbo->prepare(
                    "INSERT INTO `cs_menucategory`
                        (
                        `menugroupid`,
                        `categoryname`
                        )
                    VALUES
                        (
                        :groupid,
                        :name)"
                );
                $stmt->bindParam(':name', $row[$value]);
                $id = $this->group_id[$row['Group']];
                $stmt->bindParam(':groupid', $id);

                $stmt->execute();

                $this->inserted_cat[]       = $row[$value];
                $this->cat_id[$row[$value]] = $this->dbo->lastInsertId();

            }

            // insert menu item
            $value = 'Item';
            if (!in_array($row[$value], $this->inserted_item)) {
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
                $stmt->bindParam(':itemname', $row[$value]);
                $catid = $this->cat_id[$row['Category']];
                $stmt->bindParam(':catid', $catid);

                $stmt->execute();

                $this->inserted_item[]       = $row[$value];
                $this->item_id[$row[$value]] = $this->dbo->lastInsertId();
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
