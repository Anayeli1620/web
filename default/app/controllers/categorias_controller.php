<?php

class CategoriasController extends AppController
{

    public function index()
    {
        // Obtener todas las categorías y pasarlas a la vista
        $this->categorias = (new Categorias())->find();
    }
    public function show($id) {
        // Buscar la categoría por ID
        $this->categorias = (new Categorias())->find_first($id);

        // Si la categoría no existe, redirigir a la lista de categorías
        if (!$this->categorias) {
            Flash::error('Categoría no encontrada');
            return Redirect::to('categorias/index');
        }

        // Obtener los productos relacionados con la categoría
        $this->productos = $this->categorias->getProducto();  // Obtener todos los productos relacionados con la categoría

        // Calcular los productos más vendidos y las ventas por mes para las gráficas
        $productos_mas_vendidos = [];
        $ventas_por_mes = [];

        foreach ($this->productos as $producto) {
            // Acumulamos el total de productos vendidos por nombre
            if (!isset($productos_mas_vendidos[$producto->nombre])) {
                $productos_mas_vendidos[$producto->nombre] = 0;
            }
            $productos_mas_vendidos[$producto->nombre] += $producto->id;

            // Calcular ventas por mes
            foreach ($producto->ventas as $venta) {
                $mes = date('Y-m', strtotime($venta->fecha));
                if (!isset($ventas_por_mes[$mes])) {
                    $ventas_por_mes[$mes] = 0;
                }
                $ventas_por_mes[$mes] += $venta->total;
            }
        }

        // Pasamos los datos a la vista
        $this->productos_mas_vendidos = $productos_mas_vendidos;
        $this->ventas_por_mes = $ventas_por_mes;


        // Pasar los totales a la vista
        $this->total_unidades = $total_unidades;
    }



    public function registrar()
    {
        if (input::hasPost('categorias')) {
            $categoria = new categorias(Input::post('categorias'));
            if ($categoria->create()) {
                Flash::valid("Categoría registrada");
                Input::delete();
            }
        } else {
            Flash::error("Error al registrar la categoría");
        }
    }
    }