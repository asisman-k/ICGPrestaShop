<?php
/*
Programa 2:
Data creació: 20150103
Script  per a consultar els preus dels productes creats a la taula integració ICG i actualitzar-los al Prestashop
*/
	require_once("Utils.php");
	//$utils = new Utils(True); //When debug
    $utils = new Utils();

	//Consulta productes creats nous
	$margeActualitzacio = strtotime("-60 minutes");
	$timestampACercar = date("Y-m-d H:i:s", $margeActualitzacio);
	$result_producte = $utils->nousPreus($timestampACercar);
	//echo "PS_ICG_INTEGRATION: prestaPreus.php <br>\n";

    $total_per_actualitzar = $utils->myDB->num_rows($result_producte);
    $preu_actualitzat = 0;
	if( $total_per_actualitzar > 0 ){//Hi ha productes a crear/actualitzar
		while($row_producte = $utils->myDB->fetch_array($result_producte))
		{
				if($row_producte['ps_producte_atribut'] < 1){
					echo "ERR >> El producte ICG ".$row_producte['icg_producte']." no te PS producte atribut ".$row_producte['ps_producte_atribut']."<br>\n";
				}else{
					//print_r($row_producte);
					if(!$utils->actualitzarPreus($row_producte)){
						echo "ERR >> No existeix el producte ICG ".$row_producte['icg_producte']."  or ".$row_producte['icg_reference']." que es producte PS ".$row_producte['ps_producte']." i combinacio ".$row_producte['ps_producte_atribut'].". Comprovar taula icg_ps_preus<br>\n";
                        //$utils->myDB->consulta("UPDATE icgps.icg_ps_preus SET flag_actualitzat = 2 WHERE ps_producte = ".$row_producte['ps_producte']." AND ps_producte_atribut = ".$row_producte['ps_producte_atribut']);
                        $utils->flagNoActualitzatPreus($row_producte);
						continue;
					}
					if($row_producte['dto_percent'] > 0){
                        
						try{
                            $utils->afegirDescompte($row_producte);
						} catch (Exception $e){
    					    echo "El producte ICG ".$row_producte['icg_producte']."  or ".$row_producte['icg_reference']." que es producte PS ".$row_producte['ps_producte']." i combinacio ".$row_producte['ps_producte_atribut']." ha donat error. Comprovar taula icg_ps_preus<br>\n";    
                            echo 'Caught exception: ',  $e->getMessage(), "\n";
                            continue;
						}
						//echo "Hem actualitzat el preu del producte ".$row_producte['icg_producte']." a preu ".$row_producte['pvp_siva']." AMB descompte<br>\n";
					}else{
						//echo "Hem actualitzat el preu del producte ".$row_producte['icg_producte']." a preu ".$row_producte['pvp_siva']." sense descompte<br>\n";
					}
					$utils->flagActualitzatPreus($row_producte);
					$preu_actualitzat++;
				}
		}
	}else{
		echo date("Y-m-d H:i:s").": No hi ha preus a actualitzar.";
	}

	if($preu_actualitzat){
		echo "====================<br>\n";
		echo "Total preus per actualitzar: ".$total_per_actualitzar."<br>\n";
		echo "Total preus actualitzats: ".$preu_actualitzat."<br>\n";
	}else{
        echo "Res actualitzat <br>\n";
	}
?>