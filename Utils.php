<?php
/*
Utilitats genèriques API prestashop
*/
require_once("configuration.php");
require_once("DBMySQL.php");
require_once('PSWebServiceLibrary.php');


/**
 * @package PrestaShopWebservice
 */
class Utils
{
    /**
    *  VARIABLES I CONSTANTS
    */
    /* Variables base de dades */
    public $myDB;
    public $myDBPS;


    /* Variables de classe */
    protected $webService;
    protected $idProducte;
    protected $idFabricant;

    /**
    * MÈTODES
    */
    function __construct()
    {
      if(!isset($this->myDB))
      {
         $this->myDB = new MySQL();
         $this->webService = new PrestaShopWebservice(PS_SHOP_PATH, PS_WS_AUTH_KEY, DEBUG);
      }

    if(!isset($this->myDBPS))
      {
         $this->myDBPS = new MySQLPS();
      }
    }

    /* Obtenir productes a tractar */
    public function nousProductes($timestampACercar){
      $consulta = "SELECT * FROM icgps.icg_ps_producte WHERE flag_actualitzat = 1";
      return $this->myDB->consulta($consulta);
    }

    /* Obtenir productes a tractar */
    public function nousStocks($timestampACercar){
      $consulta = "SELECT * FROM icgps.icg_ps_stocks WHERE flag_actualitzat = 1";
      return $this->myDB->consulta($consulta);
    }

    /* Obtenir productes a tractar */
    public function nousPreus($timestampACercar){
      $consulta = "SELECT * FROM icgps.icg_ps_preus WHERE flag_actualitzat = 1";
      return $this->myDB->consulta($consulta);
    }

    /* Obtenir productes a tractar */
    public function totsProductesPS(){
      $consulta = "SELECT DISTINCT ps_producte FROM icgps.icg_ps_producte WHERE ps_producte != 0";
      return $this->myDB->consulta($consulta);
    }

    /* Obtenir combinacions a tractar */
    public function totsProductesPSSenseAtribut(){
      $consulta = "SELECT ps_producte, icg_producte, icg_color, icg_talla, ean13 FROM icgps.icg_ps_producte WHERE ps_producte_atribut = -1";
      return $this->myDB->consulta($consulta);
    }

    /**
        BLOC FABRICANT
    */
    /* Consultar si existeix fabricant a PS */
    public function existeixFabricant($idFabricantICG){
      $consulta = "SELECT * FROM icgps.icg_ps_fabricant WHERE icg_fabricant = $idFabricantICG";
      $result_ps_fabricant = $this->myDB->consulta($consulta);

      if( $this->myDB->num_rows($result_ps_fabricant) > 0 ){
        //echo "El fabricant existex, no cal crear-lo <br>\n";
        $row_fabricant = $this->myDB->fetch_array($result_ps_fabricant);
        return $row_fabricant['ps_fabricant'];
      }else{
        return false;
      }
    }

    /* Crear fabricant */
    public function crearFabricant($idFabricantICG, $nomFabricantICG){
      $idFabricantPS = self::existeixFabricant($idFabricantICG);

      if(!$idFabricantPS){
        $optCreate = array('resource' => 'manufacturers');
        $xmlCreate = $this->webService->get(array('url' => PS_SHOP_PATH.'/api/manufacturers?schema=blank'));
        $resourceCreate = $xmlCreate->children()->children();

        $resourceCreate->name = $this->encodeToUtf8($nomFabricantICG);
        $resourceCreate->active = 1;

        $optCreate['postXml'] = $xmlCreate->asXML();
        $xmlResponse = $this->webService->add($optCreate);

        $idFabricantPS = $xmlResponse->manufacturer->id;

        if($this->myDB->consulta("INSERT INTO icgps.icg_ps_fabricant (ps_fabricant, icg_fabricant) VALUES ($idFabricantPS,$idFabricantICG)")){
          //echo "Fabricant $idFabricantICG:$nomFabricantICG creat correctament al Prestashop amb num $idFabricantPS <br>\n";
        }
      }

      return $idFabricantPS;
    }



