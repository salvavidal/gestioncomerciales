<?php

namespace PrestaShopBundle\Controller\Admin\Sell\Customer;

use PrestaShopBundle\Controller\Admin\FrameworkBundleAdminController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

class CustomerController extends FrameworkBundleAdminController
{
    public function loginAsCustomerAction(Request $request, $customerId)
    {
        if (!$this->isGranted('read', 'AdminCustomers')) {
            return $this->redirectToRoute('admin_customers_index');
        }

        $customer = new \Customer($customerId);
        if (!\Validate::isLoadedObject($customer)) {
            return $this->redirectToRoute('admin_customers_index');
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
        return new RedirectResponse($this->context->link->getPageLink('my-account'));
    }
}