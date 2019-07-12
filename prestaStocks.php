<?php
/*
Programa 3:
Data creació: 20150103
Script  per a consultar el stock de la taula integració ICG i actualitzar-lo al Prestashop
*/
	require_once("Utils.php");

	$utils = new Utils();

	//Consulta productes creats nous
	$margeActualitzacio = strtotime("-60 minutes");
	$timestampACercar = date("Y-m-d H:i:s", $margeActualitzacio);
	$result_producte = $utils->nousStocks($timestampACercar);

	//echo "PS_ICG_INTEGRATION: prestaStocks.php <br>\n";
    $total_per_actualitzar = $utils->myDB->num_rows($result_producte);
    $stocks_actualitzat = 0;
	if( $total_per_actualitzar > 0 ){//Hi ha productes a crear/actualitzar
		while($row_producte = $utils->myDB->fetch_array($result_producte))
		{
			if($row_producte['ps_producte_atribut'] < 1){
				//echo "ERR >> Hi ha un problema amb el producte ICG ".$row_producte['icg_producte']." or ".$row_producte['icg_reference']." amb producte PS ".$row_producte['ps_producte']." i la combinació ".$row_producte['ps_producte_atribut']."<br>\n";
			}else{
				$id = $utils->actualitzarStock($row_producte['ps_producte'], $row_producte['ps_producte_atribut'], $row_producte['stock_actual']);

				$utils->flagActualitzatStock($row_producte);
				$stocks_actualitzat++;
			}
		}
	}else{
		//echo date("Y-m-d H:i:s").": No	hi ha stocks a actualitzar.";
	}
	if($stocks_actualitzat){
		echo "=====================<br>\n";
		echo "Total stocks per actualitzar: ".$total_per_actualitzar."<br>\n";
		echo "Total stocks actualitzats: ".$stocks_actualitzat."<br>\n";
	}
?>
