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

        $this->ventas_por_mes = $this->calcularVentasPorMes();
        $this->productos_mas_vendidos = $this->calcularProductosMasVendidos();
    }

    private function calcularVentasPorMes()
    {
        return [
            '2023-01' => 5000,
            '2023-02' => 3500,
            '2023-03' => 8000,
        ];
    }

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
        $this->clientes = (new Clientes())->find();
        $this->metodos_pago = (new MetodosPago())->find();

        if (Input::hasPost('pagos')) {
            $datosPago = Input::post('pagos');
            $ventas_a_pagar = Input::post('ventas_a_pagar', []);

            $cliente_id = $datosPago['cliente_id'] ?? null;
            $metodo_pago_id = $datosPago['metodo_pago_id'] ?? null;
            $monto_pago = isset($datosPago['monto']) ? (float)$datosPago['monto'] : 0;

            if (!$cliente_id) {
                Flash::error('Debe seleccionar un cliente.');
                return;
            }

            $cliente = (new Clientes())->find_first($cliente_id);
            if (!$cliente) {
                Flash::error('El cliente seleccionado no existe.');
                return;
            }

            if (!$metodo_pago_id) {
                Flash::error('Debe seleccionar un método de pago.');
                return;
            }

            $metodo = (new MetodosPago())->find_first($metodo_pago_id);
            if (!$metodo) {
                Flash::error('Método de pago no válido.');
                return;
            }

            if ($monto_pago <= 0) {
                Flash::error('El monto debe ser mayor a cero.');
                return;
            }

            $adeudo_actual = (float)$cliente->adeudo;
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

                if (!empty($ventas_a_pagar)) {
                    $monto_restante_por_distribuir = $monto_a_registrar;

                    foreach ($ventas_a_pagar as $venta_id => $monto_abonado) {
                        $monto_abonado = (float)$monto_abonado;
                        if ($monto_abonado > 0) {
                            $venta = (new Ventas())->find_first($venta_id);

                            if (!$venta) {
                                throw new Exception("La venta ID $venta_id no existe");
                            }

                            $monto_abonado = min($monto_abonado, $monto_restante_por_distribuir);

                            // Registrar el item de pago ANTES de actualizar la venta
                            $pago_item = new PagosItems([
                                'pago_id' => $pago->id,
                                'venta_id' => $venta->id,
                                'antes' => $venta->por_pagar,
                                'monto_pagado' => $monto_abonado,
                                'adeudo' => $venta->por_pagar - $monto_abonado,
                                'created_at' => date('Y-m-d H:i:s'),
                                'updated_in' => date('Y-m-d H:i:s')
                            ]);

                            if (!$pago_item->create()) {
                                throw new Exception("Error al registrar el item de pago para la venta $venta_id");
                            }

                            // Actualizar la venta después de registrar el item de pago
                            $venta->por_pagar -= $monto_abonado;
                            if ($venta->por_pagar <= 0) {
                                $venta->status = 'pagado';
                            }

                            if (!$venta->update()) {
                                throw new Exception("Error al actualizar la venta $venta_id");
                            }

                            // Registrar en productos_log (mantenemos esta parte igual)
                            $detalles = (new Detalles_ventas())->find("ventas_id = {$venta->id}");

                            foreach ($detalles as $detalle) {
                                $log = new ProductosLog([
                                    'producto_id' => $detalle->productos_id,
                                    'entrada' => 0,
                                    'salida' => $detalle->cantidad,
                                    'venta_id' => $venta->id,
                                    'created_at' => date('Y-m-d H:i:s')
                                ]);
                                if (!$log->create()) {
                                    throw new Exception("Error al registrar en productos_log para el producto {$detalle->productos_id}");
                                }
                            }

                            $monto_restante_por_distribuir -= $monto_abonado;
                        }
                    }
                }

                $cliente->adeudo = max(0, $adeudo_actual - $monto_a_registrar);
                $cliente->credito += $monto_a_registrar;

                if (!$cliente->update()) {
                    throw new Exception('Error al actualizar el saldo del cliente.');
                }

                $transaction->commit();

                $mensaje = "Pago registrado correctamente";
                if (strtolower($metodo->nombre) === 'efectivo' && $cambio > 0) {
                    $mensaje .= ". Cambio: $" . number_format($cambio, 2);
                }

                Flash::valid($mensaje);
                Redirect::toAction('index');

            } catch (Exception $e) {
                $transaction->rollback();
                Flash::error($e->getMessage());
                Input::keep();
            }
        }
    }

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