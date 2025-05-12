<?php

class Formbs{
    protected static function attrsdefaut($attrs, $defaults)
    {
        foreach ($defaults as $k => $v) {
            if (isset($attrs[$k])) {
                if (strpos($attrs[$k], $v) === false) {
                    $attrs[$k] .= ' '.$v;
                }
            } else {
                $attrs[$k] = $v;
            }
        }
        return $attrs;
    }

    // Formbs::btn_aceptar("Aceptar")
    public static function btn_guardar($text = "Guardar", $attrs = []){
        $text = "✉".$text;
        $attrs = Formbs::attrsdefaut($attrs, ["class" => "btn btn-success"]);
        return Form::submit($text, $attrs);
        }
    public static function btn_cancelar($text = "Cancelar", $attrs = []){
        $text = "❌ ".$text;
        $attrs = Formbs::attrsdefaut($attrs, ["class" => "btn btn-danger"]);
        return Form::button($text, $attrs);
    }

    public static function btn_limpiar($text = "limpiar", $attrs = []){
        $text = "📝 ".$text;
        $attrs = Formbs::attrsdefaut($attrs, ["class" => "btn btn-primary"]);
        return Form::submit($text, $attrs);
    }

    public static function btn_regresar($text = "regresar", $attrs = []){
        $text = "◀ ".$text;
        $attrs = Formbs::attrsdefaut($attrs, ["class" => "btn btn-secondary"]);
        return Form::submit($text, $attrs);
    }




}