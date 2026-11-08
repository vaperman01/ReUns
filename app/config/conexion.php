<?php
/**
 * ReUns - Capa de Abstracción de Conexión Segura
 * Ubicado en app/config/ para evitar acceso público directo.
 * Conecta exclusivamente vía PDO reutilizando db() y variables de entorno (.env).
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

if (!function_exists('obtener_conexion')) {
    /**
     * Devuelve la instancia singleton de PDO configurada de forma segura.
     * Mantiene compatibilidad con módulos que invoquen obtener_conexion().
     *
     * @return PDO
     */
    function obtener_conexion(): PDO {
        return db();
    }
}
