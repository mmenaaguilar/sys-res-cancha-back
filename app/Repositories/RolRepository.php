<?php
// app/Repositories/RolRepository.php

namespace App\Repositories;

use App\Core\Database;
use PDO;

class RolRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function getAllRolesCombo(): array
    {
        $sql = "SELECT rol_id, nombre FROM Roles ORDER BY nombre ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
