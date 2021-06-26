<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Check BFIE_Admin class_exists or not.
 */
if( !class_exists( 'BFIE_Admin' ) ) {

	/**
	 * The admin-specific functionality of the plugin.
	 * 
	 * @since 1.0.0
	 */
	class BFIE_Admin {

		/**
		 * Menu slug.
		 */
		public $menu_slug = BFIE_MENU_SLUG;
		
		/**
		 * settings key.
		 */
		public $settings_key = 'bfie_setting_fields';

		/**
		 * The errors of this plugin..
		 */
		private static $errors   = array();

		/**
		 * The messages of this plugin..
		 */
		private static $messages   = array();

		/**
		 * Initialize the class and set its properties.
		 */
		public function __construct() {

			add_action( 'admin_menu', array( $this, 'register_admin_menu' ) );
			add_action( 'admin_init', array( $this, 'save' ) );
		}

		/**
		 * Add messages for this plugin.
		 */
		public static function add_message( $text ) {

			self::$messages[] = $text;
		}

		/**
		 * Add errors for this plugin.
		 */
		public static function add_error( $text ) {
			self::$errors[] = $text;
		}

		/**
		 * Register admin menu.
		 */
		public function register_admin_menu() {

			add_menu_page(
				__( 'Bulk Featured Image', 'bulk-featured-image' ),
				__( 'BFIE','bulk-featured-image' ),
				'manage_options',
				$this->menu_slug,
				array($this,'process_admin_menu'),
				'dashicons-hourglass'
			);
		}

		/**
		 * Get current page.
		 */
		public function current_page() {

			return !empty( $_REQUEST['page'] ) ? sanitize_text_field( $_REQUEST['page'] ) : '';
		}

		/**
		 * Get curent section.
		 */
		public function current_section() {
			
			$current_section = $this->current_page();

			$current_menu = '';
			if( !empty($current_section) && $current_section == $this->menu_slug ) {
				$current_menu = !empty( $_REQUEST['tab'] ) ? sanitize_text_field( $_REQUEST['tab'] ) : '';
			}

			return $current_menu;
		}

		/**
		 * Get curent sub section.
		 */
		public function current_sub_section() {
			
			$bfi_get_settings = bfi_get_settings( 'general');
            $menu_items = !empty( $bfi_get_settings['bfi_posttyps'] ) ? $bfi_get_settings['bfi_posttyps'] : '';
        
            $sub_section = !empty( $_REQUEST['section'] ) ? sanitize_text_field( $_REQUEST['section'] ) : '';
			
			if( empty($sub_section)) {
				$sub_section = !empty( $menu_items[0] ) ? $menu_items[0] : '';
			}

			return $sub_section;
		}

		/**
		 * Get menu items.
		 */
		public function menu_items() {

			return apply_filters( 'bfie_menu_items', array(
				'' => __( 'General', 'bulk-featured-image' ),
				'post_types' => __( 'PostTypes', 'bulk-featured-image' ),
				'uninstall' => __( 'Uninstall', 'bulk-featured-image' ),
			) );
		}

		/**
		 * Display menu.
		 */
		public function menu() {

			$menu_items = $this->menu_items();
			
			if(empty($menu_items) || !is_array($menu_items)) {
				return;
			}

			$current_section = $this->current_section();

			$menu_link = admin_url( 'admin.php?page='.$this->menu_slug);

			?>
			<nav class="nav-tab-wrapper">

				<?php foreach ($menu_items as $key => $menu ) {

					if(!empty($key)) {
						$menu_link .= '&tab='.$key;
					}

					$active = '';
					if( !empty( $current_section ) && $key == $current_section) {
						$active = 'nav-tab-active';
					} elseif ( empty( $current_section ) && strtolower($menu) == 'general' ) {
						$active = 'nav-tab-active';
					}
					?>
					<a href="<?php echo $menu_link; ?>" class="nav-tab <?php echo $active; ?>"><?php echo $menu; ?></a>
					<?php
				} ?>	
			</nav>
			<?php

		}

		/**
		 * Get sub menu items.
		 */
		public function sub_menu_items() {

			$submenu_items = array(
				'general' => array(),
			);

			$bfi_get_settings = bfi_get_settings( 'general');
			$submenu_items['post_types'] = !empty($bfi_get_settings['bfi_posttyps']) ? $bfi_get_settings['bfi_posttyps'] : '';

			return apply_filters( 'bfie_submenu_items', $submenu_items );
		}

		/**
		 * Display sub menu items.
		 */
		public function sub_menu() {

			$sub_menu_items = $this->sub_menu_items();
			$current_section = $this->current_section();
			$section = !empty( $current_section ) ? $current_section : 'general';

			$menu_link = admin_url( 'admin.php?page='.$this->menu_slug);
			if( !empty($current_section)) {
				$menu_link .= '&tab='.$current_section;
			}

			$menu_items = !empty( $sub_menu_items[$section] ) ? $sub_menu_items[$section] : '';

			$sub_section = !empty( $_REQUEST['section'] ) ? sanitize_text_field( $_REQUEST['section'] ) : '';
			
			if( empty($sub_section)) {
				$sub_section = !empty( $menu_items[0] ) ? $menu_items[0] : '';
			}

			$last_item = ( $menu_items ) ? end($menu_items) : '';

			if( !empty($menu_items) && is_array($menu_items)) {
				?>
				<div class="bfi-submenu-wrap">
					<ul class="subsubsub">
						<?php foreach( $menu_items as $menu_item ) { 

							$current = '';
							if( !empty($menu_item) && $menu_item == $sub_section ) {
								$current = 'current';
							}

							$sep = ' | ';
							if( $menu_item == $last_item ) {
								$sep = '';
							}

							?>
							<li><a href="<?php echo $menu_link.'&section='.$menu_item; ?>" class="<?php echo $current; ?>"><?php echo ucfirst($menu_item); ?></a><?php echo $sep; ?></li>
						<?php } ?>
					</ul>
				</div>
				<?php
			}

		}

		/**
		 * Display heading title.
		 */
		public function heading_title() {

			$menu_items = $this->menu_items();
			$current_section = $this->current_section();

			$heading_title = !empty( $menu_items[$current_section] ) ? sanitize_text_field( $menu_items[$current_section] ).' '.__('Settings','bulk-featured-image') : '';
			?>
			<h1 class="wp-heading-inline"><?php echo apply_filters( 'bfie_heading_title',$heading_title,$current_section); ?></h1>
			<?php

		}

		/**
		 * Display notification.
		 */
		public function notification() {
			?>
			<div class="bfi-notification-wrap">
                <?php
                if ( sizeof( self::$errors ) > 0 ) {
	                foreach ( self::$errors as $error ) {
		                echo '<div id="message" class="error inline notice is-dismissible"><p>' . $error . '</p></div>';
	                }
                } elseif ( sizeof( self::$messages ) > 0 ) {
	                foreach ( self::$messages as $message ) {
		                echo '<div id="message" class="updated inline notice is-dismissible"><p>' . $message . '</p></div>';
	                }
                }
                ?>
            </div>
			<?php
		}

		/**
		 * Process for admin BFI setting form.
		 */
		public function process_admin_menu() {

			$current_page = $this->current_page();
			$current_section = $this->current_section();
			$current_sub_section = $this->current_sub_section();

			?>
			<div class="wrap">
				<div class="bulk-featured-image-wrap">
					<?php 
					$this->menu();
					$this->sub_menu();
					$this->heading_title();
					$this->notification();

					?>
					<form method="post" id="bfie_form_main" action="" enctype="multipart/form-data">
						<div class="bfi-content-wrap">
							<?php
					
							do_action('bfie_section_before_content', $current_section );
					
							do_action('bfie_section_content', $current_section );

							$section = '_general';
							if(!empty($current_section)) {
								$section = '_'.strtolower($current_section);
							}

							do_action('bfie_section_content'.$section, $current_section );

							do_action('bfie_section_after_content', $current_section );
							?>
						</div>

						<p class="submit">
							<button name="save" class="button-primary" type="submit" value="Save changes"><?php _e('Save changes','bulk-featured-image'); ?></button>
							<input type="hidden" name="action" value="bfi_form_action">
							<input type="hidden" name="_nonce" value="<?php echo wp_create_nonce('bfie_form_main'); ?>">
							<input type="hidden" name="current_page" value="<?php echo esc_attr( $current_page ); ?>">
							<input type="hidden" name="current_section" value="<?php echo esc_attr( $current_section ); ?>">
							<input type="hidden" name="current_sub_section" value="<?php echo esc_attr( $current_sub_section ); ?>">
						</p>
					</form>
				</div>
			</div>		
			<?php
		}

		/**
		 * Redirect section url.
		 */
		public function redirect_url() {

			$current_section = !empty( $_POST['current_section'] ) ? sanitize_text_field( $_POST['current_section'] ) : '';

			$menu_link = admin_url( 'admin.php?page='.$this->menu_slug);

			if( !empty($current_section)) {
				$menu_link .='&tab='.$current_section;
			}

			return $menu_link;
		}

		/**
		 * Save settings.
		 */
		public function save() {

			if( isset( $_POST['action'] ) && !empty( sanitize_text_field( $_POST['action'] ) ) && sanitize_text_field( $_POST['action'] )  == 'bfi_form_action' ) {

				if( isset( $_POST['_nonce'] ) && !empty( $_POST['_nonce'] ) && wp_verify_nonce( sanitize_text_field( $_POST['_nonce'] ), 'bfie_form_main' ) ) {
					
					$current_section = !empty( $_POST['current_section'] ) ? sanitize_text_field( $_POST['current_section'] ) : 'general';

					if( has_action( 'bfie_save_section_'.$current_section ) ) {

						do_action('bfie_save_section_'.$current_section);
						return true;
					}

					$settings = bfi_sanitize_text_field($_POST);

					unset($settings['save']);
					unset($settings['action']);
					unset($settings['_nonce']);
					unset($settings['current_page']);
					unset($settings['current_section']);
					unset($settings['current_sub_section']);
		            
					$setting_key = 'bfi_settings';
					$bfi_settings = get_option( $setting_key, true );

					if( !empty( $bfi_settings ) && is_array( $bfi_settings ) ) {

			            $bfi_settings[$current_section] = $settings;
		            } else{
			            $bfi_settings = array(
				            $current_section =>  $settings,
                        );
                    }

		            update_option( $setting_key, $bfi_settings );

					self::add_message( __( 'Your settings have been saved.', 'bulk-featured-image' ) );
				} else {
					self::add_error( __( 'Something went wrong. settings nonce not verified.', 'bulk-featured-image' ) );
				}
			}
		}
	}

	new BFIE_Admin();
}
