<?php
class PagosController extends AppController
{
    public function index()
    {
        $this->pagos = (new Pagos())->find("columns: 
            pagos.*,
            clientes.nombre as cliente_nombre,
            metodos_pago.nombre as metodo_pago_nombre
        ", "join: 
            LEFT JOIN clientes ON pagos.cliente_id = clientes.id
            LEFT JOIN metodos_pago ON pagos.metodo_pago_id = metodos_pago.id
        ");
    }

    public function show($id)
    {
        $this->pago = (new Pagos())->find_first($id);
        if (!$this->pago) {
            Flash::error('Pago no encontrado');
            return Redirect::to('pagos/index');
        }
        $this->cliente = $this->pago->cliente;
        $this->metodo_pago = $this->pago->metodo_pago;
        $this->total_ingresos = $this->pago->total;
        $this->ventas = (new Ventas())->find("metodos_pago_id = {$this->pago->metodo_pago_id}") ?: [];

        // ventas del mes para el show
        $this->ventas_por_mes = $this->calcularVentasPorMes();
        $this->productos_mas_vendidos = $this->calcularProductosMasVendidos();
    }

    /**
     * @return array Datos de ventas por mes
     */
    private function calcularVentasPorMes()
    {
        return [
            '2023-01' => 5000,
            '2023-02' => 3500,
            '2023-03' => 8000,
        ];
    }

    /**
     * @return array Datos de productos más vendidos
     */
    private function calcularProductosMasVendidos()
    {
        return [
            'Producto 1' => 30,
            'Producto 2' => 50,
            'Producto 3' => 15,
        ];
    }

    public function registrar()
    {
        // aqui primero se obtienen los datos del formulario
        $this->clientes = (new Clientes())->find();
        $this->metodos_pago = (new MetodosPago())->find();

        // vemos si el formulario se envia
        if (Input::hasPost('pagos')) {
            $datosPago = Input::post('pagos');
            $ventas_a_pagar = Input::post('ventas_a_pagar', []);

            // validacion del los datos del formulario que maneje 3
            $cliente_id = $datosPago['cliente_id'] ?? null;
            $metodo_pago_id = $datosPago['metodo_pago_id'] ?? null;
            $monto_pago = isset($datosPago['monto']) ? (float)$datosPago['monto'] : 0;

            // 3 Validaciónes de cliente que seleccione venta,existe o no,si tiene adeudo y ya
            if (!$cliente_id) {
                Flash::error('Debe seleccionar un cliente.');
                return;
            }
            $cliente = (new Clientes())->find_first($cliente_id);
            if (!$cliente) {
                Flash::error('El cliente seleccionado no existe.');
                return;
            }
            if (!property_exists($cliente, 'adeudo')) {
                Flash::error('El cliente no tiene la propiedad "adeudo". Verifica la estructura de la base de datos.');
                return;
            }

            // igual aqui 3 Validaciónes de método de pago, que selccione,valido, mayo a cero
            if (!$metodo_pago_id) {
                Flash::error('Debe seleccionar un método de pago.');
                return;
            }
            $metodo = (new MetodosPago())->find_first($metodo_pago_id);
            if (!$metodo) {
                Flash::error('Método de pago no válido.');
                return;
            }
            // monto que sea mayo a 0
            if ($monto_pago <= 0) {
                Flash::error('El monto debe ser mayor a cero.');
                return;
            }

            // montos a sacar
            $adeudo_actual = isset($cliente->adeudo) ? (float)$cliente->adeudo : 0;
            $monto_a_registrar = min($monto_pago, $adeudo_actual);
            $cambio = $monto_pago > $adeudo_actual ? $monto_pago - $adeudo_actual : 0;
            $transaction = new Pagos();
            $transaction->begin();

            try {
                $pago = new Pagos([
                    'cliente_id' => $cliente_id,
                    'metodo_pago_id' => $metodo_pago_id,
                    'total' => $monto_a_registrar,
                    'comentario' => $datosPago['comentario'] ?? '',
                    'created_at' => date('Y-m-d H:i:s')
                ]);

                if (!$pago->create()) {
                    throw new Exception('Error al registrar el pago principal.');
                }

                // ventas del pago
                $monto_restante_por_distribuir = $monto_a_registrar;

                $venta = (new Ventas())->find_first("clientes_id =".$cliente_id." AND por_pagar > 0 ORDER BY created_at DESC");

                $pagoitem_data = [
                    'pago_id' => $pago->id,
                    'venta_id' => $venta->id,
                    'antes' => $venta->por_pagar,
                    'monto_pagado' => $monto_a_registrar,
                    'adeudo' => $venta->por_pagar - $monto_a_registrar,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ];
                $pagoitem = new Pagositems($pagoitem_data);

                if (!$pagoitem->save()) {
                    $errors = $pagoitem->get_messages();
                    error_log("Errores al guardar pago_item: " . print_r($errors, true));
                    throw new Exception("Error al registrar el detalle del pago: " . implode(', ', $errors));
                }

                //  venta a pagar
                foreach ($ventas_a_pagar as $venta_id => $monto_abonado) {
                    $monto_abonado = (float)$monto_abonado;
                    if ($monto_abonado > 0) {
                        $venta = (new Ventas())->find_first($venta_id);
                        Flash::show("", "Venta: ".$venta_id);
                        if (!$venta) {
                            throw new Exception("La venta ID $venta_id no existe");
                        }

                        $monto_abonado = min($monto_abonado, $monto_restante_por_distribuir);

                        // Actualizar venta
                        $venta->por_pagar -= $monto_abonado;
                        if ($venta->por_pagar <= 0) {
                            $venta->status = 'pagado';
                        }

                        if (!$venta->update()) {
                            throw new Exception("Error al actualizar la venta $venta_id");
                        }

                        $monto_restante_por_distribuir -= $monto_abonado;
                    }
                }

                // Actualizar información del cliente
                $cliente->adeudo = max(0, $adeudo_actual - $monto_a_registrar);
                $cliente->credito = isset($cliente->credito) ? $cliente->credito + $monto_a_registrar : $monto_a_registrar;

                (new Ventas())->restar($cliente_id, $cliente->adeudo);
                if (!$cliente->update()) {
                    throw new Exception('Error al actualizar el saldo del cliente.');
                }
                // Confirma transacción
                $transaction->commit();

                $mensaje = "Pago registrado correctamente";
                if (strtolower($metodo->nombre) === 'efectivo' && $cambio > 0) {
                    $mensaje .= ". Cambio: $" . number_format($cambio, 2);
                }

                Flash::valid($mensaje);
                Redirect::toAction('index');

            } catch (Exception $e) {
                // Revertir transacción en caso de error
                $transaction->rollback();
                Flash::error($e->getMessage());
            }
        }
    }

    /**
     * Obtiene el saldo pendiente del cliente
     *
     * @return JSON Respuesta con el total pendiente
     */
    public function get_saldo()
    {
        $cliente_id = Input::get('cliente_id');
        $metodo_pago_id = Input::get('metodo_pago_id');

        header('Content-Type: application/json');

        if (!$cliente_id || !$metodo_pago_id) {
            echo json_encode(['error' => 'Faltan parámetros']);
            exit;
        }

        $total = 0;
        $ventas_pendientes = (new Ventas())->find("clientes_id = $cliente_id AND metodos_pago_id = $metodo_pago_id AND status != 'completada'");

        foreach ($ventas_pendientes as $venta) {
            $total += $venta->por_pagar;
        }

        echo json_encode(['total' => $total]);
        exit;
    }

    /**
     * adeudo total del cliente
     *
     * @return JSON Respuesta con el adeudo total o
     */
    public function get_adeudo()
    {
        $cliente_id = Input::get('cliente_id');
        header('Content-Type: application/json');

        if (!$cliente_id) {
            echo json_encode(['error' => 'Falta cliente_id']);
            exit;
        }

        $adeudo = 0;
        $ventas_pendientes = (new Ventas())->find("clientes_id = $cliente_id AND status != 'completada'");

        foreach ($ventas_pendientes as $venta) {
            $adeudo += $venta->por_pagar;
        }

        echo json_encode(['adeudo' => $adeudo]);
        exit;
    }

    /**
     *
     * @param int|null $cliente_id ID del cliente
     */
    public function finalizar($cliente_id = null)
    {
        $this->cliente = (new Clientes())->find($cliente_id);
        $ventas = Input::get("ventas");
        $this->ventas_a_pagar = [];
        $this->total_a_abonar = 0;

        foreach ($ventas as $k => $v) {
            if ($v !== "") {
                $item = (new Ventas())->find($k);
                $item->a_abonar = $v;
                $this->total_a_abonar += $v;
                $this->ventas_a_pagar[] = $item;
            }
        }
    }
}