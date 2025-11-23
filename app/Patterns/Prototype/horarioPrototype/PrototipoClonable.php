<?php

namespace App\Patterns\Prototype\horarioPrototype;

interface PrototipoClonable
{
    /**
     * Clona el objeto.
     *
     * @param array $overrides Valores opcionales para sobreescribir en la copia
     * @return static
     */
    public function clone(array $overrides = []);
}
