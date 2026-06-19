<?php

require_once 'config.php';

class Database
{
    private $connection;

    public function connect()
    {
        $this->connection = new mysqli(
            DB_HOST,
            DB_USER,
            DB_PASS,
            DB_NAME
        );

        if ($this->connection->connect_error) {
            die(
                "Database Connection Failed: " .
                $this->connection->connect_error
            );
        }

        return $this->connection;
    }
}