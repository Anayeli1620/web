<?php
/**
 * KumbiaPHP Web Framework
 * Parámetros de conexión a la base de datos
 */
return [
    'development' => [

        'host'     => '143.244.187.253',

        'username' => 'web', //no es recomendable usar el usuario root

        'password' => '123456789',

        'name'     => 'PuntoVenta',

        'type'     => 'mysql',

        'charset'  => 'utf8',
        'port'     => '3307',],

    'production' => [

        'host'     => 'localhost',

        'username' => 'root', //no es recomendable usar el usuario root

        'password' => '',

        'name'     => 'test',

        'type'     => 'mysql',

        'charset'  => 'utf8',

    ],
];

/**
 * Ejemplo de SQLite
 */
/*'development' => [
    'type' => 'sqlite',
    'dsn' => 'temp/data.sq3',
    'pdo' => 'On',
] */
