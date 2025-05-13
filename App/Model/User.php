<?php

namespace App\Model;

use Core\Db\Model;
use Core\Db\Bcrypt;
use Exception;
use PDO;

class User extends Model
{
    public function __construct()
    {
        $this->setTable('user');
    }

    public function getOne(int $id, bool $deleted = true): bool|array|string
    {
        try {
            $withDeleted = "";
            if ($deleted) {
                $withDeleted = "AND u.deleted = :deleted";
            }

            $query = "SELECT u.id, u.name, u.email, u.status, u.role_id,
                            u.cellphone, u.job_role, r.name as role, u.phone, u.whatsapp,
                            r.is_admin, u.created_at, u.updated_at, u.file,
                            u.postal_code, u.address, u.number, u.complement, 
                            u.neighborhood, u.city, u.state, u.document_1, u.document_2,
                            u.salary, u.start_date, u.end_date, 
                            u.birthdate, u.gender, u.auth2factor, u.deleted
                        FROM {$this->getTable()} AS u
                        INNER JOIN role AS r
                            ON u.role_id = r.id
                        WHERE u.id = :id 
                            $withDeleted
                        ORDER BY u.name";

            $stmt = $this->openDb()->prepare($query);
            $stmt->bindValue(":id", $id);
            
            if ($deleted) {
                $stmt->bindValue(":deleted", '0');
            }

            $stmt->execute();

            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            $stmt = null;
            $this->closeDb();

            return $result;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function getProfile(int $id): bool|array|string
    {
        try {
            $query = "SELECT u.id, u.name, u.email, u.status, u.role_id,
                            u.cellphone, u.job_role, r.name as role, u.phone, u.whatsapp,
                            r.is_admin, u.created_at, u.updated_at, u.file,
                            u.postal_code, u.address, u.number, u.complement, 
                            u.neighborhood, u.city, u.state, u.document_1, u.document_2,
                            u.salary, u.start_date, u.end_date, 
                            u.birthdate, u.gender, u.auth2factor   
                        FROM {$this->getTable()} AS u
                        INNER JOIN role AS r
                            ON u.role_id = r.id
                        WHERE u.id = :id 
                            AND u.deleted = :deleted
                        ORDER BY u.name";

            $stmt = $this->openDb()->prepare($query);
            $stmt->bindValue(":id", $id);
            $stmt->bindValue(":deleted", '0');
            $stmt->execute();

            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            $stmt = null;
            $this->closeDb();

            return $result;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function getAll(): bool|array|string
    {
        try {
            $query = "SELECT u.id, u.name, u.email, u.status, u.file, u.phone,  u.whatsapp,
                            u.cellphone, u.job_role, r.name as role, u.created_at, u.updated_at,
                            u.postal_code, u.address, u.number, u.complement, 
                            u.neighborhood, u.city, u.state, u.document_1, u.document_2,
                            u.salary, u.start_date, u.end_date 
                        FROM {$this->getTable()} AS u
                        INNER JOIN role AS r
                            ON u.role_id = r.id
                        WHERE u.deleted = :deleted
                        ORDER BY u.name";

            $stmt = $this->openDb()->prepare($query);
            $stmt->bindValue(":deleted", '0');
            $stmt->execute();

            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $stmt = null;
            $this->closeDb();

            return $result;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function getAllActives(): bool|array|string
    {
        try {
            $query = "SELECT u.id, u.name, u.email, u.status, u.file, u.phone,  u.whatsapp,
                            u.cellphone, u.job_role, r.name as role, u.created_at, u.updated_at,
                            u.postal_code, u.address, u.number, u.complement, 
                            u.neighborhood, u.city, u.state, u.document_1, u.document_2,
                            u.salary, u.start_date, u.end_date 
                        FROM {$this->getTable()} AS u
                        INNER JOIN role AS r
                            ON u.role_id = r.id
                        WHERE u.deleted = :deleted
                        AND u.status = :status
                        ORDER BY u.name";

            $stmt = $this->openDb()->prepare($query);
            $stmt->bindValue(":deleted", '0');
            $stmt->bindValue(":status", '1');
            $stmt->execute();

            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $stmt = null;
            $this->closeDb();

            return $result;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function totalUsers(): int|string
    {
        try {
            $query = "SELECT id
                        FROM {$this->getTable()} 
                        WHERE deleted = '0'";

            $stmt = $this->openDb()->query($query);
            $result = $stmt->rowCount();

            $stmt = null;
            $this->closeDb();

            return $result;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function getAllByRoleId(int $id): bool|array|string
    {
        try {
            $query = "SELECT u.id, u.name, u.email, u.status, u.file,  u.phone, u.whatsapp,
                            u.cellphone, u.job_role, r.name as role, u.created_at
                        FROM {$this->getTable()} AS u
                        INNER JOIN role AS r
                            ON u.role_id = r.id
                        WHERE u.role_id = :role_id AND u.deleted = '0'";

            $stmt = $this->openDb()->prepare($query);
            $stmt->bindValue(":role_id", $id);
            $stmt->execute();

            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $stmt = null;
            $this->closeDb();

            return $result;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function authByCrenditials(string $email, string $password, string $token): bool|array|string
    {
        try {
            if (!empty($email) && !empty($password) && !empty($token)) {
                $query = "SELECT u.id, u.name, u.email, u.password, u.file,
                                r.name as role, u.role_id, r.is_admin, u.auth2factor
                            FROM {$this->getTable()} AS u
                            INNER JOIN role AS r
                                ON u.role_id = r.id
                            WHERE u.email=:email AND u.password=:password
                                AND u.status = :status
                                AND u.code = :code
                                AND u.code_validated = :code_validated
                                AND u.deleted = :deleted";

                $stmt = $this->openDb()->prepare($query);
                $stmt->bindValue(":email", $email);
                $stmt->bindValue(":password", $password);
                $stmt->bindValue(":status", '1');
                $stmt->bindValue(":code", $token);
                $stmt->bindValue(":code_validated", '1');
                $stmt->bindValue(":deleted", '0');
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

    public function findByCredentials(string $email, string $password): bool|array|string
    {
        if (!empty($email) && !empty($password)) {
            try {
                $query = "SELECT u.id, u.name, u.email, u.password, u.file,
                                r.name as role, u.role_id, r.is_admin,
                                u.code, u.auth2factor
                            FROM {$this->getTable()} AS u
                            INNER JOIN role AS r
                                ON u.role_id = r.id
                            WHERE u.email=:email
                                AND u.status = :status
                                AND u.deleted = :deleted";

                $stmt = $this->openDb()->prepare($query);
                $stmt->bindValue(":email", $email);
                $stmt->bindValue(":status", '1');
                $stmt->bindValue(":deleted", '0');
                $stmt->execute();

                if ($stmt->rowCount() == 1) {
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);

                    if (Bcrypt::check($password, $user['password'])) {
                        $data = $user;
                    } else {
                        $data = false;
                    }
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

    public function checkDeletePermission(int $roleId): bool|string
    {
        try {
            $query = "SELECT role_id 
                        FROM {$this->getTable()}
                        WHERE role_id = :role_id 
                        AND deleted = :deleted";

            $stmt = $this->openDb()->prepare($query);
            $stmt->bindValue(":role_id", $roleId);
            $stmt->bindValue(":deleted", '0');
            $stmt->execute();

            $results = $stmt->rowCount();

            $stmt = null;
            $this->closeDb();

            if ($results > 0) {
                return false;
            } else {
                return true;
            }
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function searchData(string $postData): bool|array|string
    {
        try {
            $query = "SELECT id, name, job_role, email
                        FROM {$this->getTable()}
                        WHERE deleted = '0' AND status = '1' 
                            AND (name LIKE '%$postData%' OR id LIKE '%$postData%')
                        ORDER BY name  LIMIT 15";

            $stmt = $this->openDb()->query($query);
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $stmt = null;
            $this->closeDb();

            return $result;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function getAllAdminActives(): bool|array|string
    {
        try {
            $query = "SELECT u.id, u.name, u.email, u.status, u.file, u.phone,  u.whatsapp,
                            u.cellphone, u.job_role, r.name as role, u.created_at, u.updated_at,
                            u.postal_code, u.address, u.number, u.complement, 
                            u.neighborhood, u.city, u.state, u.document_1, u.document_2,
                            u.salary, u.start_date, u.end_date 
                        FROM {$this->getTable()} AS u
                        INNER JOIN role AS r
                            ON u.role_id = r.id
                        WHERE u.deleted = :deleted
                        AND u.status = :status
                        AND r.is_admin = :is_admin
                        ORDER BY u.name";

            $stmt = $this->openDb()->prepare($query);
            $stmt->bindValue(":deleted", '0');
            $stmt->bindValue(":status", '1');
            $stmt->bindValue(":is_admin", '1');
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