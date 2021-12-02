<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
$moka_url = plugins_url() . '/moka-payment-module/';
?>	<script src="//ajax.googleapis.com/ajax/libs/jquery/1.10.1/jquery.min.js"></script>




<script src="<?php echo $moka_url ?>/assets/js/card.js"></script>

<?php if ($error_message) {?>
    <div class="row">
        <ul class="woocommerce-error" id="errDiv">
            <li>
                <?php echo __('Payment Error.', 'moka-payment-module') ?>
                <b><?php echo $error_message; ?></b><br/>
                <?php echo __('Please check the form and try again.', 'moka-payment-module') ?>
            </li>
        </ul>
    </div>
<?php }?>
<link rel="stylesheet" type="text/css" href="<?php echo $moka_url ?>assets/css/moka.css">
    <div class= "row">
        <div class="col-xs-12">



            <div id="moka-form" class="mokaform" >

                <div class="tum">
                    <h3 class="odemeform-baslik"><?php echo __('Payment Form', 'moka-payment-module') ?></h3>
                    <div class="hepsi">

                        <div class="demo-container">
                            <div class="info-window cvc " ><div class="arrow-info"></div><div class="cvc-info"><img src="<?php echo $moka_url ?>img/cvc-help.png"></div></div>

                            <div class="form-group active moka">
                                <form    method="POST"   id="mokapostform" action="">


                                    <div class="mokaname mokafull">
                                        <input class="c-card card-name" placeholder="<?php echo __('Name on Card', 'moka-payment-module'); ?>" type="text" required    oninvalid="this.setCustomValidity('Kart sahibinin adını yazınız.')"  oninput="setCustomValidity('')" name="card-name" id="card-name">
                                    </div>
                                    <input value="<?php echo $orderid ?>" name="order_id" type="hidden">

                                        <div class="mokacard mokaorta">
                                            <i class="mokacardicon"></i>
                                            <input id="mokacardnumber" class="c-card cardnumber" placeholder="<?php echo __('Card Number', 'moka-payment-module'); ?>" required   oninvalid="this.setCustomValidity('Kartın üzerindeki 16 haneli numarayı giriniz.')" oninput="setCustomValidity('')" type="tel" name="number" >
                                        </div>


                                        <div class="mokaleft mokaexpry">
                                            <input class="c-date c-card"  placeholder="<?php echo __('MM/YY', 'moka-payment-module'); ?>" type="tel" maxlength="7" required  oninvalid="this.setCustomValidity('Kartın son kullanma tarihini giriniz')" oninput="setCustomValidity('')" name="expiry" >
                                        </div>

                                        <div class="mokaright mokacvc">
                                            <input class="card-cvc c-card" placeholder="CVC" required  type="number"  oninvalid="this.setCustomValidity('Kartın arkasındaki 3 ya da 4 basamaklı sayıyı giriniz')" oninput="setCustomValidity('')" name="cvc" >
                                                <div class="moka-i-icon"><img src="<?php echo $moka_url ?>img/icons/info.png" width="14px"> </div>
                                        </div>

                                        </div>

                                        </div>

                                        <div class="tekcekim-container ">

                                            <div class="tekcekim">

                                                <li class="taksit-li " for="s-option" >
                                                    <input type="radio" id="s-option"  name="mokatotal"  value="<?php echo $showtotal ?>" checked class="option-input taksitradio radio " >
                                                        <label for="s-option">Tek Çekim</label>
                                                        <div class="taksit-fiyat"> <?php echo $showtotal; ?></div>
                                                        <div class="check"><div class="inside"></div></div>
                                                </li>

                                                <div class="taksit-secenek">
                                                    <?php if ($installments_mode == 'on') {?>
                                                        <h3 class="taksit-secenekleri"><?php echo __('All Installment', 'moka-payment-module'); ?></h3>

                                                        <div class="logolar-moka">

                                                            <?php foreach ($rates as $bank => $rate) {?>



                                                                <div class="moka-banka-logo <?php echo $bank; ?>-logo"><img src="<?php echo $moka_url ?>img/<?php echo $bank ?>.svg"	></img></div>


                                                            <?php }?>
                                                        </div>

                                                    <?php }?>
                                                </div>
                                            </div>
                                        </div>
                                        <?php if ($installments_mode == 'on') {?>
                                            <div class="taksit-container ">
                                                <?php foreach ($rates as $bank => $rate) {?>


                                                    <div class="<?php echo $bank; ?>">
                                                        <div class="taksit-title "><img src="<?php echo $moka_url ?>img/<?php echo $bank ?>.svg"></div>

                                                        <?php for ($ins = 1; $ins < 13; $ins++) {?>


                                                            <?php foreach ($rates as $banks => $rate) {?>
                                                                <?php if ($bank == $banks) {?>



      	 <?php if ($rates[$banks]['installments'][$ins]['active'] == 1) {?>
                                                                    <li class="taksit-li mokaorta">
                                                                        <input type="radio" id="s-option_<?php echo $banks; ?>_<?php echo $ins; ?>" name="mokatotal[<?php echo $banks; ?>][<?php echo $ins; ?>]" value="<?php echo $rates[$banks]['installments'][$ins]['total']; ?>" class="option-input  taksitradio radio">
                                                                            <label for="s-option2"><?php echo $ins ?> <?php echo __('Installment', 'moka-payment-module'); ?></label>
                                                                            <div class="taksit-fiyat"> <?php echo $rates[$banks]['installments'][$ins]['total']; ?> / <?php echo $rates[$banks]['installments'][$ins]['monthly']; ?> </div>
                                                                            <div class="check"><div class="inside"></div></div>
                                                                    </li>


      <?php }?>


                                                                <?php }?>
                                                            <?php }?>

                                                        <?php }?>




                                                    </div>


                                                <?php }?>

                                            </div>
                                        <?php }?>
                                        <button type="submit" class="mokaode" style=""><span class="mokaOdemeTutar"><?php echo $showtotal; ?></span><span class="currency"> <?php echo $currency; ?></span><span class="mokaOdemeText"> <?php echo __('Pay', 'moka-payment-module'); ?></span></button>
                                </form>

                            </div>


                        </div>
                        <div class="card-wrapper" style="margin-left:5px;"></div>
                    </div>


                </div>
            </div>

            <script>
                var theme = "<?php echo $moka_url ?>";
                var taksit = "<?php echo $installments_mode ?>";

            </script>


            <script type="text/javascript">
                new Card({
                    form: document.querySelector('.hepsi'),
                    container: '.card-wrapper',
                    formSelectors: {

                        nameInput: 'input#card-name'
                    },

                });
                $(document).ready(function () {
                    $('input[type=radio][name=mokatotal]').change(function () {

                        $('.mokaOdemeTutar').text(this.value);
					$('.currency').text(" <?php echo $currency; ?>");
                    });
                });

                if (taksit == 'on') {
                    $(document).ready(function () {
                        cardshow(0);
                        $(".maximum-logo").click(function () {
                            cardshow(0);
                            $(".taksit-container").show();
                            $(".maximum").show();
                            $("#s-option_maximum_1").prop('checked', true);
                        });

                        $(".cardfinans-logo").click(function () {
                            cardshow(0);
                            $(".taksit-container").show();
                            $(".cardfinans").show();
                            $("#s-option_cardfinans_1").prop('checked', true);
                        });
                        $(".axess-logo").click(function () {
                            cardshow(0);
                            $(".taksit-container").show();
                            $(".axess").show();
                            $("#s-option_axess_1").prop('checked', true);
                        });
						 $(".paraf-logo").click(function () {
                        cardshow(0);
                        $(".taksit-container").show();
                        $(".paraf").show();
                        $("#s-option_paraf_1").prop('checked', true);
						});
                        $(".bonus-logo").click(function () {
                            cardshow(0);
                            $(".taksit-container").show();
                            $(".bonus").show();
                            $("#s-option_bonus_1").prop('checked', true);
                        });
                        $(".world-logo").click(function () {
                            cardshow(0);
                            $(".taksit-container").show();
                            $(".world").show();
                            $("#s-option_world_1").prop('checked', true);
                        });
                        $(".combo-logo").click(function () {
                            cardshow(0);
                            $(".taksit-container").show();
                            $(".combo").show();
                            $("#s-option_combo_1").prop('checked', true);
                        });
                        $(".amex-logo").click(function () {
                            cardshow(0);
                            $(".taksit-container").show();
                            $(".amex").show();
                            $("#s-option_amex_1").prop('checked', true);
                        });
                        $(".taksit-li").click(function () {
                            $(".taksit-li").find('input[type="radio"]').removeAttr('checked');
                            $(this).find('input[type="radio"]').prop('checked', true);
                            var price = $(this).find('input[type="radio"]').val();
                            $('.mokaOdemeTutar').text(price);
                        });
                        function cardshow(bankcode) {
                            if (bankcode == '0') {
                                $(".taksit-container").hide();
                                $(".taksit-container").children().hide();
                            } else if ((bankcode == 62) || (bankcode == 59) || (bankcode == 32) || (bankcode == 99) || (bankcode == 124) || (bankcode == 134) || (bankcode == 206)) {
                                $(".taksit-container").hide();
                                $(".taksit-container").children().hide();
                                $(".taksit-container").show();
                                $('.bonus').show();
                                $("#s-option_bonus_1").prop('checked', true);
                            } else if ((bankcode == 46) || (bankcode == 92)) {
                                $(".taksit-container").hide();
                                $(".taksit-container").children().hide();
                                $(".taksit-container").show();
                                $('.axess').show();
                                $("#s-option_axess_1").prop('checked', true);
                            } else if ((bankcode == 64) || (bankcode == 10)) {
                                $(".taksit-container").hide();
                                $(".taksit-container").children().hide();
                                $(".taksit-container").show();
                                $('.maximum').show();
                                $("#s-option_maximum_1").prop('checked', true);
                            } else if ((bankcode == 15) || (bankcode == 67) || (bankcode == 135) || (bankcode == 203)) {
                                $(".taksit-container").hide();
                                $(".taksit-container").children().hide();
                                $(".taksit-container").show();
                                $('.world').show();
                                $("#s-option_world_1").prop('checked', true);
                            } else if (bankcode == 111) {
                                $(".taksit-container").hide();
                                $(".taksit-container").children().hide();
                                $(".taksit-container").show();
                                $('.cardfinans').show();
                                $("#s-option_cardfinans_1").prop('checked', true);
                            }else if(bankcode == 12){
							$(".taksit-container").hide();
                            $(".taksit-container").children().hide();
                            $(".taksit-container").show();
                            $('.paraf').show();
                            $("#s-option_paraf_1").prop('checked', true);
						}else if(bankcode == 217){
							$(".taksit-container").hide();
                            $(".taksit-container").children().hide();
                            $(".taksit-container").show();
                            $('.combo').show();
                            $("#s-option_combo_1").prop('checked', true);
						}else if(bankcode == 100){
							$(".taksit-container").hide();
                            $(".taksit-container").children().hide();
                            $(".taksit-container").show();
                            $('.amex').show();
                            $("#s-option_amex_1").prop('checked', true);
						}
                        }
                        $.ajaxSetup({cache: false});
                        $('#mokacardnumber').keyup(function () {
                            var searchField = $('#mokacardnumber').val();
                            searchField = searchField.replace(/\s/g, '');
                            if (searchField.length < 6) {
                                cardshow(0);
                                return;
                            };
                            if (searchField.length > 6)
                                return;


                                jQuery.ajax({
  type:"POST",
  url: "/moka-payment-module/wc-api/mokapos/",
  data: {
      BinNumber: searchField
  },
  success:function(data){
    var response=JSON.parse(data);

  console.log(response.Data.BankCode);

  cardshow(response.Data.BankCode);
  },
  error: function(errorThrown){
      alert(errorThrown);
  }

});




                        });

                    });
                }

                $(".moka-i-icon img").hover(function () {
                    $(".info-window").toggleClass("info-window-active");
                });

                $('.c-card').bind('keypress keyup keydown focus', function (e) {
                    var ErrorInput = false;
                    if ($("input.card-name").hasClass("jp-card-invalid")) {
                        ErrorInput = true;
                        $("input.card-name").addClass("border");
                    }
                    if ($("input.cardnumber").hasClass("jp-card-invalid")) {
                        ErrorInput = true;
                        $("input.cardnumber").addClass("border");
                    }
                    if ($("input.c-date").hasClass("jp-card-invalid")) {
                        ErrorInput = true;
                        $("input.c-date").addClass("border");
                    }
                    if ($("input.card-cvc").hasClass("jp-card-invalid")) {
                        ErrorInput = true;
                        $("input.card-cvc").addClass("border");
                    }
                    if (ErrorInput === true) {
                        $('.mokaode').attr("disabled", true);
                        $(".mokaode").css("opacity", "0.5");

                    } else {

                        $("input.card-name").removeClass("border");
                        $("input.cardnumber").removeClass("border");
                        $("input.c-date").removeClass("border");
                        $("input.card-cvc").removeClass("border");
                        $('.mokaode').attr("disabled", false);
                        $(".mokaode").css("opacity", "1");

                    }

                });





            </script>
