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

    public function hookBackOfficeHeader()
    {
        if (Tools::getValue('configure') == $this->name) {
            $this->context->controller->addJS($this->_path.'views/js/back.js');
            $this->context->controller->addCSS($this->_path.'views/css/back.css');
        }
    }

    public function hookDisplayAdminCustomers($params)
    {
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

    public function getContent()
    {
        $output = '';
        $token = Tools::getAdminTokenLite('AdminModules');

        // Manejar solicitud AJAX
        if (Tools::getValue('ajax') == '1' && Tools::getValue('action') == 'getEmployees') {
            $onlyCommercials = Tools::getValue('only_commercials') == '1';
            $employees = $this->getAllCommercials($onlyCommercials);
            
            header('Content-Type: application/json');
            die(json_encode($employees));
        }

        if (Tools::isSubmit('submitAssignClients')) {
            $this->processAssignClients();
            $output .= $this->displayConfirmation($this->l('Configuración actualizada'));
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

        // Renderizar el contenido
        $output .= $this->renderList();
        $output .= $this->renderAssignForm($commercials, $clients);
        $output .= $this->renderClientCommercialList();

        return $output;
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
        }
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
        $helper->show_toolbar = true;
        $helper->title = $this->l('Listado de Comerciales');
        $helper->table = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;

        return $helper->generateList($commercials, $fields_list);
    }

    private function renderAssignForm($commercials, $clients)
    {
        $fields_form = [
            'form' => [
                'legend' => [
                    'title' => $this->l('Asignar Clientes a Comercial'),
                    'icon' => 'icon-user'
                ],
                'input' => [
                    [
                        'type' => 'select',
                        'label' => $this->l('Comercial'),
                        'name' => 'id_comercial',
                        'required' => true,
                        'options' => [
                            'query' => $commercials,
                            'id' => 'id',
                            'name' => 'firstname'
                        ]
                    ]
                ],
                'submit' => [
                    'title' => $this->l('Guardar'),
                ]
            ],
        ];

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitAssignClients';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = [
            'fields_value' => $this->getConfigFormValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        ];

        return $helper->generateForm([$fields_form]);
    }

    private function getConfigFormValues()
    {
        return [
            'id_comercial' => Tools::getValue('id_comercial'),
        ];
    }

    private function renderClientCommercialList()
    {
        $clients = $this->getClientCommercialList();
        
        $fields_list = [
            'id_customer' => [
                'title' => $this->l('ID Cliente'),
                'align' => 'center',
            ],
            'firstname' => [
                'title' => $this->l('Nombre'),
            ],
            'lastname' => [
                'title' => $this->l('Apellido'),
            ],
            'email' => [
                'title' => $this->l('Email'),
            ],
            'commercial_name' => [
                'title' => $this->l('Comercial Asignado'),
                'align' => 'center',
            ],
        ];

        $helper = new HelperList();
        $helper->shopLinkType = '';
        $helper->simple_header = true;
        $helper->identifier = 'id_customer';
        $helper->show_toolbar = false;
        $helper->title = $this->l('Clientes y Comerciales Asignados');
        $helper->table = $this->name . '_clients';
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;

        return $helper->generateList($clients, $fields_list);
    }

    private function getClientCommercialList()
    {
        $sql = '
            SELECT 
                c.`id_customer`, 
                c.`firstname`, 
                c.`lastname`, 
                c.`email`,
                CONCAT(e.`firstname`, " ", e.`lastname`) as commercial_name
            FROM `' . _DB_PREFIX_ . 'customer` c
            LEFT JOIN `' . _DB_PREFIX_ . 'comerciales_clientes` cc ON c.`id_customer` = cc.`id_cliente`
            LEFT JOIN `' . _DB_PREFIX_ . 'employee` e ON cc.`id_comercial` = e.`id_employee`
            ORDER BY c.`lastname` ASC, c.`firstname` ASC
        ';
        return Db::getInstance()->executeS($sql);
    }
}