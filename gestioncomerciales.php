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

if (!defined('_PS_VERSION_')) {
    exit;
}

use PrestaShop\PrestaShop\Core\Grid\Column\Type\Common\ActionColumn;
use PrestaShop\PrestaShop\Core\Grid\Action\Row\Type\LinkRowAction;

class Gestioncomerciales extends Module
{
    protected $fields_value = []; 

    public function __construct()
    {
        $this->name = 'gestioncomerciales';
        $this->tab = 'administration';
        $this->version = '1.0.0';
        $this->author = 'Salvador Vidal Villahoz';
        $this->need_instance = 1;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Gestión comerciales');
        $this->description = $this->l('Gestiona la creación de comerciales y la relación con los clientes.');
        $this->ps_versions_compliancy = array('min' => '1.7', 'max' => _PS_VERSION_);
    }

    public function install()
    {
        include(dirname(__FILE__).'/sql/install.php');
        
        // Crear la pestaña en el menú
        $tab = new Tab();
        $tab->active = 1;
        $tab->class_name = 'AdminGestionComerciales';
        $tab->name = array();
        foreach (Language::getLanguages(true) as $lang) {
            $tab->name[$lang['id_lang']] = 'Gestión Comerciales';
        }
        $tab->id_parent = (int)Tab::getIdFromClassName('SELL');
        $tab->module = $this->name;
        $tab->add();

        return parent::install() && 
               $this->registerHook('header') &&
               $this->registerHook('backOfficeHeader') &&
               $this->registerHook('displayAdminCustomers') &&
               $this->registerHook('actionCustomerGridDefinitionModifier') &&
               $this->installOverrides();
    }

    public function uninstall()
    {
        include(dirname(__FILE__).'/sql/uninstall.php');
        
        // Eliminar la pestaña del menú
        $id_tab = (int)Tab::getIdFromClassName('AdminGestionComerciales');
        if ($id_tab) {
            $tab = new Tab($id_tab);
            $tab->delete();
        }
        
        return parent::uninstall() && $this->uninstallOverrides();
    }
	
    public function hookBackOfficeHeader()
    {
        if (Tools::getValue('configure') == $this->name) {
            $this->context->controller->addJS($this->_path.'views/js/back.js');
            $this->context->controller->addCSS($this->_path.'views/css/back.css');
        }
    }

    public function hookDisplayAdminCustomers($params)
    {
        // Añadir botón de login como cliente en el listado de clientes
        $id_customer = (int)Tools::getValue('id_customer');
        
        if ($id_customer) {
            $customer = new Customer($id_customer);
            if (Validate::isLoadedObject($customer)) {
                $token = Tools::getAdminTokenLite('AdminCustomers');
                $loginUrl = $this->context->link->getAdminLink('AdminCustomers', true, [], [
                    'action' => 'loginAsCustomer',
                    'id_customer' => $id_customer,
                    'token' => $token
                ]);
                
                return '<div class="btn-group">
                    <a href="'.$loginUrl.'" class="btn btn-primary" target="_blank">
                        <i class="icon-user"></i> '.$this->l('Login como Cliente').'
                    </a>
                </div>';
            }
        }
    }

    public function hookActionCustomerGridDefinitionModifier($params)
    {
        // Añadir columna de acción para login como cliente
        $definition = $params['definition'];
        
        $definition
            ->getColumns()
            ->addAfter(
                'optin',
                (new ActionColumn('login_as_customer'))
                    ->setName($this->l('Login como Cliente'))
                    ->setOptions([
                        'actions' => [
                            (new LinkRowAction('login_as_customer'))
                                ->setIcon('account_circle')
                                ->setOptions([
                                    'route' => 'admin_customers_login_as_customer',
                                    'route_param_name' => 'customerId',
                                    'route_param_field' => 'id_customer',
                                    'target' => '_blank',
                                ])
                                ->setName($this->l('Login como Cliente')),
                        ],
                    ])
            );
    }

