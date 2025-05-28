<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

use PrestaShop\PrestaShop\Core\Grid\Column\Type\Common\ActionColumn;
use PrestaShop\PrestaShop\Core\Grid\Action\Row\Type\LinkRowAction;
use PrestaShop\PrestaShop\Core\Grid\Action\Row\RowActionCollection;

class Gestioncomerciales extends Module
{
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
        
        if (!parent::install() || 
            !$this->registerHook('displayBackOfficeHeader') ||
            !$this->registerHook('displayAdminCustomers') ||
            !$this->registerHook('actionCustomerGridDefinitionModifier')) {
            return false;
        }
        
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
        
        if (!$tab->add()) {
            return false;
        }

        return true;
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
        
        return parent::uninstall();
    }

    public function hookDisplayBackOfficeHeader()
    {
        if (Tools::getValue('configure') == $this->name) {
            $this->context->controller->addJS($this->_path.'views/js/back.js');
            $this->context->controller->addCSS($this->_path.'views/css/back.css');
        }
    }

    public function hookDisplayAdminCustomers($params)
    {
        // Añadir botón en el listado de clientes
        $id_customer = (int)Tools::getValue('id_customer');
        if (!$id_customer) {
            return '';
        }

        $token = Tools::getAdminTokenLite('AdminCustomers');
        $loginUrl = $this->context->link->getAdminLink('AdminCustomers', true, [], [
            'action' => 'loginAsCustomer',
            'id_customer' => $id_customer,
            'token' => $token
        ]);

        return '<div class="btn-group">
            <a href="'.$loginUrl.'" class="btn btn-default" target="_blank">
                <i class="icon-user"></i> '.$this->l('Login como Cliente').'
            </a>
        </div>';
    }

    public function hookActionCustomerGridDefinitionModifier($params)
    {
        /** @var \PrestaShop\PrestaShop\Core\Grid\Definition\GridDefinition */
        $definition = $params['definition'];

        // Crear una nueva colección de acciones
        $rowActionCollection = new RowActionCollection();

        // Crear la acción de login
        $loginAction = new LinkRowAction('login_as_customer');
        $loginAction->setName($this->l('Login como Cliente'))
            ->setIcon('account_circle')
            ->setOptions([
                'route' => 'admin_customers_index',
                'route_param_name' => 'id_customer',
                'route_param_field' => 'id_customer',
                'extra_route_params' => [
                    'action' => 'loginAsCustomer'
                ],
                'target' => '_blank'
            ]);

        // Añadir la acción a la colección
        $rowActionCollection->add($loginAction);

        // Obtener las acciones existentes
        $actionsColumn = $definition->getColumns()->get('actions');
        $existingActions = $actionsColumn->getOptions()['actions'];

        // Añadir las acciones existentes a la colección
        foreach ($existingActions as $action) {
            $rowActionCollection->add($action);
        }

        // Actualizar las acciones en la columna
        $actionsColumn->setOptions([
            'actions' => $rowActionCollection
        ]);
    }

    public function getContent()
    {
        $output = '';
        
        if (Tools::isSubmit('submitAssignClients')) {
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
                $output .= $this->displayConfirmation($this->l('Clientes asignados correctamente al comercial.'));
            } else {
                $output .= $this->displayError($this->l('Selecciona un comercial y al menos un cliente.'));
            }
        }

        return $output . $this->renderForm();
    }

    protected function renderForm()
    {
        $commercials = $this->getAllCommercials();
        $clients = $this->getAllClients();
        $clientCommercialList = $this->getClientCommercialList();

        $this->context->smarty->assign([
            'commercials' => $commercials,
            'clients' => $clients,
            'clientCommercialList' => $clientCommercialList,
            'module_dir' => $this->_path,
        ]);

        return $this->display(__FILE__, 'views/templates/admin/assign_clients.tpl');
    }

    private function getAllCommercials()
    {
        $id_lang = (int)$this->context->language->id;
        
        $sql = '
            SELECT 
                e.id_employee AS id, 
                e.firstname, 
                e.lastname, 
                pl.name AS profile
            FROM ' . _DB_PREFIX_ . 'employee e
            LEFT JOIN ' . _DB_PREFIX_ . 'profile_lang pl ON e.id_profile = pl.id_profile AND pl.id_lang = ' . $id_lang . '
            ORDER BY e.lastname ASC, e.firstname ASC';
        
        return Db::getInstance()->executeS($sql);
    }

    private function getAllClients()
    {
        $sql = 'SELECT `id_customer`, `firstname`, `lastname`, `email` FROM `' . _DB_PREFIX_ . 'customer`';
        return Db::getInstance()->executeS($sql);
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
            ORDER BY c.`lastname` ASC, c.`firstname` ASC';
        
        return Db::getInstance()->executeS($sql);
    }
}