    /**
        BLOC PRODUCTE
    */
    /* Consultar si existeix producte a PS mirant la nostra taula */
    public function existeixProducte($idProducteICG){
      $consulta = "SELECT * FROM icgps.icg_ps_producte WHERE icg_producte = $idProducteICG";
      $result_ps_producte = $this->myDB->consulta($consulta);

      if( $this->myDB->num_rows($result_ps_producte) > 0 ){
        $row_producte = $this->myDB->fetch_array($result_ps_producte);
        //echo "El producte ".$row_producte['ps_producte']." existex, no cal crear-lo <br>\n";
        return $row_producte['ps_producte'];
      }else{
        return false;
      }
    }

    /* Consultar si existeix un producte a PS mirant remotament a PS */
    public function existeixRealmentProducte($idProductPS){
      $opt['resource'] = 'products';
      $opt['id'] = $idProductPS;
      //echo "Producte PS: ".$idProductPS;
      try{
        $xml = $this->webService->head($opt);
        return True;
      }catch (Exception $e){
        return False;
      }
    }

    public function crearProducte($row_producte,$idFabricantPS){
      $idProductePS;
      $optCreate = array('resource' => 'products');
      $xmlCreate = $this->webService->get(array('url' => PS_SHOP_PATH.'/api/products?schema=blank'));
      $resourceCreate = $xmlCreate->children()->children();
      $resourceCreate->name->language[0] = $this->encodeToUtf8($row_producte['descripcio']);
      $resourceCreate->name->language[1] = $this->encodeToUtf8($row_producte['descripcio']);
      $resourceCreate->name->language[2] = $this->encodeToUtf8($row_producte['descripcio']);
      if(isset($idFabricantPS)){
        $resourceCreate->id_manufacturer = $idFabricantPS;
      }
      $resourceCreate->price = 0;
      $resourceCreate->id_category_default = ICG_CATEGORY;
      $resourceCreate->associations->categories->category->id = ICG_CATEGORY;
      $resourceCreate->active = 1;
      $resourceCreate->id_shop_default = 1;
      $resourceCreate->available_for_order = 1;
      $resourceCreate->show_price = 1;
      $resourceCreate->id_tax_rules_group = 1;
      $resourceCreate->minimal_quantity = 1;
      $resourceCreate->state = 1;
      $resourceCreate->reference = $row_producte['icg_producte'];
      //$resourceCreate->advanced_stock_management = 1;
      //TODO: Convert àéï...
      $stringURL = $this->creaUrlLink($row_producte['descripcio']); // Converts spaces to dashes
      $resourceCreate->link_rewrite->language[0] = $stringURL;
      $resourceCreate->link_rewrite->language[1] = $stringURL;
      $resourceCreate->link_rewrite->language[2] = $stringURL;

      $optCreate['postXml'] = $xmlCreate->asXML();
      $xmlResponse = $this->webService->add($optCreate);
      $idProductePS = $xmlResponse->product->id;

      if($this->myDB->consulta("UPDATE icgps.icg_ps_producte SET ps_producte = ".$idProductePS." WHERE icg_producte = ".$row_producte['icg_producte'])){
        //echo "Producte ".$row_producte['icg_producte']." creat correctament a Prestashop amb num $idProductePS i el idFabricant $idFabricantPS <br>\n";
      }

      return $idProductePS;
    }

    /**
        BLOC GRUPS TALLA/COLOR

    */
    public function crearGrupTallaColor($row_producte, $idProductePS, $tipus){
      $idGrupTallaColor;
      $optCreate = array('resource' => 'product_options');
      $xmlCreate = $this->webService->get(array('url' => PS_SHOP_PATH.'/api/product_options?schema=blank'));
      $resourceCreate = $xmlCreate->children()->children();
      $resourceCreate->name->language[0] = $idProductePS."_".$tipus;
      $resourceCreate->name->language[1] = $idProductePS."_".$tipus;
      $resourceCreate->name->language[2] = $idProductePS."_".$tipus;
      $resourceCreate->public_name->language[0] = $this->encodeToUtf8($row_producte['descripcio'])." ".$tipus;
      $resourceCreate->public_name->language[1] = $this->encodeToUtf8($row_producte['descripcio'])." ".$tipus;
      $resourceCreate->public_name->language[2] = $this->encodeToUtf8($row_producte['descripcio'])." ".$tipus;
      $resourceCreate->group_type = "select";

      $optCreate['postXml'] = $xmlCreate->asXML();
      $xmlResponse = $this->webService->add($optCreate);
      $idGrupTalla = $xmlResponse->product_option->id;
      //echo "El grup talla $idGrupTalla del producte PS $idProductePS s'ha creat correctament <br>\n";

      return $idGrupTalla;
    }

