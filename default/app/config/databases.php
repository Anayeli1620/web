<?php
/**
 * KumbiaPHP Web Framework
 * Parámetros de conexión a la base de datos
 */
return [
    'development' => [

        'host'     => 'localhost',

        'username' => 'web',

        'password' => '123456789',

        'name'     => 'PuntoVenta',

        'type'     => 'mysql',

        'charset'  => 'utf8',
        'port'     => '3307',],

    'production' => [

        'host'     => 'localhost',

        'username' => 'web', //no es recomendable usar el usuario root

        'password' => '123456789',

        'name'     => 'PuntoVenta',

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
