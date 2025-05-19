<?php
class Pagos extends ActiveRecord
{
    public function initialize()
    {
        // Relación con otras tablas (clientes, métodos de pago, etc.)
        $this->belongs_to('cliente', 'model: Clientes', 'fk: cliente_id');
        $this->belongs_to('metodo', 'model: MetodosPago', 'fk: metodo_pago_id');




//        // Validación para cliente_id (presencia y existencia en la tabla 'clientes')
//        $this->validates_presence_of("cliente_id");
//        $this->validates_numericality_of("cliente_id", [
//            'only_integer' => true,
//            'greater_than' => 0,
//            'message' => 'El cliente seleccionado no es válido'
//        ]);
//
//
//        // Validación para metodo_pago_id (presencia y existencia en la tabla 'metodos_pago')
//        $this->validates_presence_of("metodo_pago_id");
//        $this->validates_numericality_of("metodo_pago_id", [
//            'only_integer' => true,
//            'greater_than' => 0,
//            'message' => 'El método de pago seleccionado no es válido'
//        ]);
//
//        // Validación para comentario (presencia y longitud)
//        $this->validates_presence_of("cometario");
//        $this->validates_length_of("cometario", [
//            'min' => 5, 'max' => 255,
//            'too_short' => 'El comentario debe tener al menos 5 caracteres',
//            'too_long' => 'El comentario debe tener máximo 255 caracteres'
//        ]);
//    }

        // Relación con los detalles de los pagos (si es necesario en el futuro)
    }


}
