<?php

class MetodosPago extends ActiveRecord {
    // Relación con ventas
    public function getVentas() {
        return (new Ventas())->find("metodos_pago_id = $this->id");

        return $this->has_many('Ventas');
    }

    public function initialize() {
        // Validación para el nombre del método de pago
//        $this->validates_presence_of("nombre");
//        $this->validates_length_of(
//            "nombre",
//            ['min' => 3, 'max' => 50, 'too_short' => 'El nombre debe tener al menos 3 caracteres', 'too_long' => 'El nombre debe tener máximo 50 caracteres']
//        );


    }
}
