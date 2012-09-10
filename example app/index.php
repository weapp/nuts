<?php
require('./Nuts/Autoloader.php');
Nuts_Autoloader::register();

$nuts = new Nuts_Nuts();
$nuts->processURL(array('Nuts_Hola'));

$nuts->config['description'] = "Hola";
$nuts->config['keywords'] = array('Hola','mundo');

if($nuts->route("","hola")):
elseif($nuts->route("hola/%name", "hola")): 
else:
    $nuts->error404();
endif;

$twig = $nuts->initTwig();
$nuts->render();
?>