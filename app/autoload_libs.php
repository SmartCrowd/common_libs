<?php
/**
 * Loader for common lis
 *
 * @author :  ignat
 * @date   :  13.11.14 11:40
 */

/*
 * Dependency injection initialization for library.
 */
if (defined('COMMON_LIBS_INIT_LOADED')) {
    return;
}

define('COMMON_LIBS_INIT_LOADED', true);

$libs = dirname(__FILE__) . "/";
$objects = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($libs), RecursiveIteratorIterator::SELF_FIRST);
foreach($objects as $name => $object){
    if (strstr($name, '.php')){
        require($name);
    }
}
