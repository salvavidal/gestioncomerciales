<?php
/**
* 2007-2024 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2024 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/
$sql = array();

// Crear la tabla comerciales
$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'comerciales` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `nombre_apellidos` VARCHAR(255) NOT NULL,
    `telefono` VARCHAR(20) DEFAULT NULL,
    `correo` VARCHAR(255) NOT NULL,
    `observaciones` TEXT DEFAULT NULL,
    PRIMARY KEY (`id`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

// Crear la tabla comerciales_clientes
$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'comerciales_clientes` (
    `id_comercial` INT(11) NOT NULL,
    `id_cliente` INT(11) NOT NULL,
    PRIMARY KEY (`id_comercial`, `id_cliente`),
    INDEX (`id_cliente`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

foreach ($sql as $query) {
    if (Db::getInstance()->execute($query) == false) {
        return false;
    }
}
