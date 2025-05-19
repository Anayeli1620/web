<?php


class ClientesController extends AppController
{

    // Acción para mostrar todos los clientes
    public function index()
    {
        // Obtener todos los clientes de la base de datos
        $this->clientes = (new clientes())->find();

        // Iterar sobre los clientes y agregar la información de saldo
        foreach ($this->clientes as $cliente) {

        }
    }

    // Acción para mostrar un cliente específico
    public function show($id)
    {
        // Buscar el cliente por su ID
        $this->clientes = (new Clientes())->find_first($id);

        // Validar si el cliente existe
        if (!$this->clientes) {
            Flash::error('Cliente no encontrado');
            return Redirect::to('clientes/index');
        }

        // Calcular el saldo del cliente (credito - adeudo)
        $this->saldo = (float)$this->clientes->credito - (float)$this->clientes->adeudo;

        // Obtener todas las ventas del cliente
        $ventas = $this->clientes->getVentas();

        // Inicializar variables para estadísticas
        $total_unidades = 0;
        $total_ingresos = 0;
        $ultima_fecha = null;
        $productos_mas_vendidos = [];
        $ventas_por_mes = [];

        // Procesar las ventas del cliente
        if (!empty($ventas)) {
            foreach ($ventas as $venta) {
                $detalleVenta = (new DetallesVentas())->find_first("ventas_id = {$venta->id}");

                if (!$detalleVenta) {
                    Flash::warning("La venta con ID {$venta->id} no tiene detalles asociados.");
                    continue;
                }

                $producto = (new Productos())->find_first($detalleVenta->productos_id);
                if (!$producto) {
                    Flash::warning("Detalle de venta con ID {$detalleVenta->id} no tiene producto asociado.");
                    continue;
                }

                $total_unidades += $detalleVenta->cantidad;
                $total_ingresos += ($producto->precio * $detalleVenta->cantidad);

                // Actualizar la última fecha de venta
                if ($ultima_fecha === null || $venta->fecha > $ultima_fecha) {
                    $ultima_fecha = $venta->fecha;
                }

                // Contar los productos más vendidos
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

        // Pasar los datos de ventas y estadísticas a la vista
        $this->total_unidades = $total_unidades;
        $this->total_ingresos = $total_ingresos;
        $this->ultima_fecha = $ultima_fecha;
        $this->productos_mas_vendidos = $productos_mas_vendidos;
        $this->ventas_por_mes = $ventas_por_mes;
    }

    public function registrar()
    {
        if (input::hasPost('clientes')) {
            $cliente = new clientes(Input::post('clientes'));
            if ($cliente->create()) {
                Flash::valid("cliente registrado");
                Input::delete();
            }
        } else {
            Flash::error("Error al registrar el cliente");
        }

    }

    public function upload($id = null)
    {
        View::select(null);
        View::template(null);
        header('Content-Type: application/json');
        ob_clean(); // Limpiar buffer de salida

        try {
            if (empty($_FILES['fileup'])) {
                throw new Exception('No se recibió ningún archivo', 400);
            }

            $archivo = $_FILES['fileup'];
            $directorio = "public/uploads/clientes/";

            if (!is_dir($directorio)) {
                if (!mkdir($directorio, 0755, true)) {
                    throw new Exception('No se pudo crear el directorio', 500);
                }
            }

            // Validar tipo de imagen
            $mime_permitidos = ['image/jpeg', 'image/png', 'image/gif'];
            if (!in_array($archivo['type'], $mime_permitidos)) {
                throw new Exception('Solo se permiten imágenes JPEG, PNG o GIF', 400);
            }


            // Conservar el nombre original del archivo
            $nombre_original = $archivo['name'];

            // Limpiar el nombre del archivo (opcional, para seguridad)
            $nombre_limpio = preg_replace('/[^a-zA-Z0-9._-]/', '_', $nombre_original);
            $ruta_completa = $directorio . $nombre_limpio;

            // Verificar si el archivo ya existe y añadir sufijo si es necesario
            $contador = 1;
            while (file_exists($ruta_completa)) {
                $info = pathinfo($nombre_original);
                $nombre_limpio = $info['filename'] . '_' . $contador . '.' . $info['extension'];
                $ruta_completa = $directorio . $nombre_limpio;
                $contador++;
            }
            if (move_uploaded_file($archivo['tmp_name'], $ruta_completa)) {
                // Si tenemos ID, actualizamos el producto
                if ($id) {
                    $producto = (new Productos())->find_first($id);
                    if ($producto) {
                        $producto->imagen = "/uploads/clientes/" . $nombre_archivo;
                        $producto->update();
                    }
                }

                echo json_encode([
                    'success' => true,
                    'file' => $nombre_archivo,
                    'path' => "/uploads/clientes/" . $nombre_archivo,
                    'producto_id' => $id
                ]);
            } else {
                throw new Exception('Error al mover el archivo. Verifica permisos.', 500);
            }
        } catch (Exception $e) {
            http_response_code($e->getCode() ?: 500);
            echo json_encode([
                'error' => true,
                'message' => $e->getMessage()
            ]);
        }
        exit; // Importante para evitar salida adicional
    }
}