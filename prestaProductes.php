<?php
/*
Programa 1:
Data creació: 20150102
Script  per a consultar els productes creats a la taula integracio ICG i actualitzar el model de dades a Prestashop
*/
require_once("Utils.php");

$utils = new Utils();
$productes_creats = 0;
$combinacions_creades = 0;
$total_per_actualitzar=0;
$productes_jaexistents = 0;
$combinacions_error = 0;
$combinacions_eliminades = 0;
$combinacions_invisibilitzades = 0;
$combinacions_revisibilitzades = 0;

try {
	//Consulta productes creats nous
	$margeActualitzacio = strtotime("-60 minutes");
	$timestampACercar = date("Y-m-d H:i:s", $margeActualitzacio);
	$result_producte = $utils->nousProductes($timestampACercar);
	//echo "PS_ICG_INTEGRATION: prestaProductes.php <br>\n";
    $total_per_actualitzar = $utils->myDB->num_rows($result_producte);
	if( $total_per_actualitzar > 0 ){//Hi ha productes a crear/actualitzar
		while($row_producte = $utils->myDB->fetch_array($result_producte))
		{
			$idProductePS = $utils->existeixProducte($row_producte['icg_producte']);
			//echo "Producte PS: ".$idProductePS."<br>";
			if (!$idProductePS & $row_producte['descatalogado'] <> 'T' & $row_producte['visibleweb'] <> 'F'){
					$idFabricantICG = $row_producte['fabricant'];
					$nomFabricantICG = $row_producte['nom_fabricant'];
					///Crear Fabricant (Si existeix, recupera n�mero)
					if($idFabricantICG != 0){
						$idFabricantPS = $utils->crearFabricant($idFabricantICG, $nomFabricantICG);
					}
					//Crear Producte
					$idProductePS =  $utils->crearProducte($row_producte,$idFabricantPS);
					//Crear grup atributs talla
					$idGrupTalla = $utils->crearGrupTallaColor($row_producte, $idProductePS, "talla");
					//Crear grup atributs color
					$idGrupColor = $utils->crearGrupTallaColor($row_producte, $idProductePS, "color");

					//Desar Producte/GrupTalla/GrupColor
					$utils->desarProducteTallaColor($idProductePS,$idGrupTalla,$idGrupColor);
					$productes_creats++;
			}elseif ($idProductePS){
				//Consulta $idGrupTalla i $idGrupColor a la taula ps_producte_t_c
                if($row_producte['fabricant'] != 0 && $row_producte['ps_producte'] > 0){
                    $utils->actualitzarMarca($idProductePS, $row_producte['fabricant'], $row_producte['nom_fabricant']);
                    echo "S'ha actualitzat a la marca ".$row_producte['nom_fabricant']."<br>\n";
                }
				$idGrupTalla = $utils->getGrup($idProductePS, "ps_grup_talla");
				$idGrupColor = $utils->getGrup($idProductePS, "ps_grup_color");
				echo "El producte $idProductePS ja existeix, i te un grup talla $idGrupTalla i color $idGrupColor<br>\n";
			}

			if($row_producte['ps_producte_atribut'] == 0 & $row_producte['descatalogado'] <> 'T' & $row_producte['visibleweb'] <> 'F'){
    		    //echo "La combinació és nova i a ICG esta posat per exisitir a la web, doncs la creem";
                $nomTalla = $utils->encodeToUtf8($row_producte['icg_talla']);
				$idTalla = $utils->inserirAtribut($row_producte, $idGrupTalla, $nomTalla);
				//echo "Talla inserida";
				$nomColor = $utils->encodeToUtf8($row_producte['icg_color']);
				$idColor = $utils->inserirAtribut($row_producte, $idGrupColor, $nomColor);
				//echo "Color inserit";
				$idCombination = $utils->inserirCombinacio($idProductePS, $idTalla, $idColor, $row_producte);
				if($idCombination){
					$combinacions_creades++;
					//echo "Combinacio inserida";
				}else{
					$combinacions_error++;
				}
			}elseif ($row_producte['ps_producte_atribut'] > 0){
				echo "La combinacio ".$row_producte['ps_producte_atribut']." ens consta que ja existeix a PS <br>\n";
                //Comprovar que ha canviat
                //Descatalogat?
                if($row_producte['descatalogado'] == 'T'){
                    echo "La combinació esta descatalogada, anem a esborrar-la: ".$row_producte['ps_producte_atribut']."<br>\n";
                    if($utils->eliminarCombinacio($row_producte['ps_producte'],$row_producte['ps_producte_atribut'])){
                        $combinacions_eliminades++;
                    }
                    
                }
                //Visibilitat?
                if($row_producte['visibleweb'] == 'F'){
                    echo "Hem trobat un producte per invisibilitzar a la web: ".$row_producte['icg_producte']." or ".$row_producte['icg_reference']." <br>\n";
                    if($utils->canviVisibilitatProducte($row_producte['ps_producte'], $row_producte['ps_producte_atribut'],0)){
                        $combinacions_invisibilitzades++;
                    }else{
                        echo "No hem pogut invisbilitzar \n";
                    }
                }
                //EAN?

                if($row_producte['icg_reference'] <> '' & $row_producte['visibleweb'] <> 'F' & $row_producte['descatalogado'] <> 'T'){
                    echo "Hem trobat un producte per actualitzar la referencia: ".$row_producte['icg_producte']." or ".$row_producte['icg_reference']." <br>\n";
                    if($utils->actualitzarReferencia($row_producte['ps_producte'], $row_producte['icg_reference'])){
                        echo "Referencia actualitzada ".$row_producte['icg_reference']."\n";
                    }else{
                        echo "No hem pogut actualitzar referencia ".$row_producte['icg_reference']." de ".$row_producte['ps_producte']."\n";
                    }
                }
				$productes_jaexistents++;
                
    		}elseif ($row_producte['ps_producte_atribut'] < -1){
                $idCombinacio = $row_producte['ps_producte_atribut']*-1;
                if($row_producte['ps_producte'] < -1){
                    $row_producte['ps_producte'] = $row_producte['ps_producte']*-1;
                }

				echo "La combinacio ".$row_producte['ps_producte_atribut']." ja existeix però no esta activa.<br>\n";
                //Comprovar que ha canviat
                //Descatalogat = F (hauriem de tornar a crear)
                
                //Visibilitat?
                if($row_producte['visibleweb'] == 'T'){
                    echo "Hem trobat un producte per visibilitzar a la web: ".$row_producte['icg_producte']."  or ".$row_producte['icg_reference']." <br>\n";
                    if($utils->canviVisibilitatProducte($row_producte['ps_producte'], $idCombinacio, 1)){
                        $combinacions_revisibilitzades++;
                    }else{
                        echo "No hem pogut invisbilitzar \n";
                    }
                }
                if($row_producte['visibleweb'] == 'F'){
                    echo "Hem trobat un producte per invisibilitzar a la web: ".$row_producte['icg_producte']." or ".$row_producte['icg_reference']." <br>\n";
                    if($utils->canviVisibilitatProducte($row_producte['ps_producte'], $row_producte['ps_producte_atribut'],0)){
                        $combinacions_invisibilitzades++;
                    }else{
                        echo "No hem pogut invisbilitzar \n";
                    }
                }
            }elseif ($row_producte['ps_producte_atribut'] == 0){
                //Visibilitat?
                if($row_producte['ps_producte'] < -1 & $row_producte['visibleweb'] == 'T'){
                    echo "Hem trobat un producte per visibilitzar a la web: ".$row_producte['icg_producte']."  or ".$row_producte['icg_reference']." <br>\n";
                    if($utils->canviVisibilitatProducte($row_producte['ps_producte'], $row_producte['ps_producte_atribut'], 1)){
                        $combinacions_revisibilitzades++;
                    }else{
                        echo "No hem pogut invisbilitzar \n";
                    }
                }
                if($row_producte['ps_producte'] > 1 & $row_producte['visibleweb'] == 'F'){
                    echo "Hem trobat un producte per invisibilitzar a la web: ".$row_producte['icg_producte']." or ".$row_producte['icg_reference']." <br>\n";
                    if($utils->canviVisibilitatProducte($row_producte['ps_producte'], $row_producte['ps_producte_atribut'],0)){
                        $combinacions_invisibilitzades++;
                    }else{
                        echo "No hem pogut invisbilitzar \n";
                    }
                }
				$productes_jaexistents++;
			}
			//echo "Si hem arribat aqui, vol dir que s'ha creat o ja existia el producte $idProductePS a Prestashop amb la combinacio $idCombination<br>\n";
			$utils->flagActualitzatProducte($row_producte);
		}
    }else{
		//echo date("Y-m-d H:i:s").": No hi ha productes a actualitzar<br>\n";
	}

} catch (PDOException $e) {
    //show exception
    echo $e->getMessage();
    print_r($row_producte);

} finally {
	if($combinacions_creades || $combinacions_eliminades || $combinacions_invisibilitzades){
		echo "===============================<br>\n";
		echo "Total a actualitzar: ".$total_per_actualitzar."<br>\n";
		echo "Total de productes creats: ".$productes_creats."<br>\n";
		echo "Total de combinacions creades: ".$combinacions_creades."<br>\n";
		echo "Total productes ja existents: ".$productes_jaexistents."<br>\n";
		echo "Total de combinacions amb error: ".$combinacions_error."<br>\n";
        echo "Total combiancions eliminades a PS: ".$combinacions_eliminades."<br>\n";
        echo "Total productes invisibilitzats: ".$combinacions_invisibilitzades."<br>\n";
        echo "Total productes revisibilitzats: ".$combinacions_revisibilitzades."<br>\n";
	}
}

?>