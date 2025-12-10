<?php

class ContactController extends ContactControllerCore
{
    public function postProcess()
    {
        if (Tools::isSubmit('submitMessage')) {
            require_once _PS_MODULE_DIR_.'rz_captcha/rz_captcha.php';

            $token = Tools::getValue('g-recaptcha-response');

            if (!Rz_Captcha::validateCaptcha($token)) {
                sleep(Configuration::get("RZ_CAPTCHA_INVALID_CAPTCHA_WAIT"));
                $module = Module::getInstanceByName('rz_captcha');
                if ($module) {
                    $this->errors[] = $module->l('Captcha validation failed.');
                } else {
                    $this->errors[] = Tools::displayError('Captcha validation failed.');
                }
            return;
            }
        }

        parent::postProcess();
    }
}
