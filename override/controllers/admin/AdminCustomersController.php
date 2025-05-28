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
        // Obtener el ID del empleado logueado
        $id_employee = (int)$this->context->employee->id;

        // Verificar si el empleado tiene un rol específico que deba tener restricciones
        if ($this->context->employee->id_profile != _PS_ADMIN_PROFILE_) {
            error_log("Override aplicado para el empleado ID: $id_employee");
            // Modificar la consulta de clientes para mostrar solo aquellos asignados al empleado
            $this->_where .= ' AND a.id_customer IN (
                SELECT id_cliente 
                FROM ' . _DB_PREFIX_ . 'comerciales_clientes 
                WHERE id_comercial = ' . (int)$id_employee . '
            )';
        }

        // Llamar al método original para procesar la lista con el nuevo filtro
        parent::getList($id_lang, $order_by, $order_way, $start, $limit, $id_lang_shop);
    }

    public function loginAsCustomer()
    {
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
        Context::getContext()->cookie->id_employee_before_customer_login = Context::getContext()->employee->id;
        
        // Limpiar cookie actual
        Context::getContext()->cookie->logout();
        
        // Crear nueva sesión para el cliente
        Context::getContext()->cookie->id_customer = (int)$customer->id;
        Context::getContext()->cookie->customer_lastname = $customer->lastname;
        Context::getContext()->cookie->customer_firstname = $customer->firstname;
        Context::getContext()->cookie->logged = 1;
        Context::getContext()->cookie->passwd = $customer->passwd;
        Context::getContext()->cookie->email = $customer->email;
        
        // Redirigir al front-office
        Tools::redirect('index.php');
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
}