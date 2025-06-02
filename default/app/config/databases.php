<?php
/**
 * KumbiaPHP Web Framework
 * Parámetros de conexión a la base de datos
 */
return [
    'development' => [

        'host'     => 'db-mysql-sfo3-58066-do-user-18079590-0.m.db.ondigitalocean.com',

        'username' => 'doadmin',

        'password' => 'AVNS_6-KQujFcvyFYZv3VV7X',

        'name'     => 'PuntoVenta',

        'type'     => 'mysql',

        'charset'  => 'utf8',
        'port'     => '3307',],

    'production' => [

        'host'     => 'db-mysql-sfo3-58066-do-user-18079590-0.m.db.ondigitalocean.com',

        'username' => 'doadmin', //no es recomendable usar el usuario root

        'password' => 'AVNS_6-KQujFcvyFYZv3VV7X',

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
