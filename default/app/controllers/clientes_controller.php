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

    public function show($id)
    {
        $this->clientes = (new Clientes())->find_first($id);

        if (!$this->clientes) {
            Flash::error('Cliente no encontrado');
            return Redirect::to('clientes/index');
        }

        $this->saldo = (float)$this->clientes->credito - (float)$this->clientes->adeudo;

        $ventas = $this->clientes->getVentas();

        $total_unidades = 0;
        $total_ingresos = 0;
        $ultima_fecha = null;
        $productos_mas_vendidos = [];
        $ventas_por_mes = [];

        if (!empty($ventas)) {
            foreach ($ventas as $venta) {
                $detalleVenta = (new Detalles_ventas())->find_first("ventas_id = {$venta->id}");

                if (!$detalleVenta) continue;

                $producto = (new Productos())->find_first($detalleVenta->productos_id);
                if (!$producto) continue;

                $total_unidades += $detalleVenta->cantidad;
                $total_ingresos += ($producto->precio * $detalleVenta->cantidad);

                if ($ultima_fecha === null || $venta->fecha > $ultima_fecha) {
                    $ultima_fecha = $venta->fecha;
                }

                if (!isset($productos_mas_vendidos[$producto->nombre])) {
                    $productos_mas_vendidos[$producto->nombre] = 0;
                }
                $productos_mas_vendidos[$producto->nombre] += $detalleVenta->cantidad;

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
        ob_clean();

        try {
            if (empty($_FILES['fileup'])) {
                throw new Exception('No se recibió ningún archivo', 400);
            }

            $archivo = $_FILES['fileup'];
            $directorio = "public/uploads/clientes/";

            // Crear directorio si no existe
            if (!is_dir($directorio)) {
                if (!mkdir($directorio, 0755, true)) {
                    throw new Exception('No se pudo crear el directorio', 500);
                }
            }

            // Validar tipo de imagen
            $mime_permitidos = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            if (!in_array($archivo['type'], $mime_permitidos)) {
                throw new Exception('Solo se permiten imágenes JPEG, PNG, GIF o WebP', 400);
            }

            // Limpiar nombre del archivo
            $nombre_limpio = preg_replace('/[^a-zA-Z0-9._-]/', '_', $archivo['name']);
            $ruta_completa = $directorio . $nombre_limpio;

            // Verificar si el archivo ya existe
            $contador = 1;
            while (file_exists($ruta_completa)) {
                $info = pathinfo($nombre_limpio);
                $nombre_limpio = $info['filename'] . '_' . $contador . '.' . $info['extension'];
                $ruta_completa = $directorio . $nombre_limpio;
                $contador++;
            }

            // Mover archivo
            if (!move_uploaded_file($archivo['tmp_name'], $ruta_completa)) {
                throw new Exception('Error al mover el archivo. Verifica permisos.', 500);
            }

            // Actualizar cliente si hay ID
            if ($id) {
                $cliente = (new Clientes())->find_first($id);
                if ($cliente) {
                    $cliente->imagen = "/uploads/clientes/" . $nombre_limpio;
                    $cliente->update();
                }
            }

            // Respuesta exitosa
            echo json_encode([
                'success' => true,
                'file' => $nombre_limpio,
                'path' => "/uploads/clientes/" . $nombre_limpio,
                'cliente_id' => $id
            ]);

        } catch (Exception $e) {
            // Respuesta de error
            http_response_code($e->getCode() ?: 500);
            echo json_encode([
                'success' => false,
                'error' => true,
                'message' => $e->getMessage(),
                'file_info' => !empty($archivo) ? $archivo : null
            ]);
        }
        exit;
    }
}