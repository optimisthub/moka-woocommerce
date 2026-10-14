<p align="center">
  <a href="https://wordpress.org/plugins/optimisthub-moka-united-payment-for-woocommerce/">
    <img alt="Optimist Hub Payment Gateway with Moka United for WooCommerce" src="https://ps.w.org/optimisthub-moka-united-payment-for-woocommerce/assets/banner-1544x500.png">
  </a>
</p>

---

> # ⚠️ Bu eklenti artık geliştirilmiyor
>
> **Moka United for WooCommerce** artık **WordPress.org** üzerinden dağıtılıyor. Yeni sürümler, güvenlik düzeltmeleri ve destek yalnızca resmî eklentide yayınlanıyor. **Bu depo yalnızca arşiv amacıyla duruyor; yeni sürüm yayınlanmayacaktır.**
>
> ## 👉 [Resmî eklentiyi WordPress.org'dan indirin](https://wordpress.org/plugins/optimisthub-moka-united-payment-for-woocommerce/)
>
> | | |
> |---|---|
> | **Resmî eklenti** | <https://wordpress.org/plugins/optimisthub-moka-united-payment-for-woocommerce/> |
> | **Bu depodaki son sürüm** | 3.9.2 |
> | **Durum** | Geliştirme durduruldu |
>
> ### Geçiş nasıl yapılır
>
> 1. Yönetim panelinizde **Moka United güncellemesi** bildirimini görün ve bu depodan gelen **3.9.2** sürümüne güncelleyin.
> 2. Güncellemeden sonra panelde geçiş köprüsü belirir: *"Moka United eklentisi yenilendi"*.
> 3. **Yeni sürümü kur ve geç** butonuna basın — resmî eklenti WordPress.org'dan kurulur.
> 4. **Ayarlarınız ve işlem geçmişiniz korunur**; geçit kimliği (`mokapay`) aynıdır. Eski eklenti, yeni sürüm etkinleştirildikten **sonra** devre dışı bırakılır — kurulum başarısız olursa ödeme almaya devam edersiniz.
>
> ### Neden resmî eklentiye geçmelisiniz
>
> - **Güvenlik:** resmî eklenti WordPress.org incelemesinden geçti (`APPROVED`), kart verisi hiçbir zaman sitenizde toplanmaz (PCI kapsamı **SAQ A**).
> - **Güncellemeler:** WordPress çekirdeği üzerinden otomatik gelir; bu depodaki gibi ayrı bir güncelleme sunucusuna bağlı değildir.
> - **Blok checkout desteği:** yeni WooCommerce blok ödeme sayfasıyla çalışır.
> - **Çok döviz desteği:** Moka'nın kabul ettiği 14 para birimi (TRY → `TL` eşlemesiyle).
> - **Türkçe + İngilizce** arayüz ve güncel uyumluluk (WordPress 7.1, PHP 7.4–8.x).
>
> ---
>
> ## ⚠️ This plugin is no longer maintained
>
> Development has moved to **WordPress.org**: **<https://wordpress.org/plugins/optimisthub-moka-united-payment-for-woocommerce/>**
>
> This repository is kept for archival only and will not receive new releases. Install the official plugin — your settings and transaction history are preserved, and the old plugin is deactivated only after the new one is active.

---

## Description

## Description

- This is a WooCommerce module developed by Optimist Hub team. 
- After the integration you will be able to use pay with Moka POS automatically.
- You can easily integrate Moka United POS WooCommerce module into your e-commerce website and start receiving payment seamlessly and securely. 
- With the open source code, you can make easily make new developments on your website. 
- Moka United POS WooCommerce module supports SEO tools and is 100% compatible with Google.
- You can test your website in Sandbox, one of the best test environments.
- Moka United PAY WooCommerce module allows you to sell with installments on your website.
- Moka United PAY WooCommerce module is 100% compatible with WooCommerce and Wordpress systems. 
- After the integration you can offer manually created orders and payment support to your customers.

