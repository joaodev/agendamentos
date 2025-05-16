<?php

namespace Core\Db;

class Crud extends InitDb
{
    public function create(array $dataPost): bool|string
    {
        try {
            $fields = "";
            $values = "";
            $totalData = count($dataPost);

            foreach ($dataPost as $field => $value) {
                $totalData--;
                $fields .= $field . ($totalData >= 1 ? ',' : null);
                $values .= $value . ($totalData >= 1 ? "','" : null);
            }

            $values = "'" . $values . "'";

            if (!empty($fields) && !empty($values)) {
                $pdo = $this->openDb();

                $query = "INSERT INTO {$this->getTable()} ({$fields}) VALUES ({$values})";
                $stmt = $pdo->prepare($query);
                $stmt->execute();

                $lastId = $pdo->lastInsertId();
                if (!$lastId) {
                    $pdo->rollBack();
                    return false;
                }

                $stmt = null;
                $this->closeDb();

                return $lastId;
            } else {
                return false;
            }
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    public function update(array $data, string $id, string $idField, array $secondParams = null): bool|string
    {
        try {
            $otherParam = "";
            if (!empty($secondParams['field']) && $secondParams['value']) {
                if ((int) $secondParams['value']) {
                    $otherParam = " AND {$secondParams['field']} = {$secondParams['value']}";
                } else {
                    $otherParam = " AND {$secondParams['field']} = '{$secondParams['value']}'";
                }
            }

            $query = "UPDATE {$this->getTable()} SET ";
            $totalItens = count($data);
            $counter = 0;

            foreach ($data as $label => $val) {
                $counter++;
                $comma = ($counter < $totalItens) ? ", " : "";
                $key = $label . "=:" . $label . $comma;
                $query .= $key;
            }

            $query .= " WHERE {$idField} =:id {$otherParam}";
            $stmt = $this->openDb()->prepare($query);

            foreach ($data as $column => $value) {
                $stmt->bindValue(":" . $column, $value);
            }
            $stmt->bindValue(":id", $id);
            $stmt->execute();

            $stmt = null;
            $this->closeDb();

            return true;
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    public function delete(string $id, string $idField): bool|string
    {
        try {
            $query = "
                DELETE FROM {$this->getTable()}  
                WHERE {$idField} = :id
            ";

            $stmt = $this->openDb()->prepare($query);
            $stmt->bindValue(":id", $id);
            $stmt->execute();

            $stmt = null;
            $this->closeDb();

            return true;
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }
}