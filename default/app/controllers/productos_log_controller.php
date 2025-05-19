<?php

class ProductosLogController extends AppController
{
    public function index()
    {
        $this->productos_log = (new ProductosLog())->find("columns: 
        productos_log.*,
        productos.nombre as producto_nombre,
        productos.precio as producto_precio,
        ventas.id as venta_id,
        ventas.fecha as venta_fecha
    ", "join: 
        LEFT JOIN productos ON productos_log.producto_id = productos.id
        LEFT JOIN ventas ON productos_log.venta_id = ventas.id
    ");
    }

    public function show($id)
    {
        $this->producto_log = (new ProductosLog())->find_first("columns:
            productos_log.*,
            productos.nombre as producto_nombre,
            productos.precio as producto_precio,
            ventas.id as venta_id,
            ventas.fecha as venta_fecha,
            ventas.total as venta_total
        ", "join:
            LEFT JOIN productos ON productos_log.producto_id = productos.id
            LEFT JOIN ventas ON productos_log.venta_id = ventas.id
        ", "conditions: productos_log.id = {$id}");

        if (!$this->producto_log) {
            Flash::error('Registro de producto no encontrado');
            return Redirect::to('productos_log/index');
        }

        // Calcular stock actual
        $this->stock_actual = $this->producto_log->entrada - $this->producto_log->salida;


    // ... (el método registrar permanece igual)

        // Buscar el producto log por ID
        $this->producto_log = (new ProductosLog())->find_first($id);

        if (!$this->producto_log) {
            Flash::error('Producto log no encontrado');
            return Redirect::to('productos_log/index');
        }

        // Obtener detalles adicionales, como el producto relacionado
        $this->producto = $this->producto_log->producto;  // Relación con el producto

        // Calcular las entradas y salidas acumuladas
        $entradas = $this->producto_log->entrada;
        $salidas = $this->producto_log->salida;

        // Pasamos los datos a la vista
        $this->entradas = $entradas;
        $this->salidas = $salidas;
    }

    public function registrar()
    {
        if (input::hasPost('productos_log')) {
            $productos_log = new ProductosLog(Input::post('productos_log'));
            if ($productos_log->create()) {
                Flash::valid("Producto log registrado");
                Input::delete();
            } else {
                Flash::error("Error al registrar el producto log");
            }
        }
    }
}