## ClientIP & ClientPort (Required)

TCMB regulation makes the end user `ClientIP` and `ClientPort` fields mandatory on every payment request.
The plugin sends both automatically, with no configuration required on a standard server.

**Behind a proxy, load balancer or CDN** the connection is terminated by the intermediary, so the real
customer address and port must be forwarded to PHP. Configure your edge to pass these headers:

```nginx
# nginx
proxy_set_header X-Real-IP        $remote_addr;
proxy_set_header X-Forwarded-For  $proxy_add_x_forwarded_for;
proxy_set_header X-Forwarded-Port $remote_port;
```

```apache
# Apache
RemoteIPHeader X-Forwarded-For
```

Cloudflare and most CDNs set `CF-Connecting-IP` / `X-Forwarded-For` automatically.

The plugin reads, in order:

- **IP** : `CF-Connecting-IP` -> `True-Client-IP` -> `X-Real-IP` -> `X-Forwarded-For` (first public address) -> `Client-IP` -> `REMOTE_ADDR`
- **Port** : `X-Forwarded-Port` -> `X-Real-Port` -> `X-Client-Port` -> `REMOTE_PORT`

Private, reserved and loopback addresses are discarded, because Moka rejects them.
If your infrastructure uses custom header names, override the detection:

```php
add_filter('optimisthub_moka_client_ip', fn() => $_SERVER['HTTP_X_MY_CLIENT_IP'] ?? null);
add_filter('optimisthub_moka_client_port', fn() => $_SERVER['HTTP_X_MY_CLIENT_PORT'] ?? null);
```

Since recurring subscription payments run from cron with no visitor connection, the original
`ClientIP` / `ClientPort` captured on the first payment are reused for renewals.

## Requirements & Release Notes

Moka United Pos, Moka United Pay plugin;

- Minimum PHP 7.4+ requirement is required.
- PHP cURL extension is required.
- Tried with MYSQL 5.7.43, 8.0.
- Tested with PHP versions 7.4, 8.1.
- Fully compatible with WooCommerce version 6.0+.
- Fully compatible with WordPress 5.8.2+.

## How To Install

> **Bu depodan kurulum artık önerilmez.** Resmî eklentiyi WordPress.org'dan kurun:
> <https://wordpress.org/plugins/optimisthub-moka-united-payment-for-woocommerce/>
>
> Yönetim panelinizden veya WordPress eklenti dizininden arayarak da kurabilirsiniz: **Optimist Hub Payment Gateway with Moka United for WooCommerce**.

- (Eski yol, arşiv) Moka United Pay WooCommerce Plugin Download ZIP file and then Install with wordpress extension installer page.

### Test Cards

For sandbox usage : https://developer.mokaunited.com/home.php?page=test-kartlari

### Changelog 

Version history : https://github.com/optimisthub/moka-woocommerce/wiki/Changelog

### Filters & Actions 

Filters & Actions : https://github.com/optimisthub/moka-woocommerce/wiki/Filters-&-Actions

### Other integrations

- Moka United PHP Client : https://github.com/optimisthub/moka-php 
- Moka United OpenCart 3.x : https://github.com/optimisthub/moka-opencart-3.x
- Moka United OpenCart 2.3x : https://github.com/optimisthub/moka-opencart-2.3
- Moka United OpenCart 2.2x : https://github.com/optimisthub/moka-opencart-2.2
- Moka United Presta Shop : https://github.com/optimisthub/moka-prestashop
- Moka United Magento : https://github.com/optimisthub/moka-magento

#### Powerfull WordPress Plugins by Optimisthub 

- Smart SEO Friendly Sitemap Generator *[Already Published]* : https://github.com/optimisthub/smart-sitemap-generator
- Scheduled Posts Issue Fixer : https://github.com/optimisthub/scheduled-posts-issue-fixer
- Enable Svg Support : https://github.com/optimisthub/wordpress-svg-enabler