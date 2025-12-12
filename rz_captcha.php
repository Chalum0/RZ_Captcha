<?php
if (!defined("_PS_VERSION_")) {
    exit();
}

class Rz_Captcha extends Module
{
    public function __construct()
    {
        $this->name = "rz_captcha";
        $this->tab = "front_office_features";
        $this->version = "1.1.1";
        $this->author = "Raphaël Zanatta";
        $this->need_instance = 0;
        $this->ps_versions_compliancy = [
            "min" => "1.6.0.0",
            "max" => "1.6.2.24",
        ];
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = "RZ Captcha";
        $this->description = $this->l("This module adds a captcha to prestashop's default contact form.");

        $this->module_url =
            Tools::getProtocol(Tools::usingSecureMode()) .
            $_SERVER["HTTP_HOST"] .
            $this->getPathUri();
        $this->confirmUninstall = $this->l(
            "Are you sure you want to uninstall ? Removing the Captcha from your webite may lead to spam."
        );
    }

    public function install()
    {
        return parent::install()
            && $this->registerHook("displayGDPRConsent");
    }

    public function uninstall()
    {
        return parent::uninstall();
    }


    // BO
    public function renderForm()
    {
        $helper = new HelperForm();
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite("AdminModules");
        $helper->currentIndex = AdminController::$currentIndex . "&configure=" . $this->name;
        $helper->show_toolbar = false;
        $helper->default_form_language = (int) Configuration::get("PS_LANG_DEFAULT");
        $helper->allow_employee_form_lang = $helper->default_form_language;
        $helper->submit_action = "submitCaptcha";
        $helper->fields_value = [
            "RZ_CAPTCHA_RECAPTCHA_SITE_KEY" => Configuration::get("RZ_CAPTCHA_RECAPTCHA_SITE_KEY"),
            "RZ_CAPTCHA_RECAPTCHA_SECRET_KEY" => Configuration::get("RZ_CAPTCHA_RECAPTCHA_SECRET_KEY"),
            "RZ_CAPTCHA_INVALID_CAPTCHA_WAIT" => (int) Configuration::get("RZ_CAPTCHA_INVALID_CAPTCHA_WAIT"),
            "RZ_CAPTCHA_CAPTCHA_ENABLED" => (int) Configuration::get("RZ_CAPTCHA_CAPTCHA_ENABLED")
        ];

        $fields_form = [
            "form" => [
                "tabs" => [
                    "creds" => $this->l("Credentials"),
                    "config" => $this->l("Configuration"),
                ],
                "legend" => ["title" => $this->l("Captcha Settings")],
                "input" => [
                    ["type" => 'text',
                        "label" => $this->l("Site Key"),
                        "name" => 'RZ_CAPTCHA_RECAPTCHA_SITE_KEY',
                        "tab" => "creds",
                        "autoload_rte" => true,
                        "cols" => 60,
                        "rows" => 10,
                        "desc" => $this->l("The site key google gave you")],
                    ["type" => 'text',
                        "label" => $this->l("Secret Key"),
                        "name" => 'RZ_CAPTCHA_RECAPTCHA_SECRET_KEY',
                        "tab" => 'creds',
                        "autoload_rte" => true,
                        "cols" => 60,
                        "rows" => 10,
                        "desc" => $this->l("The secret key google gave you")],
                    [
                        'type'    => 'switch',
                        'label'   => $this->l('Enable captcha'),
                        'name'    => 'RZ_CAPTCHA_CAPTCHA_ENABLED',
                        'tab'     => 'creds',
                        'is_bool' => true,
                        'desc'    => $this->l('Turn the captcha protection on or off.'),
                        'values'  => [
                            [
                                'id'    => 'RZ_CAPTCHA_CAPTCHA_ENABLED_on',
                                'value' => 1,
                                'label' => $this->l('Enabled'),
                            ],
                            [
                                'id'    => 'RZ_CAPTCHA_CAPTCHA_ENABLED_off',
                                'value' => 0,
                                'label' => $this->l('Disabled'),
                            ],],],
                    [
                        'type'         => 'html',
                        'label'        => $this->l('Wait time between failed attemps in seconds (min. 0 max. 30)'),
                        'name'         => 'RZ_CAPTCHA_INVALID_CAPTCHA_WAIT',
                        'tab'         => 'config',
                        'html_content' => sprintf(
                            '<input type="number"
                                    name="RZ_CAPTCHA_INVALID_CAPTCHA_WAIT"
                                    min="0"
                                    max="30"
                                    value="%d"
                                    class="fixed-width-sm" />',
                            (int) Tools::getValue(
                                'RZ_CAPTCHA_INVALID_CAPTCHA_WAIT',
                                (int) Configuration::get('RZ_CAPTCHA_INVALID_CAPTCHA_WAIT'))),],

                ],
                "submit" => [
                    "title" => $this->l("Save"),
                    "name" => "submitCaptcha",
                ]
            ]
        ];
        return $helper->generateForm([$fields_form]);
    }

