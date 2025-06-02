<?php
class Ventas extends ActiveRecord
{
    // Relaciones belongs_to
    public $belongs_to = [
        'cliente' => [
            'model' => 'Clientes',
            'foreign_key' => 'clientes_id'
        ],
        'empleado' => [
            'model' => 'Empleados',
            'foreign_key' => 'empleados_id'
        ],
        'metodo_pago' => [  // Relación con método de pago
            'model' => 'Metodos_pago',
            'foreign_key' => 'metodos_pago_id'
        ]
    ];

    // Relaciones has_many
    public $has_many = [
        'detalles' => [
            'model' => 'Detalles_ventas',
            'foreign_key' => 'ventas_id'
        ]
    ];
    public $columns = [
        'id',
        'clientes_id',
        'empleados_id',
        'metodos_pago_id',
        'productos_id',
        'usuario_id',
        'fecha',
        'total',
        'por_pagar',
        'status',
        'monto_pagado',
        'cambio',
        'comentario',
        'forma_pago',
        'cancelada',
        'created_at',  // era created_in een dado caso
        'updated_in',
    ];

    // Validaciones antes de guardar
    public function before_save()
    {
        $errors = [];
        if (empty($this->clientes_id)) {
            $errors['clientes_id'] = 'Debe seleccionar un cliente';
        }
        if (empty($this->empleados_id)) {
            $errors['empleados_id'] = 'Empleado no asignado';
        }
        if (empty($this->metodos_pago_id)) {
            $errors['metodos_pago_id'] = 'Debe seleccionar un método de pago';
        }
        if (empty($this->fecha)) {
            $errors['fecha'] = 'Fecha requerida';
        }
        if (!isset($this->total) || $this->total <= 0) {
            $errors['total'] = 'Total de venta debe ser mayor a cero';
        }
        if (!isset($this->por_pagar)) {
            $this->por_pagar = 0;
        }
        if (!empty($errors)) {
            $this->validation_errors = $errors;
            error_log("Errores de validación Venta: " . json_encode($errors));
            return false;
        }
        return true;


    }

    public function before_update()
    {
        $this->updated_in = date('Y-m-d H:i:s');
    }


    // Mensajes de error para mostrar
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

    // Inicializar datos al crear venta
    public function before_create()
    {
        if (empty($this->fecha)) {
            $this->fecha = date('Y-m-d H:i:s');
        }
        if (empty($this->status)) {
            $this->status = 'carrito';
        }
        if (empty($this->usuario_id)) {
            $this->usuario_id = Auth::get('id');
        }
        if (empty($this->empleados_id)) {
            $this->empleados_id = Auth::get('id');
        }
        if (!isset($this->total)) {
            $this->total = 0;
        }
        if (!isset($this->por_pagar)) {
            $this->por_pagar = 0;
        }
        if (!isset($this->cancelada)) {
            $this->cancelada = 0;
        }


    }
    public function find_all_by_cliente($cliente_id)
    {
        return $this->find("conditions: cliente_id = $cliente_id AND por_pagar > 0");
    }

}
