# Install
Integració ICG-Prestashop
## Views in ICG Manager database
You need some views prepared in MS SQL Server and access avaiable from outside in order to work.
*  TODO

## Prestashop
You need to create a database (maybe in the same server of your Prestashop) and create integration tables
*  [Integration database schema](https://github.com/oriolpiera/ICGPrestaShop/blob/master/integration_tables.sql)

Also you need to activate WebService in your Prestashop installation.


# Documentation
## Models
This models files get data from integration tables and publish into your Prestashop
#### prestaProductes.php
Create new products and new combinations 
#### prestaStocks.php
Update stock of every combinations
#### prestaPreus.php
Update price of every combinations

## Scripts
This scripts get data from the ICG Manager database and insert into integration database tables
#### scriptProductesCarrega.php
This is the first script that you have to run. Get the last products, stocks and prices from ICG and put into integration tables in mysql.

## Modules
Some generic modules to access to recurses
#### DBMSSQLServer.php
A simple module to get data from ICG Manager tables in the MS SQL Server database
#### DBMySQLServer.php
A simple module to get, insert and update data to MySQL integration database
#### PSWebServiceLibrary.php
Last WebService library from [Prestashop repository](https://github.com/PrestaShop/PrestaShop-webservice-lib)

## Configuration
Private zone, change to **configuration.php** and don't commit it!
#### configuration_example.php
To declare some variables to access to Modules. You have to modify with your credentials that in order to get it works.





