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

    // ... [resto del código del módulo permanece igual]
}