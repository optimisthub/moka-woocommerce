<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
$moka_url = plugins_url() . '/moka-payment-module/';
?>
<!-- Latest compiled and minified CSS -->
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
    <!-- Latest compiled and minified JavaScript -->

    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>

    <div class="panel">
        <div class="row kahvedigital_moka-header">
            <img src="<?php echo $moka_url ?>img/logo.png" class="col-xs-4 col-md-2 text-center" id="payment-logo" />
            <div class="col-xs-6 col-md-5 text-center">
                <h4>Moka Ödeme Kuruluşu A.Ş.</h4>
                <h4>Hızlı Güvenli ve Kolay</h4>
            </div>
            <div class="col-xs-12 col-md-5 text-center">
                <a href="https://moka.com" class="btn btn-primary" id="create-account-btn">Moka SanalPOS'a başvurun</a><br />
                Moka SanalPOS'unuz varsa ?<a href="https://pos.moka.com"> Hesabınıza giriş yapın</a>
            </div>
        </div>

        <hr />


        <div class="kahvedigital_moka-content">
            <div class="row">
                <div class="col-md-4 col-sm-4">
                    <div class="thumbnail">
                        <figure class="figure text-center">
                            <img src="<?php echo $moka_url ?>img/icons/icon_clock.png" width="140" height="100"/>
                        </figure>
                        <p class="text text-center">
                            7x24 kesintisiz
                            <br>tahsilat imkanı
                        </p>
                    </div>
                </div>
                <div class="col-md-4 col-sm-4">
                    <div class="thumbnail">
                        <figure class="figure text-center">
                            <img src="<?php echo $moka_url ?>img/icons/icon_money.png" width="140" height="100"/>
                        </figure>
                        <p class="text text-center">
                            Hesaplı
                            <br>satış avantajı
                        </p>
                    </div>
                </div>
                <div class="col-md-4 col-sm-4">
                    <div class="thumbnail">
                        <figure class="figure text-center">
                            <img src="<?php echo $moka_url ?>img/icons/icon_credit_card.png" width="140" height="100"/>
                        </figure>
                        <p class="text text-center">
                            Bütün kredi kartları için
                            <br>taksitli satış imkanı
                        </p>
                    </div>
                </div>
                <div class="col-md-4 col-sm-4">
                    <div class="thumbnail">
                        <figure class="figure text-center">
                            <img src="<?php echo $moka_url ?>img/icons/icon_visa_mastercard.png" width="140" height="100"/>
                        </figure>
                        <p class="text text-center">
                            Visa ve MasterCard
                            <br>tahsilat imkanı
                        </p>
                    </div>
                </div>
                <div class="col-md-4 col-sm-4">
                    <div class="thumbnail">
                        <figure class="figure text-center">
                            <img src="<?php echo $moka_url ?>img/icons/icon_exchange.png" width="140" height="100"/>
                        </figure>
                        <p class="text text-center">
                            Yabancı kartlar ile
                            <br>işlem yapabilme
                        </p>
                    </div>
                </div>
                <div class="col-md-4 col-sm-4">
                    <div class="thumbnail">
                        <figure class="figure text-center">
                            <img src="<?php echo $moka_url ?>img/icons/icon_cogs.png" width="140" height="100"/>
                        </figure>
                        <p class="text text-center">
                            Hızlı ve kolay
                            <br>entegrasyon
                        </p>
                    </div>
                </div>
            </div>
            <hr />
        </div>
    </div>

    <div class="panel">
        <div class="row">   
            <div class="alert alert-success"> Bu plugin için teknik destek işlemleri Moka Ödeme A.Ş. adına <a href="http://kahvedigital.com">KahveDigital</a> tarafından <b>ÜCRETSİZ</b> sağlanmaktadır</div>
        </div>
        <div class="row">
            <div class="col-sm-6 text-center">            

                <div class="row">
                    <div class="col-sm-2"></div>
                    <img src="<?php echo $moka_url ?>img/kahvedigital-help.jpg" class="col-sm-8 text-center" id="payment-logo" />
                    <div class="col-sm-2"></div>
                </div>
            </div>
            <style>

                .zbtn {
                    border: none;
                    font-family: inherit;
                    font-size: 13px;
                    color: white !important;
                    background: none;
                    cursor: pointer;
                    padding: 25px 80px;
                    display: inline-block;

                    margin: 15px 30px;
                    text-transform: uppercase;
                    letter-spacing: 1px;
                    font-weight: 700;
                    max-width: 350px;
                    min-width: 350px;
                    outline: none;
                    position: relative;
                    -webkit-transition: all 0.3s;
                    -moz-transition: all 0.3s;
                    transition: all 0.3s;
                }
                .btn-2a {
                    border-radius: 0 0 5px 5px;
                }

                .btn-2a:hover {
                    box-shadow: 0 4px #e91027;
                    top: 2px;
                }

                .btn-2a:active {
                    box-shadow: 0 0 #e91027;
                    top: 6px;
                }
                .btn-2 {
                    background: #ff2b42;
                    color: #fff;
                    box-shadow: 0 6px #e91027;
                    -webkit-transition: none;
                    -moz-transition: none;
                    transition: none;
                }

            </style>
            <div class="col-sm-6 panel text-center">
                <h1>Destek</h1><hr/>
                <a class="zbtn btn-2 btn-2a" href="http://docs.kahvedigital.com/moka/woocommerce"> Kullanım Klavuzu</a></br>
                <a class="zbtn btn-2 btn-2a" href="http://client.kahvedigital.com/admin/login/signin">Destek Sistemi</a></br>
                <a class="zbtn btn-2 btn-2a">+90(0212)570 81 29</a></br>
                <a class="zbtn btn-2 btn-2a" href="mailto:destek@kahvedigital.com">destek@kahvedigital.com</a>

            </div>

            <hr/>
        </div>





        <div class="panel">
            <div class="col-sm-12 text-center">
                <a href="https://www.facebook.com/kahvedigital/"><img src="<?php echo $moka_url ?>img/icons/facebook.png" width="32px" /></a>
                <a href="https://twitter.com/kahvedigital"><img src="<?php echo $moka_url ?>img/icons/twitter.png" width="32px" /></a>
                <a href="https://www.youtube.com/user/kahvedigital"><img src="<?php echo $moka_url ?>img/icons/youtube.png" width="32px" /></a>
                <a href="https://www.linkedin.com/company/kahve-digital/"><img src="<?php echo $moka_url ?>img/icons/linkedin.png" width="32px" /></a>
                <a href="https://www.instagram.com/kahvedigital/"><img src="<?php echo $moka_url ?>img/icons/instagram.png" width="32px" /></a>
                <a href="https://wordpress.org/support/users/kahvedigital"><img src="<?php echo $moka_url ?>img/icons/wordpress.png" width="32px" /></a>
                <a href="https://github.com/kahvedigital/"><img src="<?php echo $moka_url ?>img/icons/github.png" width="32px" /></a>
            </div>
            <hr/>
        </div>
    </div>