    /* Desar grups Talla Color d'un producte */
    public function desarProducteTallaColor($idProductePS,$idGrupTalla,$idGrupColor){
      try{
        $resultat = $this->myDB->consulta("INSERT INTO icgps.ps_producte_t_c (ps_producte, ps_grup_talla, ps_grup_color) VALUES ($idProductePS,$idGrupTalla,$idGrupColor)");
      } catch (Exception $e){
        echo "Error: No s'ha pogut crear entrada ProducteTallaColor per: $idProductePS,$idGrupTalla,$idGrupColor <br>\n";
      }
    }

    /*
    A partir d'un ID d'atribut, consulta el nom en llengua ICG = 4
    */
    public function obtenirNomAtribut($id){
      //echo "obtenirNomAtribut <br>\n";
      $opt['resource'] = 'product_option_values';
      $opt['id'] = $id;
      $xml = $this->webService->get($opt);
      $resources = $xml->product_option_value->name;
      foreach($resources->language as $language){
        if($language['id'] == PS_LANG_ICG){
          //echo "Obtenim el valor: ".$language." <br>\n";
          return $language[0];
        }
      }
    }

    /* Consultar si existeix un atribut dins un grup d'atributs */
    public function existeixAtribut($idGrup,$nomAtribut){
      //echo "Dins grup $idGrup existeix atribut: $nomAtribut <br>\n";
      $opt['resource'] = 'product_options';
      $opt['id'] = $idGrup;
      $xml = $this->webService->get($opt);

      foreach($xml->product_option->associations->product_option_values->product_option_value as $valor){
        $nomActual = self::obtenirNomAtribut($valor->id);
        //echo "comparcio ($nomActual) amb ($nomAtribut)";
        if($nomActual == $nomAtribut){
          return $valor->id;
        }

      }

      return false;
    }


    /* Inserir talla */
    public function inserirAtribut($row_producte, $idGrupAtribut, $nomAtribut){
      //echo "Dins inserir atribut: $idGrupAtribut, $nomAtribut <br>\n";
      $idAtribut = self::existeixAtribut($idGrupAtribut,$nomAtribut);
      //echo "Existeix atribuit? ".$idAtribut."<br>";

      if(!$idAtribut){
        //echo $idGrupAtribut." - ".$nomAtribut." no existeixen, anem a inserir-los";
        $optCreate = array('resource' => 'product_option_values');
        $xmlCreate = $this->webService->get(array('url' => PS_SHOP_PATH.'/api/product_option_values?schema=blank'));
        $resourceCreate = $xmlCreate->children()->children();
        $resourceCreate->id_attribute_group = $idGrupAtribut;
        
        $resourceCreate->name->language[0] = $this->encodeToUtf8($nomAtribut);
        $resourceCreate->name->language[1] = $this->encodeToUtf8($nomAtribut);
        $resourceCreate->name->language[2] = $this->encodeToUtf8($nomAtribut);

        $optCreate['postXml'] = $xmlCreate->asXML();
        $xmlResponse = $this->webService->add($optCreate);
        $idAtribut = $xmlResponse->product_option_value->id;
        //echo "El atribut $idAtribut amb nom $nomAtribut s'ha creat correctament en el grup $idGrupAtribut <br>\n";
      }else{
        //echo "El atribut $idAtribut amb nom $nomAtribut ja estava creada correctament en el grup $idGrupAtribut <br>\n";
      }

      return $idAtribut;
    }

    /* Obtenir productes a tractar */
    public function getGrup($idProducte, $tipus){
      $consulta = "SELECT * FROM icgps.ps_producte_t_c WHERE ps_producte = ".$idProducte;
      //echo "Consulta $consulta <br>\n";
      $result = $this->myDB->consulta($consulta);
      $row_producte = $this->myDB->fetch_array($result);

      return $row_producte["$tipus"];
    }

