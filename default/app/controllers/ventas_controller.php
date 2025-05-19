<?php
class VentasController extends AppController
{
    public function metodos_pago()
    {
        return $this->belongs_to('MetodoPago', 'metodo_pago_id');
    }

    public function index()
    {
        // Consulta con JOINS para obtener nombres relacionados
        $this->Ventas = (new Ventas())->find("columns: 
            ventas.*,
            clientes.nombre as cliente_nombre,
            empleados.nombre as empleado_nombre,
            metodos_pago.nombre as metodo_pago_nombre,
            usuarios.email as usuario_email
        ", "join: 
            LEFT JOIN clientes ON ventas.clientes_id = clientes.id
            LEFT JOIN empleados ON ventas.empleados_id = empleados.id
            LEFT JOIN metodos_pago ON ventas.metodos_pago_id = metodos_pago.id
            LEFT JOIN usuarios ON ventas.usuario_id = usuarios.id
        ");
    }

    // ... (el resto de tus métodos permanecen igual)


    public function nueva($cliente_id = null){
        $this->cliente = null;
        $this->venta =null;
        $search = Input::get('search');  // Obtener el término de búsqueda


        if($cliente_id != null){
            $this->cliente = (new Clientes())->find($cliente_id);
            $this->venta = (new Ventas())->get_carrito($cliente_id);
        }
        // Si hay un término de búsqueda
        if ($search) {
            // Realizar la búsqueda de productos (o clientes) que coincidan con el término
            $this->productos = (new Productos())->find("conditions: nombre LIKE '%{$search}%'");
        } else {
            // Si no hay búsqueda, obtener todos los productos
            $this->productos = (new Productos())->find();
        }
        if(Input::hasGet("cliente")){
            $cliente_id = Input::get("cliente");
            $this->cliente = (new Clientes())->find($cliente_id);
            $this->venta = (new Ventas())->crear($cliente_id);
            Redirect::toAction("nueva/{$cliente_id}");
        }

        if(Input::hasPost("producto_id")){
            $producto = (new Productos())->find(Input::post("producto_id"));
            $cantidad = 1;

            if ($this->venta) {
                $this->venta->add_item($producto, $cantidad);
                $detalle_venta = (new DetallesVentas())->find_first("ventas_id = {$this->venta->id} AND productos_id = {$producto->id}");

            } else {
                Flash::error("No se ha creado una venta. Selecciona un cliente primero.");
                return Redirect::toAction("nueva");
            }
        }

    }






    public function show($id)
    {
        $this->ventas = (new Ventas())->find_first($id);

        if (!$this->ventas) {
            Flash::error('Venta no encontrada');
            return Redirect::to('ventas/index');
        }

        $this->detalles_ventas = (new DetallesVentas())->find("ventas_id = {$this->ventas->id}");

        if (empty($this->detalles_ventas)) {
            Flash::error('No se encontraron productos vendidos para esta venta.');
        }

        $total_unidades = 0;
        $total_ingresos = 0;
        $ultima_fecha = null;
        $ventas_por_mes = [];
        $productos_mas_vendidos = [];

        if (!empty($this->detalles_ventas)) {
            foreach ($this->detalles_ventas as $detalle) {
                $producto = (new Productos())->find_first($detalle->productos_id);
                $total_unidades += $detalle->cantidad;
                $total_ingresos += $detalle->cantidad * $detalle->precio;
                $mes = date("Y-m", strtotime($this->ventas->fecha));

                if (!isset($ventas_por_mes[$mes])) {
                    $ventas_por_mes[$mes] = 0;
                }

                $ventas_por_mes[$mes] += $detalle->cantidad * $detalle->precio;

                if (!isset($productos_mas_vendidos[$producto->nombre])) {
                    $productos_mas_vendidos[$producto->nombre] = 0;
                }
                $productos_mas_vendidos[$producto->nombre] += $detalle->cantidad;
            }
            $ultima_fecha = $this->ventas->fecha;
        }

        $this->productos_mas_vendidos = $productos_mas_vendidos;
        $this->total_unidades = $total_unidades;
        $this->total_ingresos = $total_ingresos;
        $this->ultima_fecha = $ultima_fecha;
        $this->ventas_por_mes = $ventas_por_mes;
    }

    public function registrar()
    {
        if (Input::hasPost('ventas') && Input::hasPost('detalles_ventas')) {
            $venta = new Ventas(Input::post('ventas'));

            if ($venta->create()) {
                Flash::valid("Venta registrada correctamente");
                $id_venta = $venta->id;
                $detalles = Input::post('detalles_ventas');

                foreach ($detalles as $detalle) {
                    $nuevo_detalle = new DetallesVentas();
                    $nuevo_detalle->ventas_id = $id_venta;
                    $nuevo_detalle->productos_id = $detalle['productos_id'];
                    $nuevo_detalle->cantidad = $detalle['cantidad'];
                    $nuevo_detalle->precio = $detalle['precio']; // asegúrate de enviar esto desde el formulario

                    if ($nuevo_detalle->create()) {
                        // Opcional: puedes validar stock aquí si quieres
                    }
                }

            }
        }
    }
//cliente= x(seleccion de clientes)



    public function registra()
    {
        if (input::hasPost('ventas')) {
            $venta = new ventas(Input::post('ventas'));
            if ($venta->create()) {
                Flash::valid("venta registrado");
                Input::delete();
            }
        } else {
            Flash::error("Error al registrar el venta");
        }

    }
}







