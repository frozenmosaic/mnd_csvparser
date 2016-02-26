<?php

class CascDel
{
    private $location_id;
    private $dbo;
    private $host     = "localhost";
    private $dbname   = "menudrive";
    private $user     = "root";
    private $password = "vy";

    private $errors_msg = array(
        1 => "Encountered error(s).",
        2 => "No information on provided Location ID.");
    private $errors_code = array();

    public function __construct($location_id)
    {
        $this->location_id = $location_id;
        $this->run();
    }

    public function run()
    {
        $this->connectDtb();
        $this->delete();
        $this->printStatus();
    }

    /**
     * Connect to database using information provided in class fields
     * @return [type] [description]
     */
    public function connectDtb()
    {
        try
        {
            $connStr   = "mysql:host=" . $this->host . ";dbname=" . $this->dbname;
            $this->dbo = new \PDO($connStr, $this->user, $this->password);
        } catch (PDOException $e) {
            $e->getMessage();
        }
    }

    public function printStatus()
    {
        if (!empty($this->errors_code)) {
            foreach ($this->errors_code as $code) {
                print_r($this->errors_msg[$code]);
            }
        } else {
            print_r("Successfully cascade deleted.");
        }
    }

    public function delete()
    {
        $select_group =
        "SELECT toppinggroupid
    		FROM `cs_toppinggroup`
    		WHERE locationid = " . $this->location_id;
        $res = $this->dbo->query($select_group);
        if ($res != false) {
            $groups = $res->fetchAll();
            if (count($res) == 0) {
                $this->errors_code[] = 3;
            }
            // print_r("<pre>");
            // print_r($groups);
            // print_r("</pre>");
        } else {
            $this->errors_code[] = 2;
        }

        $delete_modmods =
            "DELETE FROM `cs_modifiers_modifier`
    		WHERE toppingitem_id = ";
        $delete_item =
            "DELETE FROM `cs_toppingitems`
    		WHERE toppinggroupid = ";
        $delete_label =
            "DELETE FROM `cs_custom_topping_labels`
    		WHERE topping_group_id = ";
        if (!empty($groups)) {
            foreach ($groups as $row) {
                $value = $row['toppinggroupid'];

                $str_modmods = $delete_modmods . $value;
                $this->dbo->query($str_modmods);

                $str_label = $delete_label . $value;
                $this->dbo->query($str_label);

                $str_item = $delete_item . $value;
                $this->dbo->query($str_item);
            }
        }

        $delete_group =
        "DELETE FROM `cs_toppinggroup`
        	WHERE locationid = " . $this->location_id;
        $this->dbo->query($delete_group);

    }
}
