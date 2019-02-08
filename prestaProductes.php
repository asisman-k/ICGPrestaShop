<?php
/*
Programa 1:
Data creació: 20150102
Script  per a consultar els productes creats a la taula integracio ICG i actualitzar el model de dades a Prestashop
*/
	require_once("Utils.php");

	$utils = new Utils();

	//Consulta productes creats nous
	$margeActualitzacio = strtotime("-60 minutes");
	$timestampACercar = date("Y-m-d H:i:s", $margeActualitzacio);
	$result_producte = $utils->nousProductes($timestampACercar);
	echo "PS_ICG_INTEGRATION: prestaProductes.php <br>\n";

	if( $utils->myDB->num_rows($result_producte) > 0 ){//Hi ha productes a crear/actualitzar
		while($row_producte = $utils->myDB->fetch_array($result_producte))
		{
			$idProductePS = $utils->existeixProducte($row_producte['icg_producte']);
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
					$idGrupTalla = $utils->crearGrupTalla($row_producte, $idProductePS);
					//Crear grup atributs color
					$idGrupColor = $utils->crearGrupColor($row_producte, $idProductePS);

					//Desar Producte/GrupTalla/GrupColor
					$utils->desarProducteTallaColor($idProductePS,$idGrupTalla,$idGrupColor);
			}else{
				//TODO: cas producte existent
				//Consultar si el producte existeix realment a Prestashop?
				//Consulta $idGrupTalla i $idGrupColor a la taula ps_producte_t_c
				$idGrupTalla = $utils->getGrup($idProductePS, "ps_grup_talla");
				$idGrupColor = $utils->getGrup($idProductePS, "ps_grup_color");
				echo "El producte $idProductePS ja existeix, i te un grup talla $idGrupTalla i color $idGrupColor<br>\n";
			}

			if($row_producte['ps_producte_atribut'] == 0){
				$nomTalla = $row_producte['icg_talla'];
				$idTalla = $utils->inserirAtribut($row_producte, $idGrupTalla, $nomTalla);

				$nomColor = $row_producte['icg_color'];
				$idColor = $utils->inserirAtribut($row_producte, $idGrupColor, $nomColor);

				$idCombination = $utils->inserirCombinacio($idProductePS, $idTalla, $idColor, $row_producte);
			}else{
				echo "La combinacio ".$row_producte['ps_producte_atribut']." ja existeix. No cal fer res.<br>\n";
			}

			echo "Si hem arribat aqui, vol dir que s'ha creat o ja existia el producte $idProductePS a Prestashop<br>\n";

			$utils->flagActualitzatProducte($row_producte);
		}
	}else{
		echo date("Y-m-d H:i:s").": No	 hi ha productes a actualitzar.";
	}
?>
