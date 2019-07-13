<?php
/*
scriptCheckPSProducts
Data creació: 20190222
Script  per a comprovar quins productes s'han esborrat de PS i encara no ho sabem
*/
require_once("Utils.php");

$utils = new Utils();
$productes_existents = 0;
$productes_reiniciats = 0;

try {
    //Consulta tots els productes
    $result_producte = $utils->totsProductesPS();
    //echo "PS_ICG_INTEGRATION: scriptCheckPSProducts.php <br>\n";
    $total_per_comprovar = $utils->myDB->num_rows($result_producte);
    if( $total_per_comprovar > 0 ){//Hi ha productes a crear/actualitzar
        while($row_producte = $utils->myDB->fetch_array($result_producte)){
            //echo "Producte PS: ".$idProductePS."<br>";
            $idProductePS = $row_producte['ps_producte'];
            $productes_existents++;
            if(!$utils->existeixRealmentProducte($idProductePS)){
                //Consultar si el producte existeix realment a Prestashop
                $utils->netejaIntegracioPS($idProductePS);
                $productes_reiniciats++;
                continue;
            }
        }
    }

}catch(Exception $e){
    //show exception
    echo $e->getMessage();
    print_r($row_producte);
}finally{
    if($productes_reiniciats){
        echo "===============================<br>\n";
        echo "Total productes comprovats: ".$productes_existents."<br>\n";
        echo "Total de productes que no hem trobat a PS i hem marcat per recrear: ".$productes_reiniciats."<br>\n";    
    }
}
?>