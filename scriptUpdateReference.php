<?php
/*
Programa per carregar les referencies dels pares a les taula d'integració ICG 
*/
    require_once("DBMySQL.php");
    require_once("DBMSSQLServer.php");
    require_once("Utils.php");

    $utils = new Utils();
    $msDB = new MSSQL();
    $myDB = new MySQL();
    
    for ($i = 1; $i <= 25; $i++) {
    $sql = "SELECT * FROM icgps.icg_ps_producte WHERE icg_reference = 0 LIMIT 1";
    
    foreach($myDB->consulta($sql) as $row){
        $sqlms = "SELECT * FROM view_imp_articles WHERE CODARTICULO = ".$row['icg_producte'];
        //$result = $msDB->consulta($sqlms);
        //$msrow_product = $result->fetch(PDO::FETCH_ASSOC);
          
        $msrow_product = $msDB->fetch_array($sqlms);
        
        if($msrow_product){
            $myDB->consulta("UPDATE icg_ps_producte SET icg_reference = '".$msrow_product['Referencia']."' , timestamp = '".date("Y-m-d H:i:s")."', flag_actualitzat = 1 WHERE icg_producte = ".$utils->encodeToUtf8($msrow_product['CODARTICULO']));
            $myDB->consulta("UPDATE icg_ps_preus SET icg_reference = '".$msrow_product['Referencia']."' , timestamp = '".date("Y-m-d H:i:s")."', flag_actualitzat = 1 WHERE icg_producte = ".$utils->encodeToUtf8($msrow_product['CODARTICULO']));
            $myDB->consulta("UPDATE icg_ps_stocks SET icg_reference = '".$msrow_product['Referencia']."' , timestamp = '".date("Y-m-d H:i:s")."', flag_actualitzat = 1 WHERE icg_producte = ".$utils->encodeToUtf8($msrow_product['CODARTICULO']));
        }else{
            echo "Potser s'ha de treure ja l'script ".$row['icg_producte']."\n";
        }
        
    }
    }
?>