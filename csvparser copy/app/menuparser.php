<?php
namespace Parser;

include 'csvparser.php';

/**
 * Subclass of CSV Parser for parsing menu CSV data
 * @author Vy Huynh
 */
class MenuParser extends CSVParser
{

    public function __construct($file)
    {
        parent::__construct($file);
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
     * Check for existing Menu Groups in database
     * @param  [type] $group name of menu group to check
     * @return boolean        return true if there is a duplicate in database,
     *                               false if $group is unique menu group
     */
    public function duplicateGroup($group)
    {
        $query =
        "SELECT *
            FROM  cs_menugroup
            WHERE locationid = '" . $this->location_id .
            "' AND menugroupname = " . $this->dbo->quote($group);
        $stmt = $this->dbo->query($query);
        $res  = $stmt->fetchAll();

        if (count($res) > 0) {
            // should have only one duplicate
            return $res[0]['menugroupid'];
        } else {
            return -1;
        }
    }

    public function duplicateCat($cat, $group_id)
    {
        $query =
            "SELECT *
            FROM  cs_menucategory
            WHERE menugroupid = '" . $group_id .
            "' AND categoryname = " . $this->dbo->quote($cat);
        $stmt = $this->dbo->query($query);
        $res  = $stmt->fetchAll();
        // print_r("<pre>" . $query . "</pre>");

        if (count($res) > 0) {
            // should have only one duplicate
            return $res[0]['catid'];
        } else {
            return -1;
        }
    }

    public function duplicateItem($item, $cat_id)
    {
        $query =
            "SELECT *
            FROM  cs_menuitem
            WHERE catid = '" . $cat_id .
            "' AND itemname = " . $this->dbo->quote($item);
        $stmt = $this->dbo->query($query);
        $res  = $stmt->fetchAll();

        if (count($res) > 0) {
            // should have only one duplicate
            return $res[0]['menuitemid'];
        } else {
            return -1;
        }
    }

    public function duplicateSizeName($sizename, $cat_id)
    {
        $query =
            "SELECT *
            FROM  cs_categorysize
            WHERE sizename = " . $this->dbo->quote($sizename) .
            " AND categoryid = " . $cat_id;
        $stmt = $this->dbo->query($query);
        $res  = $stmt->fetchAll();

        if (count($res) == 1) {
            // should have only one duplicate
            return $res[0]['sizeid'];
        } else {
            return -1;
        }
    }

    public function duplicatePrice($item_id, $size_id)
    {
        $query =
            "SELECT *
            FROM  cs_price
            WHERE itemid = '" . $item_id .
            "' AND sizeid = " . $size_id;
        $stmt = $this->dbo->query($query);
        $res  = $stmt->fetchAll();
        if (count($res) == 1) {
            // should have only one duplicate
            return $res[0]['priceid'];
        } else {
            return -1;
        }
    }

    /**
     * Insert data into database using raw csv data, row by row
     * @return [void]
     */
    public function insert()
    {
        $count = count($this->csv_data);
// print_r("<pre>");
//         print_r($this->csv_data);
//         print_r("</pre>");
        $size_counter = array(); // format: category => size number

        for ($i = 0; $i < $count; $i++) {
            $row = $this->csv_data[$i]; // array of values for each csv data row

            // insert menu group
            $group    = $row['Group'];
            $item     = $row['Item'];
            $sizename = !empty($row['Size Names']) ? $row['Size Names'] : 'size1';
            $cat   = $row['Category'];
            $price = $row['Price'];


            $gate_check_group = $this->duplicateGroup($group);
            if ($gate_check_group == -1) {
                // if group does not already exist in database
                if (!in_array($group, $this->inserted_group)) {
                    // if group has not been imported before in this spreadsheet

                    $stmt = $this->dbo->prepare(
                        "INSERT INTO `cs_menugroup`
                        (
                        `menugroupname`,
                        `locationid`
                        )
                    VALUES (
                    " . $this->dbo->quote($group) . ",
                        :locationid
                        )"
                    );
                    // $stmt->bindParam(':name', $group);
                    $stmt->bindParam(':locationid', $this->location_id);

                    $stmt->execute();

                    // record group in spreasheet that just got inserted
                    $this->inserted_group[] = $group;
                    $this->group_id[$group] = $this->dbo->lastInsertId();
                }
            } else {
                // group is in database
                // get group id
                $this->group_id[$group] = $gate_check_group;
            }

            // insert menu category
            $gate_check_cat = $this->duplicateCat($cat, $this->group_id[$group]);
            if ($gate_check_cat == -1) {
                // category does not exist in database
                // if (!in_array($cat, $this->inserted_cat)) {
                $query =
                    "INSERT INTO `cs_menucategory`
                        (
                        `menugroupid`,
                        `categoryname`
                        )
                    VALUES
                        (
                        :groupid,
                    " . $this->dbo->quote($cat) . "
                        )"
                ;

                $stmt = $this->dbo->prepare($query);
                // $stmt->bindParam(':name', $cat);

                $id = $this->group_id[$group];
                $stmt->bindParam(':groupid', $id);

                $stmt->execute();

                // track inserted categories
                $this->inserted_cat[] = $cat;

                // record category id
                $this->cat_id[$cat] = $this->dbo->lastInsertId();

                // }

            } else {
                // get id from database instead
                $this->cat_id[$cat] = $gate_check_cat;
            }

            // insert menu item
            $gate_check_item = $this->duplicateItem($item, $this->cat_id[$cat]);
            if ($gate_check_item == -1) {
                // if (!in_array($item, $this->inserted_item)) {
                $stmt = $this->dbo->prepare(
                    "INSERT INTO
                        `cs_menuitem`
                            (
                            `catid`,
                            `itemname`
                            )
                        VALUES
                            (
                            :catid,
                        " . $this->dbo->quote($item) . "
                            )
                        ");
                // $stmt->bindParam(':itemname', $item);
                $catid = $this->cat_id[$cat];
                $stmt->bindParam(':catid', $catid);

                $stmt->execute();

                $this->inserted_item[] = $item;
                $this->item_id[$item]  = $this->dbo->lastInsertId();
                // }
            } else {
                // get id from database
                $this->item_id[$item] = $gate_check_item;
            }
            
            // insert category size names

            // get category id
            $cat_id = $this->cat_id[$cat];
            // construct identifying string for each size name based on candidate keys {cat_id, sizename}
            $sizename_str_id = $cat_id . $sizename;

            // check for duplicate size names in database, candidate keys {sizename, catid}
            $gate_check_sizename = $this->duplicateSizeName($sizename, $cat_id);
            if ($gate_check_sizename == -1) {
                // size name does not yet exist in database

                $stmt = $this->dbo->prepare(
                    "INSERT INTO `cs_categorysize`
                                (
                                `categoryid`,
                                `sizename`
                                )
                            VALUES
                                (
                                :catid,
                            " . $this->dbo->quote($sizename) . "
                                )
                            ");
                $stmt->bindParam(':catid', $cat_id);
                // $stmt->bindParam(':sizename', $sizename);
                $stmt->execute();

                // track inserted size name
                $this->inserted_sizename[] = $sizename_str_id;
                // track id of inserted size name
                $this->size_id[$sizename_str_id] = $this->dbo->lastInsertId();

                // increase size counter (number of sizes unique for each category)
                if ($gate_check_cat != -1) {
                    if (array_key_exists($cat_id, $size_counter)) {
                        $size = $size_counter[$cat_id];
                    } else {
                        $get_cur_size =
                            "SELECT size
                            FROM cs_menucategory
                            WHERE catid = " . $cat_id;
                        $stmt = $this->dbo->query($get_cur_size);
                        $stmt = $stmt->fetchAll();
                        $size = $stmt[0]['size'];
                    }
                } else {
                    $size = 0;
                }
                $size_counter[$cat_id] = $size + 1;

            } else {
                // size name already in database
                $this->size_id[$sizename_str_id] = $gate_check_sizename;
            }

            // insert price
            $gate_check_price = $this->duplicatePrice($this->item_id[$item], $this->size_id[$sizename_str_id]);
            if ($gate_check_price == -1) {
                // price for this item of this size does not exist

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

                $itemid = $this->item_id[$item];
                $sizeid = $this->size_id[$sizename_str_id];

                $stmt = $this->dbo->prepare($query);
                $stmt->bindParam(':itemid', $itemid);
                $stmt->bindParam(':sizeid', $sizeid);
                $stmt->bindParam(':price', $price);

                $stmt->execute();
            } else {
                $update_price = 
                    "UPDATE `cs_price`
                    SET price = $price 
                    WHERE priceid = $gate_check_price";
                $this->dbo->query($update_price);
            }
        }

        // do size insertion
        foreach ($size_counter as $cat_id => $size) {
            $update_size =
                "UPDATE `cs_menucategory`
                SET size = $size
                WHERE catid = $cat_id";

            $stmt = $this->dbo->query($update_size);
        }

        // print_r("<pre>");
        // print_r($this->size_id);
        // print_r("</pre>");

        $this->inserted_group    = array();
        $this->inserted_cat      = array();
        $this->inserted_item     = array();
        $this->inserted_sizename = array();
    }

}
