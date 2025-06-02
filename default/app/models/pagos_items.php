<?php


class PagosItems extends ActiveRecord
{
    public function initialize()
    {
        $this->belongs_to('pago', 'model: Pagos', 'fk: pago_id');
        $this->belongs_to('venta', 'model: Ventas', 'fk: venta_id');
        $this->has_many('pagos_items');


        // Validaciones
        $this->validates_presence_of('pago_id', 'message: El ID del pago es obligatorio');
        $this->validates_presence_of('venta_id', 'message: La venta es obligatoria');
        $this->validates_numericality_of('antes', 'message: Debe ser un número válido');
        $this->validates_numericality_of('monto_pagado', 'message: Debe ser un número válido');
        $this->validates_numericality_of('adeudo', 'message: Debe ser un número válido');
    }

    // Opcional: método para obtener información de la venta
    public function getVentaInfo()
    {
        return (new Ventas())->find_first($this->venta_id);
    }
}