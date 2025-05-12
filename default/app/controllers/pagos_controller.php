
<?php


class PagosController extends AppController

{



    public function index() {
        // Obtener todos los empleados y pasarlos a la vista
        $this->pagos = (new pagos())->find();

    }
    //lista los clientes
    //mostrarar las ventas adeudadas

    public function nueva($cliente_id = null)
    {
        $this->cliente = null;
        $this->ventas = null;

        if (Input::hasGet("cliente")) {
            $cliente_id = Input::get("cliente");
            $this->cliente = (new Clientes())->find($cliente_id);
            Redirect::toAction("nuevo/{$cliente_id}");
        }

        if($cliente_id != null){
            $this->cliente = (new Clientes())->find($cliente_id);
        }

        if ($this->cliente !== null):
            $this->ventas = (new Ventas())->por_pagar($cliente_id);
        endif;
    }

    //parametro get:
    public function finalizar($cliente_id = null){
        $this->cliente = (new Clientes())->find($cliente_id);
        $ventas= Input::get("ventas");
        $this->ventas_a_pagar=[];
        $this->total_a_abono=0;
        foreach ($ventas as $k => $v){
            if ($v !== "") {
                $item = (new Ventas())->find($k);
                $item->a_abonar = $v;
                $this->total_a_abonar += $v;
                $this->ventas_a_pagar[] = $item;
            }

        }
    }


    public function show($id)
    {
        // Obtener el pago por su ID
        $this->pago = (new Pagos())->find_first($id);

        if (!$this->pago) {
            Flash::error('Pago no encontrado');
            return Redirect::to('pagos/index');
        }

        // Obtener cliente y método de pago relacionados
        $this->cliente = $this->pago->cliente;
        $this->metodo_pago = $this->pago->metodo_pago;

        // Calcular el total de ingresos del pago
        $this->total_ingresos = $this->pago->total;

        // Asegúrate de inicializar $ventas correctamente
        $this->ventas = (new Ventas())->find("metodos_pago_id = {$this->pago->metodo_pago_id}") ?: [];

        // Generar datos para las gráficas
        $ventas_por_mes = $this->calcularVentasPorMes();
        $productos_mas_vendidos = $this->calcularProductosMasVendidos();

        // Pasar los datos a la vista
        $this->ventas_por_mes = $ventas_por_mes;
        $this->productos_mas_vendidos = $productos_mas_vendidos;
    }



    // Función para calcular ventas por mes
    private function calcularVentasPorMes()
    {
        // Ejemplo básico de ventas por mes, ajusta si tienes otro método de obtener las ventas
        $ventas_por_mes = [
            '2023-01' => 5000,
            '2023-02' => 3500,
            '2023-03' => 8000,
        ];
        return $ventas_por_mes;
    }

    // Función para calcular los productos más vendidos (cuando tengas pagos_items)
    private function calcularProductosMasVendidos()
    {
        // Aquí puedes agregar lógica para calcular los productos más vendidos
        return [
            'Producto 1' => 30,
            'Producto 2' => 50,
            'Producto 3' => 15,
        ];
    }


    public function registrar()
    {
        if (Input::hasPost('pagos')) {
            $pago = new Pagos(Input::post('pagos'));
            if ($pago->create()) {
                Flash::valid("Pago registrado");
                Input::delete();
            } else {
                Flash::error("Error al registrar el pago");
            }
        }
    }
}

