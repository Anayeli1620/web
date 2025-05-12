<?php

class Htmlbs
{
    protected static function attrsdefaut($attrs, $defaults)
    {
        foreach ($defaults as $k => $v) {
            if (isset($attrs[$k])) {
                if (strpos($attrs[$k], $v) === false) {
                    $attrs[$k] .= ' ' . $v;
                }
            } else {
                $attrs[$k] = $v;
            }
        }
        return $attrs;
    }

    public static function img($src, $alt = '', $attrs = []){
        $attrs = Htmlbs::attrsdefaut($attrs, ["class" => ""]);
        return '<img src="'.PUBLIC_PATH."storage/$src\" alt=\"$alt\" ".Tag::getAttrs($attrs).'/>';

    }
}

