<?php
class VentasController extends AppController
{
    public function index()
    {
        try {
            $this->ventas = (new Ventas())->find("columns: 
                ventas.*,
                clientes.nombre as cliente_nombre,
                empleados.nombre as empleado_nombre,
                metodos_pago.nombre as metodo_pago_nombre
            ", "join: 
                LEFT JOIN clientes ON ventas.clientes_id = clientes.id
                LEFT JOIN empleados ON ventas.empleados_id = empleados.id
                LEFT JOIN metodos_pago ON ventas.metodos_pago_id = metodos_pago.id
            ", "order: ventas.fecha DESC");
        } catch (Exception $e) {
            Flash::error("Error al cargar ventas: " . $e->getMessage());
        }
    }
    public function show($id)
    {
        // venta principal (solo una)
        $venta = (new Ventas())->find_first((int) $id);
        $this->venta = (new Ventas())->find($id); // en singular


        if (!$venta) {
            Flash::error('Venta no encontrada');
            return Redirect::to('ventas/index');
        }

        // detalles de esa venta
        $detalles_ventas = (new Detalles_ventas())->find("conditions: ventas_id = $id");

        // Cálculos
        $total_unidades = 0;
        $total_ingresos = 0;
        foreach ($detalles_ventas as $detalle) {
            $producto = (new Productos())->find_first($detalle->productos_id);
            $cantidad = $detalle->cantidad;
            $precio = $producto ? $producto->precio : 0;
            $total_unidades += $cantidad;
            $total_ingresos += $precio * $cantidad;
        }

        // Productos más vendidos
        $productos_mas_vendidos = [];
        $detalles = (new Detalles_ventas())->find();
        foreach ($detalles as $d) {
            $productos_mas_vendidos[$d->productos_id] = ($productos_mas_vendidos[$d->productos_id] ?? 0) + $d->cantidad;
        }

        $productos_nombres = [];
        foreach (array_keys($productos_mas_vendidos) as $prod_id) {
            $producto = (new Productos())->find_first($prod_id);
            if ($producto) {
                $productos_nombres[$producto->nombre] = $productos_mas_vendidos[$prod_id];
            }
        }

        // Ventas por mes
        $ventas_por_mes = [];
        $todas_ventas = (new Ventas())->find();
        foreach ($todas_ventas as $v) {
            $mes = date('Y-m', strtotime($v->fecha));
            $ventas_por_mes[$mes] = ($ventas_por_mes[$mes] ?? 0) + $v->total;
        }

        // datos a la vista
        $this->venta = $venta;
        $this->detalles_ventas = $detalles_ventas;
        $this->total_unidades = $total_unidades;
        $this->total_ingresos = $total_ingresos;
        $this->ultima_fecha = $venta->fecha;
        $this->productos_mas_vendidos = $productos_nombres;
        $this->ventas_por_mes = $ventas_por_mes;
    }


    public function registrar($cliente_id = null)
    {
        // Obtener el cliente actual de la sesión
        $cliente_actual = Session::get('cliente_id');

        if ($cliente_id !== null && $cliente_id != $cliente_actual) {
            Session::set('cliente_id', $cliente_id);
            $cliente_seleccionado = $cliente_id;
        } else {
            $cliente_seleccionado = $cliente_actual;
        }

        $carrito_key = 'carrito_venta_cliente_' . $cliente_seleccionado;
        if (!Session::has($carrito_key)) {
            Session::set($carrito_key, []);
        }
        $carrito = $this->obtenerCarritoCliente($cliente_seleccionado);

// Debug: Verificar contenido del carrito
        error_log("Contenido del carrito para cliente $cliente_seleccionado: " . print_r($carrito, true));
        if ($cliente_id !== null) {
            Session::set('cliente_id', $cliente_id);
        }
        if ($accion === 'finalizar') {
            $clientes_id = $ventas_post['clientes_id'] ?? null;
            $empleados_id = $ventas_post['empleados_id'] ?? Session::get('empleado_id');
            $metodos_pago_id = $ventas_post['metodos_pago_id'] ?? null;
            $comentario = $ventas_post['comentario'] ?? null;

            // Obtener el carrito actual
            $carrito_key = 'carrito_venta_cliente_' . $clientes_id;
            $carrito = Session::get($carrito_key, []);

            if (!$clientes_id || !$empleados_id || empty($carrito) || !$metodos_pago_id) {
                Flash::error("Faltan datos para finalizar la venta o el carrito está vacío");
                error_log("Carrito vacío al finalizar venta. Contenido: " . print_r($carrito, true));
                return Redirect::to("ventas/registrar/{$clientes_id}");
            }

        }
        $buscar_cliente = Input::get('buscar_cliente') ?? '';
        $buscar_producto = Input::get('buscar_producto') ?? '';

        $this->clientes = $buscar_cliente
            ? (new Clientes())->find("activo = 1 AND nombre LIKE '%$buscar_cliente%'")
            : (new Clientes())->find("activo = 1");

        $this->productos = $buscar_producto
            ? (new Productos())->find("stock > 0 AND nombre LIKE '%$buscar_producto%'")
            : (new Productos())->find("stock > 0");

        $this->metodos_pago = (new MetodosPago())->find();

        $cliente_seleccionado = Session::get('cliente_id');
        $this->cliente_seleccionado = $cliente_seleccionado;
        $carrito_key = 'carrito_venta_cliente_' . $cliente_seleccionado;

        if (!Session::has($carrito_key)) {
            Session::set($carrito_key, []);
        }

        $carrito = Session::get($carrito_key);

        if (Input::hasPost('accion')) {
            $accion = Input::post('accion');
            $ventas_post = Input::post('ventas') ?? [];

            if (!empty($ventas_post['clientes_id'])) {
                Session::set('cliente_id', $ventas_post['clientes_id']);
                $cliente_seleccionado = $ventas_post['clientes_id'];
                $this->cliente_seleccionado = $cliente_seleccionado;
                $carrito_key = 'carrito_venta_cliente_' . $cliente_seleccionado;
                if (!Session::has($carrito_key)) {
                    Session::set($carrito_key, []);
                }
                $carrito = Session::get($carrito_key);
            }

            if ($accion === 'agregar') {
                $producto_id = Input::post('producto_id');
                $cantidad = (int)Input::post('cantidad');

                if ($producto_id && $cantidad > 0) {
                    $producto = (new Productos())->find_first($producto_id);
                    if ($producto && $producto->stock >= $cantidad) {
                        $found = false;
                        foreach ($carrito as &$item) {
                            if ($item['producto']->id == $producto->id) {
                                $item['cantidad'] += $cantidad;
                                $item['subtotal'] = $item['cantidad'] * $producto->precio;
                                $found = true;
                                break;
                            }
                        }
                        unset($item);

                        if (!$found) {
                            $carrito[] = [
                                'producto' => $producto,
                                'cantidad' => $cantidad,
                                'subtotal' => $cantidad * $producto->precio,
                            ];
                        }

                        Session::set($carrito_key, $carrito);
                        Flash::valid("Producto agregado al carrito");
                    } else {
                        Flash::error("Producto no válido o stock insuficiente");
                    }
                } else {
                    Flash::error("Debe seleccionar producto y cantidad válida");
                }
                return Redirect::to("ventas/registrar/{$cliente_seleccionado}");
            }

            if ($accion === 'eliminar') {
                $index = Input::post('index');
                if (isset($carrito[$index])) {
                    unset($carrito[$index]);
                    $carrito = array_values($carrito);
                    Session::set($carrito_key, $carrito);
                    Flash::valid("Producto eliminado del carrito");
                } else {
                    Flash::error("Índice de producto inválido");
                }
                return Redirect::to("ventas/registrar/{$cliente_seleccionado}");
            }

            if ($accion === 'finalizar') {
                $clientes_id = $ventas_post['clientes_id'] ?? null;
                $empleados_id = $ventas_post['empleados_id'] ?? Session::get('empleado_id');
                $metodos_pago_id = $ventas_post['metodos_pago_id'] ?? null;
                $comentario = $ventas_post['comentario'] ?? null;

                if (!$clientes_id || !$empleados_id || empty($carrito) || !$metodos_pago_id) {
                    Flash::error("Faltan datos para finalizar la venta o el carrito está vacío");
                    return Redirect::to("ventas/registrar/{$cliente_seleccionado}");
                }

                $total_carrito = array_reduce($carrito, fn($carry, $item) => $carry + $item['subtotal'], 0);
                $metodo = (new MetodosPago())->find_first($metodos_pago_id);
                $nombre_metodo = $metodo ? $metodo->nombre : 'pendiente';

                $venta = new Ventas();

                if (!empty($carrito)) {
                    $venta->productos_id = $carrito[0]['producto']->id;
                } else {
                    $venta->productos_id = null;
                }

                $venta->usuario_id = Auth::get('id');
                $venta->clientes_id = $clientes_id;
                $venta->metodos_pago_id = $metodos_pago_id;
                $venta->fecha = date('Y-m-d H:i:s');
                $venta->total = $total_carrito;

//  Obtener el adeudo actual del cliente y sumarlo
                $cliente = (new Clientes())->find_first($clientes_id);
                if ($cliente) {
                    $nuevo_adeudo = (float)$cliente->adeudo + (float)$total_carrito;
                    $venta->por_pagar = $nuevo_adeudo;
                }

                $venta->comentario = $comentario;
                $venta->forma_pago = $nombre_metodo ?? 'PPP';
                $venta->cancelada = 0;
                $venta->status = 'completada';
                $venta->created_at = date('Y-m-d H:i:s');
                $venta->updated_in = date('Y-m-d H:i:s');

                if (!$venta->save()) {
                    $errors = $venta->get_messages();
                    Flash::error("Error al guardar venta: " . implode(", ", $errors));
                    return Redirect::to("ventas/registrar/{$cliente_seleccionado}");
                }
//  REGISTRAR DETALLES DE VENTA
                foreach ($carrito as $item) {
                    $detalle = new Detalles_ventas();
                    $detalle->ventas_id = $venta->id;
                    $detalle->productos_id = $item['producto']->id;
                    $detalle->descripcion = $item['producto']->nombre;
                    $detalle->cantidad = $item['cantidad'];
                    $detalle->unitario = $item['producto']->precio;
                    $detalle->descuento = 0;

                    // Asignar subtotal explícitamente (por si el before_save falla)
                    $detalle->subtotal = $item['cantidad'] * $item['producto']->precio;

                    if (!$detalle->save()) {
                        // Manejo mejorado de errores
                        $errorInfo = $detalle->get_error_messages();
                        error_log("Error al guardar detalle: ".print_r($errorInfo, true));
                        Flash::error("Error con producto {$item['producto']->nombre}: ".implode(", ", $errorInfo));

                        // Opcional: Rollback manual si estás usando transacciones
                        // ActiveRecord::rollback();
                        // return Redirect::to("ventas/registrar/{$cliente_seleccionado}");
                    }
                }
                foreach ($carrito as $item) {

                    // 🔻 Descontar stock
                    $producto = (new Productos())->find_first($item['producto']->id);
                    if ($producto) {
                        $producto->stock -= $item['cantidad'];
                        $producto->save();
                    }
                }

                $cliente = (new Clientes())->find_first($clientes_id);
                if ($cliente) {
                    $cliente->adeudo = (float)$cliente->adeudo + (float)$total_carrito;
                    $cliente->save();
                }

                Session::delete($carrito_key);
                Flash::valid("Venta registrada con éxito");
                return Redirect::to('ventas/index');
            }
        }

        $this->carrito = $carrito;
        $this->total_carrito = array_reduce($carrito, fn($carry, $item) => $carry + $item['subtotal'], 0);
        $this->buscar_cliente = $buscar_cliente;
        $this->buscar_producto = $buscar_producto;
    }
    private function obtenerCarritoCliente($cliente_id) {
        $carrito_key = 'carrito_venta_cliente_' . $cliente_id;

        if (!Session::has($carrito_key)) {
            Session::set($carrito_key, []);
        }

        return Session::get($carrito_key);
    }

    public function guardarCarritoAction() {
        if ($this->request->isPost()) {
            $data = json_decode(file_get_contents('php://input'), true);

            try {
                Session::set('carrito_temporal_' . $data['cliente_actual'], $data['carrito']);
                return json_encode([
                    'success' => true,
                    'message' => 'Carrito guardado temporalmente'
                ]);
            } catch (Exception $e) {
                return json_encode([
                    'success' => false,
                    'message' => $e->getMessage()
                ]);
            }
        }
    }

    public function cancelar($cliente_id = null)
    {
        if ($cliente_id !== null) {
            $carrito_key = 'carrito_venta_cliente_' . $cliente_id;
            Session::delete($carrito_key);
            Session::delete('cliente_id');
            Flash::valid("Venta cancelada y carrito limpiado");
        } else {
            Flash::error("Cliente no especificado para cancelar");
        }
        return Redirect::to("ventas/registrar");
    }

    public function finalizarVenta($datosVenta) {
        // Crear la venta
        $venta = new Ventas();
        $venta->clientes_id = $datosVenta['clientes_id'];
        $venta->metodos_pago_id = $datosVenta['metodos_pago_id'];
        $venta->total = $datosVenta['total'];
        $venta->fecha = $datosVenta['fecha'];
        $venta->comentario = $datosVenta['comentario'];

        if (!$venta->save()) {
            Flash::error('No se pudo guardar la venta.');
            return;
        }

        // Aquí creamos el registro en pagos_items (o tabla equivalente)
        $pagoitem = new Pagositems();
        $pagoitem->venta_id = $venta->id;
        $pagoitem->cliente_id = $venta->clientes_id;
        $pagoitem->monto_pagado = 0; // 0 si es crédito, o total si pago completo
        $pagoitem->saldo_pendiente = $venta->total; // saldo inicial
        $pagoitem->fecha_pago = date('Y-m-d H:i:s');

        if (!$pagoitem->save()) {
            Flash::error('No se pudo crear el registro de pago inicial.');
            return;
        }

        // O si es pago contado, monto_pagado = total, saldo_pendiente = 0

        Flash::valid('Venta y pago registrado correctamente');
        Redirect::to('ventas/index');
    }

}