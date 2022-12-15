<?php

namespace App\Model;

use Core\Db\Model;
use Exception;
use PDO;

class Config extends Model
{
    public function __construct()
    {
        $this->setTable('site_config');
    }

    public function getOne()
    {
        try {
            $query = "
                SELECT uuid, version, site_title, primary_color, secondary_color, email,phone,
                        cellphone, full_address, logo, logo_icon, mail_host, mail_port,
                        mail_username, mail_password, mail_from_address, mail_to_address, updated_at,
                        google_maps, facebook, linkedin, instagram, twitter, youtube, tiktok,
                        pagseguro_email, pagseguro_token, file_menu, logo_site
                FROM site_config
                ORDER BY id LIMIT 1
            ";

            $stmt = $this->openDb()->query($query);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            $stmt = null;
            $this->closeDb();

            return $result;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }
}