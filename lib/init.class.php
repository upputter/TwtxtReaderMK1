<?php
    require_once(__DIR__ . '/../vendor/autoload.php');

    $excludeFromClassLoading = [
        'twtxt.v1.class.php',
    ];

    loadPHPclasses(__DIR__);
    loadPHPclasses(__DIR__ . '/ViewHelpers');
    loadPHPclasses(__DIR__ . '/TwtxtParsers');
    loadPHPclasses(__DIR__ . '/CacheDriver/TwtxtFiles');

    function loadPHPclasses($directory) {
        global $excludeFromClassLoading;        
        $d = dir($directory);
        while (false !== ($entry = $d->read())) {           
            $currentFile = $directory. '/' . $entry;            
            if (
                is_file($currentFile)
                AND __FILE__ != $currentFile
                AND !in_array($entry, $excludeFromClassLoading)
            ) {
                require_once($currentFile);
            }
        }
        $d->close();
    }
    $config = new Config('./private/config.ini');
