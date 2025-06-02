<?php

class Detalles_ventas extends ActiveRecord {
    // Relación con productos
    public function getProducto() {
        return (new Productos())->find_first($this->productos_id);  // Accedemos al producto a través de productos_id
    }
    public function initialize()
    {
        $this->belongs_to('ventas', 'model: Ventas', 'fk: ventas_id');
        $this->belongs_to('productos', 'model: Productos', 'fk: productos_id');

    }

    public $belongs_to = [
        'venta' => ['model' => 'Ventas', 'foreign_key' => 'ventas_id'],
        'producto' => ['model' => 'Productos', 'foreign_key' => 'productos_id']
    ];


    public function before_save()
    {
        // Calcular subtotal automáticamente
        $this->subtotal = $this->cantidad * $this->precio;
        $this->importe = $this->subtotal; // ← aquí se arregla todo

        return true;


    }
    public function get_messages()
    {
        $errors = [];
        if (!empty($this->validation_errors)) {
            foreach ($this->validation_errors as $field => $message) {
                $errors[] = ucfirst(str_replace('_', ' ', $field)) . ": $message";
            }
        }
        return $errors;
    }
    public $attr_accessible = ['ventas_id', 'productos_id', 'cantidad', 'precio', 'unitario', 'descuento', 'subtotal', 'importe', 'descripcion'];

    //public function initialize() {
        // Validación para ventas_id (presencia y existencia en la tabla de ventas)
      //  $this->validates_presence_of("ventas_id");
     //   $this->validates_numericality_of("ventas_id", ['only_integer' => true,'greater_than' => 0,'message' => 'El id de la venta debe ser un número entero mayor que 0']);


        // Validación para productos_id (presencia y existencia en la tabla de productos)
        //  $this->validates_presence_of("productos_id");
       // $this->validates_numericality_of("productos_id", ['only_integer' => true,'greater_than' => 0,'message' => 'El id del producto debe ser un número entero mayor que 0']);


        // Validación para la descripción
      //  $this->validates_presence_of("descripcion");
       // $this->validates_length_of("descripcion", ['min' => 5, 'max' => 255,'too_short' => 'La descripción debe tener al menos 5 caracteres','too_long' => 'La descripción debe tener máximo 255 caracteres']);

        // Validación para la cantidad
        //$this->validates_presence_of("cantidad");
       // $this->validates_numericality_of("cantidad", ['only_integer' => true,'greater_than' => 0,'message' => 'La cantidad debe ser un número entero mayor que 0']);

        // Validación para el importe
       // $this->validates_presence_of("importe");
        //$this->validates_numericality_of("importe", ['greater_than' => 0,'message' => 'El importe debe ser un número mayor que 0']);
    //}
}

