<?php

namespace App\Controllers;

use App\Core\Database;
use App\Core\Helpers\ApiHelper;
use PDO;

class MetodoPagoController extends ApiHelper
{
    public function list()
    {
        try {
            $db = Database::getConnection();
            // Traemos todos los métodos ordenados alfabéticamente
            $stmt = $db->query("SELECT metodo_pago_id, nombre FROM MetodoPago ORDER BY nombre ASC");
            $metodos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $this->sendResponse($metodos);
        } catch (\Exception $e) {
            $this->sendError($e);
        }
    }
}