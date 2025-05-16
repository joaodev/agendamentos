<?php

namespace Core\Db;

use Core\Init\Bootstrap;
use PDO;

class InitDb
{
    public mixed $db;
    protected string $table;

    public function getTable(): string
    {
        return $this->table;
    }

    public function setTable(string $table): void
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