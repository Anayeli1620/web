<?php

class EmpleadosController extends AppController {

    public function index() {
        // Obtener todos los empleados y pasarlos a la vista
        $this->empleados = (new Empleados())->find();
    }

    public function show($id) {
        // Buscar el empleado por su ID
        $this->empleados = (new Empleados())->find_first($id);

        // Validar si el empleado
        // existe
        if (!$this->empleados) {
            Flash::error('Empleado no encontrado');
            return Redirect::to('empleados/index');
        }

        // Obtener todas las ventas del empleado
        $ventas = $this->empleados->getVentas();

        // Variables inicializadas claramente para estadísticas
        $total_unidades = 0;
        $total_ingresos = 0;
        $ultima_fecha = null;
        $productos_mas_vendidos = [];
        $ventas_por_mes = [];

        // Comprobar primero si hay ventas
        if (!empty($ventas)) {
            foreach ($ventas as $venta) {
                $detalleVenta = (new Detalles_ventas())->find_first("ventas_id = {$venta->id}");

                // Validar que el detalle de la venta exista
                if (!$detalleVenta) {
                    Flash::warning("La venta con ID {$venta->id} no tiene detalles asociados.");
                    continue;
                }

                $producto = (new Productos())->find_first($detalleVenta->productos_id);

                // Validar que el producto exista
                if (!$producto) {
                    Flash::warning("Detalle de venta con ID {$detalleVenta->id} no tiene producto asociado.");
                    continue;
                }

                $total_unidades += $detalleVenta->cantidad;
                $total_ingresos += ($producto->precio * $detalleVenta->cantidad);

                // Actualizar última fecha correctamente
                if ($ultima_fecha === null || $venta->fecha > $ultima_fecha) {
                    $ultima_fecha = $venta->fecha;
                }

                // Contar productos más vendidos
                if (!isset($productos_mas_vendidos[$producto->nombre])) {
                    $productos_mas_vendidos[$producto->nombre] = 0;
                }
                $productos_mas_vendidos[$producto->nombre] += $detalleVenta->cantidad;

                // Ventas por mes
                $mes = date("Y-m", strtotime($venta->fecha));
                if (!isset($ventas_por_mes[$mes])) {
                    $ventas_por_mes[$mes] = 0;
                }
                $ventas_por_mes[$mes] += ($producto->precio * $detalleVenta->cantidad);
            }

            if ($ultima_fecha === null) {
                $ultima_fecha = 'Sin ventas';
            }
        } else {
            $ultima_fecha = 'Sin ventas';
        }

        // Pasar claramente los datos procesados a la vista
        $this->total_unidades = $total_unidades;
        $this->total_ingresos = $total_ingresos;
        $this->ultima_fecha = $ultima_fecha;
        $this->productos_mas_vendidos = $productos_mas_vendidos;
        $this->ventas_por_mes = $ventas_por_mes;
    }

    public function registrar(){
        if(input::hasPost('empleados')){
            $empleado=new empleados(Input::post('empleados'));
            if($empleado->create()){
                Flash::valid("empleado registrado");
                Input::delete();
            }
        }
        else{
            Flash::error("Error al registrar el empleado");
            }

        }
}
