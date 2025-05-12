<?php


class ProductosController extends AppController
{

    public function index()
    {
        // Obtener todos los productos y pasarlos a la vista
        $this->productos = (new Productos())->find();
    }
    public function show($id) {
        View::template("plantilla");

        $this->productos = (new Productos())->find_first($id);

        if (!$this->productos) {
            Flash::error('Producto no encontrado');
            return Redirect::to('productos/index');
        }

        // Obtener categoría
        $this->categoria = $this->productos->getCategoria();

        // Obtener detalles de ventas relacionados al producto
        $detalles_ventas = (new DetallesVentas())->find("productos_id = {$this->productos->id}");

        $this->ventas = [];
        foreach ($detalles_ventas as $detalle) {
            $venta = (new Ventas())->find_first($detalle->ventas_id);
            if ($venta) {
                $this->ventas[] = $venta;
            }
        }

        // Cálculo de productos más vendidos
        $this->productos_mas_vendidos = [];
        foreach ($this->ventas as $venta) {
            $detalles = (new DetallesVentas())->find("ventas_id = {$venta->id}");
            foreach ($detalles as $detalle) {
                $producto = (new Productos())->find_first($detalle->productos_id);
                if ($producto) {
                    if (!isset($this->productos_mas_vendidos[$producto->nombre])) {
                        $this->productos_mas_vendidos[$producto->nombre] = 0;
                    }
                    $this->productos_mas_vendidos[$producto->nombre] += $detalle->cantidad;
                }
            }
        }

        // Cálculo de ventas por mes
        $this->ventas_por_mes = [];
        foreach ($this->ventas as $venta) {
            $mes = date('Y-m', strtotime($venta->fecha));
            if (!isset($this->ventas_por_mes[$mes])) {
                $this->ventas_por_mes[$mes] = 0;
            }
            $this->total_ventas = 0;
            foreach ($this->ventas as $venta) {
                $this->total_ventas += (float) $venta->total;
            }
        }

        // Total de ventas
        $this->total_ventas = array_sum(array_column($this->ventas, 'total'));
    }


    // Método para registrar un producto
    public function registrar() {
        if (Input::hasPost('productos')) {
            $producto = new Productos(Input::Post('productos'));

            if ($producto->create()) {
                Flash::valid("Producto registrado");
                Input::delete();
            }
        } else {
            Flash::error("Error al registrar el producto");
        }
    }
}
