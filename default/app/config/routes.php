<?php
/**
 * KumbiaPHP Web Framework
 * Archivo de rutas (Opcional)
 * 
 * Usa este archivo para definir el enrutamiento estatico entre
 * controladores y sus acciones.Un controlador se puede enrutar a 
 * otro controlador utilizando '*' como comodin así:
 * 
 * '/controlador1/accion1/valor_id1'  =>  'controlador2/accion2/valor_id2'
 * 
 * Ej:
 * Enrutar cualquier petición a posts/adicionar a posts/insertar/*
 * '/posts/adicionar/*' => 'posts/insertar/*'
 * 
 * Otros ejemplos:
 * 
 * '/prueba/ruta1/*' => 'prueba/ruta2/*',
 * '/prueba/ruta2/*' => 'prueba/ruta3/*',
 */


/**
 * Configuración de rutas para el módulo de ventas
 */

// Ruta para el registro inicial de ventas
Router::connect('/ventas/registrar', 'ventas/registrar');

// Ruta para el carrito de compras
Router::connect('/ventas/nueva/:id', 'ventas/nueva');

// Rutas para acciones del carrito
Router::connect('/ventas/agregar_producto/:id', 'ventas/agregar_producto');
Router::connect('/ventas/eliminar_producto/:id', 'ventas/eliminar_producto');
Router::connect('/ventas/aplicar_descuento/:id', 'ventas/aplicar_descuento');

// Rutas para finalizar/cancelar ventas
Router::connect('/ventas/finalizar/:id', 'ventas/finalizar');
Router::connect('/ventas/cancelar/:id', 'ventas/cancelar');

// Ruta principal del módulo
Router::connect('/ventas', 'ventas/index');
return [
    'routes' => [
        /**
         * Muestra la info relacionado con el framework
         */
        '/' => 'index/index',
        /**
         * Status del config.php/config.ini
         */
        '/status' => 'pages/kumbia/status'
        
        ],
];
