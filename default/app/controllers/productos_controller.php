<?php


class ProductosController extends AppController
{

    public function index()
    {
        $this->productos = (new Productos())->find("columns: 
        productos.*,
        categorias.nombre as categoria_nombre
    ", "join: 
        LEFT JOIN categorias ON productos.categorias_id = categorias.id
    ");
    }

    public function show($id)
    {
        View::template("plantilla");

        $this->productos = (new Productos())->find_first($id);

        if (!$this->productos) {
            Flash::error('Producto no encontrado');
            return Redirect::to('productos/index');
        }

        // Obtener categoría
        $this->categoria = $this->productos->getCategoria();

        // Obtener detalles de ventas relacionados al producto
        $detalles_ventas = (new DetallesVentas())->find("productos_id = {$this->productos->id}");

        $this->ventas = [];
        foreach ($detalles_ventas as $detalle) {
            $venta = (new Ventas())->find_first($detalle->ventas_id);
            if ($venta) {
                $this->ventas[] = $venta;
            }
        }

        // Cálculo de productos más vendidos
        $this->productos_mas_vendidos = [];
        foreach ($this->ventas as $venta) {
            $detalles = (new DetallesVentas())->find("ventas_id = {$venta->id}");
            foreach ($detalles as $detalle) {
                $producto = (new Productos())->find_first($detalle->productos_id);
                if ($producto) {
                    if (!isset($this->productos_mas_vendidos[$producto->nombre])) {
                        $this->productos_mas_vendidos[$producto->nombre] = 0;
                    }
                    $this->productos_mas_vendidos[$producto->nombre] += $detalle->cantidad;
                }
            }
        }

        // Cálculo de ventas por mes
        $this->ventas_por_mes = [];
        foreach ($this->ventas as $venta) {
            $mes = date('Y-m', strtotime($venta->fecha));
            if (!isset($this->ventas_por_mes[$mes])) {
                $this->ventas_por_mes[$mes] = 0;
            }
            $this->total_ventas = 0;
            foreach ($this->ventas as $venta) {
                $this->total_ventas += (float)$venta->total;
            }
        }

        // Total de ventas
        $this->total_ventas = array_sum(array_column($this->ventas, 'total'));
    }


    // Método para registrar un producto
    public function registrar()
    {
        if (Input::hasPost('productos')) {
            $producto = new Productos(Input::Post('productos'));

            if ($producto->create()) {
                Flash::valid("Producto registrado");
                Input::delete();
            }
        } else {
            Flash::error("Error al registrar el producto");
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
            $directorio = "public/uploads/productos/";

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
                        $producto->imagen = "/uploads/productos/" . $nombre_archivo;
                        $producto->update();
                    }
                }

                echo json_encode([
                    'success' => true,
                    'file' => $nombre_archivo,
                    'path' => "/uploads/productos/" . $nombre_archivo,
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