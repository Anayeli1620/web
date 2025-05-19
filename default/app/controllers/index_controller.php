<?php
class IndexController extends AppController
{
    public function index()
    {
        $this->venta = (new Ventas())->find(5);
        $this->venta->forma_pago = 'PPD';
        $this->venta->save();

        $producto = (new Productos())->find(random_int(1, 100));
        $cantidad = random_int(1, 10);
        $this->venta->add_item($producto, $cantidad);
        $this->venta->set_finalizar();
    }

    public function upload($tipo = 'index')
    {
        View::select(null);
        View::template(null);

        try {
            if (empty($_FILES['fileup'])) {
                throw new Exception('No se recibió ningún archivo');
            }

            $archivo = $_FILES['fileup'];

            // Ruta relativa a la carpeta index
            $directorio = "public/index/";

            // Crear directorio si no existe
            if (!is_dir($directorio)) {
                mkdir($directorio, 0755, true);
            }

            // Validar y sanitizar el nombre del archivo
            $nombre_original = pathinfo($archivo['name'], PATHINFO_FILENAME);
            $extension = pathinfo($archivo['name'], PATHINFO_EXTENSION);
            $nombre_sanitizado = preg_replace('/[^a-zA-Z0-9-_]/', '', $nombre_original);
            $nombre_archivo = $nombre_sanitizado . '_' . time() . '.' . $extension;

            $ruta_completa = $directorio . $nombre_archivo;

            // Validar tipo de imagen
            $mime_permitidos = ['image/jpeg', 'image/png', 'image/gif'];
            if (!in_array($archivo['type'], $mime_permitidos)) {
                throw new Exception('Solo se permiten imágenes JPEG, PNG o GIF');
            }

            if (move_uploaded_file($archivo['tmp_name'], $ruta_completa)) {
                echo json_encode([
                    'success' => true,
                    'file' => $nombre_archivo,
                    'path' => "/index/" . $nombre_archivo  // Ruta accesible desde web
                ]);
            } else {
                throw new Exception('Error al mover el archivo. Verifica permisos.');
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'error' => true,
                'message' => $e->getMessage()
            ]);
        }
    }
}