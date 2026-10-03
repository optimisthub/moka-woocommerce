<?php
/**
 * WordPress.org geçiş köprüsü (sürüm 3.9.0).
 *
 * Bu sınıf 3.8.9'da YOKTUR; 3.9.0 ile eklenir ve tek amacı mevcut
 * kullanıcıları yeni WP.org eklentisine tek tıkla taşımaktır.
 *
 * @package OptimistHub\Moka
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Eski eklentiden yeni WP.org eklentisine geçişi yönetir.
 *
 * Neden gerekli: yeni eklenti WordPress.org'da **farklı bir slug** ile
 * yayınlanır. WordPress, farklı slug'lı bir eklentiyi otomatik olarak
 * diğeriyle değiştirmez; ayrıca bu eklenti WP.org'da yayınlanmadığı için
 * güncelleme akışı da yoktur.
 *
 * Geçit kimliği (`mokapay`) ve ayar anahtarı
 * (`woocommerce_mokapay_settings`) iki eklentide **aynıdır**; bu yüzden
 * mağazacı ayarlarını yeniden girmek zorunda kalmaz.
 *
 * İki eklenti aynı anda etkin olursa geçit çakışır (ikisi de `mokapay`
 * kaydeder), bu yüzden geçiş **devre dışı bırakmayı** da içerir.
 */
class Optimisthub_Migration {

	/**
	 * Yeni eklentinin WordPress.org slug'ı.
	 */
	const TARGET_SLUG = 'optimist-hub-payment-gateway-with-moka-united-for-woocommerce';

	/**
	 * Yeni eklentinin ana dosyası (slug'a göre).
	 */
	const TARGET_FILE = 'optimist-hub-payment-gateway-with-moka-united-for-woocommerce/optimist-hub-payment-gateway-with-moka-united-for-woocommerce.php';

	/**
	 * Eylem adı.
	 */
	const ACTION = 'optimisthub_moka_migrate';

	/**
	 * Yönetici bildiriminin kapatıldığını tutan seçenek.
	 */
	const DISMISS_OPTION = 'optimisthub_moka_migration_dismissed';

	/**
	 * Kancaları bağlar.
	 */
	public function __construct() {
		add_action( 'admin_notices', array( $this, 'notice' ) );
		add_action( 'admin_post_' . self::ACTION, array( $this, 'handle' ) );
		add_action( 'admin_init', array( $this, 'maybeDismiss' ) );
	}

	/**
	 * Yeni eklenti zaten kurulu mu.
	 *
	 * @return bool
	 */
	public static function isTargetInstalled() {
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$plugins = get_plugins();

		return isset( $plugins[ self::TARGET_FILE ] );
	}

	/**
	 * Yeni eklenti etkin mi.
	 *
	 * @return bool
	 */
	public static function isTargetActive() {
		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		return is_plugin_active( self::TARGET_FILE );
	}

	/**
	 * Yönetici bildirimini basar.
	 *
	 * @return void
	 */
	public function notice() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( get_option( self::DISMISS_OPTION ) ) {
			return;
		}

		// Yeni eklenti zaten etkinse bildirime gerek yok.
		if ( self::isTargetActive() ) {
			return;
		}

		$installed = self::isTargetInstalled();
		$url       = wp_nonce_url(
			admin_url( 'admin-post.php?action=' . self::ACTION ),
			self::ACTION
		);

		printf( '<div class="notice notice-info is-dismissible">' );

		printf( '<h3 style="margin-bottom:.5em">%s</h3>', esc_html__( 'Moka United eklentisi yenilendi', 'moka-woocommerce' ) );

		if ( $installed ) {
			printf(
				'<p>%s</p>',
				esc_html__( 'Yeni sürüm kurulu ancak devre dışı. Ayarlarınız ve işlem geçmişiniz korunacak şekilde etkinleştirebilirsiniz.', 'moka-woocommerce' )
			);
		} else {
			printf(
				'<p>%s</p>',
				esc_html__( 'Yeni sürüm artık WordPress.org üzerinden güncelleniyor. Ayarlarınız ve işlem geçmişiniz korunacak.', 'moka-woocommerce' )
			);
		}

