<?php
class Detalles_ventas extends ActiveRecord
{
    // Configuración de relaciones (solo una forma)
    public function initialize()
    {
        $this->belongs_to('ventas', 'model: Ventas', 'fk: ventas_id');
        $this->belongs_to('productos', 'model: Productos', 'fk: productos_id');
    }

    // Método before_save corregido
//    public function before_save()
//    {
//        // Validación básica de campos requeridos
//        if (empty($this->cantidad) || empty($this->unitario)) {
//            error_log("Error: Cantidad o precio unitario faltante");
//            return false;
//        }
//
//        // Cálculos seguros
//        $this->subtotal = (float)$this->cantidad * (float)$this->unitario;
//        $this->importe = (0);
//fghjkjhgfghfghjkjhgv
// esto se oculta de detalles_venats
//        // Asignación de fechas
//        $this->created_at = date('Y-m-d H:i:s');
//        $this->updated_in = date('Y-m-d H:i:s');
//
//        return true;
//    }

    // Atributos accesibles (completos)
    public $attr_accessible = [
        'ventas_id',
        'productos_id',
        'cantidad',
        'unitario',  // Cambiado de 'precio' a 'unitario'
        'descuento',
        'subtotal',
        'importe',
        'descripcion',
        'created_at',
        'updated_in'
    ];

    // Validaciones básicas (recomendado descomentar)
    public function validar()
    {
        $this->validates_presence_of("ventas_id");
        $this->validates_numericality_of("ventas_id", [
            'only_integer' => true,
            'greater_than' => 0
        ]);

        $this->validates_presence_of("productos_id");
        $this->validates_numericality_of("productos_id", [
            'only_integer' => true,
            'greater_than' => 0
        ]);

        $this->validates_presence_of("cantidad");
        $this->validates_numericality_of("cantidad", [
            'only_integer' => true,
            'greater_than' => 0
        ]);

        $this->validates_presence_of("unitario");
        $this->validates_numericality_of("unitario", [
            'greater_than' => 0
        ]);
    }

    // Método para obtener el producto relacionado
    public function getProducto() {
        return (new Productos())->find_first($this->productos_id);
    }
    // En tu modelo Detalles_ventas.php, reemplaza el método existente por:
    public function get_messages() {
        $errors = [];
        if (!empty($this->validation_errors)) {
            foreach ($this->validation_errors as $field => $message) {
                $errors[] = ucfirst($field) . ": $message";
            }
        }
        return $errors;
    }
    public function get_error_messages()
    {
        $messages = [];

        // 1. Errores de validación
        if (!empty($this->validation_errors)) {
            foreach ($this->validation_errors as $field => $error) {
                $messages[] = ucfirst(str_replace('_', ' ', $field)) . ": $error";
            }
        }

        // 2. Errores de base de datos
        if (!empty($this->error)) {
            $messages[] = $this->error;
        }

        // 3. Si no hay errores específicos, mensaje genérico
        if (empty($messages)) {
            $messages[] = "Error desconocido al guardar el registro";
        }

        return $messages;
    }
}