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
        Taksit tablosunu temanızın ya da içeriklerinizin istediğiniz alanında, <strong>[moka-taksit-tablosu]</strong> kısa kodu ile gösterebilirsiniz.
    </p>
</div>

<hr>
<h3>Açık Kaynak</h3>
<p>Sizlerde GitHub üzerinden kodun geliştirmesine katkıda bulunabilirsiniz.</p>
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