RZ Captcha

This prestashop module provides a captcha protection for your website.
It is compatible with Prestashop versions 1.6.x. It uses google reCaptcha v2 visible therefore you'll need to create a google account and create a site key and secret key in order to use the module.

Make sure to read the documentation before installing the module.

---

## Install
To install the module, follow these steps:

1. Download the release from the [GitHub repository](https://github.com/Cahlum0/RZ_Captcha/releases).
2. Unzip the downloaded file in the `modules` directory of your Prestashop installation.
3. Go to the `Modules and Services` page in your Prestashop BO.
4. Search for `RZ Captcha` in the search bar.
5. Click on the `Install` button. **!! Warning !!** this module does an override of the `ContactController` class; Make sure this class is not overridden by another module.
6. Open the configuration page of the module and enter your google reCaptcha v2 **Visible** site and secret keys.
7. Enable Captcha and save the configuration.
8. Finally go to the `Contact` page and test the captcha.
