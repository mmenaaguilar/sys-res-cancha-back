<?php
// app/Services/RolService.php

namespace App\Services;

use App\Repositories\RolRepository;

class RolService
{
    private RolRepository $rolRepository;

    public function __construct()
    {
        $this->rolRepository = new RolRepository();
    }

    public function getAllRolesCombo(): array
    {
        return $this->rolRepository->getAllRolesCombo();
    }
}
