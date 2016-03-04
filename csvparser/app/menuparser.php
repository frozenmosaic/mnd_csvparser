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
     * Check for existing Menu Groups in database
     * @param  [type] $group name of menu group to check
     * @return boolean        return true if there is a duplicate in database,
     *                               false if $group is unique menu group
     */
    public function duplicateGroup($group)
    {
        $query =
            "SELECT *
            FROM `cs_menugroup`
            WHERE locationid = '" . $this->location_id .
            "' AND menugroupname = '" . $group . "'";
        $stmt = $this->dbo->query($query);
        $res  = $stmt->fetchAll();

        if (count($res) > 0) {
            return true;
        } else {
            return false;
        }
    }

    public function getGroupID()
    {

    }

    /**
     * Insert data into database using raw csv data, row by row
     * @return [void]
     */
    public function insert()
    {
        $count = count($this->csv_data);

        $size_counter = array(); // format: category => size number

        for ($i = 0; $i < $count; $i++) {
            $row = $this->csv_data[$i]; // array of values for each csv data row

            // insert menu group
            $group    = $row['Group'];
            $item     = $row['Item'];
            $sizename = !empty($row['Size Names']) ? $row['Size Names'] : 'size1';
            $sizename = $sizename;
            $cat      = $row['Category'];
            $price    = $row['Price'];

            if (!$this->duplicateGroup($group)) {
                // if group does not already exist in database
                if (!in_array($group, $this->inserted_group)) { // if group has not been imported before in this spreadsheet

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
                    $this->inserted_group[] = $group; // record group that just got inserted

                    $this->group_id[$group] = $this->dbo->lastInsertId();
                }
            } else {
                // group is in database
                $this->group_id[$group] = $this->getGroupID();
            }

            // insert menu category

            if (!in_array($cat, $this->inserted_cat)) {
                $query =
                    "INSERT INTO `cs_menucategory`
                        (
                        `menugroupid`,
                        `categoryname`
                        )
                    VALUES
                        (
                        :groupid,
                        :name
                        )"
                ;

                $stmt = $this->dbo->prepare($query);
                $stmt->bindParam(':name', $cat);

                $id = $this->group_id[$group];
                $stmt->bindParam(':groupid', $id);

                $stmt->execute();

                // track inserted categories
                $this->inserted_cat[] = $cat;

                // record category id
                $this->cat_id[$cat] = $this->dbo->lastInsertId();

            }

            // insert menu item
            if (!in_array($item, $this->inserted_item)) {
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

            // insert category size names

            // get category id
            $cat_id = $this->cat_id[$cat];
            // construct identifying string for each size name based on candidate keys {cat_id, sizename}
            $sizename_str_id = $cat_id . $sizename;

            if (!in_array($sizename_str_id, $this->inserted_sizename)) {
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

                // track inserted size name
                $this->inserted_sizename[] = $sizename_str_id;

                // increase size counter (number of sizes unique for each category)
                if (array_key_exists($cat_id, $size_counter)) {
                    $size = $size_counter[$cat_id];
                } else {
                    $size = 0;
                }
                $size_counter[$cat_id] = $size + 1;

                // track id of inserted size name
                $this->sizename_id[$sizename_str_id] = $this->dbo->lastInsertId();
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

            $itemid = $this->item_id[$item];

            // if ($size == 1) {
            //     $str = 'size1' . $cat;
            // } elseif (empty($size)) {
            //     if (empty($sizename)) {
            //         $str = 'size1' . $cat;
            //     } else {
            //         $str = $sizename . $cat;
            //     }
            // } elseif ($size > 1) {
            //     $str = $sizename . $cat;
            // }

            $sizeid = $this->sizename_id[$sizename_str_id];

            $stmt = $this->dbo->prepare($query);
            $stmt->bindParam(':itemid', $itemid);
            $stmt->bindParam(':sizeid', $sizeid);
            $stmt->bindParam(':price', $price);

            $stmt->execute();
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
        // print_r($this->inserted_sizename);
        // print_r("</pre>");

        //     print_r("<pre>");
        //     print_r($this->sizename_id);
        //     print_r("</pre>");

        $this->inserted_group    = array();
        $this->inserted_cat      = array();
        $this->inserted_item     = array();
        $this->inserted_sizename = array();
    }

}