    /**
        BLOC COMBINACIONS
    */

        /* Inserir combinacio */
        public function inserirCombinacio($idProductePS, $idTalla, $idColor, $row_producte){

            $optCreate = array('resource' => 'combinations');
            $xmlCreate = $this->webService->get(array('url' => PS_SHOP_PATH.'/api/combinations?schema=blank'));
            $resourceCreate = $xmlCreate->children()->children();
            $resourceCreate->id_product = $idProductePS;
            $resourceCreate->minimal_quantity = 1;
            $resourceCreate->ean13 = $row_producte['ean13'];
            $resourceCreate->associations->product_option_values->product_option_value->id = $idTalla;
            $product_option_value = $resourceCreate->associations->product_option_values->addChild('product_option_value');
            $product_option_value->addChild('id',$idColor);

            $optCreate['postXml'] = $xmlCreate->asXML();
            $xmlResponse = $this->webService->add($optCreate);
            $idCombinacio = $xmlResponse->combination->id;

            if($this->myDB->consulta("UPDATE icgps.icg_ps_producte SET ps_producte = ".$idProductePS.", ps_producte_atribut = ".$idCombinacio." WHERE icg_producte = ".$row_producte['icg_producte']." AND icg_color = '".$this->encodeToUtf8($row_producte['icg_color'])."' AND icg_talla = '".$this->encodeToUtf8($row_producte['icg_talla'])."'")){
              $this->myDB->consulta("UPDATE icgps.icg_ps_stocks SET ps_producte = ".$idProductePS.", ps_producte_atribut = ".$idCombinacio.", flag_actualitzat = 1 WHERE icg_producte = ".$row_producte['icg_producte']." AND icg_color = '".$this->encodeToUtf8($row_producte['icg_color'])."' AND icg_talla = '".$this->encodeToUtf8($row_producte['icg_talla'])."'");
              $this->myDB->consulta("UPDATE icgps.icg_ps_preus SET ps_producte = ".$idProductePS.", ps_producte_atribut = ".$idCombinacio.", flag_actualitzat = 1 WHERE icg_producte = ".$row_producte['icg_producte']." AND icg_color = '".$this->encodeToUtf8($row_producte['icg_color'])."' AND icg_talla = '".$this->encodeToUtf8($row_producte['icg_talla'])."'");
              //echo "inserirCombinacio: La combinacio $idCombinacio ($idTalla,$idColor) s'ha creat correctament <br>\n";
            }

            return $idCombinacio;
        }

        public function eliminarCombinacio($ps_producte, $ps_producte_atribut){
            //$row_producte['ps_producte'],$row_producte['ps_producte_atribut']);
            $optUpdate = array('resource' => 'combinations');
            $optUpdate['id'] = $ps_producte_atribut;
            $xmlUpdate = 0;
            try{
                $xmlUpdate = $this->webService->get($optUpdate);
            }catch (Exception $e){
                //La combinació no existeix a Prestashop, tenim dades incorrectes a la BD d'integració
                echo $e->getMessage();
                print("<pre>".print_r($optUpdate,true)."</pre>");
                print("<pre>".print_r($ps_producte_atribut,true)."</pre>");
                return false;
            }
            $this->myDB->consulta("UPDATE icgps.icg_ps_producte SET ps_producte_atribut = -1, flag_actualitzat = 0 WHERE ps_producte = ".$ps_producte." AND ps_producte_atribut =".$ps_producte_atribut);
            $this->myDB->consulta("UPDATE icgps.icg_ps_stocks SET ps_producte_atribut = -1, flag_actualitzat = 0 WHERE ps_producte = ".$ps_producte." AND ps_producte_atribut =".$ps_producte_atribut);
            $this->myDB->consulta("UPDATE icgps.icg_ps_preus SET ps_producte_atribut = -1, flag_actualitzat = 0 WHERE ps_producte = ".$ps_producte." AND ps_producte_atribut =".$ps_producte_atribut);            
        }


