<?php
# MySQL Prestashop
define (DB_HOST, "localhost");
define (DB_USER, "prestashop_dbuser");
define (DB_PASSWORD, "prestashop_dbpass");
define (DB_NAME_ICG, "database_integration_name");
define (DB_NAME_PS, "database_prestashop_name");

# MSSQL ICG
define (ICG_HOST, "IP_icg_server");
define (ICG_NAME, "database_icg_name");
define (ICG_USER, "database_icg_user");
define (ICG_PASSWORD, "database_icg_pass");

# API Prestashop
define (DEBUG, false);         // Debug mode
define (PS_SHOP_PATH, 'URL of your shop');                // Root path of your PrestaShop store
define (PS_WS_AUTH_KEY, 'token');   // Auth key (Get it in your Back Office)
define (ICG_LANG, 'lang where you storage the name in ICG'); #ICG lang where load the name of products
define (ICG_CATEGORY, 'category where you save the products taht you need to manage from PS');
?>
