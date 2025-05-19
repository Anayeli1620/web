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

    // Método para registrar un nuevo pago_item
    public function registrar()
    {
        if (input::hasPost('pagos_items')) {
            // Crear un nuevo registro de pago_item con los datos del formulario
            $pago_item = new PagosItems(Input::post('pagos_items'));
            if ($pago_item->create()) {
                Flash::valid("Pago registrado exitosamente");
                Input::delete();  // Limpiar los campos del formulario
            } else {
                Flash::error("Error al registrar el pago");
            }
        }
    }
}
