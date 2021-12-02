<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class KahveDigital
{

    const max_installment = 12;

    public static function getAvailablePrograms()
    {
        return array(
            'axess' => array('name' => 'Axess', 'bank' => 'Akbank A.Ş.', 'installments' => true),
            'world' => array('name' => 'WordCard', 'bank' => 'Yapı Kredi Bankası', 'installments' => true),
            'bonus' => array('name' => 'BonusCard', 'bank' => 'Garanti Bankası A.Ş.', 'installments' => true),
            'cardfinans' => array('name' => 'CardFinans', 'bank' => 'FinansBank A.Ş.', 'installments' => true),
            'maximum' => array('name' => 'Maximum', 'bank' => 'T.C. İş Bankası', 'installments' => true),
            'paraf' => array('name' => 'Paraf', 'bank' => 'Halk Bankası', 'installments' => true),
            'combo' => array('name' => 'Kart Combo', 'bank' => 'Ziraat Bankası', 'installments' => true),
            'amex' => array('name' => 'Amex', 'bank' => 'Amerikan Express', 'installments' => true),
        );
    }

    public static function setRatesFromPost($posted_data)
    {
        $banks = KahveDigital::getAvailablePrograms();
        $return = array();
        foreach ($banks as $k => $v) {
            $return[$k] = array();
            for ($i = 1; $i <= self::max_installment; $i++) {
                $return[$k]['installments'][$i]['value'] = isset($posted_data[$k]['installments'][$i]['value']) ? ((float) $posted_data[$k]['installments'][$i]['value']) : 0.0;
                $return[$k]['installments'][$i]['active'] = isset($posted_data[$k]['installments'][$i]['active']) ? ((int) $posted_data[$k]['installments'][$i]['active']) : 0;
            }
        }
        return $return;
    }

    public static function setRatesDefault()
    {
        $banks = KahveDigital::getAvailablePrograms();
        $return = array();
        foreach ($banks as $k => $v) {
            $return[$k] = array('active' => 0);
            for ($i = 1; $i <= self::max_installment; $i++) {
                $return[$k]['installments'][$i]['value'] = (float) (1 + $i + ($i / 5) + 0.1);
                $return[$k]['installments'][$i]['active'] = $v['installments'];
                if ($i == 1) {
                    $return[$k]['installments'][$i]['value'] = 0.00;
                    $return[$k]['installments'][$i]['active'] = 1;
                }
            }
        }
        return $return;
    }

    public static function register_all_ins()
    {
        if (isset($_POST['kahvedigital_moka_rates'])) {
            update_option('kahvedigital_moka_rates', KahveDigital::setRatesFromPost($_POST['kahvedigital_moka_rates']));
        }

    }

    public static function createRatesUpdateForm($rates)
    {
        $moka_url = plugins_url() . '/moka-payment-module/';
        $return = '<table class="kahvedigital_moka_table table">'
            . '<thead>'
            . '<tr><th>Banka</th>';
        for ($i = 1; $i <= self::max_installment; $i++) {
            $return .= '<th>' . $i . ' taksit</th>';
        }
        $return .= '</tr></thead><tbody>';

        $banks = KahveDigital::getAvailablePrograms();
        foreach ($banks as $k => $v) {
            $return .= '<tr>'
                . '<th text-align="left"><img src="' . $moka_url . 'img/' . $k . '.svg" width="80px"></th>';
            for ($i = 1; $i <= self::max_installment; $i++) {
                $return .= '<td>'
                    . ' <input type="checkbox"  name="kahvedigital_moka_rates[' . $k . '][installments][' . $i . '][active]" '
                    . ' value="1" ' . ((int) $rates[$k]['installments'][$i]['active'] == 1 ? 'checked="checked"' : '') . '/>'
                    . '<input type="number" step="0.01" maxlength="4" size="4" style="width:60px" '
                    . ((int) $rates[$k]['installments'][$i]['active'] == 0 ? 'disabled="disabled"' : '')
                    . ' value="' . ((float) $rates[$k]['installments'][$i]['value']) . '"'
                    . ' name="kahvedigital_moka_rates[' . $k . '][installments][' . $i . '][value]"/></td>';
            }
            $return .= '</tr>';
        }
        $return .= '</tbody></table>';
        return $return;
    }

    public static function calculatePrices($price, $rates)
    {
        $banks = KahveDigital::getAvailablePrograms();
        $return = array();
        foreach ($banks as $k => $v) {
            if ($v['installments'] == false) {
                continue;
            }

            for ($i = 1; $i <= self::max_installment; $i++) {
                $return[$k]['installments'][$i] = array(
                    'active' => $rates[$k]['installments'][$i]['active'],
                    'total' => number_format((((100 + $rates[$k]['installments'][$i]['value']) * $price) / 100), 2, '.', ''),
                    'monthly' => number_format((((100 + $rates[$k]['installments'][$i]['value']) * $price) / 100) / $i, 2, '.', ''),
                );
            }
        }
        return $return;
    }

    public function getRotatedRates($price, $rates)
    {
        $prices = KahveDigital::calculatePrices($price, $rates);
        for ($i = 1; $i <= self::max_installment; $i++) {

        }
    }

    public static function createInstallmentsForm($price, $rates)
    {
        $moka_url = plugins_url() . '/moka-payment-module/';
        $prices = KahveDigital::calculatePrices($price, $rates);
        $return = '<table class="kahvedigital_moka_table table installments">'
            . '<thead>'
            . '<tr><th>Banka</th>';
        for ($i = 1; $i <= self::max_installment; $i++) {
            $return .= '<th>' . $i . ' taksit</th>';
        }
        $return .= '</tr></thead><tbody>';

        $banks = KahveDigital::getAvailablePrograms();
        foreach ($banks as $k => $v) {
            $return .= '<tr>'
                . '<th><img src="' . $moka_url . 'img/' . $k . '.svg"></th>';
            for ($i = 1; $i <= self::max_installment; $i++) {
                $return .= '<td><input type="number" step="0.001" maxlength="4" size="4" '
                    . ' value="' . ((float) $rates[$k]['installments'][$i]) . '"'
                    . ' name="kahvedigital_moka_rates[' . $k . '][installments][' . $i . ']"/></td>';
            }
            $return .= '</tr>';
        }
        $return .= '</tbody></table>';
        return $return;
    }

    public static function frontInstallmentsForm($price, $rates)
    {
        $prices = KahveDigital::calculatePrices($price, $rates);
        $return = '<table class="kahvedigital_moka_table table">'
            . '<thead>'
            . '<tr>';
        $banks = KahveDigital::getAvailablePrograms();
        $return .= '<th  style="width:90px;">Taksit</th>';
        foreach ($banks as $k => $v) {
            $return .= '<th><img src="' . get_site_url() . '/wp-content/plugins/moka-payment-module/img/' . $k . '.svg" style="margin:3px;"></th>';
        }
        $return .= '</tr></thead><tbody>';

        for ($i = 1; $i <= self::max_installment; $i++) {
            $return .= '<tr class="ins"><td><input type="radio"' . ' value="' . $i . '"'
                . ' name="kahvedigital_moka_selected_installment"/>' . $i . '<small> Taksit</small></td>';
            foreach ($banks as $k => $v) {
                $rate = $rates[$k]['installments'][$i]['value'];
                $total = round((((float) $rate / 100) * $price) + $price, 2);
                $return .= '<td>' . $rates[$k]['installments'][$i]['active'] ? $total . ' TL' : ' - ' . '</td>';
            }
            $return .= '</tr>';
        }
        $return .= '</tbody></table>';
        return $return;
    }

    public static function getProductInstallments($price, $rates)
    {
//        print_r($rates);
        //        exit;
        $prices = KahveDigital::calculatePrices($price, $rates);
        $banks = KahveDigital::getAvailablePrograms();
        $return = '
		<link rel="stylesheet" type="text/css" href="' . get_site_url() . '/wp-content/plugins/moka-payment-module/css/product_tab.css" />
		<section class="page-product-box"><h3 class="page-product-heading">Taksit Seçenekleri</h3><div class="row">';
        $bank_counter = 0;
        foreach ($banks as $k => $v) {
            if (!$v['installments']) {
                continue;
            }

            $bank_counter++;
            if ($bank_counter == 5) {
                $return .= '</div><div class="row">';
            }
            $return .= '<div class="kahvedigital_moka_bank">
				<div class="box">
					<div class="block_title" align="center"><img src="' . get_site_url() . '/wp-content/plugins/moka-payment-module/img/' . $k . '.png"></div>';
            $return .= '<table class="borderless">
						<tr>
							<th>Taksit</th>
							<th>Aylık </th>
							<th>Toplam</th>
						</tr>';
            for ($i = 1; $i <= 9; $i++) {
                $rate = $rates[$k]['installments'][$i]['value'];
                $total = round((((float) $rate / 100) * $price) + $price, 2);
                $monthly = round(($total / $i), 2);
                $return .= '<tr style="text-align:center">
					<td>' . $i . '</td>
					<td class="' . $k . '">' . ($rates[$k]['installments'][$i]['active'] ? $monthly . get_woocommerce_currency_symbol() : '-') . '</td>
					<td>' . ($rates[$k]['installments'][$i]['active'] ? $total . get_woocommerce_currency_symbol() : '-') . '</td>
				</tr>';
            }
            $return .= '</table></div></div>';
        }
        $return .= '<div class="kahvedigital_moka_bank">
				<div class="box">
					<div class="block_title"><h3>Diğer Kartlar</h3></div>
					Tüm bankaların kartları ile visa/mastercard/amex tek çekim (taksitsiz) ödeme yapabilirsiniz.
					<hr/>

					</div>
					</div>';

        $return .= '</div></section>'
            . '<!-- Latest compiled and minified CSS -->
                    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
                    <!-- Latest compiled and minified JavaScript -->
                    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>';
        return $return;
    }

}