		printf(
			'<p><a href="%s" class="button button-primary">%s</a> <a href="%s" style="margin-left:.5em">%s</a></p>',
			esc_url( $url ),
			$installed
				? esc_html__( 'Yeni sürüme geç', 'moka-woocommerce' )
				: esc_html__( 'Yeni sürümü kur ve geç', 'moka-woocommerce' ),
			esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=' . self::ACTION . '&dismiss=1' ), self::ACTION ) ),
			esc_html__( 'Şimdi değil', 'moka-woocommerce' )
		);

		printf( '</div>' );
	}

	/**
	 * "Şimdi değil" seçildiyse bildirimi susturur.
	 *
	 * @return void
	 */
	public function maybeDismiss() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Yalnizca kapatma bayragi okunur, islem yapilmaz.
		if ( ! isset( $_GET['dismiss'] ) || '1' !== $_GET['dismiss'] ) {
			return;
		}
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		check_admin_referer( self::ACTION );

		update_option( self::DISMISS_OPTION, 1 );

		wp_safe_redirect( admin_url( 'plugins.php' ) );
		exit;
	}

	/**
	 * Geçişi çalıştırır.
	 *
	 * Sıra **önemlidir**: önce yeni eklenti kurulur/etkinleştirilir, sonra
	 * eski devre dışı bırakılır. Tersi olsaydı, kurulum başarısız olduğunda
	 * mağaza **ödeme alamaz** durumda kalırdı.
	 *
	 * @return void
	 */
	public function handle() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Bu işlem için yetkiniz yok.', 'moka-woocommerce' ) );
		}

		check_admin_referer( self::ACTION );

		// Kapatma isteği.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce yukarida dogrulandi.
		if ( isset( $_GET['dismiss'] ) ) {
			update_option( self::DISMISS_OPTION, 1 );

			wp_safe_redirect( admin_url( 'plugins.php' ) );
			exit;
		}

		$result = self::migrate();

		$redirect = add_query_arg(
			array(
				'optimisthub_moka_migrated' => $result['success'] ? '1' : '0',
				'optimisthub_moka_message'  => rawurlencode( $result['message'] ),
			),
			admin_url( 'plugins.php' )
		);

		wp_safe_redirect( $redirect );
		exit;
	}

	/**
	 * Geçişi uygular.
	 *
	 * Test edilebilirlik için ayrı ve saf bir adımdır: yönlendirme veya
	 * çıktı üretmez, yalnız sonucu döndürür.
	 *
	 * @return array<string, mixed> `success` ve `message`.
	 */
	public static function migrate() {
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		if ( ! function_exists( 'activate_plugin' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		// 1) Gerekirse yeni eklentiyi kur.
		if ( ! self::isTargetInstalled() ) {
			if ( ! function_exists( 'plugins_api' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
			}

			if ( ! class_exists( 'Plugin_Upgrader' ) ) {
				require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
			}

			$upgrader = new Plugin_Upgrader( new Automatic_Upgrader_Skin() );
			$installed = $upgrader->install( 'https://downloads.wordpress.org/plugin/' . self::TARGET_SLUG . '.zip' );

			if ( is_wp_error( $installed ) ) {
				return array(
					'success' => false,
					'message' => sprintf(
						/* translators: %s: hata mesaji */
						__( 'Yeni eklenti kurulamadı: %s', 'moka-woocommerce' ),
						$installed->get_error_message()
					),
				);
			}

			if ( true !== $installed ) {
				return array(
					'success' => false,
					'message' => __( 'Yeni eklenti kurulamadı. Lütfen WordPress.org üzerinden elle kurun.', 'moka-woocommerce' ),
				);
			}
		}

		// 2) Yeni eklentiyi etkinleştir.
		if ( ! self::isTargetActive() ) {
			$activated = activate_plugin( self::TARGET_FILE );

			if ( is_wp_error( $activated ) ) {
				return array(
					'success' => false,
					'message' => sprintf(
						/* translators: %s: hata mesaji */
						__( 'Yeni eklenti etkinleştirilemedi: %s', 'moka-woocommerce' ),
						$activated->get_error_message()
					),
				);
			}
		}

		// 3) Eski eklentiyi devre dışı bırak (yeni eklenti çalıştıktan SONRA).
		deactivate_plugins( plugin_basename( OPTIMISTHUB_MOKA_FILE ) );

		update_option( self::DISMISS_OPTION, 1 );

		return array(
			'success' => true,
			'message' => __( 'Geçiş tamamlandı. Ayarlarınız ve işlem geçmişiniz korundu.', 'moka-woocommerce' ),
		);
	}
}
