
<?php
class DetallesVentasController extends AppController
{
    public function index()
    {
        $this->detalles_ventas = (new Detalles_ventas())->find("columns: 
            detalles_ventas.*,
            ventas.fecha as venta_fecha,
            productos.nombre as producto_nombre,
            productos.precio as producto_precio
        ", "join: 
            LEFT JOIN ventas ON detalles_ventas.ventas_id = ventas.id
            LEFT JOIN productos ON detalles_ventas.productos_id = productos.id
        ");
    }

    public function show($id)
    {
        $this->detalles_ventas = (new Detalles_ventas())->find("columns:
            detalles_ventas.*,
            productos.nombre as producto_nombre,
            productos.precio as producto_precio_actual,
            ventas.fecha as venta_fecha,
            ventas.total as venta_total
        ", "join:
            LEFT JOIN productos ON detalles_ventas.productos_id = productos.id
            LEFT JOIN ventas ON detalles_ventas.ventas_id = ventas.id
        ", "conditions: ventas_id = {$id}");

        // Resto del código de show() permanece igual...


        // Obtener todos los detalles de la venta (productos asociados)
        $this->detalles_ventas = (new Detalles_ventas())->find("ventas_id = {$id}");
        // Si no se encuentran detalles, mostrar mensaje de error
        if (empty($this->detalles_ventas)) {
            Flash::error('No se encontraron productos para esta venta.');
        }
        // Inicializamos los arrays que usaremos para los gráficos
        $productos_mas_vendidos = [];
        $ventas_por_mes = [];
        // Obtener todos los productos vendidos y sus cantidades
        foreach ($this->detalles_ventas as $detalle) {
            $producto = $detalle->getProducto();  // Obtener el producto de cada detalle de venta
            if ($producto) {
                // Calcular el total de ingresos: precio * cantidad
                if (isset($productos_mas_vendidos[$producto->nombre])) {
                    $productos_mas_vendidos[$producto->nombre] += $detalle->cantidad;  // Incrementamos la cantidad del producto
                } else {
                    $productos_mas_vendidos[$producto->nombre] = $detalle->cantidad;  // Si el producto no existe, lo inicializamos
                }
            }
        }

        // Obtener ventas por mes (asumiendo que hay una relación con la tabla de Ventas)
        $ventas = (new Ventas())->find("id = {$id}");
        foreach ($ventas as $venta) {
            $mes = date('Y-m', strtotime($venta->fecha));  // Formato: Año-Mes (Ej. 2024-08)
            if (isset($ventas_por_mes[$mes])) {
                $ventas_por_mes[$mes] += $venta->total;  // Sumar las ventas para el mes
            } else {
                $ventas_por_mes[$mes] = $venta->total;  // Si el mes no existe, inicializamos con el total de la venta
            }
            // Obtener la última fecha de venta directamente desde la tabla ventas
            $ultima_fecha = $venta->fecha;  // La fecha de la venta
        }
        // Pasamos los datos a la vista
        $this->productos_mas_vendidos = $productos_mas_vendidos;  // Productos más vendidos
        $this->ventas_por_mes = $ventas_por_mes;  // Ventas por mes
        $this->ultima_fecha = $ultima_fecha;  // Última fecha de venta
        // Calculamos los totales para mostrar el resumen
        $total_unidades = 0;
        $total_ingresos = 0;
        foreach ($this->detalles_ventas as $detalle) {
            $total_unidades += $detalle->cantidad;
            $producto = $detalle->getProducto();  // Obtener el producto asociado al detalle
            if ($producto) {
                // Calcular el total de ingresos: precio * cantidad
                $total_ingresos += $producto->precio * $detalle->cantidad;
            }
        }
        // Pasamos los totales a la vista
        $this->total_unidades = $total_unidades;
        $this->total_ingresos = $total_ingresos;
    }
    public function registrar()
    {
        $this->empleados = (new Empleados())->find();

        // Si se envió el formulario con un empleado seleccionado
        if (Input::hasPost('empleado_id')) {
            $empleado_id = Input::post('empleado_id');
            Session::set('empleado_seleccionado', $empleado_id); // Guardamos en sesión
            Redirect::to("detalles_ventas/registrar"); // Redirigimos para evitar reenvío de formulario
        }

        // Si hay un empleado en sesión, cargamos sus ventas
        if (Session::has('empleado_seleccionado')) {
            $empleado_id = Session::get('empleado_seleccionado');
            $this->empleado_seleccionado = $empleado_id;

            $ventas = (new Ventas())->find("conditions: empleados_id = $empleado_id");
            $detalles = [];

            foreach ($ventas as $venta) {
                $detalles_venta = (new Detalles_ventas())->find("ventas_id = {$venta->id}");
                foreach ($detalles_venta as $detalle) {
                    $producto = $detalle->getProducto();
                    $detalles[] = [
                        'venta_id' => $venta->id,
                        'fecha' => $venta->fecha,
                        'producto' => $producto ? $producto->nombre : 'Producto eliminado',
                        'descripcion' => $detalle->descripcion,
                        'cantidad' => $detalle->cantidad,
                        'unitario' => $detalle->unitario,
                        'subtotal' => $detalle->subtotal,
                        'descuento' => $detalle->descuento,
                        'importe' => $detalle->importe,
                    ];
                }
            }

            $this->detalles_empleado = $detalles;
        }
    }

}