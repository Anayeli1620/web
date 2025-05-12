<?php

class ProductosLogController extends AppController
{
    public function index()
    {
        // Obtener todos los registros de productos_log
        $this->productos_log = (new ProductosLog())->find();
    }

    public function show($id)
    {
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
