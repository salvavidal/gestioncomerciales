<?php

class AdminGestionComercialesController extends ModuleAdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->bootstrap = true;
        $this->name = 'AdminGestionComerciales';
    }

    public function initContent()
    {
        // Llama a la función getContent del módulo para cargar la configuración
        $this->content = $this->module->getContent();

        // Asignar token y currentIndex para el formulario
        $this->context->smarty->assign([
            'content' => $this->content,
            'currentIndex' => AdminController::$currentIndex . '&configure=' . $this->module->name,
            'token' => $this->token
        ]);

        parent::initContent();
    }

    public function loginAsCustomer()
    {
        $id_customer = (int)Tools::getValue('id_customer');
        if (!$id_customer) {
            Tools::redirectAdmin($this->context->link->getAdminLink('AdminCustomers'));
        }

        $customer = new Customer($id_customer);
        if (!Validate::isLoadedObject($customer)) {
            Tools::redirectAdmin($this->context->link->getAdminLink('AdminCustomers'));
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