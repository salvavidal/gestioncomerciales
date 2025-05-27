{*
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
*}

<div class="panel">
	<h3><i class="icon icon-credit-card"></i> {l s='Gestión comerciales' mod='gestioncomerciales'}</h3>
	<p>
		<strong>{l s='Here is my new generic module!' mod='gestioncomerciales'}</strong><br />
		{l s='Thanks to PrestaShop, now I have a great module.' mod='gestioncomerciales'}<br />
		{l s='I can configure it using the following configuration form.' mod='gestioncomerciales'}
	</p>
	<br />
	<p>
		{l s='This module will boost your sales!' mod='gestioncomerciales'}
	</p>
</div>

<form action="{$form_action}" method="post">
    <div class="form-group">
        <label>{$module->l('Selecciona Comercial')}</label>
        <select name="id_comercial" required>
            {foreach from=$commercials item=commercial}
                <option value="{$commercial.id}">{$commercial.nombre_apellidos}</option>
            {/foreach}
        </select>
    </div>

    <div class="form-group">
        <label>{$module->l('Selecciona Clientes')}</label>
        {foreach from=$clients item=client}
            <div>
                <input type="checkbox" name="id_clients[]" value="{$client.id_customer}" id="client_{$client.id_customer}">
                <label for="client_{$client.id_customer}">{$client.firstname} {$client.lastname}</label>
            </div>
        {/foreach}
    </div>

    <button type="submit" name="submitAssignClients" class="btn btn-default">{$module->l('Asignar Clientes')}</button>
</form>

