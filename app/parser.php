<?php
namespace Parser;

include '/Users/VyHuynh/Sites/MenuDrive/vendor/parsecsv/php-parsecsv/parsecsv.lib.php';

class Parser
{

    private $csv_file       = 'menu.csv';
    private $modifiers_file = 'modifiers.csv';

    private $parseCSV;
    private $parseCSV_mod;

    private $dbo;

    private $location_id = 842;

    private $errors = array();

    public $csv_data;
    public $csv_mod;

    private $inserted_group   = array(); // used for both menu groups and modifier groups
    private $inserted_cat     = array();
    private $inserted_item    = array();
    private $inserted_size    = array();
    private $inserted_moditem = array();

    private $group_id = array();
    private $cat_id   = array();
    private $item_id  = array();
    private $size_id  = array();
    private $modgroup_id = array();

    public function __construct()
    {
        $this->parseCSV     = new \parseCSV($this->csv_file);
        $this->parseCSV_mod = new \parseCSV($this->modifiers_file);

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
        $this->csv_mod  = $this->parseCSV_mod->data;

        // remove empty rows
        foreach ($this->csv_data as $key => $row) {
            $empty = true;

            foreach ($row as $elem) {
                if ($elem != "") {
                    $empty = false;
                }
            }

            if ($empty) {
                unset($this->csv_data[$key]);
            }
        }

        $this->csv_data = array_values($this->csv_data);
    }

    public function validateData($data)
    {
        // unwanted commas
        

        
        // empty names: [menu] group, category, item; [mod] name, item

        print_r("<pre>");
        print_r($data);
        print_r("</pre>");
    }

    public function run()
    {
        $this->getCSVData();
        $this->connectDtb();
        $this->validateData($this->csv_data);
        // if (validateData($this->csv_data)) {
            $this->insertMenu();
            // $this->insertMods();
        // }
    }

    public function insertMods()
    {
        $count        = count($this->csv_mod);
        $topping_item = array();

        for ($i = 0; $i < $count; $i++) {
            $row   = $this->csv_mod[$i];
            $name  = $row['Name'];
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
                $this->modgroup_id[$name] = $this->dbo->lastInsertId();
            }

            // insert mod items
            if (!in_array($item, $this->inserted_moditem)) {
                $groupid = $this->modgroup_id[$name];
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

                $sequence = count($this->inserted_moditem) + 1;

                $stmt->bindParam(':groupid', $groupid);
                $stmt->bindParam(':name', $item);
                $stmt->bindParam(':price', $price);
                $stmt->bindParam(':sequence', $sequence);

                $stmt->execute();
                $this->inserted_moditem[] = $item;

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
            $cat = $row['Category'];
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
                print_r($query);
                $this->inserted_cat[]       = $cat;
                $this->cat_id[$cat] = $this->dbo->lastInsertId();

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

                $this->inserted_item[]       = $item;
                $this->item_id[$item] = $this->dbo->lastInsertId();
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
