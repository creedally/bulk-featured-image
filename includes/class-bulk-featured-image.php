<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Check BFIE class_exists or not.
 */
if ( ! class_exists( 'BFIE' ) ) {

	/**
	 * The core plugin class.
	 *
	 * This is used to define internationalization, admin-specific hooks, and
	 * public-facing site hooks.
	 *
	 * Also maintains the unique identifier of this plugin as well as the current
	 * version of the plugin.
	 *
	 * @since      1.0.0
	 */
	class BFIE {

		/**
		 * The instance of this class. 
		 */

		private static $instance;

		public static function get_instance() {
			if ( ! isset( self::$instance ) && ! ( self::$instance instanceof BFIE ) ) {
				self::$instance = new BFIE;
				self::$instance->includes();
				self::$instance->setup_actions();
			}

			return self::$instance;
		}

		/**
		 * Define the core functionality of the plugin.
		 */
		private function __construct() {
			self::$instance = $this;
		}

		private function includes() {
			
			/**
			 * The function responsible for all common function of this plugin.
			 */
			require_once BFIE_PATH . '/includes/functions.php';

			/**
			 * The class responsible for defining list tabele functionality
			 * of the plugin.
			 */
			require_once BFIE_PATH . '/admin/class-bfi-list-table.php';

			/**
			 * The class responsible for defining all actions that occur in the admin area.
			 */
			require_once BFIE_PATH . '/admin/class-bulk-featured-image-admin.php';

			/**
			 * The class responsible for defining all settings fileds occur in the admin area.
			 */
			require_once BFIE_PATH . '/admin/class-bulk-featured-image-settings-fields.php';
		}

		/**
		 * Setup actions.
		 */
		private function setup_actions() {
			
			$pluing_name = BFIE_PLUGIN_BASENAME;
			add_action( "plugin_action_links_{$pluing_name}", array($this ,'plugin_settings_link'));
			add_action( 'admin_init', array( $this, 'activation_redirect' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
			add_action( 'init', array( $this, 'load_textdomain' ) );
			add_action('has_post_thumbnail', array( $this, 'has_post_thumbnail'), 10, 2 );
			add_action('post_thumbnail_html', array( $this, 'post_thumbnail_html'), 10, 2 );
			add_filter('woocommerce_placeholder_img_src', array( $this, 'product_thumbnail_html' ), 10, 1);
			add_filter('post_thumbnail_id', array( $this, 'bfi_get_post_thumbnail_id' ),10, 1);
		}

		/**
		 * Settings for plugin link.
		 */
		public function plugin_settings_link( $links ) {

			$action_links = array(
				'settings' => '<a href="' . admin_url( 'admin.php?page='.BFIE_MENU_SLUG ) . '" aria-label="' . __( 'Settings', 'bulk-featured-image' ) . '">' . esc_html__( 'Settings', 'bulk-featured-image' ) . '</a>',
			);

			return array_merge( $action_links, $links );
		}

		/**
		 * Activation redirect.
		 */
		public function activation_redirect() {

			if ( !get_transient( '_bfie_activation_redirect' ) ) {
				return;
			}
	
			delete_transient( '_bfie_activation_redirect' );

			if ( is_network_admin() || isset( $_GET['activate-multi'] ) ) {
				return;
			}
	
			wp_safe_redirect( admin_url( 'admin.php?page='.BFIE_MENU_SLUG ) );
			exit;
		}

		/**
		 * Register the JavaScript and Style for the admin area.
		 *
		 * @since    1.0.0
		 */
		public function enqueue_admin_scripts() {
			
			if( !empty($_REQUEST['page']) && $_REQUEST['page'] == BFIE_MENU_SLUG ) {
				wp_enqueue_style('bootstrap-style',BFIE_PLUGIN_URL.'assets/css/bootstrap.min.css');
			}
			wp_enqueue_style('select2-style', BFIE_PLUGIN_URL.'assets/css/select2.min.css');
			wp_enqueue_style('bulk-featured-image',BFIE_PLUGIN_URL.'assets/css/bulk-featured-image-admin.css');

			wp_enqueue_script( 'select2-script', BFIE_PLUGIN_URL . 'assets/js/select2.min.js', array( 'jquery', ), '', true );
			wp_enqueue_script( 'bulk-featured-image', BFIE_PLUGIN_URL . 'assets/js/bulk-featured-image-admin.js', array( 'jquery', ), '', true );
			wp_enqueue_media();
			wp_localize_script(
				'bulk-featured-image',
				'bfie_object',
				array(
					'ajax_url' => admin_url( 'admin-ajax.php' ),
					'delete_post_message' => __('Are You sure you want to Remove this Image!','bulk-featured-image' ),
				)
			);
		}

		/**
		 * Register the JavaScript and Style for the public area.
		 *
		 * @since    1.0.0
		 */
		public function enqueue_scripts() {

		}

		/**
		 * Load textdomain.
		 *
		 * @since    1.0.0
		 */
		public function load_textdomain() {
			load_plugin_textdomain( 'bulk-featured-image', false, basename( BFIE_PATH ) . '/languages' );
		}

		/**
		 * This method is allow display default image in post.
		 *
		 * @param boolean $has_thumbnail
		 * @param object $post
		 * @return boolean $has_thumbnail
		 */
		public function has_post_thumbnail( $has_thumbnail, $post ) {

			if( empty( $post ) ) {
                global $post;
			}

			$post_type = get_post_type( $post );
			$get_pt_settings = bfi_get_settings('post_types');
			
			$bfi_upload_file = !empty( $get_pt_settings[$post_type]['bfi_upload_file']) ? $get_pt_settings[$post_type]['bfi_upload_file'] : 0;

			if( !empty($post_type) && !empty($bfi_upload_file)) {
				$has_thumbnail = (bool)$bfi_upload_file;
			}

			return $has_thumbnail;
		}

		/**
		 * This method is use to display default image in post.
		 *
		 * @param string $html
		 * @param int $post_id
		 * @return string $html
		 */
		public function post_thumbnail_html( $html, $post_id ) {

			global $post;

			$post_type = !empty($post->post_type) ? $post->post_type : '';
			$get_pt_settings = bfi_get_settings('post_types');
			$get_default_enable = !empty( bfi_get_settings('general')['enable_default_image'] ) ? bfi_get_settings('general')['enable_default_image'] : [];

			if( empty($html) && !empty($post_type) && !empty($get_pt_settings[$post_type]['bfi_upload_file']) && in_array($post_type, $get_default_enable) ) {
				$post_thumbnail_id = sanitize_text_field( $get_pt_settings[$post_type]['bfi_upload_file'] );
				$html = wp_get_attachment_image( $post_thumbnail_id, 'post-thumbnail' );
			}

			return $html;
		}

		/**
		 * This method is used to modify the placeholder image source for WooCommerce products.
		 *
		 * @param string $attachment_src Default placeholder image source.
		 * @return string $attachment_src Modified placeholder image source.
		 */
		public function product_thumbnail_html( $attachment_src ) {
			global $post;

			$post_type = get_post_type($post);

			// Check if the current post type is a product
			if( $post_type === 'product' ) {
				$get_pt_settings = bfi_get_settings('post_types');
				$get_default_enable = !empty( bfi_get_settings('general')['enable_default_image'] ) ? bfi_get_settings('general')['enable_default_image'] : [];

				// Check if default image is enabled for products
				if( !empty($get_pt_settings[$post_type]['bfi_upload_file']) && in_array($post_type, $get_default_enable) ) {
					$product_thumbnail_id = sanitize_text_field( $get_pt_settings[$post_type]['bfi_upload_file'] );
					$default_src = wp_get_attachment_image_url( $product_thumbnail_id, 'woocommerce_thumbnail' );

					// Use the custom image URL as the placeholder image source
					if ( $default_src ) {
						$attachment_src = $default_src;
					}
				}
			}

			return $attachment_src;
		}

		/**
		 * Filters and retrieves the post thumbnail ID.
		 *
		 * This method is used to check if a post has a thumbnail. If no thumbnail is set and the
		 * post type has a default image specified in the settings, it returns the default image ID.
		 * If a thumbnail is set or no default image is configured, it returns the current thumbnail ID.
		 *
		 * @param int|null $thumbnail_id The current post thumbnail ID. If no thumbnail is set, this will be null.
		 * 
		 * @return int|null The post thumbnail ID. Returns the default image ID if no thumbnail is set and
		 *                  a default image is configured for the post type. Otherwise, returns the
		 *                  original thumbnail ID or null if none is set.
		 */
		public function bfi_get_post_thumbnail_id($thumbnail_id) {
			global $post;
		
			if (empty($post) || !is_a($post, 'WP_Post')) {
				return $thumbnail_id;
			}

			$post_type = get_post_type($post);
		
			$get_pt_settings = bfi_get_settings('post_types');
		
			$get_default_enable = !empty(bfi_get_settings('general')['enable_default_image']) ? bfi_get_settings('general')['enable_default_image'] : [];
		
			if (empty($thumbnail_id) && !empty($post_type) && !empty($get_pt_settings[$post_type]['bfi_upload_file']) && in_array($post_type, $get_default_enable)) {
				$post_thumbnail_id = absint($get_pt_settings[$post_type]['bfi_upload_file']);
				return $post_thumbnail_id;
			}

			return $thumbnail_id;
		}
		
	}
}
