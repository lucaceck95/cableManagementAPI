<?php
class db
{
    //properties
    private $dbhost = 'localhost';
    private $dbuser = 'root';
    private $dbpass = '';
    private $dbname = 'cable_management';

    //connect
    public function connect()
    {
        $mysqlConStr = "mysql:host=$this->dbhost;dbname=$this->dbname";
        $dbConnection = new PDO($mysqlConStr, $this->dbuser, $this->dbpass);

        $dbConnection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        return $dbConnection;
    }
}
