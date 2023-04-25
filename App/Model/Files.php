<?php

namespace App\Model;

use Core\Db\Crud;
use Core\Db\Logs;
use Core\Db\Model;
use Core\Di\Container;

class Files extends Model
{
    private $grantedExtensions = [
        'jpg',
        'jpeg',
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

    public function uploadFiles(array $files, string $module, string $uuid): bool
    {
        if (!empty($files)) {
            $parentDir = $this->configurePath("$module/$uuid");
            if (!$parentDir) {
                return false;
            }

            for ($i = 0; $i < count($files); $i++) {
                $file = $files['file_' . $i];
                $fileName = $file["name"];
                $fileName = str_replace(" ", "_", $fileName);

                if ($this->validateExtension($fileName)) {
                    $tmpName = $file["tmp_name"];
                    $uploadDir = $parentDir . '/' . $fileName;

                    if (move_uploaded_file($tmpName, $uploadDir)) {

                        $this->setFileWatermark($uploadDir);

                        $crudFiles = new Crud();
                        $crudFiles->setTable($this->getTable());
                        $crudFiles->create([
                            'uuid' => $this->NewUUID(),
                            'parent_uuid' => $uuid,
                            'file' => $fileName
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

    public function setFileWatermark($dir): void
    {
        $configModel = Container::getClass("Config", "app");
        $config = $configModel->getEntity();
        $image = false;

        if (!empty($dir) && !empty($config['logo_watermark'])) {
            $imageFile = $dir;
            $watermarkFile = "../public/uploads/logo/{$config['logo_watermark']}";

            $imageExt = pathinfo($imageFile);
            $imageExt = $imageExt['extension'];

            if ($imageExt == 'jpg' || $imageExt == 'jpeg') {
                $image = @imagecreatefromjpeg($imageFile);
            }

            if ($imageExt == 'png') {
                $image = @imagecreatefrompng($imageFile);
            }

            if ($image) {
                $watermark = imagecreatefrompng($watermarkFile);
                $watermarkWidth = imagesx($watermark);
                $watermarkHeight = imagesy($watermark);

                $imageWidth = imagesx($image);
                $imageHeight = imagesy($image);

                $positionX = ($imageWidth / 2) - ($watermarkWidth / 2);
                $positionY = ($imageHeight / 2) - ($watermarkHeight / 2);

                imagecopy(
                    $image,
                    $watermark,
                    (int) $positionX,
                    (int) $positionY,
                    0,
                    0,
                    $watermarkWidth,
                    $watermarkHeight
                );

                header('Content-type: image/png');
                imagepng($image, $dir);
                imagedestroy($image);
                imagedestroy($watermark);
            }
        }
    }
}