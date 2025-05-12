<?php
class IndexController extends AppController
{
    public function index()
    {
        $this->venta = (new Ventas())->find(5);
        $this->venta->forma_pago = 'PPD';                         // Asignar la forma de pago 'PPD'
        $this->venta->save();


        $producto = (new Productos())->find(random_int(1, 100));


        $cantidad = random_int(1,10);                             // Generar una cantidad aleatoria entre 1 y 10
        $this->venta->add_item($producto, $cantidad);             // Agregar el producto y la cantidad a la venta
        $this->venta->set_finalizar();
    }

    // /KumbiaPHP/default/public/index/subir/empleados/90
    // /KumbiaPHP/default/public/index/subir/:modelo/:id
    public function subir($modelo, $id)
    {
        View::select(null);
        View::template(null);
        //echo json_encode($_FILES);

        $archivo = $_FILES['fileup'];
        $directorio = "/var/www/html/Kumbiaphp/default/public/storage/$modelo";

        if (!is_dir($directorio)) {
            if (mkdir($directorio, 0755, true)) {
                echo "¡Carpeta creada exitosamente!";
            } else {
                echo "Error: No se pudo crear la carpeta.";
            }
        }

        $ruta_archivo = $directorio . $archivo['name'];
        if (move_uploaded_file($archivo['tmp_name'],$ruta_archivo )) {
            echo json_encode(['success' => true, 'archivo' => $archivo['name']]);
        } else {
            echo json_encode(['error' => 'Error al guardar el archivo']);
        }

        $extension = pathinfo($ruta_archivo, PATHINFO_EXTENSION); // Ejemplo: "png"
        $nueva_ruta = $directorio ."/$id.$extension";
        rename($ruta_archivo, $nueva_ruta);
    }
}
