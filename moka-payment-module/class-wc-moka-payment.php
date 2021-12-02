<?php

/*
 * Plugin Name:WooCommerce Moka Payment Gateway
 * Plugin URI: https://www.kahvedigital.com
 * Description: Moka Payment gateway for woocommerce
 * Version: 1.0.0
 * Author: KahveDigital
 * Author URI: http://kahvedigital.com
 * Domain Path: /i18n/languages/
 */

if (!defined('ABSPATH')) {
    exit;
}

include plugin_dir_path(__FILE__) . 'includes/class-kahvedigital_mokaconfig.php';
global $moka_db_version;
$moka_db_version = '1.0';
register_deactivation_hook(__FILE__, 'moka_deactivation');
register_activation_hook(__FILE__, 'moka_activate');
add_action('plugins_loaded', 'moka_update_db_check');

function moka_update_db_check()
{
    global $moka_db_version;
    global $wpdb;
    $installed_ver = get_option("moka_db_version");
    if ($installed_ver != $moka_db_version) {
        moka_update();
    }
}

function moka_update()
{
    global $moka_db_version;
    update_option("moka_db_version", $moka_db_version);
}

function moka_activate()
{
    global $wpdb;
    global $moka_db_version;
    $moka_db_version = '1.0';

    $charset_collate = $wpdb->get_charset_collate();
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    add_option('moka_db_version', $moka_db_version);
}

function moka_deactivation()
{
    global $wpdb;
    global $moka_db_version;

    delete_option('moka_db_version');
    flush_rewrite_rules();
}

function moka_install_data()
{
    global $wpdb;
}

add_action('plugins_loaded', 'woocommerce_moka_from_init', 0);