    public function getContent()
    {
        $out = "";

        $ok = true;

        if (Tools::isSubmit("submitCaptcha")) {
            $site_key   = Tools::getValue('RZ_CAPTCHA_RECAPTCHA_SITE_KEY');
            $secret_key = Tools::getValue('RZ_CAPTCHA_RECAPTCHA_SECRET_KEY');
            $invalid_captcha_wait = Tools::getValue('RZ_CAPTCHA_INVALID_CAPTCHA_WAIT');
            $captcha_enabled = (int) Tools::getValue('RZ_CAPTCHA_CAPTCHA_ENABLED');

            Configuration::updateValue(
                "RZ_CAPTCHA_RECAPTCHA_SITE_KEY",
                $site_key,
                false
            );

            Configuration::updateValue(
                "RZ_CAPTCHA_RECAPTCHA_SECRET_KEY",
                $secret_key,
                false
            );

            if ($captcha_enabled) {
                if (!(trim($site_key) === '' && trim($secret_key) === '')) {
                    Configuration::updateValue(
                        "RZ_CAPTCHA_CAPTCHA_ENABLED",
                        $captcha_enabled,
                        false
                    );
                } else {
                    $out .= $this->displayError($this->l("Cannot turn on Captcha without keys."));
                    $ok = false;
                }
            } else {
                Configuration::updateValue(
                    "RZ_CAPTCHA_CAPTCHA_ENABLED",
                    $captcha_enabled,
                    false
                );
            }

            $ok = ($invalid_captcha_wait >= 0 && $invalid_captcha_wait <= 30) && $ok;

            if ($invalid_captcha_wait >= 0 && $invalid_captcha_wait <= 30) {
                Configuration::updateValue(
                    "RZ_CAPTCHA_INVALID_CAPTCHA_WAIT",
                    $invalid_captcha_wait,
                    false
                );

            } else {
                $out .= $this->displayError($this->l("Invalid failed reCaptcha wait time."));
            }

            if ($ok) {
                $out .= $this->displayConfirmation(
                    $this->l("Settings updated")
                );
            }
        }

        return $out . $this->renderForm();
    }


    // Captcha
    public function hookDisplayGDPRConsent($params)
    {
        // To be sure we are in the contact form
        if (!empty($params['moduleName']) && $params['moduleName'] !== 'contactform') {
            return '';
        }

        if ($this->context->controller->php_self !== 'contact') {
                return '';
            }

        $this->context->smarty->assign([
            'recaptcha_site_key' => Configuration::get('RZ_CAPTCHA_RECAPTCHA_SITE_KEY'),
        ]);

        if (Configuration::get("RZ_CAPTCHA_CAPTCHA_ENABLED")) {
            return $this->display(__FILE__, 'views/templates/hook/captcha.tpl');
        }
    }

    public static function validateCaptcha($captchaResponse)
    {
        if (empty($captchaResponse)) {
            return false;
        }

        $secret = Configuration::get("RZ_CAPTCHA_RECAPTCHA_SECRET_KEY");
        $params = array(
            'secret'   => $secret,
            'response' => $captchaResponse,
            'remoteip' => Tools::getRemoteAddr(),
        );

        $url = 'https://www.google.com/recaptcha/api/siteverify?'.http_build_query($params);
        $result = Tools::file_get_contents($url);
        if ($result === false) {
            return false;
        }
        $data = json_decode($result, true);
        return !empty($data['success']);
    }


}
