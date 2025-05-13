<?php 

namespace App\Model;

use Core\Db\Model;
use Exception;
use PDO;

class ChangesHistoric extends Model
{
    public function __construct()
    {
        $this->setTable('changes_historic');
    }

    public function getAllByModule(string $field, int $id): bool|array|string
    {
        try {
            $field = 'o.'.$field;

            $query = "SELECT $field, o.id, o.status, o.created_at, 
                            o.user_id, o.customer_id,
                            u.id as userCod, u.name as userName,
                            c.id as customerCod, c.name as customerName
                        FROM {$this->getTable()} AS o
                        LEFT JOIN user AS u
                            ON o.user_id = u.id
                        LEFT JOIN customers AS c
                            ON o.customer_id = c.id
                        WHERE $field = :id
                        ORDER BY o.created_at DESC";

            $stmt = $this->openDb()->prepare($query);
            $stmt->bindValue(":id", $id);
            $stmt->execute();

            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $stmt = null;
            $this->closeDb();
            
            return $result;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }
}