function woocommerce_moka_from_init()
{
    if (!class_exists('WC_Payment_Gateway')) {
        return;
    }

    class WC_Gateway_Mokapos extends WC_Payment_Gateway
    {

        public function __construct()
        {
            $this->id = 'mokapos';
            $this->method_title = __('Moka Checkout form', 'moka-payment-module');
            $this->method_description = __('Moka Payment Module', 'moka-payment-module');
            $this->icon = plugins_url('/moka-payment-module/img/cards.png', dirname(__FILE__));
            $this->has_fields = false;
            $this->supports = array('products', 'refunds');
            $this->rates = get_option('kahvedigital_moka_rates');
            $this->init_form_fields();
            $this->init_settings();
            $this->kahvedigital_moka_tdmode = $this->settings['kahvedigital_moka_tdmode'];
            $this->kahvedigital_moka_dealercode = $this->settings['kahvedigital_moka_dealercode'];
            $this->kahvedigital_moka_username = $this->settings['kahvedigital_moka_username'];
            $this->kahvedigital_moka_password = $this->settings['kahvedigital_moka_password'];
            $this->installments_mode = $this->settings['installments_mode'];
            $this->title = $this->settings['title'];
            $this->description = $this->settings['description'];
            $this->enabled = $this->settings['enabled'];
            $this->order_button_text = $this->settings['button_title'];
            add_action('init', array($this, 'check_mokapos_response'));
            add_action('woocommerce_api_wc_gateway_mokapos', array($this, 'check_mokapos_response'));
            add_action('admin_notices', array($this, 'checksFields'));
            if (version_compare(WOOCOMMERCE_VERSION, '2.0.0', '>=')) {
                add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
            } else {
                add_action('woocommerce_update_options_payment_gateways', array($this, 'process_admin_options'));
            }
            add_action('woocommerce_receipt_mokapos', array($this, 'receipt_page'));
            add_action('woocommerce_api_' . $this->id, array($this, 'callback_handler'));
        }

        function callback_handler()
        {
            header('HTTP/1.1 200 OK');

            $moka = array();
            $dealer_code = $this->kahvedigital_moka_dealercode;
            $username = $this->kahvedigital_moka_username;
            $password = $this->kahvedigital_moka_password;

            $checkkey = hash("sha256", $dealer_code . "MK" . $username . "PD" . $password);

            $moka = array();
            $moka['PaymentDealerAuthentication'] = array(
                'DealerCode' => $dealer_code,
                'Username' => $username,
                'Password' => $password,
                'CheckKey' => $checkkey,

            );

            $moka['BankCardInformationRequest'] = array(
                'BinNumber' => $_POST['BinNumber'], //$BinData['binNumber']
            );

            $moka_url = "https://service.moka.com/PaymentDealer/GetBankCardInformation";

            $veri = json_encode($moka);
            $ch = curl_init($moka_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $veri);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            $result = curl_exec($ch);
            curl_close($ch);

            print_r($result);
            die();
        }

        function checksFields()
        {
            global $woocommerce;

            if ($this->enabled == 'no') {
                return;
            }

        }

        function init_form_fields()
        {
            $this->form_fields = array(
                'enabled' => array(
                    'title' => __('Enable/Disable', 'moka-payment-module'),
                    'label' => __('Enable Moka Form', 'moka-payment-module'),
                    'type' => 'checkbox',
                    'default' => 'no',
                ),
                'title' => array(
                    'title' => __('Title', 'moka-payment-module'),
                    'type' => 'text',
                    'description' => __('This message will show to the user during checkout.', 'moka-payment-module'),
                    'default' => 'Kredi Kartı İle Öde',
                ),
                'description' => array(
                    'title' => __('Description.', 'moka-payment-module'),
                    'type' => 'text',
                    'description' => __('This controls the description which the user sees during checkout.', 'moka-payment-module'),
                    'default' => __('Pay with your credit card via Moka.', 'moka-payment-module'),
                    'desc_tip' => true,
                ),
                'button_title' => array(
                    'title' => __('Checkout Button.', 'moka-payment-module'),
                    'type' => 'text',
                    'description' => __('Checkout Button.', 'moka-payment-module'),
                    'default' => __('Pay with Moka.', 'moka-payment-module'),
                    'desc_tip' => true,
                ),
                'kahvedigital_moka_dealercode' => array(
                    'title' => __('Moka Dealer Code.', 'moka-payment-module'),
                    'type' => 'text',
                    'desc_tip' => __('Dealer Code Given by Moka System.', 'moka-payment-module'),
                ),
                'kahvedigital_moka_username' => array(
                    'title' => __('Moka Username.', 'moka-payment-module'),
                    'type' => 'text',
                    'desc_tip' => __('Username Given by Moka System.', 'moka-payment-module'),
                ),
                'kahvedigital_moka_password' => array(
                    'title' => __('Moka Password.', 'moka-payment-module'),
                    'type' => 'text',
                    'desc_tip' => __('Password Given by Moka System.', 'moka-payment-module'),
                ),
                'kahvedigital_moka_tdmode' => array(
                    'title' => __('Three D Selection.', 'moka-payment-module'),
                    'type' => 'select',
                    'default' => 'off',
                    'options' => array(
                        'off' => __('OFF', 'moka-payment-module'),
                        'on' => __('ON', 'moka-payment-module'),
                    ),
                ),
                'installments_mode' => array(
                    'title' => __('Installments Options.', 'moka-payment-module'),
                    'type' => 'select',
                    'default' => 'off',
                    'options' => array(
                        'off' => __('OFF', 'moka-payment-module'),
                        'on' => __('ON', 'moka-payment-module'),
                    ),
                ),
            );
        }

        public function admin_options()
        {
            $this->rates = get_option('kahvedigital_moka_rates');
            if (isset($_POST['kahvedigital_moka_rates'])) {
                KahveDigital::register_all_ins();
            }
            $moka_url = plugins_url() . '/moka-payment-module/';
            echo '<img src="' . $moka_url . 'img/logo.png" width="150px"/>';
            echo '<h2>Moka ödeme ayarları</h2><hr/>';
            echo '<table class="form-table">';
            $this->generate_settings_html();
            echo '</table>';
            if ($this->rates == false) {
                $installments = KahveDigital::createRatesUpdateForm(KahveDigital::setRatesDefault());
            } else {
                $installments = KahveDigital::createRatesUpdateForm(get_option('kahvedigital_moka_rates'));
            }

            echo '<input name="save" class="button-primary woocommerce-save-button" type="submit" value="Kaydet"><hr/>';
            echo "<hr/><h1>";
            echo __('installments options.', 'moka-payment-module');
            echo "</h1><hr/> ";
            echo $installments;
            echo '<input name="save" class="button-primary woocommerce-save-button" type="submit" value="Kaydet"><hr/>';
            include dirname(__FILE__) . '/includes/kahvedigital_moka-help-about.php';
        }

        
                private function setcookieSameSite($name, $value, $expire, $path, $domain, $secure, $httponly)
        {

            if (PHP_VERSION_ID < 70300) {

                setcookie($name, $value, $expire, "$path; samesite=None", $domain, $secure, $httponly);
            } else {
                setcookie($name, $value, [
                    'expires' => $expire,
                    'path' => $path,
                    'domain' => $domain,
                    'samesite' => 'None',
                    'secure' => $secure,
                    'httponly' => $httponly,
                ]);

            }
        }

        function post2Moka($order_id)
        {
            global $woocommerce;
            if (version_compare(get_bloginfo('version'), '4.5', '>=')) {
                wp_get_current_user();
            } else {
                get_currentuserinfo();
            }

            $order = new WC_Order($order_id);
            $wooCommerceCookieKey = 'wp_woocommerce_session_';
            foreach ($_COOKIE as $name => $value) {
                if (stripos($name, $wooCommerceCookieKey) === 0) {
                    $wooCommerceCookieKey = $name;
                }
            }

            $setCookie = $this->setcookieSameSite($wooCommerceCookieKey, $_COOKIE[$wooCommerceCookieKey], time() + 86400, "/", $_SERVER['SERVER_NAME'], true, true);

            $ip = $_SERVER['REMOTE_ADDR'];

            $user_meta = get_user_meta(get_current_user_id());

            if (!function_exists('replaceSpace')) {

                function replaceSpace($veri)
                {
                    $veri = str_replace("/s+/", "", $veri);
                    $veri = str_replace(" ", "", $veri);
                    $veri = str_replace(" ", "", $veri);
                    $veri = str_replace(" ", "", $veri);
                    $veri = str_replace("/s/g", "", $veri);
                    $veri = str_replace("/s+/g", "", $veri);
                    $veri = trim($veri);
                    return $veri;
                }

            }

            $name = $_POST['card-name'];
            $number = $_POST['number'];
            $expiry = $_POST['expiry'];
            $cvc = $_POST['cvc'];
            $total = $_POST['mokatotal'];

            $expiry = explode("/", $expiry);
            $expiryMM = $expiry[0];
            $expiryYY = $expiry[1];
            $expiryMM = replaceSpace($expiryMM);
            $expiryYY = replaceSpace($expiryYY);
            $number = replaceSpace($number);
            if (is_array($total)) {
                $bankalar = KahveDigital::getAvailablePrograms();
                foreach ($bankalar as $key => $value) {

                    $isim = $key;
                    for ($x = 1; $x <= 12; $x++) {

                        $taksit = $total[$key][$x];
                        if (!empty($taksit)) {
                            $installement = $x;

                            $paid = number_format($taksit, 2, '.', '');
                        }
                    }
                }
            }
            if (empty($paid)) {
                $taksit = $total;
                $paid = number_format($taksit, 2, '.', '');
                $installement = 1;
            }

            $amount = $order->get_total();
            $user_id = get_current_user_id();
            $currency = $order->get_currency();
            if ($currency == 'TRY') {$currency = "TL";}
            $ucdaktif = $this->kahvedigital_moka_tdmode;
            if ($ucdaktif == 'off') {
                $moka_url = "https://service.moka.com/PaymentDealer/DoDirectPayment";
            } else {

                $moka_url = "https://service.moka.com/PaymentDealer/DoDirectPaymentThreeD";
            }
            $dealer_code = $this->kahvedigital_moka_dealercode;
            $username = $this->kahvedigital_moka_username;
            $password = $this->kahvedigital_moka_password;
            $orderid = 'Kahve' . $order_id . "-" . time();
            $SubMerchantName = "";
            $checkkey = hash("sha256", $dealer_code . "MK" . $username . "PD" . $password);
            $veri = array('PaymentDealerAuthentication' => array('DealerCode' => $dealer_code, 'Username' => $username, 'Password' => $password,
                'CheckKey' => $checkkey),
                'PaymentDealerRequest' => array('CardHolderFullName' => $name,
                    'CardNumber' => $number,
                    'ExpMonth' => $expiryMM,
                    'ExpYear' => '20' . $expiryYY,
                    'CvcNumber' => $cvc,
                    'Amount' => $paid,
                    'Currency' => $currency,
                    'InstallmentNumber' => $installement,
                    'ClientIP' => $_SERVER['REMOTE_ADDR'],
                    'RedirectUrl' => $order->get_checkout_payment_url(true),
                    'OtherTrxCode' => (string) $order_id,
                    'ReturnHash' => 1,
                    'SubMerchantName' => $SubMerchantName));

            $veri = json_encode($veri);
            $ch = curl_init($moka_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $veri);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            $result = curl_exec($ch);
            curl_close($ch);
            $result = json_decode($result);
            $ResultCode = $result->ResultCode;
            $record = array(
                'id_cart' => $order_id,
                'id_customer' => $user_id,
                'amount' => $paid,
                'amount_paid' => $showtotal,
                'installment' => $installement,
                'kahvedigital_moka' => $orderid,
                'result_code' => '0',
                'result_message' => '',
                'result' => false,
            );
            if ($result->ResultCode == 'Success') {
                session_start();
                $_SESSION['CodeForHash'] = $result->Data->CodeForHash;
                header("Location:" . $result->Data->Url);
            } else {

                switch ($ResultCode) {
                    case "PaymentDealer.CheckPaymentDealerAuthentication.InvalidRequest":
                        $errr = "Hatalı hash bilgisi";
                        break;
                    case "PaymentDealer.RequiredFields.AmountRequired":
                        $errr = "Tutar Göndermek Zorunludur.";
                        break;
                    case "PaymentDealer.RequiredFields.ExpMonthRequired":
                        $errr = "Son Kullanım Tarihi Gönderme Zorunludur.";
                        break;

                    case "PaymentDealer.CheckPaymentDealerAuthentication.InvalidAccount":
                        $errr = "Böyle bir bayi bulunamadı";
                        break;
                    case "PaymentDealer.CheckPaymentDealerAuthentication.VirtualPosNotFound":
                        $errr = "Bu bayi için sanal pos tanımı yapılmamış";
                        break;
                    case "PaymentDealer.CheckDealerPaymentLimits.DailyDealerLimitExceeded":
                        $errr = "Bayi için tanımlı günlük limitlerden herhangi biri aşıldı";
                        break;
                    case "PaymentDealer.CheckDealerPaymentLimits.DailyCardLimitExceeded":
                        $errr = "Gün içinde bu kart kullanılarak daha fazla işlem yapılamaz";

                    case "PaymentDealer.CheckCardInfo.InvalidCardInfo":
                        $errr = "Kart bilgilerinde hata var";
                        break;
                    case "PaymentDealer.DoDirectPayment3dRequest.InstallmentNotAvailableForForeignCurrencyTransaction":

                        $errr = "Yabancı para ile taksit yapılamaz";
                        break;
                    case "PaymentDealer.DoDirectPayment3dRequest.ThisInstallmentNumberNotAvailableForDealer":
                        $errr = "Bu taksit sayısı bu bayi için yapılamaz";
                        break;
                    case "PaymentDealer.DoDirectPayment3dRequest.InvalidInstallmentNumber":
                        $errr = "Taksit sayısı 2 ile 9 arasıdır";
                        break;
                    case "PaymentDealer.DoDirectPayment3dRequest.ThisInstallmentNumberNotAvailableForVirtualPos":
                        $errr = "Sanal Pos bu taksit sayısına izin vermiyor";
                        break;

                    default:
                        $errr = "Beklenmeyen bir hata oluştu";
                }
                $error_msg = $errr;
                $record['result_code'] = $ResultCode;
                $record['result_message'] = $error_msg;
            }
            if (!$result or $result == null) {
                $record['result_code'] = 'CURL-LOAD_ERROR';
                $record['result_message'] = 'WebServis Error ';
                return $record;
            }
            if (isset($result->ResultCode) and $result->ResultCode == "Success") {
                if (isset($result->Data->IsSuccessful) and $result->Data->IsSuccessful) {
                    $record['result_code'] = '99';
                    $record['result_message'] = $result->ResultCode;
                    $record['result'] = true;
                    return $record;
                }
                return $record;
            }
            return $record;
        }

        function receipt_page($orderid)
        {
            global $woocommerce;
            $error_message = false;
            $order = new WC_Order($orderid);
            $rates = Kahvedigital::calculatePrices($order->get_total(), $this->rates);
            $status = $order->get_status();
            $showtotal = $order->get_total();
            $currency = $order->get_currency();
            $installments_mode = $this->installments_mode;
            if ($status != 'pending') {
                return 'ok';
            }

            if (isset($_POST['order_id']) and $_POST['order_id'] == $orderid) {
                $record = $this->post2Moka($orderid);
            }

            
           if (isset($_POST['hashValue'])) {
            session_start();   
            $record['result_code'] = $_POST['resultCode'];
            $record['result_message'] = $_POST['resultMessage'];

            $hashValue = $_POST['hashValue'];
               
            $HashSession = hash("sha256", $_SESSION['CodeForHash']."T");

            if ($hashValue == $HashSession) {
                $record['result'] = true;
            } else {
                $record['result'] = false;
            }

        }
            
            
            if (isset($record['result'])) {
                if ($record['result']) {
                    if ($record['amount_paid'] - $record['amount'] > 0) {
                        $installment_fee = $showtotal - $record['amount'];
                        $order_fee = new stdClass();
                        $order_fee->id = 'komisyon-farki';
                        $order_fee->name = __('Installment Fee', 'moka-payment-module');
                        $order_fee->amount = $installment_fee;
                        $order_fee->taxable = false;
                        $order_fee->tax = 0;
                        $order_fee->tax_data = array();
                        $order_fee->tax_class = '';
                        $fee_id = $order->add_fee($order_fee);
                        $order->calculate_totals(true);
                    }
                    $order->update_status('processing', __('Processing Moka payment', 'woocommerce'));

                    $order->add_order_note(__('Payment successful.', 'moka-payment-module') . '<br/>' . __('Payment ID', 'moka-payment-module') . ': ' . esc_sql($record['amount_paid']));
                    $order->payment_complete();
                    WC()->cart->empty_cart();
                    wp_redirect($this->get_return_url());
                    $error_message = false;
                } else {
                    $order->update_status('pending', '3D Secure yönlendirmesi bekleniyor', 'woocommerce');
                    $error_message = __('Banka Cevabı:', 'moka-payment-module') . 'HATA:' . $record['result_message'];
                }
            }
            if ($status != 'pending') {
                $order->get_status();
            }

            include dirname(__FILE__) . '/mokaform.php';
        }

        function process_payment($order_id)
        {
            $order = new WC_Order($order_id);

            if (version_compare(WOOCOMMERCE_VERSION, '2.1.0', '>=')) {
                /* 2.1.0 */
                $checkout_payment_url = $order->get_checkout_payment_url(true);
            } else {
                /* 2.0.0 */
                $checkout_payment_url = get_permalink(get_option('woocommerce_pay_page_id'));
            }

            return array(
                'result' => 'success',
                'redirect' => $checkout_payment_url,
            );
        }

    }

}

add_filter('woocommerce_payment_gateways', 'woocommerce_add_moka_checkout_form_gateway');

function woocommerce_add_moka_checkout_form_gateway($methods)
{
    $methods[] = 'WC_Gateway_Mokapos';
    return $methods;
}

function moka_checkout_form_load_plugin_textdomain()
{
    load_plugin_textdomain('moka-payment-module', false, plugin_basename(dirname(__FILE__)) . '/i18n/languages/');
}

add_action('plugins_loaded', 'moka_checkout_form_load_plugin_textdomain');
