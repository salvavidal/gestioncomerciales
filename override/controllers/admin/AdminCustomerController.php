<?php

// Asegurarse de que el controlador de PrestaShop esté cargado
require_once _PS_OVERRIDE_DIR_ . 'controllers/admin/AdminCustomersController.php';

class AdminCustomersController extends AdminCustomersControllerCore
{
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
}
