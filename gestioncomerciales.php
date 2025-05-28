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
        // Este hook es necesario aunque esté vacío
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
        // Solo mostrar el botón en la vista de detalle del cliente
        if (Tools::getValue('controller') == 'AdminCustomers' && Tools::getValue('id_customer')) {
            $id_customer = (int)Tools::getValue('id_customer');
            $customer = new Customer($id_customer);
            
            if (Validate::isLoadedObject($customer)) {
                $token = Tools::getAdminTokenLite('AdminCustomers');
                $loginUrl = $this->context->link->getAdminLink('AdminCustomers') . '&id_customer=' . $id_customer . '&action=loginAsCustomer&token=' . $token;
                
                return '<div class="btn-group">
                    <a href="'.$loginUrl.'" class="btn btn-primary">
                        <i class="icon-user"></i> '.$this->l('Login como Cliente').'
                    </a>
                </div>';
            }
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

    public function getContent()
    {
        $output = '';
        
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
            'token' => Tools::getAdminTokenLite('AdminModules'),
        ]);

        // Renderizar el listado de comerciales y el formulario unificado de asignación
        $output .= $this->renderList();
        $output .= $this->renderAssignForm($commercials, $clients);
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

        return $helper->generateList($commercials, $fields_list);
    }

    private function renderAssignForm($commercials, $clients)
    {
        $commercial_options = [];
        foreach ($commercials as $commercial) {
            $commercial_options[] = [
                'id_option' => $commercial['id'],
                'name' => $commercial['firstname'] . ' ' . $commercial['lastname']
            ];
        }

        $fields_form = [
            'form' => [
                'legend' => [
                    'title' => $this->l('Asignar Clientes a Comercial'),
                    'icon' => 'icon-user'
                ],
                'input' => [
                    [
                        'type' => 'select',
                        'label' => $this->l('Selecciona Comercial'),
                        'name' => 'id_comercial',
                        'required' => true,
                        'options' => [
                            'query' => $commercial_options,
                            'id' => 'id_option',
                            'name' => 'name'
                        ]
                    ]
                ],
                'submit' => [
                    'title' => $this->l('Asignar Clientes'),
                    'class' => 'btn btn-default pull-right'
                ]
            ]
        ];

        $helper = new HelperForm();
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->identifier = $this->identifier;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;
        $helper->title = $this->displayName;
        $helper->submit_action = 'submitAssignClients';

        $helper->fields_value = [
            'id_comercial' => '',
        ];

        $output = $helper->generateForm([$fields_form]);
        
        $output .= '<div class="panel">
            <div class="panel-heading">' . $this->l('Seleccionar Clientes') . '</div>
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

        $output .= '</tbody></table></div></div>';

        return $output;
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