    public function getContent()
    {
        $token = Tools::getAdminTokenLite('AdminModules');

        // Manejar solicitud AJAX
        if (Tools::getValue('ajax') == '1' && Tools::getValue('action') == 'getEmployees') {
            $onlyCommercials = Tools::getValue('only_commercials') == '1';
            $employees = $this->getAllCommercials($onlyCommercials);
            
            header('Content-Type: application/json');
            echo json_encode($employees);
            exit;
        }

        if (Tools::isSubmit('submitAssignClients')) {
            $this->processAssignClients();
        }

        // Obtener datos de comerciales y clientes
        $commercials = $this->getAllCommercials(true);
        $allEmployees = $this->getAllCommercials(false);
        $clients = $this->getAllClients();

        // Asignar variables para la plantilla
        $this->context->smarty->assign([
            'commercials' => $commercials,
            'allEmployees' => $allEmployees,
            'module_dir' => $this->_path,
            'currentIndex' => AdminController::$currentIndex . '&configure=' . $this->name,
            'token' => $token,
        ]);

        $output = $this->renderList();
        $output .= '<div class="panel">
            <div class="panel-heading">
                ' . $this->l('Asignación de Clientes a Comercial') . '
            </div>
            <div class="form-wrapper">
                <form method="post" class="form-horizontal">
                    <div class="form-group">
                        <label class="control-label col-lg-3">' . $this->l('Seleccionar Empleado') . '</label>
                        <div class="col-lg-9">
                            <select name="id_comercial" class="form-control" required>
                                <option value="">' . $this->l('Seleccione un empleado') . '</option>';
                                foreach ($allEmployees as $employee) {
                                    $output .= '<option value="' . $employee['id'] . '">' . 
                                        $employee['firstname'] . ' ' . $employee['lastname'] . ' (' . $employee['profile'] . ')' .
                                    '</option>';
                                }
        $output .= '</select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="control-label col-lg-3">' . $this->l('Clientes Disponibles') . '</label>
                        <div class="col-lg-9">
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th><input type="checkbox" id="checkAll" /></th>
                                            <th>' . $this->l('ID') . '</th>
                                            <th>' . $this->l('Nombre') . '</th>
                                            <th>' . $this->l('Apellido') . '</th>
                                            <th>' . $this->l('Email') . '</th>
                                        </tr>
                                    </thead>
                                    <tbody>';
                                    foreach ($clients as $client) {
                                        $output .= '<tr>
                                            <td><input type="checkbox" name="id_clients[]" value="' . $client['id_customer'] . '" /></td>
                                            <td>' . $client['id_customer'] . '</td>
                                            <td>' . $client['firstname'] . '</td>
                                            <td>' . $client['lastname'] . '</td>
                                            <td>' . $client['email'] . '</td>
                                        </tr>';
                                    }
        $output .= '</tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="panel-footer">
                        <button type="submit" name="submitAssignClients" class="btn btn-default pull-right">
                            <i class="process-icon-save"></i> ' . $this->l('Guardar') . '
                        </button>
                    </div>
                </form>
            </div>
        </div>';

        $output .= $this->renderClientCommercialList();

        return $output;
    }

    private function renderList()
    {
        $commercials = $this->getAllCommercials(true);

        $fields_list = [
            'id' => [
                'title' => $this->l('ID'),
                'align' => 'center',
                'class' => 'fixed-width-xs'
            ],
            'firstname' => [
                'title' => $this->l('Nombre'),
                'type' => 'text'
            ],
            'lastname' => [
                'title' => $this->l('Apellido'),
                'type' => 'text'
            ],
            'profile' => [
                'title' => $this->l('Perfil'),
                'type' => 'text',
                'align' => 'center'
            ]
        ];

        $helper = new HelperList();
        $helper->shopLinkType = '';
        $helper->simple_header = true;
        $helper->identifier = 'id';
        $helper->actions = ['edit', 'delete'];
        $helper->show_toolbar = true;
        $helper->title = $this->l('Listado de Comerciales (Empleados)');
        $helper->table = $this->name . '_commercial_list';
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;

        $list = '<div class="panel">
            <div class="panel-heading clearfix">
                <i class="icon-user"></i> ' . $this->l('Listado de Comerciales (Empleados)') . '
            </div>
            <div class="panel-body">
                <div class="row" style="margin-bottom: 15px;">
                    <div class="col-md-12">
                        <div class="btn-group pull-right" role="group">
                            <button type="button" id="showOnlyCommercials" class="btn btn-sm btn-primary" style="border-radius: 4px 0 0 4px;">
                                <i class="icon-user"></i> ' . $this->l('Solo Comerciales') . '
                            </button>
                            <button type="button" id="showAllEmployees" class="btn btn-sm btn-default" style="border-radius: 0 4px 4px 0; margin-left: -1px;">
                                <i class="icon-group"></i> ' . $this->l('Todos los Empleados') . '
                            </button>
                        </div>
                        <div class="clearfix"></div>
                    </div>
                </div>
                <div id="' . $helper->table . '" data-ajax-url="' . $this->context->link->getAdminLink('AdminModules') . '&configure=' . $this->name . '">';
        $list .= $helper->generateList($commercials, $fields_list);
        $list .= '</div>
            </div>
        </div>';

        return $list;
    }

