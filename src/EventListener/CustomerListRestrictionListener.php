<?php

namespace Gestioncomerciales\EventListener;

use Doctrine\DBAL\Connection;
use PrestaShop\PrestaShop\Core\Grid\Query\GridQueryBuilderModifierInterface;
use PrestaShopBundle\Event\HookEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CustomerListRestrictionListener implements EventSubscriberInterface
{
    private $connection;
    private $context;

    public function __construct(Connection $connection, $context)
    {
        $this->connection = $connection;
        $this->context = $context;
    }

    public static function getSubscribedEvents()
    {
        return [
            'actionCustomerGridQueryBuilderModifier' => 'onCustomerGridQueryBuilderModifier',
        ];
    }

    public function onCustomerGridQueryBuilderModifier(HookEvent $event)
    {
        $id_employee = (int) $this->context->employee->getId();

        // Verificar si el usuario no es un administrador
        if ($this->context->employee->id_profile != _PS_ADMIN_PROFILE_) {
			error_log("Ejecutando el listener para el empleado ID: $id_employee"); // Línea de log para verificar
            // Modificar la consulta para restringir los clientes asignados
            $searchQueryBuilder = $event->getQueryBuilder();
            $searchQueryBuilder->andWhere('c.id_customer IN (
                SELECT id_cliente FROM ' . _DB_PREFIX_ . 'comerciales_clientes WHERE id_comercial = :id_employee
            )');
            $searchQueryBuilder->setParameter('id_employee', $id_employee);
        }
    }
}
