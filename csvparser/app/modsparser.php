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

    public function duplicateGroup($group)
    {
        $query =
        "SELECT *
            FROM  cs_toppinggroup
            WHERE locationid = :locationid
            AND toppinggroupname = " . $this->dbo->quote($group);
        $stmt = $this->dbo->prepare($query);
        $stmt->bindParam(':locationid', $this->location_id);
        // $stmt->bindParam(':group', $group);
        $stmt->execute();
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
            WHERE toppinggroupid = :groupid
            AND toppingitemname = " . $this->dbo->quote($item);

        $stmt = $this->dbo->prepare($query);
        $stmt->bindParam(':groupid', $group_id);
        // $stmt->bindParam(':item', $item);

        $stmt->execute();
        $res = $stmt->fetchAll();
        // echo "<pre>" . $item . $group_id . "</pre>";
        // print_r(count($res));

        if (count($res) > 0) {
            // should have only one duplicate
            return $res[0]['topping_id'];
        } else {
            return -1;
        }
    }

    public function duplicateLabel($label, $group_id)
    {
        $query =
        "SELECT *
            FROM  cs_custom_topping_labels
            WHERE topping_group_id = :groupid
            AND topping_label = " . $this->dbo->quote($label);

        $stmt = $this->dbo->prepare($query);
        $stmt->bindParam(':groupid', $group_id);
        // $stmt->bindParam(':item', $item);

        $stmt->execute();
        $res = $stmt->fetchAll();

        if (count($res) > 0) {
            // should have only one duplicate
            // return $res[0]['topping_id'];
        } else {
            return -1;
        }
    }

    /**
     * @inheritDoc
     */
    public function insert()
    {
        $count = count($this->csv_data);
        // print_r("<pre>");
        // print_r($this->csv_data);
        // print_r("</pre>");
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

            // escape string
            // $name = $this->dbo->quote($name);
            // $item = $this->dbo->quote($item);
            // $type = $this->dbo->quote($type);

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
                    " . $this->dbo->quote($name) . ",
                        :min,
                        :max,
                    " . $this->dbo->quote($type) . ",
                        :locationid,
                        :qty_item
                        )
                ");

                if ($type == "select" || $type == "radio") {
                    $min = 1;
                    $max = 1;
                }

                // $stmt->bindParam(':name', $name);
                $stmt->bindParam(':min', $min);
                $stmt->bindParam(':max', $max);
                // $stmt->bindParam(':type', $type);
                $stmt->bindParam(':qty_item', $qty_item);
                $stmt->bindParam(':locationid', $this->location_id);

                $stmt->execute();
                // $this->inserted_group[] = $name;
                $this->group_id[$name] = $this->dbo->lastInsertId();
            } else {
                $this->group_id[$name] = $gate_check_group;
            }
            $group_id = $this->group_id[$name];

            // insert mod items
            $gate_check_item = $this->duplicateItem($item, $group_id);
            if ($gate_check_item == -1) {
                $query =
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
                    " . $this->dbo->quote($item) . ",
                        :price,
                        :sequence,
                        :extra_mul
                        )
                    ";

                $stmt = $this->dbo->prepare($query);

                $sequence = count($this->inserted_item) + 1;

                $stmt->bindParam(':groupid', $group_id);
                // $stmt->bindParam(':name', $item);
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
                                AND `toppinggroupid` = $group_id
                    ";

                    $stmt = $this->dbo->prepare($query);
                    $stmt->execute();
                }

            }

            if ($topping_item[$name] == 'Custom') {

                $labels = explode(";", $custom_labels);

                foreach ($labels as $label) {
                    $label            = trim($label);
                    $gate_check_label = $this->duplicateLabel($label, $group_id);
                    if ($gate_check_label == -1) {
                        $query =
                        "INSERT INTO `cs_custom_topping_labels`
                            (
                            `topping_group_id`,
                            `topping_label`
                            )
                        VALUES
                            (
                            :groupid,
                        " . $this->dbo->quote($label) . "
                            )
                        ";

                        $stmt = $this->dbo->prepare($query);
                        $stmt->bindParam(':groupid', $group_id);

                        $stmt->execute();
                    }

                }

            }

        }

        print_r("<pre>");
        print_r($this->group_id);
        print_r("</pre>");
    }
}
