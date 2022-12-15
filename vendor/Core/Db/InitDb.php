<?php

namespace Core\Db;

use Core\Init\Bootstrap;
use PDO;

class InitDb
{
    public mixed $db;
    protected mixed $table;

    public function getTable()
    {
        return $this->table;
    }

    public function setTable($table): void
    {
        $this->table = $table;
    }

    public function openDb(): PDO
    {
        return $this->db = Bootstrap::getDb();
    }

    public function closeDb(): void
    {
        $this->db = null;
    }
}