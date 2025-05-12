<?php

class productos extends ActiveRecord{
    // Relación con la categoría
    public function initialize() {
        $this->belongs_to('categoria', 'model: Categorias', 'fk: categorias_id');

//
//        $this->validates_presence_of("nombre");
//
//        $this->validates_length_of(
//            "nombre",
//            ['min' => 15, 'max' => 40, 'too_short' => 'El nombre debe tener al menos 15 caracteres', 'too_long' => 'El nombre debe tener máximo 40 caracteres']
//);
//
//
//
//        $this->validates_presence_of("nombre");
//        $this->validates_presence_of("precio");
//        $this->validates_presence_of("stock");
//        $this->validates_presence_of("categorias_id");
}

    public function bajastock($id, $cantidad) {
        $sql = "UPDATE productos SET stock = GREATEST(0, stock - $cantidad) WHERE id = $id";
        (new Productos())->sql($sql);
    }
    // Obtener la categoría
    public function getCategoria(){
        return (new categorias())->find_first($this->categorias_id);
    }

    // Relación con las ventas
    public function getVentas() {
        return $this->has_many('ventas', 'fk: producto_id'); // Aquí 'producto_id' debe ser la columna de la tabla 'ventas' que hace referencia al 'producto'
    }
}
