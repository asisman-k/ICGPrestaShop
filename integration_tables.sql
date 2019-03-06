SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;


CREATE TABLE IF NOT EXISTS `icg_ps_fabricant` (
  `icg_fabricant` int(5) NOT NULL,
  `ps_fabricant` int(5) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `icg_ps_preus` (
  `id` int(11) NOT NULL,
  `tarifa` int(4) NOT NULL,
  `icg_producte` int(11) NOT NULL,
  `icg_talla` varchar(15) NOT NULL,
  `icg_color` varchar(15) NOT NULL,
  `ps_producte` int(11) NOT NULL,
  `ps_producte_atribut` int(11) NOT NULL,
  `pvp` float NOT NULL,
  `dto_percent` float NOT NULL,
  `preu_oferta` float NOT NULL,
  `dto_euros` float NOT NULL,
  `iva` float NOT NULL,
  `pvp_siva` float NOT NULL,
  `preu_oferta_siva` float NOT NULL,
  `dto_euros_siva` float NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `flag_actualitzat` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `icg_ps_producte` (
  `id` int(11) NOT NULL,
  `ps_producte` int(11) NOT NULL,
  `ps_producte_atribut` int(11) NOT NULL,
  `icg_producte` int(11) NOT NULL,
  `icg_color` varchar(15) NOT NULL,
  `icg_talla` varchar(15) NOT NULL,
  `fabricant` int(5) NOT NULL,
  `nom_fabricant` varchar(30) NOT NULL,
  `ean13` bigint(20) NOT NULL,
  `descripcio` text NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `flag_actualitzat` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `icg_ps_stocks` (
  `id` int(11) NOT NULL,
  `ps_producte` int(11) NOT NULL,
  `ps_producte_atribut` int(11) NOT NULL,
  `icg_producte` int(11) NOT NULL,
  `icg_color` varchar(15) NOT NULL,
  `icg_talla` varchar(15) NOT NULL,
  `ean13` bigint(20) NOT NULL,
  `stock_actual` smallint(6) NOT NULL,
  `offset` smallint(6) NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `flag_actualitzat` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `ps_producte_oferta` (
  `ps_producte` int(11) NOT NULL,
  `specific_price` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `ps_producte_t_c` (
  `ps_producte` int(8) NOT NULL,
  `ps_grup_talla` int(8) NOT NULL,
  `ps_grup_color` int(8) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `icg_ps_fabricant`
  ADD PRIMARY KEY (`icg_fabricant`,`ps_fabricant`);

ALTER TABLE `icg_ps_preus`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `icg_ps_producte`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `icg_ps_stocks`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `ps_producte_oferta`
  ADD PRIMARY KEY (`ps_producte`);

ALTER TABLE `ps_producte_t_c`
  ADD PRIMARY KEY (`ps_producte`);


ALTER TABLE `icg_ps_preus`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `icg_ps_producte`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `icg_ps_stocks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
