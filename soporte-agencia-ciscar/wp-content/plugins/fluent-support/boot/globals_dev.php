<?php

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Enable Query Log
 */
if (!function_exists('fluent_support_eqL')) {
    function fluent_support_eqL()
    {
        defined('SAVEQUERIES') || define('SAVEQUERIES', true);
    }
}

/**
 * Get Query Log
 */
if (!function_exists('fluent_support_gql')) {
    function fluent_support_gql()
    {
        $result = [];
        foreach ((array)$GLOBALS['wpdb']->queries as $key => $query) {
            $result[++$key] = array_combine([
                'query', 'execution_time'
            ], array_slice($query, 0, 2));
        }
        return $result;
    }
}
