<?php
class Pagositems extends ActiveRecord
{
    public function initialize()
    {
        $this->belongs_to('pago', 'model: Pagos', 'fk: pago_id');
        $this->belongs_to('venta', 'model: Ventas', 'fk: venta_id');

        $this->validates_presence_of('pago_id', 'message: El ID del pago es obligatorio');
        $this->validates_presence_of('venta_id', 'message: La venta es obligatoria');
        $this->validates_numericality_of('antes', 'message: Debe ser un número válido');
        $this->validates_numericality_of('monto_pagado', 'message: Debe ser un número válido');
        $this->validates_numericality_of('adeudo', 'message: Debe ser un número válido');
    }
}
