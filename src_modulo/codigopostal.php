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
        $this->version = '1.1.0';
        $this->author = 'Roberto Carlos Moyano';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = [
            'min' => '1.7.0.0',
            'max' => '8.99.99',
        ];
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Filtro Avanzado de Código Postal');
        $this->description = $this->l('Bloquea o permite compras basándose en el código postal del cliente.');
        $this->confirmUninstall = $this->l('¿Estás seguro de que quieres desinstalar el módulo?');
    }

    public function install()
    {
        // Añadimos las DOS variables de configuración
        return parent::install() &&
            $this->registerHook('displayPaymentTop') &&
            Configuration::updateValue('CODIGOPOSTAL_MODO', 1) && // 1 = Permitidos, 0 = Bloqueados
            Configuration::updateValue('CODIGOPOSTAL_LISTA', '');
    }

    public function uninstall()
    {
        // Limpiamos la basura de la base de datos
        return Configuration::deleteByName('CODIGOPOSTAL_MODO') &&
            Configuration::deleteByName('CODIGOPOSTAL_LISTA') &&
            parent::uninstall();
    }

    // --- FASE 3: BACKOFFICE ---

    public function getContent()
    {
        $output = '';

        if (Tools::isSubmit('submitCodigoPostal')) {
            // Recogemos el interruptor (1 o 0) y el texto
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

        // Cargamos los valores guardados
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
                        'type' => 'radio', // Aquí está el interruptor mágico
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
                        'desc' => $this->l('Escribe los códigos postales separados por comas (ejemplo: 35000, 38000, 51000).')
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

    // --- FASE 4: LÓGICA FRONTOFFICE (CEREBRO ACTUALIZADO) ---

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

        // Miramos si el cliente está en la lista escrita
        $esta_en_lista = in_array($codigo_cliente, $codigos_array);
        $bloquear = false;

        // LA DECISIÓN LÓGICA
        if ($modo === 1 && !$esta_en_lista) {
            // MODO LISTA BLANCA: Bloqueamos si NO está en la lista
            $bloquear = true;
        } elseif ($modo === 0 && $esta_en_lista) {
            // MODO LISTA NEGRA: Bloqueamos si SÍ está en la lista
            $bloquear = true;
        }

        if (!$bloquear) {
            return ''; // Vía libre, puede pagar
        }

        // Si ha caído en la trampa, bloqueamos el pago
        $css_bloqueo = '<style>.payment-options, .conditions-to-approve { display: none !important; }</style>';
        $mensaje_error = '<div class="alert alert-danger" style="border: 2px solid #dc3545; margin-bottom: 20px;">
                            <h4 style="color: #dc3545; font-weight: bold;">' . $this->l('Envío no disponible') . '</h4>
                            <p>' . $this->l('Lo sentimos, actualmente no realizamos envíos a tu código postal: ') . '<b>' . $codigo_cliente . '</b>.</p>
                            <p>' . $this->l('Por favor, modifica tu dirección de entrega para poder finalizar la compra.') . '</p>
                          </div>';

        return $css_bloqueo . $mensaje_error;
    }
}