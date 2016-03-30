<?php

class Decode
{

    // table to clean up
    private $table = 'test';
    // primary of table
    private $primary_key = 'menuitemid';

    // column that has data to clean up
    private $column = 'itemname';

    // columns that have data to clean up
    // private $columns = array(
    //     'itemname',
    //     'description',
    // );

    private $dbo;
    private $host = "localhost";
    // private $dbname   = "menudrive";
    // private $user     = "root";
    // private $password = "vy";
    private $dbname   = "mnd";
    private $user     = "root";
    private $password = "mysql*root";

    public function __construct()
    {
        $this->connectDtb();
        $this->decodeSingleColumn();
    }

    /**
     * Connect to database using information provided in class fields
     * @return [type] [description]
     */
    public function connectDtb()
    {
        // connect to dtb
        try
        {
            $connStr   = "mysql:host=" . $this->host . ";dbname=" . $this->dbname;
            $this->dbo = new \PDO($connStr, $this->user, $this->password);
        } catch (PDOException $e) {
            $e->getMessage();
        }

    }

    /**
     * Decode and update, for one single column
     * @return [type] [description]
     */
    public function decodeSingleColumn()
    {
        // get data
        $select =
            "SELECT
                $this->primary_key,
                $this->column
            FROM
                $this->table
            WHERE
                $this->column LIKE '%&#%;'";

        $stmt = $this->dbo->query($select);
        if ($stmt != false) {
            $result = $stmt->fetchAll();
            // update
            foreach ($result as $row) {
                $id   = $row[$this->primary_key];
                $data = $row[$this->column];

                $data_decoded = html_entity_decode($data);

                $update =
                    "UPDATE $this->table
                SET $this->column = '$data_decoded'
                WHERE $this->primary_key = $id";

                $stmt = $this->dbo->query($update);
                // if ($stmt != false) {
                //     print_r('Successful.');
                // } else {
                //     echo 'Errors encountered when updating data.';
                // }

                // print_r("<pre>");
                // print_r($update);
                // print_r("</pre>");
            }
        } else {
            echo 'Errors encountered when selecting data. No updating was made.';
        }

    }

    /**
     * Decode and update data for multiple columns
     * @return [type] [description]
     */
    public function decodeMultipleColumns()
    {

        // get data
        $col_str = $this->primary_key . ', ' . implode(', ', $this->columns);

        $select =
            "SELECT $col_str
            FROM $this->table
            WHERE ";
        $like_str      = " LIKE '%&#%;' ";
        $this->columns = array_values($this->columns);

        $str = '';
        for ($i = 0; $i < count($this->columns); $i++) {
            $col = $this->columns[$i];
            if ($i == (count($this->columns) - 1)) {
                $str = $col . $like_str;
                $select .= $str;
            } else {
                $str = $col . $like_str . 'OR ';
                $select .= $str;
            }
        }

        $stmt   = $this->dbo->query($select);
        $result = $stmt->fetchAll();

        // update

        foreach ($result as $row) {
            foreach ($this->columns as $col) {
                $id           = $row[$this->primary_key];
                $data         = $row[$col];
                $data_decoded = html_entity_decode($data);

                $update =
                    "UPDATE $this->table
                    SET $col = '$data_decoded'
                    WHERE $this->primary_key = $id";

                if ($this->dbo->query($update) != false) {
                    print_r('Successful');
                }
                print_r("<pre>");
                print_r($update);
                print_r("</pre>");
            }
        }

    }

}

$decode = new Decode();