    private function renderClientCommercialList()
    {
        $clients = $this->getClientCommercialList();

        foreach ($clients as &$client) {
            $client['commercial_name'] = (!empty($client['commercial_firstname']) && !empty($client['commercial_lastname']))
                ? trim($client['commercial_firstname'] . ' ' . $client['commercial_lastname'])
                : $this->l('Sin asignar');
        }

        $fields_list = [
            'id_customer' => [
                'title' => $this->l('ID Cliente'),
                'align' => 'center',
                'class' => 'fixed-width-xs',
            ],
            'firstname' => [
                'title' => $this->l('Nombre'),
                'type' => 'text',
            ],
            'lastname' => [
                'title' => $this->l('Apellido'),
                'type' => 'text',
            ],
            'email' => [
                'title' => $this->l('Email'),
                'type' => 'text',
            ],
            'commercial_name' => [
                'title' => $this->l('Comercial Asignado'),
                'type' => 'text',
                'align' => 'center',
            ],
        ];

        $helper = new HelperList();
        $helper->shopLinkType = '';
        $helper->simple_header = true;
        $helper->identifier = 'id_customer';
        $helper->actions = [];
        $helper->show_toolbar = false;
        $helper->title = $this->l('Listado de Clientes y Comerciales Asignados');
        $helper->table = $this->name . '_client_commercial_list';
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;

        return $helper->generateList($clients, $fields_list);
    }

    private function processAssignClients()
    {
        $id_comercial = (int)Tools::getValue('id_comercial');
        $id_clients = Tools::getValue('id_clients');

        if ($id_comercial && !empty($id_clients)) {
            foreach ($id_clients as $id_cliente) {
                Db::getInstance()->delete('comerciales_clientes', 'id_cliente = ' . (int)$id_cliente);
                Db::getInstance()->insert('comerciales_clientes', [
                    'id_comercial' => (int)$id_comercial,
                    'id_cliente' => (int)$id_cliente
                ]);
            }
            $this->context->controller->confirmations[] = $this->l('Clientes asignados correctamente al comercial.');
        } else {
            $this->context->controller->errors[] = $this->l('Selecciona un comercial y al menos un cliente.');
        }
    }

    private function getAllCommercials($onlyCommercials = false)
    {
        $id_lang = (int)$this->context->language->id;
        
        $sql = '
            SELECT 
                e.id_employee AS id, 
                e.firstname, 
                e.lastname, 
                pl.name AS profile
            FROM ' . _DB_PREFIX_ . 'employee e
            LEFT JOIN ' . _DB_PREFIX_ . 'profile_lang pl ON e.id_profile = pl.id_profile AND pl.id_lang = ' . $id_lang;
        
        if ($onlyCommercials) {
            $sql .= ' WHERE LOWER(pl.name) LIKE "%comercial%" OR LOWER(pl.name) LIKE "%sales%" OR LOWER(pl.name) LIKE "%ventas%"';
        }
        
        $sql .= ' ORDER BY e.lastname ASC, e.firstname ASC';
        
        return Db::getInstance()->executeS($sql);
    }

    private function getAllClients()
    {
        $sql = 'SELECT `id_customer`, `firstname`, `lastname`, `email` FROM `' . _DB_PREFIX_ . 'customer`';
        return Db::getInstance()->executeS($sql);
    }

    private function getCommercialById($id_commercial)
    {
        $sql = 'SELECT * FROM `' . _DB_PREFIX_ . 'comerciales` WHERE `id` = ' . (int)$id_commercial;
        return Db::getInstance()->getRow($sql);
    }

    private function getClientCommercialList()
    {
        $sql = '
            SELECT 
                c.`id_customer`, 
                c.`firstname`, 
                c.`lastname`, 
                c.`email`,
                e.`firstname` AS commercial_firstname, 
                e.`lastname` AS commercial_lastname
            FROM `' . _DB_PREFIX_ . 'customer` c
            LEFT JOIN `' . _DB_PREFIX_ . 'comerciales_clientes` cc ON c.`id_customer` = cc.`id_cliente`
            LEFT JOIN `' . _DB_PREFIX_ . 'employee` e ON cc.`id_comercial` = e.`id_employee`
            ORDER BY c.`lastname` ASC, c.`firstname` ASC
        ';
        return Db::getInstance()->executeS($sql);
    }
}