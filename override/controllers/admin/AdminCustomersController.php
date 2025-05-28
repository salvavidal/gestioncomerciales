<?php

class AdminCustomersController extends AdminCustomersControllerCore
{
    public function __construct()
    {
        parent::__construct();
        $this->bootstrap = true;
        
        // Registrar la acción loginAsCustomer
        if (!in_array('loginAsCustomer', $this->actions)) {
            $this->actions[] = 'loginAsCustomer';
        }
    }

    public function getList($id_lang, $order_by = null, $order_way = null, $start = 0, $limit = null, $id_lang_shop = false)
    {
        $id_employee = (int)$this->context->employee->id;

        if ($this->context->employee->id_profile != _PS_ADMIN_PROFILE_) {
            $this->_where .= ' AND a.id_customer IN (
                SELECT id_cliente 
                FROM ' . _DB_PREFIX_ . 'comerciales_clientes 
                WHERE id_comercial = ' . (int)$id_employee . '
            )';
        }

        parent::getList($id_lang, $order_by, $order_way, $start, $limit, $id_lang_shop);
    }

    public function renderView()
    {
        $tpl = parent::renderView();
        
        // Añadir botón de login como cliente
        if (Tools::getValue('id_customer')) {
            $this->toolbar_btn['login_as_customer'] = [
                'href' => self::$currentIndex . '&action=loginAsCustomer&id_customer=' . (int)Tools::getValue('id_customer') . '&token=' . $this->token,
                'desc' => $this->l('Login como Cliente'),
                'icon' => 'process-icon-preview',
                'class' => 'btn-info'
            ];
        }
        
        return $tpl;
    }

    public function initPageHeaderToolbar()
    {
        parent::initPageHeaderToolbar();
        
        if (Tools::getValue('id_customer')) {
            $this->page_header_toolbar_btn['login_as_customer'] = [
                'href' => self::$currentIndex . '&action=loginAsCustomer&id_customer=' . (int)Tools::getValue('id_customer') . '&token=' . $this->token,
                'desc' => $this->l('Login como Cliente'),
                'icon' => 'process-icon-preview'
            ];
        }
    }

    public function postProcess()
    {
        if (Tools::isSubmit('action') && Tools::getValue('action') === 'loginAsCustomer') {
            $this->loginAsCustomer();
            return;
        }
        
        parent::postProcess();
    }

    public function loginAsCustomer()
    {
        if (!Module::isEnabled('gestioncomerciales')) {
            Tools::redirectAdmin($this->context->link->getAdminLink('AdminCustomers'));
            return;
        }

        $id_customer = (int)Tools::getValue('id_customer');
        if (!$id_customer) {
            Tools::redirectAdmin($this->context->link->getAdminLink('AdminCustomers'));
            return;
        }

        $customer = new Customer($id_customer);
        if (!Validate::isLoadedObject($customer)) {
            Tools::redirectAdmin($this->context->link->getAdminLink('AdminCustomers'));
            return;
        }

        // Guardar el ID del empleado actual
        $this->context->cookie->id_employee_before_customer_login = $this->context->employee->id;
        
        // Limpiar cookie actual
        $this->context->cookie->logout();
        
        // Crear nueva sesión para el cliente
        $this->context->cookie->id_customer = (int)$customer->id;
        $this->context->cookie->customer_lastname = $customer->lastname;
        $this->context->cookie->customer_firstname = $customer->firstname;
        $this->context->cookie->logged = 1;
        $this->context->cookie->passwd = $customer->passwd;
        $this->context->cookie->email = $customer->email;
        
        // Redirigir al front-office
        Tools::redirect($this->context->link->getPageLink('my-account'));
    }
}