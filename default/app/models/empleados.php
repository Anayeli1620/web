<?php
class Empleados extends ActiveRecord
{
    //static $db = 'PuntoVenta';

    public function initialize()
    {
        /*     * Nombre de la relacion
         * Nombre de la clase del modelo con quien se relaciona
         * Nombre de la columna de relacion
         */
        $this->has_many('ventas', 'model: Ventas', 'fk: empleados_id');
        $this->has_many('ventas');


        // Validación para el nombre
       // $this->validates_presence_of("nombre");
       // $this->validates_length_of("nombre",['min' => 3, 'max' => 50, 'too_short' => 'El nombre debe tener al menos 3 caracteres', 'too_long' => 'El nombre debe tener máximo 50 caracteres']);

        // Validación para el email
        //$this->validates_presence_of("email");
        //$this->validates_format_of("email", ['with' => '/^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6}$/','message' => 'Por favor ingresa un correo electrónico válido']);

        // Validación para el teléfono
        //$this->validates_presence_of("telefono");
        //$this->validates_format_of("telefono", ['with' => '/^\d{10}$/','message' => 'El teléfono debe tener 10 dígitos']);
    }

    public function getVentas()
    {
        return (new ventas())->find("empleados_id = $this->id");
    }
}