        public function flagActualitzatProducte($row_producte){
          $this->myDB->consulta("UPDATE icgps.icg_ps_producte SET flag_actualitzat = 0 WHERE id = ".$row_producte['id']);
        }



        /**
        BLOC STOCK
        */
        /* Canviar stock */
        public function actualitzarStock($idProductePS, $idAtributPS, $stock){

          $result = $this->myDBPS->consulta("UPDATE ".DB_NAME_PS.".ps_stock_available SET quantity = ".$stock." WHERE id_product = ".$idProductePS." AND id_product_attribute =".$idAtributPS);
          //Per a cada nou stock
          if($result)
          {
              //echo "El stock del producte ($idProductePS _ $idAtributPS) s'ha actualitzat correctament a $stock";
          }

        }


        public function flagActualitzatStock($row_producte){
          $this->myDB->consulta("UPDATE icgps.icg_ps_stocks SET flag_actualitzat = 0 WHERE id = ".$row_producte['id']);
        }


        /**
        BLOC PREUS
        */
        /* Actualitzar preu */
        public function actualitzarPreus($producte){
          //Obtenir XML
          $optUpdate = array('resource' => 'combinations');
          $optUpdate['id'] = $producte['ps_producte_atribut'];
          $xmlUpdate = 0;
          try{
            $xmlUpdate = $this->webService->get($optUpdate);
          }catch (Exception $e){
            //La combinació no existeix a Prestashop, tenim dades incorrectes a la BD d'integració
            echo $e->getMessage();
            print("<pre>".print_r($optUpdate,true)."</pre>");
            print("<pre>".print_r($producte,true)."</pre>");
            return false;
          }

          
          $resourceUpdate = $xmlUpdate->children()->children();

          //Modificar XML
          $resourceUpdate->price = $producte['pvp_siva'];

          //Enviar XML
          $optUpdate['putXml'] = $xmlUpdate->asXML();
          $optUpdate['id'] = $producte['ps_producte_atribut'];
          $xmlResponse = $this->webService->edit($optUpdate);
          return true;
        }

        public function existeixDescompte($producte){
          //echo "dins existeix descompte <br>\n";
          $result = $this->myDB->consulta("SELECT * FROM icgps.ps_producte_oferta WHERE ps_producte = ".$producte['ps_producte']);
          if($this->myDB->num_rows($result) > 0 ){
            $result_ps_producte = $this->myDB->fetch_array($result);
            //echo "Hem trobat el preu del producte ".$producte['ps_producte']." es el ".$result_ps_producte['specific_price']." <br>\n";
            return $result_ps_producte['specific_price'];
          }else{
            //echo "NO hem trobat el preu del producte ".$producte['ps_producte']." <br>\n";
            return false;
          }
        }

        /* Afegir descompte */
        public function afegirDescompte($producte){
          //echo "dins afegir descompte <br>\n";
          $idDescompte = self::existeixDescompte($producte);

          if($idDescompte){
            //echo "Actualitzem el $idDescompte  <br>\n";
            //Obtenir XML
            $optUpdate = array('resource' => 'specific_prices');
            $optUpdate['id'] = $idDescompte;
            $xmlUpdate = $this->webService->get($optUpdate);
            $resourceUpdate = $xmlUpdate->children()->children();

            //Omplir camps
            $resourceUpdate->reduction = $producte['dto_percent']/100;

            //Enviar XML
            $optUpdate['putXml'] = $xmlUpdate->asXML();
            $optUpdate['id'] = $idDescompte;
            $xmlResponse = $this->webService->edit($optUpdate);

            }else{

              //echo "NO Actualitzem el $idDescompte. L'afegim de nou  <br>\n";
                //Obtenir XML
                $optCreate = array('resource' => 'specific_prices');
                $xmlCreate = $this->webService->get(array('url' => PS_SHOP_PATH.'/api/specific_prices?schema=blank'));
                $resourceCreate = $xmlCreate->children()->children();

                //Omplir camps
                $resourceCreate->id_product = $producte['ps_producte'];
                $resourceCreate->id_shop = 0;
                $resourceCreate->id_cart = 0;
                $resourceCreate->id_currency = 0;
                $resourceCreate->id_country = 0;
                $resourceCreate->id_group = 0;
                $resourceCreate->id_customer = 0;
                $resourceCreate->price = 0;
                $resourceCreate->reduction_tax = 0;
                $resourceCreate->from = '0000-00-00 00:00:00';
                $resourceCreate->to = '0000-00-00 00:00:00';
                $resourceCreate->reduction = $producte['dto_percent']/100;
                $resourceCreate->reduction_type = 'percentage';
                $resourceCreate->from_quantity = 1;

                //Enviar XML
                $optCreate['postXml'] = $xmlCreate->asXML();
                $xmlResponse = $this->webService->add($optCreate);

                //Desar codi descompte del producte
                $idDescompte = $xmlResponse->specific_price->id;
                $this->myDB->consulta("INSERT INTO icgps.ps_producte_oferta (ps_producte, specific_price) VALUES (".$producte['ps_producte'].",$idDescompte)");

            }
        }

