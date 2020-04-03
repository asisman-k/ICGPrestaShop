<?php
/*
Programa principal per a carregar les novetats (articles, stocks i preus) a ICG a les taula d'integració ICG 
*/
    require_once("DBMySQL.php");
    require_once("DBMSSQLServer.php");
    require_once("Utils.php");

    $utils = new Utils();
    $myDB = new MySQL();
    $margeActualitzacio = strtotime("-1440 minutes");//24hores
    //$margeActualitzacio = strtotime("-21600 minutes");//15 dies
    //$margeActualitzacio = strtotime("-14400 minutes");//10 dies
    $timestampACercar = date("Y-m-d H:i:s", $margeActualitzacio);
    //$query = "SELECT * FROM view_imp_articles WHERE Visible_Web = 'T' AND Fecha_Modificado BETWEEN '".date("Y-m-d H:i:s",$margeActualitzacio)."' AND '".date("Y-m-d H:i:s")."'";
    //$query = "SELECT * FROM view_imp_articles WHERE Visible_Web = 'T' AND CODARTICULO = 8765"; //Recarrega un producte concret
    //$query = "SELECT * FROM view_imp_articles WHERE Visible_Web = 'T'";//All web products
    $query = "SELECT * FROM view_imp_articles WHERE Fecha_Modificado BETWEEN '".date("Y-m-d H:i:s",$margeActualitzacio)."' AND '".date("Y-m-d H:i:s")."'"; //Last modified products

    $msDB = new MSSQL();
    //echo "PS_ICG_INTEGRATION: scriptProductesCarrega.php <br>\n\n";
    //Consulta links desats i explora el primer buscant nous links i els guarda a enllacos
    $result = $msDB->consulta($query);
    $productes_trobats_icg = 0;
    $productes_jatrobats = 0;
    $productes_encuats = 0;
    $stocs_encuats = 0;
    $preus_encuats = 0;
    $descatalogats_actualitzats = 0;
    $eans_actualitzats = 0;
    $visibles_actualitzats = 0;
    $marques_actualitzades = 0;
    if( $result > 0 ){//Hi ha productes a crear/actualitzar
        foreach ($result->fetchAll(PDO::FETCH_ASSOC) as $msrow_product)
        {
            $ean13;
            $productes_trobats_icg++;
            $result3 = $myDB->consulta("SELECT * FROM icgps.icg_ps_producte WHERE icg_producte = ".$msrow_product['CODARTICULO']." AND icg_color = '".$utils->encodeToUtf8($msrow_product['COLOR'])."' AND icg_talla = '".$utils->encodeToUtf8($msrow_product['TALLA'])."'");
            $myrows_prestashop = $myDB->num_rows($result3);
            if( $myrows_prestashop > 0 ){ //És una actualització de producte
                ////echo "Ja existeix el producte ".$msrow_product['DESCRIPCION'].": ".$msrow_product['CODARTICULO']."  =>  ".$msrow_product['TALLA']." - ".$msrow_product['COLOR']." a la taula temporal d'enllaç icg_ps_producte <br>";
                $myrow_producte = $myDB->fetch_array($result3);
                if( $msrow_product['DESCATALOGADO'] != $myrow_producte['descatalogado']){
                    //S'ha actualitzat el descatalogat
                    $myDB->consulta("UPDATE icgps.icg_ps_producte SET descatalogado = '".$msrow_product['DESCATALOGADO']."' , timestamp = '".date("Y-m-d H:i:s")."', flag_actualitzat = 1 WHERE icg_producte = ".$utils->encodeToUtf8($msrow_product['CODARTICULO'])." AND icg_color = '".$utils->encodeToUtf8($msrow_product['COLOR'])."' AND icg_talla = '".$utils->encodeToUtf8($msrow_product['TALLA'])."'");
                    $descatalogats_actualitzats++;
                    echo ">>DESCATALOGADO: S'ha canviat ".$myrow_producte['descatalogado']." per ".$msrow_product['DESCATALOGADO']." en el producte  ".$msrow_product['CODARTICULO']." AND icg_color = '".$msrow_product['COLOR']."' AND icg_talla = '".$msrow_product['TALLA']."'<br>\n";
                }
                if( intval($msrow_product['CODBARRAS']) != $myrow_producte['ean13']){
                    //S'ha actualitzat el descatalogat
                    $myDB->consulta("UPDATE icgps.icg_ps_producte SET ean13 = '".$msrow_product['CODBARRAS']."' , timestamp = '".date("Y-m-d H:i:s")."', flag_actualitzat = 1 WHERE icg_producte = ".$msrow_product['CODARTICULO']." AND icg_color = '".$utils->encodeToUtf8($msrow_product['COLOR'])."' AND icg_talla = '".$utils->encodeToUtf8($msrow_product['TALLA'])."'");
                    $eans_actualitzats++;
                    echo ">>CODBARRAS: S'ha canviat ".$myrow_producte['ean13']." per ".$msrow_product['CODBARRAS']." en el producte  ".$msrow_product['CODARTICULO']." AND icg_color = '".$msrow_product['COLOR']."' AND icg_talla = '".$msrow_product['TALLA']."'<br>\n";
                }                
                if( $msrow_product['Visible_Web'] != $myrow_producte['visibleweb']){
                    //S'ha actualitzat el descatalogat
                    $myDB->consulta("UPDATE icgps.icg_ps_producte SET visibleweb = '".$msrow_product['Visible_Web']."', timestamp = '".date("Y-m-d H:i:s")."', flag_actualitzat = 1 WHERE icg_producte = ".$msrow_product['CODARTICULO']." AND icg_color = '".$utils->encodeToUtf8($msrow_product['COLOR'])."' AND icg_talla = '".$utils->encodeToUtf8($msrow_product['TALLA'])."'");
                    $visibles_actualitzats++;
                    echo ">>Visible_Web: S'ha canviat ".$myrow_producte['visibleweb']." per ".$msrow_product['Visible_Web']." en el producte  ".$msrow_product['CODARTICULO']." AND icg_color = '".$msrow_product['COLOR']."' AND icg_talla = '".$msrow_product['TALLA']."'<br>\n";
                }
                if( $msrow_product['Codigo_Marca'] != $myrow_producte['fabricant'] || $msrow_product['Descripcion_Marca'] != $myrow_producte['nom_fabricant']){
                    //S'ha actualitzat el descatalogat
                    $myDB->consulta("UPDATE icgps.icg_ps_producte SET fabricant = '".$msrow_product['Codigo_Marca']."', nom_fabricant = '".$msrow_product['Descripcion_Marca']."', timestamp = '".date("Y-m-d H:i:s")."', flag_actualitzat = 1 WHERE icg_producte = ".$msrow_product['CODARTICULO']." AND icg_color = '".$utils->encodeToUtf8($msrow_product['COLOR'])."' AND icg_talla = '".$utils->encodeToUtf8($msrow_product['TALLA'])."'");
                    $marques_actualitzades++;
                    echo ">>Marca: S'ha canviat ".$myrow_producte['nom_fabricant']." per ".$msrow_product['Descripcion_Marca']." en el producte  ".$msrow_product['CODARTICULO']." AND icg_color = '".$msrow_product['COLOR']."' AND icg_talla = '".$msrow_product['TALLA']."'<br>\n";
                }

                $productes_jatrobats++;
			}else{
                $descatalogado = 'F';
                if($msrow_product['DESCATALOGADO'] == 'T'){
                    $descatalogado = 'T';
                }
                if($msrow_product['Codproveedor'] == 0){
                    $result2 = $myDB->consulta("INSERT INTO icgps.icg_ps_producte (icg_producte, icg_reference, icg_talla, icg_color, descripcio, timestamp,flag_actualitzat, ean13, descatalogado, visibleweb) VALUES (".$msrow_product['CODARTICULO'].", '".$msrow_product['Referencia']."', '".$utils->encodeToUtf8($msrow_product['TALLA'])."', '".$utils->encodeToUtf8($msrow_product['COLOR'])."','".$utils->encodeToUtf8($msrow_product['DESCRIPCION'])."', '".date("Y-m-d H:i:s")."',1,'".$msrow_product['CODBARRAS']."','".$descatalogado."','".$msrow_product['Visible_Web']."')");
                }else{
                    $result2 = $myDB->consulta("INSERT INTO icgps.icg_ps_producte (icg_producte, icg_reference, icg_talla, icg_color, descripcio, timestamp,flag_actualitzat, fabricant, ean13, nom_fabricant, descatalogado, visibleweb) VALUES (".$msrow_product['CODARTICULO'].", '".$msrow_product['Referencia']."', '".$utils->encodeToUtf8($msrow_product['TALLA'])."', '".$utils->encodeToUtf8($msrow_product['COLOR'])."','".$utils->encodeToUtf8($msrow_product['DESCRIPCION'])."', '".date("Y-m-d H:i:s")."',1,".$msrow_product['Codigo_Marca'].",'".$msrow_product['CODBARRAS']."','".$utils->encodeToUtf8($msrow_product['Descripcion_Marca'])."','".$descatalogado."','".$msrow_product['Visible_Web']."')");
                }
                
                if($myDB->mysql_affected_rows() > 0){
                    ////echo "S'ha creat el producte: ".$msrow_product['CODARTICULO']."  =>  ".$msrow_product['TALLA']." - ".$msrow_product['COLOR']."<br>\n";
                        $productes_encuats++;
                }

                /**
                 * Aprofitem per inserir també el preu
                 * */
                
                $result_preu = $msDB->consulta("SELECT * FROM view_imp_preus WHERE Codarticulo = ".$msrow_product['CODARTICULO']." AND Talla = '".$utils->encodeToUtf8($msrow_product['TALLA'])."' AND Color = '".$utils->encodeToUtf8($msrow_product['COLOR'])."'");
                //Per a cada nou stock
                $msrow_preu = $result_preu->fetch(PDO::FETCH_ASSOC);
                $myDB->consulta("INSERT INTO icgps.icg_ps_preus (tarifa, icg_producte, icg_reference, icg_talla, icg_color, timestamp, pvp, dto_percent, preu_oferta, dto_euros, iva, pvp_siva, preu_oferta_siva, dto_euros_siva, flag_actualitzat) VALUES (1,".$msrow_product['CODARTICULO'].", '".$msrow_product['Referencia']."',  '".$utils->encodeToUtf8($msrow_product['TALLA'])."', '".$utils->encodeToUtf8($msrow_product['COLOR'])."','".date("Y-m-d H:i:s")."',".$msrow_preu['Pbruto_iva'].",".$msrow_preu['Dto_porc'].",".$msrow_preu['Pneto_iva'].",".$msrow_preu['Dto_impote_iva'].",".$msrow_preu['Iva'].",".$msrow_preu['Pbruto_s_iva'].",".$msrow_preu['Pneto_s_iva'].",".$msrow_preu['Dto_importe_s_iva'].",1)");
                
                if($myDB->mysql_affected_rows() > 0){
                    ////echo "S'ha actualitzat els preus del producte correctament: ".$msrow_product['CODARTICULO']."  =>  ".$msrow_product['TALLA']." - ".$msrow_product['COLOR']."<br>\n";
                        $preus_encuats++;
                }else{
                    ////echo "El producte ".$msrow_product['CODARTICULO']."  =>  ".$msrow_product['TALLA']." - ".$msrow_product['COLOR']." no exiteix encara.<br>\n";
                    ////echo "No s'ha actualitzat el preu del producte<br>\n";
                }


                /**
                Aprofitem per inserir també l'stock
                */
                $result_stock = $msDB->consulta("SELECT * FROM view_imp_stocks WHERE Codarticulo = ".$msrow_product['CODARTICULO']." AND Talla = '".$utils->encodeToUtf8($msrow_product['TALLA'])."' AND Color = '".$utils->encodeToUtf8($msrow_product['COLOR'])."'");
                //Per a cada nou stock
                $msrow_stock = $result_stock->fetch(PDO::FETCH_ASSOC);
                $myDB->consulta("INSERT INTO icgps.icg_ps_stocks (icg_producte, icg_reference, icg_talla, icg_color, timestamp,ean13,stock_actual,flag_actualitzat) VALUES (".$msrow_product['CODARTICULO'].", '".$msrow_product['Referencia']."', '".$utils->encodeToUtf8($msrow_product['TALLA'])."', '".$utils->encodeToUtf8($msrow_product['COLOR'])."','".date("Y-m-d H:i:s")."','".$msrow_product['CODBARRAS']."',".intval($msrow_stock['Stock_disponible']).",1)");

                if($myDB->mysql_affected_rows() > 0){
                    ////echo "S'ha actualitzat l'stock del producte correctament: ".$msrow_product['CODARTICULO']."  =>  ".$msrow_product['TALLA']." - ".$msrow_product['COLOR']."<br>\n";
                    $stocs_encuats++;
                }else{
                    ////echo "El producte ".$msrow_product['CODARTICULO']."  =>  ".$msrow_product['TALLA']." - ".$msrow_product['COLOR']." no exiteix encara.<br>\n";
                    ////echo "No s'ha actualitzat el stock del producte<br>\n";
                }
            }
        }
    }else{
        //echo date("Y-m-d H:i:s").": No hi ha productes modificats a ICG.";
    }

    //Sortida script
    if($preus_encuats or $stocs_encuats or $productes_encuats or $descatalogats_actualitzats or $eans_actualitzats or $visibles_actualitzats){
        echo "  ============ ============= ============= ==========<br> \n";
        echo "Hem trobat ".$productes_trobats_icg." productes a la base de dades d'ICG<br>\n";
        echo "Hem trobat ".$descatalogats_actualitzats." productes que hem actualitzat el flag descatalogat.<br>\n";
        echo "Hem trobat ".$eans_actualitzats." productes que hem actualitzat el seu EAN13.<br>\n";
        echo "Hem trobat ".$visibles_actualitzats." productes que hem actualitzat la seva visibilitat web.<br>\n";
        echo "Hem trobat ".$productes_jatrobats." productes que ja teníem entrats<br>\n";
        echo "Hem trobat ".$productes_encuats." productes nous apunt per ser carregats<br>\n";
        echo "Hem trobat ".$stocs_encuats." stocs per ser actualitzats<br>\n";
        echo "Hem trobat ".$preus_encuats." preus per ser actualitzats<br>\n";
        echo "Hem trobat ".$marques_actualitzades." preus per ser actualitzats<br>\n";
        
    }  
?>