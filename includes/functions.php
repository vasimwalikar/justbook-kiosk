<?php

require '../vendor/autoload.php';
require '../vendor/danjam/slim-mustache-view/src/Mustache.php';

function init(){

    $app = new \Slim\App(array('debug'=>true));
    $container = $app->getContainer();
    $container['view'] = function(){
        $view = new \Slim\Views\Mustache([
            'loader' => new Mustache_Loader_FilesystemLoader('../templates'),
            'partials_loader' => new Mustache_Loader_FilesystemLoader('../templates/partials')
        ]);
        return $view;
    };
    $container['db'] = function(){
        $conn = oci_connect("memp", "memp", "stagingdb.justbooksclc.com");
//        $conn = oci_connect('memp', 'Dragon$32Sushi', ' (DESCRIPTION =
//            (ADDRESS_LIST =
//              (ADDRESS = (PROTOCOL = TCP)(HOST = db1.csvm47svs5ut.us-east-1.rds.amazonaws.com)(PORT = 1521))
//            )
//            (CONNECT_DATA =
//              (SERVICE_NAME = db4)
//            )
//          )');
        return $conn;
    };
    return $app;
}

?>
