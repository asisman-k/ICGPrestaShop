<?php
/*
Programa principal per a carregar les novetats (articles, stocks i preus) a ICG a les taula d'integració ICG 
*/
	require_once("DBMySQL.php");
	require_once("DBMSSQLServer.php");

	$myDB = new MySQL();
	//$margeActualitzacio = strtotime("-1440 minutes");//24hores
	$margeActualitzacio = strtotime("-144000 minutes");//2400hores
	$timestampACercar = date("Y-m-d H:i:s", $margeActualitzacio);
	$query = "SELECT * FROM view_imp_articles WHERE Visible_Web = 'T' AND Fecha_Modificado BETWEEN '".date("Y-m-d H:i:s",$margeActualitzacio)."' AND '".date("Y-m-d H:i:s")."'";
	//$query = "SELECT * FROM view_imp_articles WHERE Visible_Web = 'T'";
	$msDB = new MSSQL();
	echo "PS_ICG_INTEGRATION: scriptProductesCarrega.php <br>\n\n";

	//Consulta links desats i explora el primer buscant nous links i els guarda a enllacos
	$result = $msDB->consulta($query);
	//$result = $msDB->consulta("SELECT * FROM view_imp_articles WHERE Visible_Web = 'T' AND Fecha_Modificado BETWEEN '2015-11-03' AND '2015-11-06 23:59:59.997'");
	$num_linies = $result->fetchColumn();
	if( $num_linies > 0 ){//Hi ha productes a crear/actualitzar
		while($row = $result->fetch(PDO::FETCH_ASSOC))
		{
			$ean13;
			$result3 = $myDB->consulta("SELECT * FROM icgps.icg_ps_producte WHERE icg_producte = ".$row['CODARTICULO']." AND icg_color = '".$row['COLOR']."' AND icg_talla = '".$row['TALLA']."'");

			if( $myDB->num_rows($result3) > 0 ){ //És una actualització de producte
				//Primer mirem si té definit el codi de barres
				if($row['CODBARRAS'] == 0){
					//$result2 = $myDB->consulta("UPDATE icgps.icg_ps_producte SET descripcio = '".iconv("ISO-8859-1","UTF-8", $row['DESCRIPCION'])."', timestamp =  '".date("Y-m-d H:i:s")."', flag_actualitzat = 1, fabricant = ".$row['Codproveedor']." WHERE icg_producte = ".$row['CODARTICULO']." AND icg_talla = '".$row['TALLA']."' AND icg_color = '".$row['COLOR']."'");
					//$ean13 = "UPDATE: No te definit el ean13";
				} else{
					//$result2 = $myDB->consulta("UPDATE icgps.icg_ps_producte SET descripcio = '".iconv("ISO-8859-1","UTF-8", $row['DESCRIPCION'])."', timestamp = '".date("Y-m-d H:i:s")."', flag_actualitzat = 1, fabricant = ".$row['Codproveedor'].", ean13 = ".$row['CODBARRAS']." WHERE icg_producte = ".$row['CODARTICULO']." AND icg_talla = '".$row['TALLA']."' AND icg_color = '".$row['COLOR']."'");
					//$ean13 = "UPDATE: Si te definit el ean13. Es el ".$row['CODBARRAS'];
				}
			}else{//És una creació de producte
					if($row['Codproveedor'] == 0){
						$result2 = $myDB->consulta("INSERT INTO icgps.icg_ps_producte (icg_producte, icg_talla, icg_color, descripcio, timestamp,flag_actualitzat, ean13) VALUES (".$row['CODARTICULO'].", '".$row['TALLA']."', '".$row['COLOR']."','".iconv("ISO-8859-1","UTF-8", $row['DESCRIPCION'])."', '".date("Y-m-d H:i:s")."',1,'".$row['CODBARRAS']."')");
					}else{
						$result2 = $myDB->consulta("INSERT INTO icgps.icg_ps_producte (icg_producte, icg_talla, icg_color, descripcio, timestamp,flag_actualitzat, fabricant, ean13, nom_fabricant) VALUES (".$row['CODARTICULO'].", '".$row['TALLA']."', '".$row['COLOR']."','".iconv("ISO-8859-1","UTF-8", $row['DESCRIPCION'])."', '".date("Y-m-d H:i:s")."',1,".$row['Codigo_Marca'].",'".$row['CODBARRAS']."','".$row['Descripcion_Marca']."')");
					}
					//Falta insert taula icg_ps_preus
					$ean13 = "INSERT: ";




					/**
					Aprofitem per inserir també el preu
					*/
					$result_preu = $msDB->consulta("SELECT * FROM view_imp_preus WHERE Codarticulo = ".$row['CODARTICULO']." AND Talla = '".$row['TALLA']."' AND Color = '".$row['COLOR']."'");

					//Per a cada nou stock
					$row_preu = $msDB->fetch_array($result_preu);

					$myDB->consulta("INSERT INTO icgps.icg_ps_preus (tarifa, icg_producte, icg_talla, icg_color, timestamp, pvp, dto_percent, preu_oferta, dto_euros, iva, pvp_siva, preu_oferta_siva, dto_euros_siva, flag_actualitzat) VALUES (1,".$row['CODARTICULO'].", '".$row['TALLA']."', '".$row['COLOR']."','".date("Y-m-d H:i:s")."',".$row_preu['Pbruto_iva'].",".$row_preu['Dto_porc'].",".$row_preu['Pneto_iva'].",".$row_preu['Dto_impote_iva'].",".$row_preu['Iva'].",".$row_preu['Pbruto_s_iva'].",".$row_preu['Pneto_s_iva'].",".$row_preu['Dto_importe_s_iva'].",1)");

					if($myDB->mysql_affected_rows() > 0){
						echo "S'ha actualitzat els preus del producte correctament: ".$row['CODARTICULO']."  =>  ".$row['TALLA']." - ".$row['COLOR']."<br>\n";
					}else{
						//echo "El producte ".$row['Codarticulo']."  =>  ".$row['Talla']." - ".$row['Color']." no exiteix encara.<br>\n";
						echo "No s'ha actualitzat el preu del producte<br>\n";
					}


					/**
					Aprofitem per inserir també l'stock
					*/
					$result_stock = $msDB->consulta("SELECT * FROM view_imp_stocks WHERE Codarticulo = ".$row['CODARTICULO']." AND Talla = '".$row['TALLA']."' AND Color = '".$row['COLOR']."'");

					//Per a cada nou stock
					$row_stock = $msDB->fetch_array($result_stock);

					$myDB->consulta("INSERT INTO icgps.icg_ps_stocks (icg_producte, icg_talla, icg_color, timestamp,ean13,stock_actual,flag_actualitzat) VALUES (".$row['CODARTICULO'].", '".$row['TALLA']."', '".$row['COLOR']."','".date("Y-m-d H:i:s")."','".$row_stock['CODBARRAS']."',".intval($row_stock['Stock_disponible']).",1)");

					if($myDB->mysql_affected_rows() > 0){
						echo "S'ha actualitzat l'stock del producte correctament: ".$row['CODARTICULO']."  =>  ".$row['TALLA']." - ".$row['COLOR']."<br>\n";
					}else{
						//echo "El producte ".$row['Codarticulo']."  =>  ".$row['Talla']." - ".$row['Color']." no exiteix encara.<br>\n";
						echo "No s'ha actualitzat el stock del producte<br>\n";
					}
			}


			if($result2){
				echo "1: $ean13.<br>\n";
			}else{
				//echo "Mierder";
			}
		}
	}else{
		echo date("Y-m-d H:i:s").": No hi ha productes modificats a ICG.";
	}
?>