        public function flagActualitzatPreus($row_producte){
          $this->myDB->consulta("UPDATE icgps.icg_ps_preus SET flag_actualitzat = 0 WHERE id = ".$row_producte['id']);
        }

        public function parseja($response){
                return $this->webService->parseXML($response);
        }

        /* Utils genèrics */
        public function encodeToUtf8($string) {
          $string = preg_replace("/{|}/","",$string);
          return mb_convert_encoding($string, "UTF-8", mb_detect_encoding($string, "UTF-8, ISO-8859-1, ISO-8859-15", true));
        }

        public function creaUrlLink($s) {
          
          $s = preg_replace("/á|à|â|ã|ª|ä/","a",$s);
          $s = preg_replace("/Á|À|Â|Ã|Ä/","A",$s);
          $s = preg_replace("/é|è|ê|ë/","e",$s);
          $s = preg_replace("/É|È|Ê|Ë/","E",$s);
          $s = preg_replace("/í|ì|î|ï/","i",$s);
          $s = preg_replace("/Í|Ì|Î|Ï/","I",$s);
          $s = preg_replace("/ó|ò|ô|õ|º|ö/","o",$s);
          $s = preg_replace("/Ó|Ò|Ô|Õ|Ö/","O",$s);
          $s = preg_replace("/ú|ù|û|ü/","u",$s);
          $s = preg_replace("/Ú|Ù|Û|Ü/","U",$s);
          $s = str_replace("ñ","n",$s);
          $s = str_replace("Ñ","N",$s);
          $s = str_replace("ç","c",$s);
          $s = str_replace("Ç","C",$s);          
          $s = strtolower($s);

          $s = str_replace("'", "" , $s);
          $s = str_replace('%20', ' ', $s);//$s = str_replace(" ","_",$s);
          $s = preg_replace('~[^\pL0-9_]+~u', '-', $s);
          $s = trim($s, "-");
          $s = iconv("utf-8", "us-ascii//TRANSLIT", $s);
          $s = preg_replace('~[^-a-z0-9_]+~', "", $s);
          return $s;
        }
        
        public function netejaIntegracioPS($idProductePS){
          if($this->myDB->consulta("UPDATE icgps.icg_ps_producte SET ps_producte = 0, ps_producte_atribut = 0, flag_actualitzat = 1 WHERE ps_producte = ".$idProductePS)){
              $this->myDB->consulta("UPDATE icgps.icg_ps_stocks SET ps_producte = 0, ps_producte_atribut = 0, flag_actualitzat = 1  WHERE ps_producte = ".$idProductePS);
              $this->myDB->consulta("UPDATE icgps.icg_ps_preus SET ps_producte = 0, ps_producte_atribut = 0, flag_actualitzat = 1  WHERE ps_producte = ".$idProductePS);
              $this->myDB->consulta("DELETE FROM icgps.ps_producte_t_c WHERE ps_producte = ".$idProductePS);
              $this->myDB->consulta("DELETE FROM icgps.ps_producte_oferta WHERE ps_producte = ".$idProductePS);              
              echo "El producte PS ".$idProductePS." no exisitia i l'hem marcat per recrearse<br>\n";
            }
        }
}
?>
