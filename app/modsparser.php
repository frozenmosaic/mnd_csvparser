<?php
namespace Parser;

include 'csvparser.php';

/**
 * Subclass for parsing modifiers CSV data
 * @author Vy Huynh
 */
class ModsParser extends CSVParser
{

    public function __construct($file)
    {
    	parent::__construct($file);
    }

    /**
     * @inheritDoc
     */
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
