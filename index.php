<?php

require __DIR__ . '/vendor/autoload.php';

use ComposerUI\ComposerHelper;

function debug($var) {
    echo '<pre>';
    print_r($var);
    echo '</pre>';
}

$composer = new ComposerHelper();

debug('welcome to ComposerTools. This stuff is needed before setting up a full working UI');

/**
 * Installing packages
 *
 * You can installing a package by calling `requirePackages`.
 * Use the first parameter to define the package.
 * Use the second parameter to define the version.
 */
//$composer->requirePackages([
//    'symfony/stopwatch' => 'dev-master',
//    'symfony/yaml',
//]);


/**
 * Removing packages
 *
 * You can remove a package by calling `removePackages`.
 * Use the first parameter to define the package.
 */
//$composer->removePackages([
//    'symfony/stopwatch' => 'dev-master',
//    'symfony/yaml',
//]);

/**
 * Update packages
 *
 * You can update composer by calling `update`.
 */
$composer->update();