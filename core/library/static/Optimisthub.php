<style>
  .moka-admin-interface{display:flex;justify-content:center;flex-direction:column}.moka-admin-interface .left{width:100%}.moka-admin-interface .right{width:100%;margin-top:20px}.moka-admin-interface .right .optimist{background:#fff;padding:40px;border-radius:4px}.moka-admin-interface .right .optimist .accordion{background-color:#eee;color:#444;cursor:pointer;padding:18px;border:0;text-align:left;outline:0;font-size:15px;transition:.4s;display:block;border-bottom:1px solid #fff}.moka-admin-interface .right .optimist .accordion:hover,.moka-admin-interface .right .optimist .active{background-color:#ccc}.moka-admin-interface .right .optimist .accordion:after{content:"+";color:#777;font-weight:700;float:right;margin-left:5px}.moka-admin-interface .right .optimist .active:after{content:"−"}.moka-admin-interface .right .optimist .panel{background-color:#fff;max-height:0;overflow:hidden;transition:max-height .2s ease-out}#comission-rates{font-family:Arial,Helvetica,sans-serif;border-collapse:collapse;width:100%}#comission-rates td{padding:8px}#comission-rates td,#comission-rates th{border:1px solid #ddd}#comission-rates thead{background-color:#333;color:#fff}#comission-rates tr:nth-child(even){background-color:#f2f2f2}#comission-rates tr:hover{background-color:#ddd}#comission-rates th{padding:12px 8px;text-align:left;background-color:#04aa6d;color:#fff}#comission-rates input[type=number]{width:59px!important;font-size:11px}#comission-rates img{width:100px}.center-title h2{display:flex;justify-content:space-between}.center-title h2 a{color:#fff;background:#333!important;padding:10px;cursor:pointer;font-size:12px}.center-title h2 a:hover{background:#d00!important}
</style>
<h3>Kurulum ve Kullanım Kılavuzu</h3>

<a class="accordion">Eklenti Kurulumu ve Ayarlar</a>
<div class="panel">
    <p>
        <strong style="color:blue">Gereksinimler ve Sürüm Notları</strong>
        <br>
        Moka Pos, Moka Pay eklentisi; <br>
        <ul>
          <li>- Minimum PHP 7.1> gereksinimi zorunludur.</li>
          <li>- PHP cURL extension zorunludur.</li>
          <li>- MYSQL 8.0+ ile denenmiştir.</li>
          <li>- PHP 7.1,7.4,8.x> sürümleri ile denenmiştir.</li>
          <li>- WooCommerce 6.0+ sürümü ile tam uyumludur.</li>
          <li>- WordPress 5.8.2 ile tam uyumludur.</li>
        </ul>
    </p>
    <p>
        <strong style="color:blue">Eklenti Ayarları</strong>
        <br>
        Yandaki alandan, Bayi adı, bayi kodu, api kullanıcı adı ve şifenizi yazdıktan sonra, isteğe bağlı olarak test modunu aktif pasif yapabilirsiniz.
    </p>
</div>

<a class="accordion">Sıkça Sorulan sorular</a>
<div class="panel">
    <p>
        <strong style="color:blue">Taksit Tablosunu Temanızda Gösterme</strong>
        <br>
        Taksit tablosunu temanızın ya da içeriklerinizin istediğiniz alanında, <strong>[moka-taksit-tablosu]</strong> veya <strong>[moka-installment-table]</strong> kısa kodu ile gösterebilirsiniz.
    </p>
</div>

<hr>
<h3>Açık Kaynak</h3>
<p>Sizler de GitHub üzerinden kodun geliştirmesine katkıda bulunabilirsiniz.</p>
<a href="https://github.com/optimisthub/moka-woocommerce">
  <img src="https://img.shields.io/github/last-commit/optimisthub/moka-woocommerce?label=Recent%20Update&style=for-the-badge" alt=""> 
</a>
 
<hr>
<br>
<a href="https://optimisthub.com/?ref=<?php echo get_bloginfo('wpurl'); ?>&source=moka-woocommerce" target="_blank">
  <img style="width:220px" src="<?php echo plugins_url( 'moka-woocommerce-master/assets/img/optimisthub.svg' ); ?>" alt="">
</a>
<p><strong>Ücretsiz</strong> Moka POS WooCommerce Eklentisi Optimist Hub Bünyesinde geliştirilmiştir.</p>


<script>
var acc = document.getElementsByClassName("accordion");
var i;
for (i = 0; i < acc.length; i++) {
  acc[i].addEventListener("click", function() {
    this.classList.toggle("active");
    var panel = this.nextElementSibling;
    if (panel.style.maxHeight) {
      panel.style.maxHeight = null;
    } else {
      panel.style.maxHeight = panel.scrollHeight + "px";
    } 
  });
}
</script>