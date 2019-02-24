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
			if (!$idProductePS){
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
			}else{
				//Consulta $idGrupTalla i $idGrupColor a la taula ps_producte_t_c
				$idGrupTalla = $utils->getGrup($idProductePS, "ps_grup_talla");
				$idGrupColor = $utils->getGrup($idProductePS, "ps_grup_color");
				//echo "El producte $idProductePS ja existeix, i te un grup talla $idGrupTalla i color $idGrupColor<br>\n";
			}
			//echo "Producte Atribut PS: ".$row_producte['ps_producte_atribut']."<br>";
			if($row_producte['ps_producte_atribut'] == 0){
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
				
				
			}else{
				//echo "La combinacio ".$row_producte['ps_producte_atribut']." ja existeix. No cal fer res.<br>\n";
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
	//if($combinacions_creades){
		//echo "===============================<br>\n";
		//echo "Total a actualitzar: ".$total_per_actualitzar."<br>\n";
		//echo "Total de productes creats: ".$productes_creats."<br>\n";
		//echo "Total de combinacions creades: ".$combinacions_creades."<br>\n";
		//echo "Total productes ja existents: ".$productes_jaexistents."<br>\n";
		//echo "Total de combinacions amb error: ".$combinacions_error."<br>\n";
	//}
}

?>