<?php

namespace App\Model;

use Core\Db\Crud;
use Core\Db\Logs;
use Core\Db\Model;

class Files extends Model
{
    private $grantedExtensions = [
        'jpg',
        'jpeg',
        'png',
        'gif',
        'png',
        'pdf'
    ];

    public function __construct()
    {
        $this->setTable('files');
    }

    private function configurePath(string $path): bool|string
    {
        $parentDir = "../public/uploads/$path";
        if (!is_dir($parentDir)) {
            if (!mkdir($parentDir)) {
                return false;
            }
        }
        return $parentDir;
    }

    private function validateExtension(string $imageName): bool
    {
        $extension = pathinfo($imageName);
        $extension = $extension['extension'];

        if (!empty($imageName) && in_array($extension, $this->grantedExtensions)) {
            return true;
        } else {
            return false;
        }
    }

    public function uploadFiles(array $files, string $module, int $id, string $field): bool
    {
        if (!empty($files)) {
            $parentDir = $this->configurePath("$module/$id");
            if (!$parentDir) {
                return false;
            }

            for ($i = 0; $i < count($_FILES); $i++) {
                $file = $_FILES['file_' . $i];
                $fileName = $file["name"];
                $fileName = str_replace(" ", "_", $fileName);

                if ($this->validateExtension($fileName)) {
                    $tmpName = $file["tmp_name"];
                    $uploadDir = $parentDir . '/' . $fileName;

                    if (move_uploaded_file($tmpName, $uploadDir)) {
                        $crudFiles = new Crud();
                        $crudFiles->setTable($this->getTable());
                        $crudFiles->create([
                            'file' => $fileName,
                            $field => $id,
                        ]);
                    } else {
                        $log = new Logs();
                        $log->toLog("Erro ao enviar imagem: $uploadDir");
                    }
                }
            }
        }

        return true;
    }
}