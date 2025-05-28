<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

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
               $this->registerHook('displayHeader') &&
               $this->registerHook('displayBackOfficeHeader') &&
               $this->registerHook('displayAdminCustomers') &&
               $this->registerHook('displayAdminCustomersForm') &&
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

    public function installOverrides()
    {
        try {
            return parent::installOverrides();
        } catch (Exception $e) {
            return false;
        }
    }

    public function uninstallOverrides()
    {
        try {
            return parent::uninstallOverrides();
        } catch (Exception $e) {
            return false;
        }
    }

    public function hookDisplayHeader()
    {
        return '';
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
        // Mostrar el botón en el listado de clientes
        if (Tools::getValue('controller') == 'AdminCustomers' && !Tools::getValue('id_customer')) {
            $id_customer = (int)Tools::getValue('id_customer');
            $token = Tools::getAdminTokenLite('AdminCustomers');
            $loginUrl = $this->context->link->getAdminLink('AdminCustomers') . '&id_customer=' . $id_customer . '&action=loginAsCustomer&token=' . $token;
            
            return '<a href="'.$loginUrl.'" class="btn btn-default" title="'.$this->l('Login como Cliente').'">
                <i class="icon-user"></i> '.$this->l('Login como Cliente').'
            </a>';
        }
        return '';
    }

    public function hookDisplayAdminCustomersForm($params)
    {
        // Mostrar el botón en el detalle del cliente
        if (Tools::getValue('controller') == 'AdminCustomers' && Tools::getValue('id_customer')) {
            $id_customer = (int)Tools::getValue('id_customer');
            $token = Tools::getAdminTokenLite('AdminCustomers');
            $loginUrl = $this->context->link->getAdminLink('AdminCustomers') . '&id_customer=' . $id_customer . '&action=loginAsCustomer&token=' . $token;
            
            return '<div class="panel">
                <div class="panel-heading">
                    <i class="icon-user"></i> '.$this->l('Acciones adicionales').'
                </div>
                <div class="form-wrapper">
                    <div class="form-group">
                        <a href="'.$loginUrl.'" class="btn btn-primary">
                            <i class="icon-user"></i> '.$this->l('Login como Cliente').'
                        </a>
                    </div>
                </div>
            </div>';
        }
        return '';
    }

    public function hookActionCustomerGridDefinitionModifier($params)
    {
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

    // Resto del código sin cambios...
    [Previous content continues...]