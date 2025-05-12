<?php

class PagosItems extends ActiveRecord
{
    // Relación con pagos
    public function initialize()
    {
        $this->belongs_to('pago', 'model: Pagos', 'fk: pago_id');  // Relación con pagos
        $this->belongs_to('venta', 'model: Ventas', 'fk: venta_id');  // Relación con ventas

        // Validaciones de los campos
//        $this->validates_presence_of('pago_id', 'El ID del pago es obligatorio.');
//        $this->validates_presence_of('venta_id', 'El ID de la venta es obligatorio.');
//        $this->validates_numericality_of('antes', [
//            'only_integer' => true,
//            'message' => 'El valor de "antes" debe ser un número entero.'
//        ]);
//        $this->validates_numericality_of('monto_pagado', [
//            'only_integer' => true,
//            'message' => 'El monto pagado debe ser un número entero.'
//        ]);
//        $this->validates_numericality_of('adeudo', [
//            'only_integer' => true,
//            'message' => 'El adeudo debe ser un número entero.'
//        ]);
    }
}
