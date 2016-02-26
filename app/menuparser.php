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
     * @return boolean        return true if there is a duplicate, 
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

        if (count($res) > 0 && !in_array($group, $this->inserted_group)) {
            return true;
        } else {
            return false;
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
            if (!$this->duplicateGroup($group)) {
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
        }

        $this->inserted_group = array();
        $this->inserted_cat   = array();
        $this->inserted_item  = array();
        $this->inserted_size  = array();

    }

}
