<?php
require ("configuration.php");

/**
 * Connexió amb la base de dades d'enllaç amb ICG
 */
class MySQL{
   public $conexion;
   private $total_consultas;

   function __construct()
   {
      if(!isset($this->conexion))
      {
         $this->conexion = (mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD)) or die(mysqli_error($this->conexionPS));
         mysqli_select_db($this->conexion, DB_NAME_ICG) or die(mysqli_error($this->conexionPS));
      }
   }

   public function consulta($consulta)
   {
      $this->total_consultas++;
      $resultado = mysqli_query($this->conexion,$consulta);
      if(!$resultado)
      {
         echo 'MySQL Error: ' . mysqli_error($this->conexionPS)."Consulta: ".$consulta."<br>";
         //exit;
      }
      return $resultado;
   }

   public function fetch_array($consulta)
   {
      return mysqli_fetch_array($consulta);
   }

   public function num_rows($consulta)
   {
      return mysqli_num_rows($consulta);
   }

   public function getTotalConsultas()
   {
      return $this->total_consultas;
   }

   public function mysql_affected_rows(){
      return mysqli_affected_rows($this->conexion);
   }
}



/**
Conexio amb la BD de Prestashop
*/
class MySQLPS{
   public $conexionPS;
   private $total_consultasPS;

   function __construct()
   {
      if(!isset($this->conexionPS))
      {
         $this->conexionPS = (mysqli_connect(DB_HOST,DB_USER,DB_PASSWORD)) or die(mysqli_error($this->conexionPS));
         mysqli_select_db($this->conexionPS, DB_NAME_PS) or die(mysqli_error($this->conexionPS));
      }
   }

   public function consulta($consulta)
   {
      $this->total_consultasPS++;
      $resultado = mysqli_query($this->conexionPS,$consulta);
      if(!$resultado)
      {
         echo 'MySQL Error: ' . mysqli_error($this->conexionPS)."Consulta: ".$consulta."<br>";
         //exit;
      }
      return $resultado;
   }

   public function fetch_array($consulta)
   {
      return mysqli_fetch_array($consulta);
   }

   public function num_rows($consulta)
   {
      return mysqli_num_rows($consulta);
   }

   public function getTotalConsultas()
   {
      return $this->total_consultasPS;
   }

   public function mysql_affected_rows(){
      return mysqli_affected_rows($this->conexionPS);
   }
}

?>
