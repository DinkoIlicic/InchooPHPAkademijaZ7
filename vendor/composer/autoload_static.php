<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit41666e05f653c93239db8c1aaddde522
{
    public static $prefixesPsr0 = array (
        'M' => 
        array (
            'Metzli' => 
            array (
                0 => __DIR__ . '/..' . '/z38/metzli/src',
            ),
        ),
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixesPsr0 = ComposerStaticInit41666e05f653c93239db8c1aaddde522::$prefixesPsr0;

        }, null, ClassLoader::class);
    }
}
