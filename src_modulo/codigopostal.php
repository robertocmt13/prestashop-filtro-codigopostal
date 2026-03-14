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
        // Instalamos, registramos el hook y creamos la variable vacía en la BD por defecto
        return parent::install() &&
            $this->registerHook('displayPaymentTop') &&
            Configuration::updateValue('CODIGOPOSTAL_PERMITIDOS', '');
    }

    public function uninstall()
    {
        // Borramos la variable de la BD al desinstalar para dejarlo todo limpio
        return Configuration::deleteByName('CODIGOPOSTAL_PERMITIDOS') &&
            parent::uninstall();
    }

    // --- FASE 3: BACKOFFICE ---

    // Esta función se ejecuta al darle al botón "Configurar" del módulo
    public function getContent()
    {
        $output = '';

        // Si el usuario ha pulsado el botón de "Guardar" del formulario...
        if (Tools::isSubmit('submitCodigoPostal')) {
            // Recogemos lo que ha escrito en el campo de texto
            $codigos_guardados = Tools::getValue('CODIGOPOSTAL_PERMITIDOS');
            
            // Lo guardamos en la tabla ps_configuration
            Configuration::updateValue('CODIGOPOSTAL_PERMITIDOS', $codigos_guardados);
            
            // Mostramos un mensaje verde de éxito
            $output .= $this->displayConfirmation($this->l('Códigos postales actualizados correctamente.'));
        }

        // Devolvemos los mensajes (si hay) + el formulario generado
        return $output . $this->renderForm();
    }

    // Esta función genera el HTML del formulario usando HelperForm de PrestaShop
    protected function renderForm()
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitCodigoPostal'; // Tiene que coincidir con el if() de getContent()
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        // Definimos la estructura del formulario
        $helper->fields_value['CODIGOPOSTAL_PERMITIDOS'] = Configuration::get('CODIGOPOSTAL_PERMITIDOS');

        $form = [
            'form' => [
                'legend' => [
                    'title' => $this->l('Configuración de Códigos Postales'),
                    'icon' => 'icon-cogs',
                ],
                'input' => [
                    [
                        'type' => 'text', // Un campo de texto normal
                        'label' => $this->l('Códigos postales permitidos'),
                        'name' => 'CODIGOPOSTAL_PERMITIDOS',
                        'desc' => $this->l('Escribe los códigos postales separados por comas (ejemplo: 18001, 18002, 28001).'),
                        'required' => true
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

    // --- FIN FASE 3 ---

    // --- FASE 4: LÓGICA FRONTOFFICE ---

    public function hookDisplayPaymentTop($params)
    {
        // 1. Obtenemos los códigos permitidos que guardaste en el Backoffice
        $codigos_guardados = Configuration::get('CODIGOPOSTAL_PERMITIDOS');
        
        // Si el administrador no ha escrito nada aún, no bloqueamos la tienda
        if (empty($codigos_guardados)) {
            return ''; 
        }

        // Convertimos tu texto "18001, 18002" en una lista (array) limpia sin espacios
        $codigos_permitidos = array_map('trim', explode(',', $codigos_guardados));

        // 2. Cargamos el carrito actual del cliente
        $cart = $this->context->cart;
        
        // Si por algún casual llega aquí sin dirección, no hacemos nada
        if (!$cart->id_address_delivery) {
            return ''; 
        }

        // 3. Cargamos la dirección completa y sacamos el Código Postal
        $address = new Address($cart->id_address_delivery);
        $codigo_cliente = trim($address->postcode);

        // 4. LA MAGIA: Comparamos el código del cliente con tu lista de permitidos
        if (in_array($codigo_cliente, $codigos_permitidos)) {
            // ¡Todo en orden! Está en la lista. No devolvemos ningún error.
            return ''; 
        }

        // 5. Si NO está en la lista: Mostramos error y OCULTAMOS los métodos de pago
        $css_bloqueo = '<style>.payment-options, .conditions-to-approve { display: none !important; }</style>';
        
        $mensaje_error = '<div class="alert alert-danger" style="border: 2px solid #dc3545; margin-bottom: 20px;">
                            <h4 style="color: #dc3545; font-weight: bold;">' . $this->l('Envío no disponible') . '</h4>
                            <p>' . $this->l('Actualmente no realizamos envíos al código postal: ') . '<b>' . $codigo_cliente . '</b>.</p>
                            <p>' . $this->l('Por favor, modifica tu dirección de envío en el paso anterior para poder finalizar la compra.') . '</p>
                          </div>';

        return $css_bloqueo . $mensaje_error;
    }
}