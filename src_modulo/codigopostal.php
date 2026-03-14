<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

class CodigoPostal extends Module
{
    public function __construct()
    {
        $this->name = 'codigopostal';
        $this->tab = 'shipping_logistics';
        $this->version = '1.0.0';
        $this->author = 'Roberto Carlos Moyano';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = [
            'min' => '1.7.0.0',
            'max' => '8.99.99',
        ];
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Filtro de Código Postal');
        $this->description = $this->l('Bloquea compras si el código postal del cliente no está en la lista permitida.');
        $this->confirmUninstall = $this->l('¿Estás seguro de que quieres desinstalar el módulo?');
    }

    public function install()
    {
        // Instalamos el módulo y lo "enganchamos" en la parte superior del pago
        return parent::install() &&
            $this->registerHook('displayPaymentTop');
    }

    public function uninstall()
    {
        return parent::uninstall();
    }

    // Aquí está la prueba visual para la magia de Docker (Hot-Reload)
    public function hookDisplayPaymentTop($params)
    {
        return '<div class="alert alert-warning" style="border: 2px solid red; font-weight: bold; margin-bottom: 20px;">
                    ¡Hola mundo!
                </div>';
    }
}