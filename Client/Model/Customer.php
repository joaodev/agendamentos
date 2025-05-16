<?php

namespace Client\Model;

use Core\Db\Model;
use Exception;
use PDO;

class Customer extends Model
{
    public function __construct()
    {
        $this->setTable('customers');
    }

    public function getOne($id): bool|array|string
    {
        try {
            $query = "SELECT id, name, email, phone, cellphone, 
                            document_1, document_2,
                            postal_code, address, number, complement, 
                            neighborhood, city, state, file,
                            status, created_at, updated_at, code
                        FROM customers
                        WHERE id = :id";

            $stmt = $this->openDb()->prepare($query);
            $stmt->bindValue(":id", $id);
            $stmt->execute();

            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            $stmt = null;
            $this->closeDb();

            return $result;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function authByCrenditials($email, $code): bool|array|string
    {
    	try {
            if (!empty($email) && !empty($code)) {
                $query = "SELECT id, name, email, code, file
                            FROM customers
                            WHERE email = :email
                                AND code = :code
                                AND status = :status";

                $stmt = $this->openDb()->prepare($query);
                $stmt->bindValue(":email", $email);
                $stmt->bindValue(":code", $code);
                $stmt->bindValue(":status", '1');
                $stmt->execute();

                if ($stmt->rowCount() == 1) {
                    $result = $stmt->fetch(PDO::FETCH_ASSOC);
                } else {
                    $result = false;
                }

                $stmt = null;
                $this->closeDb();

                return $result;
            } else {
                return false;
            }
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function findByCrenditials($email): bool|array|string
    {
        if (!empty($email)) {
            try {
                $query = "SELECT id, name, email, code, file
                            FROM customers
                            WHERE email=:email
                                AND status = :status";

                $stmt = $this->openDb()->prepare($query);
                $stmt->bindValue(":email", $email);
                $stmt->bindValue(":status", '1');
                $stmt->execute();
              
                if ($stmt->rowCount() == 1) {
                    $customer = $stmt->fetch(PDO::FETCH_ASSOC);
                    $data = $customer;
                } else {
                    $data = false;
                }

                $stmt = null;
                $this->closeDb();

                return $data;
            } catch (Exception $e) {
                return $e->getMessage();
            }
        } else {
            return false;
        }
    }
}