<?php
require ("configuration.php");
/*
Classe genèrica per treballar amb una BD MS SQL Server
*/
class MSSQL{
	public $conexion;
	private $total_consultas;

   function __construct(){
      ini_set('mssql.charset', 'UTF-8');
		
      if(!isset($this->conexion))
      {  // Connect to MS SQL
         $server = ICG_HOST;
         $myDB = ICG_NAME;
         $odbc="dblib:host=$server;dbname=$myDB";
         $this->total_consultas = 0;

         try {
            $this->conexion = new PDO( $odbc , ICG_USER, ICG_PASSWORD);
         } catch (PDOException $e) {
            echo 'Connection failed: ' . $e->getMessage();
         }
      }
   }
   public function getConexion(){
      return $this->conexion;
   }
   public function consulta($consulta){
      $this->total_consultas++;
      $resultat = $this->conexion->prepare($consulta);
      if($resultat->execute())
      {
         return $resultat;
      } else {
		 //echo 'MSSQL Error: Consulta: '.$consulta."<br>";
         return 0;
      }
   }

   public function fetch_array($consulta){
      $resultat = $this->conexion->prepare($consulta);
      if($resultat->execute())
      {
         return $resultat->fetch(PDO::FETCH_ASSOC);
      } else {
    	 echo 'MSSQL Error: Consulta: '.$consulta."<br>";
         return 0;
      }
   }

   public function num_rows($consulta){
      $result = $this->conexion->prepare($consulta);
      $result->execute();
      return $result->rowCount();
   }

   public function getTotalConsultas(){
      return $this->total_consultas;
   }

	public function closeConnection(){
		return $this->conexion->closeCursor();
	}
}
?>
