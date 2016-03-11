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

    public function duplicateGroup($group)
    {
        $query =
        "SELECT *
            FROM  cs_toppinggroup
            WHERE locationid = '" . $this->location_id .
            "' AND toppinggroupname = '" . $group . "'";
        $stmt = $this->dbo->query($query);
        $res = $stmt->fetchAll();

        if (count($res) > 0) {
            // should have only one duplicate
            return $res[0]['toppinggroupid'];
        } else {
            return -1;
        }
    }

    public function duplicateItem($item, $group_id)
    {
        $query =
            "SELECT *
            FROM  cs_toppingitems
            WHERE toppinggroupid = '" . $group_id .
            "' AND toppingitemname = '" . $item . "'";
        $stmt = $this->dbo->query($query);
        $res  = $stmt->fetchAll();

        if (count($res) > 0) {
            // should have only one duplicate
            return $res[0]['topping_id'];
        } else {
            return -1;
        }
    }

    /**
     * @inheritDoc
     */
    public function insert()
    {
        $count        = count($this->csv_data);
        $topping_item = array(); // track type of modifier group, format: group name => type

        for ($i = 0; $i < $count; $i++) {
            $row           = $this->csv_data[$i];
            $name          = $row['Group'];
            $min           = $row['Min'];
            $max           = $row['Max'];
            $type          = $row['Type'];
            $qty_item      = $row['Qty for 1 item'];
            $item          = $row['Item'];
            $price         = $row['Main Price'];
            $left          = $row['Left Price'];
            $whole         = $row['Whole Price'];
            $right         = $row['Right Price'];
            $extra_mul     = $row['Extra Multiplied By'];
            $custom_labels = $row['Custom Labels'];

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

            if (isset($extra_mul)) {
                $extra_mul = 1;
            } else {
                $extra_mul = 0;
            }

            // insert mod groups
            $gate_check_group = $this->duplicateGroup($name);
            if ($gate_check_group == -1) {

                $stmt = $this->dbo->prepare(
                    "INSERT INTO `cs_toppinggroup`
                        (
                        `toppinggroupname`,
                        `mintop`,
                        `maxtop`,
                        `group_type`,
                        `locationid`,
                        `quantity_sign_total_modifiers`
                        )
                    VALUES
                        (
                        :name,
                        :min,
                        :max,
                        :type,
                        :locationid,
                        :qty_item
                        )
                ");

                if ($type == "select" || $type == "radio") {
                    $min = 1;
                    $max = 1;
                }

                $stmt->bindParam(':name', $name);
                $stmt->bindParam(':min', $min);
                $stmt->bindParam(':max', $max);
                $stmt->bindParam(':type', $type);
                $stmt->bindParam(':qty_item', $qty_item);
                $stmt->bindParam(':locationid', $this->location_id);

                $stmt->execute();
                $this->inserted_group[] = $name;
                $this->group_id[$name]  = $this->dbo->lastInsertId();
            } else {
                $this->group_id[$name] = $gate_check_group;
            }

            // insert mod items
            $gate_check_item = $this->duplicateItem($item, $this->group_id[$name]);
            if ($gate_check_item == -1) {
                $groupid = $this->group_id[$name];
                $query   =
                    "INSERT INTO `cs_toppingitems`
                        (
                        `toppinggroupid`,
                        `toppingitemname`,
                        `toppingitemprice`,
                        `sequence`,
                        `allow_extra`
                        )
                    VALUES
                        (
                        :groupid,
                        :name,
                        :price,
                        :sequence,
                        :extra_mul
                        )
                    ";

                $stmt = $this->dbo->prepare($query);

                $sequence = count($this->inserted_item) + 1;

                $stmt->bindParam(':groupid', $groupid);
                $stmt->bindParam(':name', $item);
                $stmt->bindParam(':price', $price);
                $stmt->bindParam(':sequence', $sequence);
                $stmt->bindParam(':extra_mul', $extra_mul);

                $stmt->execute();
                $this->inserted_item[] = $item;

            }

            if ($topping_item[$name] == 'Custom' || $topping_item[$name] = 'Pizza') {
                if (is_numeric($left) && is_numeric($whole) && is_numeric($right)) {
                    $query =
                        "UPDATE `cs_toppingitems`
                        SET `left_price` = $left,
                            `whole_price`= $whole,
                            `right_price` = $right,
                            `toppingitemprice` = 0.0
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

                $labels = explode(";", $custom_labels);
                $stmt->bindParam(':groupid', $groupid);

                foreach ($labels as $value) {
                    $value = trim($value);
                    $stmt->bindParam(':label', $value);
                    $stmt->execute();
                }

            }

        }

        // print_r("<pre>");
        // print_r($this->group_id);
        // print_r("</pre>");
    }
}
