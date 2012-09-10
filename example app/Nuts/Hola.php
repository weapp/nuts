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