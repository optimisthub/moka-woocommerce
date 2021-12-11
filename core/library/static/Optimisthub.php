<style>
    .moka-admin-interface{display:flex;justify-content:center;}  
    .moka-admin-interface .left{width:70%;}  
    .moka-admin-interface .right{width:28%;margin-left:2%;}  
    .moka-admin-interface .right .optimist{background:#fff;padding:40px;border-radius:4px;}   

 
    .moka-admin-interface .right .optimist .accordion{background-color:#eee;color:#444;cursor:pointer;padding:18px; border:0;text-align:left;outline:0;font-size:15px;transition:.4s;display:block;border-bottom:1px solid #fff;}.moka-admin-interface .right .optimist .active,.moka-admin-interface .right .optimist .accordion:hover{background-color:#ccc}.moka-admin-interface .right .optimist .accordion:after{content:"+";color:#777;font-weight:700;float:right;margin-left:5px}.moka-admin-interface .right .optimist .active:after{content:"−"}.moka-admin-interface .right .optimist .panel{background-color:#fff;max-height:0;overflow:hidden;transition:max-height .2s ease-out}
    
</style>

<img src="<?php echo plugins_url( 'moka-woocommerce/assets/img/optimisthub.svg' ); ?>" alt="">
<p>Moka POS WordPress Eklentisi OptimistHub Bünyesinde geliştirilmiştir.</p>

<h3>Kurulum ve Kullanım Kılavuzu</h3>
 

<a class="accordion">Eklenti Kurulumu ve Ayarlar</a>
<div class="panel">
  <p>Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.</p>
</div>

<a class="accordion">Sıkça Sorulan sorular</a>
<div class="panel">
  <p>Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.</p>
</div>
 

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