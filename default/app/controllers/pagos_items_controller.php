<?php

class PagosItemsController extends AppController
{
    // Método para listar todos los pagos en el sistema
    public function index()
    {
        $this->pagos_items = (new PagosItems())->find("columns: 
        pagos_items.*,
        ventas.total as venta_total,
        clientes.nombre as cliente_nombre,
        metodos_pago.nombre as metodo_pago_nombre
    ", "join: 
        LEFT JOIN ventas ON pagos_items.venta_id = ventas.id
        LEFT JOIN clientes ON ventas.clientes_id = clientes.id
        LEFT JOIN pagos ON pagos_items.pago_id = pagos.id
        LEFT JOIN metodos_pago ON pagos.metodo_pago_id = metodos_pago.id
    ");
    }

    public function show($id)
    {
        $this->pago_item = (new PagosItems())->find_first("columns:
            pagos_items.*,
            ventas.total as venta_total,
            clientes.nombre as cliente_nombre,
            metodos_pago.nombre as metodo_pago_nombre,
            pagos.total as pago_total
        ", "join:
            LEFT JOIN ventas ON pagos_items.venta_id = ventas.id
            LEFT JOIN clientes ON ventas.clientes_id = clientes.id
            LEFT JOIN pagos ON pagos_items.pago_id = pagos.id
            LEFT JOIN metodos_pago ON pagos.metodo_pago_id = metodos_pago.id
        ", "conditions: pagos_items.id = {$id}");

        if (!$this->pago_item) {
            Flash::error('Pago no encontrado');
            return Redirect::to('pagos_items/index');
        }



        // Obtener los detalles de pago y venta relacionados
        $this->pago = $this->pago_item->pago;  // Relación con el pago
        $this->venta = $this->pago_item->venta;  // Relación con la venta
    }

    public function registrar()
    {
        // Obtener todas las ventas con información del cliente (incluyendo adeudo)
        $this->ventas = (new Ventas())->find("columns: 
        ventas.id, 
        ventas.total, 
        clientes.nombre as cliente_nombre,
        clientes.id as cliente_id,
        clientes.adeudo as cliente_adeudo
    ", "join: 
        LEFT JOIN clientes ON ventas.clientes_id = clientes.id
    ", "order: ventas.id DESC");

        if (Input::hasPost('pagos_items')) {
            $datos = Input::post('pagos_items');
            $venta_id = $datos['venta_id'] ?? null;
            $pago_id = $datos['pago_id'] ?? null;

            if (!$venta_id || !$pago_id) {
                Flash::error("Faltan datos de la venta o del pago.");
                return;
            }

            // Buscar la venta y el pago
            $venta = (new Ventas())->find_first($venta_id);
            $pago = (new Pagos())->find_first($pago_id);

            if (!$venta || !$pago) {
                Flash::error("Venta o pago no encontrados.");
                return;
            }

            // Obtener cliente
            $cliente = (new Clientes())->find_first($venta->clientes_id);

            // Calcular el monto_pagado desde el total del pago
            $monto_pagado = (float)$pago->total;

            // Validaciones
            if ($monto_pagado <= 0) {
                Flash::error("El monto del pago debe ser mayor a cero.");
                return;
            }

            if ($monto_pagado > $cliente->adeudo) {
                Flash::error("El monto pagado excede el adeudo actual del cliente ($".number_format($cliente->adeudo, 2).")");
                return;
            }

            // Crear el pagos_items
            $pago_item = new PagosItems([
                'venta_id' => $venta_id,
                'pago_id' => $pago_id,
                'antes' => $cliente->adeudo,
                'monto_pagado' => $monto_pagado,
                'adeudo' => max(0, $cliente->adeudo - $monto_pagado),
                'created_at' => date('Y-m-d H:i:s'),
            ]);

            // Actualizar adeudo del cliente
            $cliente->adeudo = $pago_item->adeudo;

            if ($pago_item->create() && $cliente->update()) {
                Flash::valid("¡Pago registrado correctamente!");
                return Redirect::to('pagos_items/index');
            }

            Flash::error("Error al registrar el pago");
        }

        $this->pagos_items = new PagosItems();
    }

    public function obtener_ultimo_pago($venta_id)
    {
        View::template(null);
        View::select(null, null);

        $ultimoPago = (new Pagos())->find_first([
            "columns" => "pagos.id as pago_id, pagos.total as total_pagado", // <-- Asegúrate de usar pagos.total
            "join" => "LEFT JOIN pagos_items ON pagos_items.pago_id = pagos.id",
            "conditions" => "pagos_items.venta_id = $venta_id",
            "order" => "pagos.fecha_creacion DESC"
        ]);

        echo json_encode([
            'pago_id' => $ultimoPago ? $ultimoPago->pago_id : '',
            'monto_pagado' => $ultimoPago ? $ultimoPago->total_pagado : '0.00'
        ]);
        return false;
    }

    // En el modelo PagosItems.php
    public function before_create() {
        $this->antes = (float)$this->antes;
        $this->monto_pagado = (float)$this->monto_pagado;
        $this->adeudo = (float)$this->adeudo;
    }

}
