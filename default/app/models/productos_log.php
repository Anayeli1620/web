<?php

class ProductosLog extends ActiveRecord
{
    public function initialize()
    {
        // Relación con productos
        $this->belongs_to('producto', 'model: Productos', 'fk: producto_id');

//        // Validaciones para entradas y salidas
//        $this->validates_presence_of('producto_id');
//        $this->validates_numericality_of('entrada');
//        $this->validates_numericality_of('salida');
    }
}
