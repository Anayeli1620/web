<?php
// app/controller/alumnos_controller.php
class AlumnosController extends AppController {
    //funcion => accion
    // :url/alumnos o /alumnos/index
    // app/views/alumnos/index.phtml
    public function index(){

    }
    // app/views/alumnos/ver.phtml
    public function ver($id=90)
    {
        $alumnos[80] ="Ana fernanda";
        $alumnos[90] ="Ana fernanda";
        $this->nombre=$alumnos[$id];


    }
    // app/views/alumnos/nuevo.phtml
    public function nuevo()
{

}
}