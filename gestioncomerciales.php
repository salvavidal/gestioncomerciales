<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

use PrestaShop\PrestaShop\Core\Grid\Column\Type\Common\ActionColumn;
use PrestaShop\PrestaShop\Core\Grid\Action\Row\Type\LinkRowAction;
use PrestaShop\PrestaShop\Core\Grid\Action\Row\RowActionCollection;

class Gestioncomerciales extends Module
{
    // ... (mantener el resto del código igual hasta hookActionCustomerGridDefinitionModifier)

    public function hookActionCustomerGridDefinitionModifier($params)
    {
        /** @var \PrestaShop\PrestaShop\Core\Grid\Definition\GridDefinition */
        $definition = $params['definition'];

        $definition->getColumns()->addAfter(
            'optin',
            (new ActionColumn('actions'))
                ->setName($this->l('Acciones'))
                ->setOptions([
                    'actions' => (new RowActionCollection())
                        ->add(
                            (new LinkRowAction('login_as_customer'))
                                ->setIcon('account_circle')
                                ->setName($this->l('Login como Cliente'))
                                ->setOptions([
                                    'route' => 'admin_customers_index',
                                    'route_param_name' => 'id_customer',
                                    'route_param_field' => 'id_customer',
                                    'clickable_row' => false,
                                    'use_inline_display' => true,
                                    'extra_route_params' => [
                                        'action' => 'loginAsCustomer'
                                    ]
                                ])
                        )
                ])
        );
    }

    // ... (mantener el resto del código igual)
}