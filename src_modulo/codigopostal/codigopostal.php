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
        $this->version = '1.2.0'; // ¡Versión definitiva!
        $this->author = 'Roberto Carlos Moyano';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = [
            'min' => '1.7.0.0',
            'max' => '8.99.99',
        ];
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Filtro Avanzado de Código Postal');
        $this->description = $this->l('Bloquea o permite compras basándose en el código postal del cliente (soporta prefijos).');
        $this->confirmUninstall = $this->l('¿Estás seguro de que quieres desinstalar el módulo?');
    }

    public function install()
    {
        return parent::install() &&
            $this->registerHook('displayPaymentTop') &&
            Configuration::updateValue('CODIGOPOSTAL_MODO', 1) &&
            Configuration::updateValue('CODIGOPOSTAL_LISTA', '');
    }

    public function uninstall()
    {
        return Configuration::deleteByName('CODIGOPOSTAL_MODO') &&
            Configuration::deleteByName('CODIGOPOSTAL_LISTA') &&
            parent::uninstall();
    }

    // --- BACKOFFICE ---

    public function getContent()
    {
        $output = '';

        if (Tools::isSubmit('submitCodigoPostal')) {
            $modo = (int)Tools::getValue('CODIGOPOSTAL_MODO');
            $lista = Tools::getValue('CODIGOPOSTAL_LISTA');
            
            Configuration::updateValue('CODIGOPOSTAL_MODO', $modo);
            Configuration::updateValue('CODIGOPOSTAL_LISTA', $lista);
            
            $output .= $this->displayConfirmation($this->l('Configuración actualizada correctamente.'));
        }

        return $output . $this->renderForm();
    }

    protected function renderForm()
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitCodigoPostal';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->fields_value['CODIGOPOSTAL_MODO'] = Configuration::get('CODIGOPOSTAL_MODO');
        $helper->fields_value['CODIGOPOSTAL_LISTA'] = Configuration::get('CODIGOPOSTAL_LISTA');

        $form = [
            'form' => [
                'legend' => [
                    'title' => $this->l('Configuración de Códigos Postales'),
                    'icon' => 'icon-cogs',
                ],
                'input' => [
                    [
                        'type' => 'radio',
                        'label' => $this->l('Modo de funcionamiento'),
                        'name' => 'CODIGOPOSTAL_MODO',
                        'is_bool' => false,
                        'values' => [
                            [
                                'id' => 'modo_permitir',
                                'value' => 1,
                                'label' => $this->l('Permitir SOLO estos códigos (Lista Blanca)')
                            ],
                            [
                                'id' => 'modo_bloquear',
                                'value' => 0,
                                'label' => $this->l('Bloquear SOLO estos códigos (Lista Negra - Ej: Canarias)')
                            ]
                        ]
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Códigos Postales'),
                        'name' => 'CODIGOPOSTAL_LISTA',
                        // Texto actualizado para educar al usuario
                        'desc' => $this->l('Separados por comas. Puedes usar prefijos (ej: "35" aplicará a todos los que empiecen por 35) o códigos completos (ej: "28001" para uno específico).')
                    ],
                ],
                'submit' => [
                    'title' => $this->l('Guardar'),
                    'class' => 'btn btn-default pull-right'
                ]
            ],
        ];

        return $helper->generateForm([$form]);
    }

    // --- FRONTOFFICE (LÓGICA CON PREFIJOS) ---

    public function hookDisplayPaymentTop($params)
    {
        $modo = (int)Configuration::get('CODIGOPOSTAL_MODO');
        $codigos_guardados = Configuration::get('CODIGOPOSTAL_LISTA');
        
        if (empty($codigos_guardados)) {
            return ''; 
        }

        $codigos_array = array_map('trim', explode(',', $codigos_guardados));
        
        $cart = $this->context->cart;
        if (!$cart->id_address_delivery) {
            return ''; 
        }

        $address = new Address($cart->id_address_delivery);
        $codigo_cliente = trim($address->postcode);

        // Búsqueda inteligente por prefijo o coincidencia exacta
        $esta_en_lista = false;
        foreach ($codigos_array as $codigo_guardado) {
            // El !== '' evita errores si el usuario deja una coma suelta al final
            if ($codigo_guardado !== '' && strpos($codigo_cliente, $codigo_guardado) === 0) {
                $esta_en_lista = true;
                break;
            }
        }

        $bloquear = false;

        if ($modo === 1 && !$esta_en_lista) {
            $bloquear = true;
        } elseif ($modo === 0 && $esta_en_lista) {
            $bloquear = true;
        }

        if (!$bloquear) {
            return ''; 
        }

        $css_bloqueo = '<style>.payment-options, .conditions-to-approve { display: none !important; }</style>';
        $mensaje_error = '<div class="alert alert-danger" style="border: 2px solid #dc3545; margin-bottom: 20px;">
                            <h4 style="color: #dc3545; font-weight: bold;">' . $this->l('Envío no disponible') . '</h4>
                            <p>' . $this->l('Lo sentimos, actualmente no realizamos envíos a tu código postal: ') . '<b>' . $codigo_cliente . '</b>.</p>
                            <p>' . $this->l('Por favor, modifica tu dirección de entrega para poder finalizar la compra.') . '</p>
                          </div>';

        return $css_bloqueo . $mensaje_error;
    }
}