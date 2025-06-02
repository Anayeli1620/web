<?php



class clientes extends ActiveRecord {
    // Relación con Ventas
    public function initialize() {
        // Relación con Ventas
        $this->has_many('ventas', 'model: Ventas', 'fk: clientes_id');
        $this->belongs_to('productos', 'productos_id', 'productos_id');

       // $this->validates_presence_of("nombre");
      //  $this->validates_length_of("nombre",['min' => 3, 'max' => 50, 'too_short' => 'El nombre debe tener al menos 3 caracteres', 'too_long' => 'El nombre debe tener máximo 50 caracteres']);

       // $this->validates_presence_of("email");
       // $this->validates_length_of("email", ['with' => '/^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6}$/', 'message' => 'Por favor ingresa un correo electrónico válido']);

        //$this->validates_presence_of("telefono");
       // $this->validates_length_of("telefono", ['with' => '/^\d{10}$/', 'message' => 'El teléfono debe tener 10 dígitos']);

       // $this->validates_presence_of("nombre");
       // $this->validates_presence_of("email");
      //  $this->validates_presence_of("telefono");
    }

    public function update_credito(){
        //$this->id
        $sql= "UPDATE clientes c SET adeudo = (SELECT SUM(por_pagar) FROM ventas v WHERE v.clientes_id = c.id AND por_pagar > 0 AND status = 'finalizada') WHERE c.id = {$this->id}";

        (new Clientes())->sql($sql);
    }

    public function linea_credito()
    {
        $credito_suficiente =($this-> total< $this->getCliente()->credito)? true: false;
        return $credito_suficiente;
        }


    // Añadir los nuevos atributos

}
