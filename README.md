Nuts
====

A Microframwork for PHP apps

## Installation

Download it, and place it in your proyect folder with a copy of Twig.

## More than HelloWord App (on Apache)

`.htaccess`

    RewriteEngine on
    RewriteRule ^static/(.*)$ static/$1 [QSA,L,NC]
    RewriteRule ^(.*)$ index.php [QSA,L,NC]

`index.php`

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
    
`templates/hola.twig`

    Hola {{name|default("mundo")}}!
    <br/>
    <a href="/init/hola/Manu"> Hola Manu </a> - <a href="/init/hola/Jose"> Hola Jose </a>
    <br /><br />
    <form method="post">
    <input type="hidden" name="action" value="addRegister" />
    <input type="text" name="txt" value="{{post.txt|default('escribe texto aqui')}}" /> 
    <input type="submit"/>
    </form>
    {% for register in Nuts_Hola.getRegisters %}
        {{register.txt}}
        <hr>
    {% endfor %}

`Nuts/Hola.php`

    <?php
    class Nuts_Hola{

        function public_addRegister($txt){
            $collection = DB::$db->registros;
            $collection->insert(array("txt"=>$txt));
        }
        
        function getRegisters(){
            $collection = DB::$db->registros;
            return $collection->find();
        }
        
    }
    ?>
