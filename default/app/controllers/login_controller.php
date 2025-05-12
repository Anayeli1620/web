

<?php
class LoginController extends Controller
{

    public function salir()
    {
        Auth::destroy_identity();
        Redirect::to("index");
    }

    public function index()
    {
        // especificamos el template a usar
        //hacer el template
        View::template("login");
        // Solo si se ha enviado el formulario
        if (Input::hasPost('login')) {
            // recuperamos los datos del formulario para validar acceso
            $login = Input::post('login');
            $email = $login["email"];
            // en la base de datos la tengo con md5, desde php hago la conversion
            $pwd = md5($login["password"]);

            // iniciamos el Auth, mi modelo se llama Usuarios, asi como la tabla
            $auth = new Auth("model", "class: Usuarios", "email: " . $email, "password: " . $pwd);
            if ($auth->authenticate()) {
                // Si el usuario es valido, lo mandamos al index
                // de la aplicacion ya logueado
                Redirect::to("/perfil/");
                return false;
            } else {
                Flash::error("Error");
            }
        }

    }
}