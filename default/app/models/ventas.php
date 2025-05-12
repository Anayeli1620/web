<?php
class Ventas extends ActiveRecord
{
    public function initialize()
    {

        $this->has_many('Items', 'model: DetallesVentas', 'fk: ventas_id');
        $this->belongs_to('Cliente', 'model: Clientes', 'fk: clientes_id');

        $this->belongs_to('vendedor', 'model: Empleados', 'fk: empleados_id');
        $this->belongs_to('metodo', 'model: MetodosPago', 'fk: metodos_pago_id');
        $this->belongs_to('producto', 'model: Productos', 'fk: productos_id'); // Relación con productos

//        $this->validates_presence_of("nombre");
//
//        $this->validates_length_of(
//            "nombre",
//            [
//                'min' => 15,
//                'max' => 40,
//                'too_short' => 'El nombre debe tener al menos 15 caracteres',
//                'too_long' => 'El nombre debe tener máximo 40 caracteres'
//            ]
//        );
        }

    public function get_carrito($cliente_id){
        return (new Ventas())->find("conditions: clientes_id = {$cliente_id} AND status = 'carrito'")[0];
    }




    public function crear($clientes_id)
    {
        $venta = (new Ventas());
        $venta->clientes_id = $clientes_id;
        $venta->status = "carrito";
        $venta->save();
        return $venta;
    }

        // Agrega un producto a la venta
    public function add_item($producto, $cantidad)
    {
        if ($this->status == "carrito") {
            // Verificar si el producto ya está en el carrito
            $detalle_venta = (new DetallesVentas())->find_first("ventas_id = {$this->id} AND productos_id = {$producto->id}");

            if ($detalle_venta) {
                // comen_por si.Si el producto ya está en el carrito, solo incrementamos la cantidad
                $detalle_venta->cantidad += $cantidad;
                // comen_por si se me olvida__Volver a calcular el importe correctamente
                $detalle_venta->importe = $detalle_venta->cantidad * $detalle_venta->unitario;
                $detalle_venta->save();
            } else {
                // es como validacion__Si no está en el carrito, agregamos el producto como nuevo item
                $item = new DetallesVentas();
                $item->ventas_id = $this->id;
                $item->productos_id = $producto->id;
                $item->cantidad = $cantidad;
                $item->unitario = $producto->precio;
                // Calcular el importe correctamente
                $item->importe = $cantidad * $producto->precio;
                $item->save();
            }

            // Actualizar el stock
            $producto->bajastock($producto->id, $cantidad);
        }

        if ($this->status === "finalizada") {
            $this->getCliente()->linea_credito();
        }
    }




//el comentario de abajo estaba dentro del add item
    //  $detalle->getProducto()->bajastock($producto->id, $cantidad);


        public function set_finalizar()
        {
            $this->activo = false;
            $this->status = "finalizado";
            if ($this->forma_pago === "PPP") {
                $this->por_pagar = $this->total;
            }

            $this->save();
            $this->set_total();
            $this->getCliente()->update_credito();
        }

        // Validar si el crédito es suficiente (para pagos a crédito - PPD)
        public
        function venta_valida()
        {
            if ($this->forma_pago === "PPP") {
                $credito_suficiente = ($this->total < $this->getCliente()->credito) ? 1 : 0;
                return $credito_suficiente;
            }
            return true;
        }


        public
        function set_total()
        {
            //   $this->total = $this->getItems()->sum('importe');
            $this->total = (new DetallesVentas())->sum("importe", "conditions: ventas_id = {$this->id}");
            $this->save();
        }
        public function por_pagar($cliente_id){
           return (new Ventas())->find("clientes_id = {$cliente_id} AND por_pagar > 0
           AND status= 'finalizada'");
        }
        // Obtiene detalles de la venta
        public function getDetallesVentas()
        {
            return (new DetallesVentas())->find("ventas_id = {$this->id}");
        }

        public
        function getCliente()
        {
            return (new Clientes())->find_first($this->id);
        }

        public
        function getEmpleado()
        {
            return (new Empleados())->find_first($this->id);
        }

        public
        function metodos_pago()
        {
            return $this->belongs_to('MetodosPago');
        }

        // Relación con los productos
        public function getDetalles()
        {
            return $this->has_many('DetallesVentas');
        }

}
?>
