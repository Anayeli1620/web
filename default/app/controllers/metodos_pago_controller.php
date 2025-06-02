<?php

class MetodosPagoController extends AppController
{
    public function index()
    {
        // Obtener todos los métodos de pago
        $this->metodos_pago = (new MetodosPago())->find();
    }

    public function show($id)
    {
        $this->metodos_pago = (new MetodosPago())->find_first($id);

        if (!$this->metodos_pago) {
            Flash::error('Método de pago no encontrado');
            return Redirect::to('metodos_pago/index');
        }

        // Obtener todas las ventas asociadas a este método de pago
        $this->ventas = (new MetodosPago())->find_first($id)->getVentas();

        if (!$this->ventas) {
            $this->ventas = []; // Asegurarse de que sea un array vacío si no hay ventas
        }

        // Calcular el total de ingresos generados
        $total_ingresos = 0;
        foreach ($this->ventas as $venta) {
            $total_ingresos += $venta->total ?: 0; // Asegurarse de que el total no sea NULL
        }

        // Calcular los productos más vendidos
        $this->productos_mas_vendidos = $this->calcularProductosMasVendidos();

        // Calcular las ventas por mes
        $this->ventas_por_mes = $this->calcularVentasPorMes();

        // Pasar los totales a la vista
        $this->total_ingresos = $total_ingresos;
    }

    // Función para calcular los productos más vendidos
    private function calcularProductosMasVendidos()
    {
        $productos_mas_vendidos = [];

        foreach ($this->ventas as $venta) {
            // Cargar detalles de la venta manualmente
            $detalles = (new Detalles_ventas())->find("ventas_id = {$venta->id}");

            foreach ($detalles as $detalle) {
                // Cargar el producto manualmente
                $producto = (new Productos())->find_first($detalle->productos_id);

                if ($producto) {
                    if (!isset($productos_mas_vendidos[$producto->nombre])) {
                        $productos_mas_vendidos[$producto->nombre] = 0;
                    }
                    $productos_mas_vendidos[$producto->nombre] += $detalle->cantidad;
                }
            }
        }

        return $productos_mas_vendidos;
    }


    // Función para calcular las ventas por mes
    private function calcularVentasPorMes()
    {
        $ventas_por_mes = [];
        foreach ($this->ventas as $venta) {
            $mes = date('Y-m', strtotime($venta->fecha));  // Agrupar por año y mes
            if (!isset($ventas_por_mes[$mes])) {
                $ventas_por_mes[$mes] = 0;
            }
            if ($venta->total !== NULL) {
                $ventas_por_mes[$mes] += (float)$venta->total;
            }
        }
        return $ventas_por_mes;
    }

    public function registrar()
    {
        if (Input::hasPost('metodos_pago')) {
            $metodos_pago = new MetodosPago(Input::post('metodos_pago'));
            if ($metodos_pago->create()) {
                Flash::valid("Método de pago registrado");
                Input::delete();
            }
        } else {
            Flash::error("Error al registrar el método de pago");
        }
    }
}
