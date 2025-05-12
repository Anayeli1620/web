<?php


class categorias extends ActiveRecord {
    // Relación con productos
    public function initialize() {
        // Relación con productos
        $this->has_many('producto', 'model: productos', 'fk: categorias_id');

        // Validación para nombre (presencia y longitud)
        //$this->validates_presence_of("nombre");
        //$this->validates_length_of("nombre",['min' => 15, 'max' => 40, 'too_short' => 'El nombre debe tener al menos 15 caracteres', 'too_long' => 'El nombre debe tener máximo 40 caracteres']);

        // Validación para id_categorias (presencia y formato si es necesario)
      //  $this->validates_presence_of("id_categorias");
        //  $this->validates_numericality_of("id_categorias", ['only_integer' => true,'greater_than' => 0,'message' => 'El id de la categoría debe ser un número entero mayor que 0']);

        // Validación para descripción
       // $this->validates_presence_of("descripcion");
       // $this->validates_length_of("descripcion", ['min' => 5, 'max' => 255, 'too_short' => 'La descripción debe tener al menos 5 caracteres', 'too_long' => 'La descripción debe tener máximo 255 caracteres']);
    }

    // Puedes agregar más validaciones si es necesario para otros campos
}
