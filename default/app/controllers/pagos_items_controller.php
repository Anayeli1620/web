<?php

class PagosItemsController extends AppController
{
    // Método para listar todos los pagos en el sistema
    public function index()
    {
        // Obtener todos los registros de pagos_items
        $this->pagos_items = (new PagosItems())->find();
    }

    // Método para mostrar los detalles de un pago específico
    public function show($id)
    {
        // Buscar el registro de pago_item por ID
        $this->pago_item = (new PagosItems())->find_first($id);

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
