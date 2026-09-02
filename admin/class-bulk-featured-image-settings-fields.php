<?php

/**
 * Exit if accessed directly.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Check if BFIE_Admin_Fields class exists.
 */
if( !class_exists('BFIE_Admin_Fields')) {

	/**
	 * Admin fields functionality for the Bulk Featured Image plugin.
	 *
	 * Handles general settings, post type settings, featured image
	 * uploads, default thumbnails, uninstall settings, and AJAX actions.
	 *
	 * @since 1.0.0
	 */
	class BFIE_Admin_Fields extends BFIE_Admin {
        
    	/**
		 * Initialize the class and register hooks.
		 *
		 * @since 1.0.0
		 */
        public function __construct() {

            add_action( 'bfie_section_content_general', array( $this, 'general_settings' ) );
            add_action( 'bfie_section_content_post_types', array( $this, 'post_types_settings' ) );
            add_action( 'bfie_sub_section_content', array( $this, 'sub_section_content' ) );
            add_action( 'bfie_save_section_post_types', array( $this, 'save_post_types' ) );
            add_action( 'bfie_sub_section_before_content', array($this,'add_default_post_type_thumb'));
            add_action( 'bfie_section_content_uninstall', array( $this, 'uninstall_settings') );
	        add_action( 'wp_ajax_remove_featured_image', array( $this, 'remove_featured_image') );
	        add_filter( 'manage_posts_columns',array( $this, 'set_custom_edit_book_columns') );
	        add_action( 'manage_posts_custom_column' , array( $this, 'custom_featured_image_column' ),10,2);
	        add_action( 'wp_ajax_add_featured_image' , array( $this, 'add_featured_image' ));
        }

        /**
		 * Display general settings.
		 *
		 * Displays settings for posts per page, included post types,
		 * and default thumbnail configuration.
		 *
		 * @since 1.0.0
		 *
		 * @return void
		 */
        public function general_settings() {
            $bfi_get_settings = bfi_get_settings( 'general' );
            $post_types       = bfi_post_type_lists();
            $included_post_types = ! empty( $bfi_get_settings['bfi_posttyps'] ) && is_array( $bfi_get_settings['bfi_posttyps'] ) ? $bfi_get_settings['bfi_posttyps'] : array();
            $enable_default_image = ! empty( $bfi_get_settings['enable_default_image'] ) && is_array( $bfi_get_settings['enable_default_image'] ) ? $bfi_get_settings['enable_default_image'] : array();

            ?>
            <div class="bfi-card">
                <div class="bfi-field-row">
                    <div class="bfi-field-row__info">
                        <label for="bfi_per_page" class="bfi-field-row__label"><?php _e( 'Posts per page', 'bulk-featured-image' ); ?></label>
                        <p class="bfi-field-row__desc"><?php _e( 'Number of items shown before pagination kicks in.', 'bulk-featured-image' ); ?></p>
                    </div>
                    <div class="bfi-field-row__control">
                        <div class="bfi-number-input">
                            <input type="number" min="10" max="100" name="bfi_per_page" id="bfi_per_page" class="bfi-number-input__field" value="<?php echo esc_attr( bfi_get_per_page() ); ?>">
                        </div>
                    </div>
                </div>

                <div class="bfi-field-row">
                    <div class="bfi-field-row__info">
                        <label for="bfi_posttyps" class="bfi-field-row__label"><?php _e( 'Included post types', 'bulk-featured-image' ); ?></label>
                        <p class="bfi-field-row__desc"><?php _e( 'Content types that appear in BFIE lists and searches.', 'bulk-featured-image' ); ?></p>
                    </div>
                    <div class="bfi-field-row__control bfi-field-row__control--full">
                        <select name="bfi_posttyps[]" id="bfi_posttyps" multiple class="bfie-select2 bfi-select">
                            <?php
                            if ( ! empty( $post_types ) && is_array( $post_types ) ) {
                                foreach ( $post_types as $post_type ) {
                                    $selected = in_array( sanitize_text_field( $post_type ), $included_post_types, true ) ? 'selected' : '';
                                    ?>
                                    <option <?php echo $selected; ?> value="<?php echo esc_attr( $post_type ); ?>"><?php echo esc_html( ucfirst( $post_type ) ); ?></option>
                                    <?php
                                }
                            }
                            ?>
                        </select>
                    </div>
                </div>

                <div class="bfi-field-row bfi-field-row--align-top">
                    <div class="bfi-field-row__info">
                        <label class="bfi-field-row__label"><?php _e( 'Default thumbnail', 'bulk-featured-image' ); ?></label>
                        <p class="bfi-field-row__desc"><?php _e( 'Show a placeholder image for items with no featured image.', 'bulk-featured-image' ); ?></p>
                    </div>
                    <div class="bfi-field-row__control">
                        <div class="bfi-toggle-group">
                            <?php
                            if ( ! empty( $included_post_types ) ) {
                                foreach ( $included_post_types as $e_post_type ) {
                                    $id      = 'enable_default_image_' . esc_attr( $e_post_type );
                                    $checked = in_array( esc_attr( $e_post_type ), $enable_default_image, true ) ? 'checked' : '';
                                    ?>
                                    <div class="bfi-toggle-item">
                                        <div class="bfi-toggle-item__meta">
                                            <span class="bfi-toggle-item__title"><?php echo esc_html( ucfirst( $e_post_type ) ); ?></span>
                                            <span class="bfi-toggle-item__sub"><?php printf( esc_html__( 'Applies to %s', 'bulk-featured-image' ), esc_html( $e_post_type ) ); ?></span>
                                        </div>
                                        <label class="bfi-switch" for="<?php echo esc_attr( $id ); ?>">
                                            <input type="checkbox" <?php echo $checked; ?> id="<?php echo esc_attr( $id ); ?>" class="bfi-switch__input enable-default-image" name="enable_default_image[]" value="<?php echo esc_attr( $e_post_type ); ?>">
                                            <span class="bfi-switch__slider"></span>
                                        </label>
                                    </div>
                                    <?php
                                }
                            } else {
                                echo '<p>' . esc_html__( 'No post types selected in "Included post types".', 'bulk-featured-image' ) . '</p>';
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php
        }

        /**
		 * Display post type settings.
		 *
		 * Loads the selected post type subsection and its related content.
		 *
		 * @since 1.0.0
		 *
		 * @return void
		 */
        public function post_types_settings() {
            $bfi_get_settings = bfi_get_settings( 'general');
            $menu_items = !empty( $bfi_get_settings['bfi_posttyps'] ) ? $bfi_get_settings['bfi_posttyps'] : '';
            $sub_section = !empty( $_REQUEST['section'] ) ? sanitize_text_field( $_REQUEST['section'] ) : '';
			
			if( empty($sub_section)) {
				$sub_section = !empty($menu_items[0]) ? $menu_items[0] : '';
			}

            do_action('bfie_sub_section_before_content', $sub_section );
            do_action('bfie_sub_section_content', $sub_section );
            do_action('bfie_sub_section_after_content', $sub_section );
        }

        /**
		 * Display content for the selected post type subsection.
		 *
		 * @since 1.0.0
		 *
		 * @param string $section Current post type section.
		 * @return void
		 */
        public function sub_section_content( $section ) {

            if( !empty($section) ) {
                $args = array(
                    'posttype' => sanitize_text_field($section),
                );

                $BFI_List_Table = new BFI_List_Table( $args );
                $BFI_List_Table->prepare_items();
                $BFI_List_Table->display();
            } else { ?>
                <div class="no-settings">
                    <p><?php echo sprintf( __( 'No any posttypes selected. %sSettings%s', 'bulk-featured-image' ), '<a href="'.admin_url( 'admin.php?page='.$this->menu_slug).'">', '</a>' ); ?></p>
                </div>
            <?php }
            
        }

        /**
		 * Save post type settings.
		 *
		 * Handles featured image uploads and saves post type-specific settings.
		 *
		 * @since 1.0.0
		 *
		 * @return void
		 */
        public function save_post_types() {
            $current_section = !empty( $_POST['current_section'] ) ? sanitize_text_field( $_POST['current_section'] ) : 'general';
            $current_sub_section = !empty( $_POST['current_sub_section'] ) ? sanitize_text_field( $_POST['current_sub_section'] ) : '';
            $settings = bfi_sanitize_text_field($_POST);
	
            unset($settings['save']);
            unset($settings['action']);
            unset($settings['_nonce']);
            unset($settings['_wpnonce']);
            unset($settings['_wp_http_referer']);
            unset($settings['paged']);
            unset($settings['current_page']);
            unset($settings['current_section']);
            unset($settings['current_sub_section']);

            $setting_key = 'bfi_settings';
            $bfi_settings = get_option( $setting_key, true );
            $message_updated = false;

            if( isset( $_FILES['bfi_upload_file'] ) && !empty( $_FILES['bfi_upload_file'] ) && is_array($_FILES['bfi_upload_file'])) {
                $image_url = !empty( $_FILES['bfi_upload_file']['tmp_name'] ) ? sanitize_text_field( $_FILES['bfi_upload_file']['tmp_name'] ) : '';
				$image_name = !empty( $_FILES['bfi_upload_file']['name'] ) ? sanitize_text_field( $_FILES['bfi_upload_file']['name'] ) : '';
                if( !empty($image_url) && !empty($image_name)) {
                    $attach_id = $this->process_attachment( $image_url, $image_name);
                    if( !empty($attach_id) && $attach_id > 0 ) {
                        $settings['bfi_upload_file'] = (int)sanitize_text_field($attach_id);
                        $message_updated = true;
                    }
                }
            }

            if( !empty($_POST['bfi_upload_post_id']) && is_array($_POST['bfi_upload_post_id'])) {
                foreach( $_POST['bfi_upload_post_id'] as $key => $upload_post_id ) {
                    if( !empty($upload_post_id) && $upload_post_id > 0 ) {
                        $upload_files = !empty( $_FILES['bfi_upload_file_'.$upload_post_id] ) ? $_FILES['bfi_upload_file_'.$upload_post_id] : '';
                        if( !empty($upload_files) && is_array($upload_files)) {
                            $image_url = !empty( $upload_files['tmp_name'] ) ? sanitize_text_field( $upload_files['tmp_name'] ) : '';
                            $image_name = !empty( $upload_files['name'] ) ? sanitize_text_field( $upload_files['name'] ) : '';
                            if( !empty($image_url) && !empty($image_name)) {
                                $attach_id = $this->process_attachment( $image_url, $image_name);
                                if( !empty($attach_id) && $attach_id > 0) {
                                    set_post_thumbnail( $upload_post_id, (int)$attach_id );
                                }
                            }
                        }
                    }
                }
            }

            if( !empty($bfi_settings) && is_array($bfi_settings) ) {
                if( !empty($current_sub_section) ) {
                    $bfi_settings[$current_section][$current_sub_section] = $settings;
                }
            } else{
                if( !empty($current_sub_section) ) {
                    $bfi_settings = array(
                        $current_section =>  array(
                            $current_sub_section => $settings
                        ),
                    );
                } else {
                    $bfi_settings = array(
                        $current_section =>  $settings,
                    );
                }
            }

            update_option( $setting_key, $bfi_settings );

            if ( $message_updated ) {
                self::add_message( sprintf( __( 'Your <strong>%s</strong> featured image updated successfully.', 'bulk-featured-image' ), ucwords( $current_sub_section ) ) );
            } else {
                self::add_message( __( 'Your settings have been saved successfully.', 'bulk-featured-image' ) );
            }
        }

        /**
		 * Display the default thumbnail for the selected post type.
		 *
		 * @since 1.0.0
		 *
		 * @param string $section Current post type section.
		 * @return void
		 */
        public function add_default_post_type_thumb( $section ) {
            if ( empty( $section ) ) {
                return;
            }

            $bfi_get_settings     = bfi_get_settings( 'general' );
            $enable_default_image = !empty( $bfi_get_settings['enable_default_image'] ) ? $bfi_get_settings['enable_default_image'] : '';

            if ( !empty( $enable_default_image ) && is_array( $enable_default_image ) && in_array( $section, $enable_default_image ) ) {
                $get_pt_settings    = bfi_get_settings( 'post_types' );
                $get_sub_pt_setting = !empty( $get_pt_settings[ $section ] ) ? $get_pt_settings[ $section ] : '';
                $bfi_upload_file    = !empty( $get_sub_pt_setting['bfi_upload_file'] ) ? sanitize_text_field( $get_sub_pt_setting['bfi_upload_file'] ) : '';

                ob_start();
                ?>
                <div class="bfi-card bfi-default-thumb-card">
                    <div class="bfi-default-thumb-card__header">
                        <h3 class="bfi-default-thumb-card__title"><?php _e( 'Default thumbnail', 'bulk-featured-image' ); ?></h3>
                        <p class="bfi-default-thumb-card__desc">
                            <?php printf( esc_html__( 'Used when a %s has no featured image of its own.', 'bulk-featured-image' ), esc_html( rtrim( $section, 's' ) ) ); ?>
                        </p>
                    </div>

                    <div class="bfi-default-thumb-card__body">
                        <div class="bfi-large-dropzone">
                            <div class="bfi-large-dropzone__icon">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="1.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                            </div>
                            <span class="bfi-large-dropzone__text"><?php _e( 'Drag and drop an image here', 'bulk-featured-image' ); ?></span>
                            <span class="bfi-large-dropzone__sub"><?php _e( 'OR', 'bulk-featured-image' ); ?></span>
                            
                            <input type="file" onChange="bfi_drag_drop(event)" name="bfi_upload_file" ondragover="bfi_drag()" ondrop="bfi_drop()" id="bfi_upload_file" accept=".png,.jpg,.jpeg" class="bfi-large-dropzone__file" />
                            
                            <label for="bfi_upload_file" class="button button-primary">
                                <?php _e( 'Upload image', 'bulk-featured-image' ); ?>
                            </label>
                        </div>

                        <div id="bfi_upload_preview" class="bfi-large-preview">
                            <?php if ( !empty( $bfi_upload_file ) && $bfi_upload_file > 0 ) { ?>
                                <div class="bfi-large-preview__wrapper">
                                    <img src="<?php echo esc_url( wp_get_attachment_url( $bfi_upload_file ) ); ?>" alt="Preview Image" />
                                    <input type="hidden" name="bfi_upload_file" value="<?php echo esc_attr( $bfi_upload_file ); ?>">
                                </div>
                            <?php } ?>
                        </div>
                    </div>

                    <div class="bfi-default-thumb-card__footer">
                        <p><em><?php printf( esc_html__( 'This default only appears while "Enable default thumbnail" is turned on for %s in General settings.', 'bulk-featured-image' ), esc_html( ucfirst( $section ) ) ); ?></em></p>
                    </div>
                </div>
                <?php
                $html = ob_get_contents();
                ob_get_clean();

                echo $html;
            }
        }

        /**
		 * Process an uploaded image attachment.
		 *
		 * Saves the uploaded image to the WordPress uploads directory
		 * and creates the corresponding media attachment.
		 *
		 * @since 1.0.0
		 *
		 * @param string $file_url      Temporary file path.
		 * @param string $file_tmp_name Original file name.
		 * @return int Attachment ID.
		 */
        public function process_attachment( $file_url, $file_tmp_name ) {

            $upload_dir = wp_upload_dir();
            $image_data = file_get_contents($file_url);
            $unique_file_name = wp_unique_filename($upload_dir['path'], $file_tmp_name);
            $filename = sanitize_file_name( basename( $unique_file_name ) );

            if (wp_mkdir_p($upload_dir['path'])) {
                $file = $upload_dir['path'] . '/' . $filename;
            } else {
                $file = $upload_dir['basedir'] . '/' . $filename;
            }

            file_put_contents($file, $image_data);
            $wp_filetype = wp_check_filetype($filename, null);

            $attachment = array(
                'post_mime_type' => $wp_filetype['type'],
                'post_title' => $filename,
                'post_content' => '',
                'post_status' => 'inherit'
            );

            $attach_id = wp_insert_attachment($attachment, $file);
            $attach_data = wp_generate_attachment_metadata($attach_id, $file);
            wp_update_attachment_metadata($attach_id, $attach_data);

            return $attach_id;
        }

        /**
		 * Display uninstall settings.
		 *
		 * Allows the administrator to enable or disable removal
		 * of plugin data when the plugin is uninstalled.
		 *
		 * @since 1.0.0
		 *
		 * @return void
		 */
        public function uninstall_settings() {
            $uninstall_settings = bfi_get_settings( 'uninstall' );
            $bfi_uninstall = ! empty( $uninstall_settings['bfi_uninstall'] ) ? sanitize_text_field( $uninstall_settings['bfi_uninstall'] ) : '';
            $is_uninstall_enabled = ( '1' === $bfi_uninstall );

        ?>
            <section class="bfi-uninstall">
                <div class="bfi-uninstall__header">
                    <h2 class="bfi-uninstall__heading"> <?php esc_html_e( 'Data retention', 'bulk-featured-image' ); ?> </h2>
                    <p class="bfi-uninstall__description"> <?php esc_html_e( 'This only takes effect when the plugin is deleted from Plugins → Installed Plugins — deactivating it alone won’t trigger this.', 'bulk-featured-image'); ?> </p>
                </div>
                <div class="bfi-uninstall__card">
                    <div class="bfi-uninstall__control">
                        <input type="checkbox" name="bfi_uninstall" id="bfi_uninstall" value="1" <?php checked( $is_uninstall_enabled, true ); ?> />
                        <label for="bfi_uninstall" class="bfi-uninstall__checkbox" aria-label="<?php esc_attr_e( 'Remove all data on uninstall', 'bulk-featured-image' ); ?>" ></label>
                    </div>
                    <div class="bfi-uninstall__content">
                        <div class="bfi-uninstall__title-row">
                            <label for="bfi_uninstall" class="bfi-uninstall__title" > <?php esc_html_e( 'Remove all data on uninstall', 'bulk-featured-image' ); ?> </label>
                            <span class="bfi-uninstall__badge<?php echo $is_uninstall_enabled ? '' : ' bfi-uninstall__badge--hidden'; ?>" aria-hidden="<?php echo $is_uninstall_enabled ? 'false' : 'true'; ?>" >
                                <svg class="bfi-uninstall__badge-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" >
                                    <path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                                    <line x1="12" y1="9" x2="12" y2="13"></line>
                                    <line x1="12" y1="17" x2="12.01" y2="17"></line>
                                </svg>
                                <?php esc_html_e( 'IRREVERSIBLE', 'bulk-featured-image' ); ?>
                            </span>
                        </div>
                        <p class="bfi-uninstall__text"> <?php esc_html_e( 'Deletes all BFIE settings, saved import logs, and default thumbnails when the plugin is deleted through WordPress. Leave unchecked to keep your data in case you reinstall later.', 'bulk-featured-image' ); ?>
                        </p>
                    </div>
                </div>
                <script>
                    document.addEventListener('DOMContentLoaded', function () {
                        const checkbox = document.getElementById('bfi_uninstall');
                        const badge = document.querySelector('.bfi-uninstall__badge');
                        checkbox.addEventListener('change', function () {
                            badge.classList.toggle('bfi-uninstall__badge--hidden', !this.checked);
                        });
                    });
                </script>
            </section>
            <?php
        }

        /**
		 * Remove the featured image from a post.
		 *
		 * Handles the AJAX request for removing a post's featured image.
		 *
		 * @since 1.0.0
		 *
		 * @return void
		 */
		public function remove_featured_image() {

			$status = false;
            $message = '';
			$html = '';
			$post_id = ! empty( $_POST['data_id'] ) ? sanitize_text_field( $_POST['data_id'] ) : '';
			$current_page = ! empty( $_POST['current_page'] ) ? sanitize_text_field( $_POST['current_page'] ) : '';
            if( !empty( $post_id ) && $post_id > 0 ) {
	            $delete_status =  delete_post_thumbnail($post_id);
	            if( true == $delete_status ){
		            $status = true;
		            $BFI_List_Table = new BFI_List_Table();
                    if( !empty( $current_page ) && 'bulk-featured-image' === $current_page ) {
	                    $html = $BFI_List_Table->get_thumbnail_html( $post_id );
                    } else {
	                    $html = $this->get_post_featured_html( $post_id );
                    }
		            $message = __('Thumbnail delete successfully!!!');
	            }
            }
            $response = array(
                    'status' => $status,
                    'message' => $message,
                    'html' => $html,
            );
			wp_send_json( $response );
		}

        /**
		 * Remove the featured image from a post.
		 *
		 * Handles the AJAX request for removing a post's featured image.
		 *
		 * @since 1.0.0
		 *
		 * @return void
		 */
		public function set_custom_edit_book_columns($columns) {
			$columns['featured_image'] = __( 'Featured Image', 'bulk-featured-image' );
			return $columns;
		}

        /**
		 * Get featured image HTML for a post.
		 *
		 * @since 1.0.0
		 *
		 * @param int $post_id Post ID.
		 * @return string Featured image HTML.
		 */
        public function get_post_featured_html( $post_id ) {

            ob_start();

            $BFI_List_Table = new BFI_List_Table();
            ?>
            <div class="post-bfi">
                <?php
                    echo $BFI_List_Table->get_thumbnail_html( $post_id );
                ?>
            </div>

            <?php if ( has_post_thumbnail( $post_id ) ) { ?>
                <div class="post-bfi-option">
                    <a class="bfi-img-uploader" data-id="<?php echo esc_attr( $post_id ); ?>">
                        <?php esc_html_e( 'Update Featured Image', 'bulk-featured-image' ); ?>
                    </a>
                </div>
            <?php } else { ?>
                <div class="post-bfi-option">
                    <a class="bfi-img-uploader" data-id="<?php echo esc_attr( $post_id ); ?>">
                        <?php esc_html_e( 'Add Featured Image', 'bulk-featured-image' ); ?>
                    </a>
                </div>
            <?php }

            $featured_html = ob_get_clean();

            return $featured_html;
        }

        /**
		 * Display the custom featured image column.
		 *
		 * @since 1.0.0
		 *
		 * @param string $column  Current column name.
		 * @param int    $post_id Current post ID.
		 * @return void
		 */
		public function  custom_featured_image_column( $column, $post_id ) {
			switch ( $column ) {
				case 'featured_image' :
                    echo $this->get_post_featured_html($post_id);
					break;
                default : ;
			}
		}

        /**
		 * Add a featured image to a post.
		 *
		 * Handles the AJAX request for assigning an attachment
		 * as the featured image of a post.
		 *
		 * @since 1.0.0
		 *
		 * @return void
		 */
		public function add_featured_image() {
			$status = false;
			$message = '';
			$html = '';
			$post_id = ! empty( $_POST['data_id'] ) ? sanitize_text_field( $_POST['data_id'] ) : '';
			$attach_id_array = ! empty( $_POST['attach_id'] ) ?  $_POST['attach_id'] : array();
			$attach_id = $attach_id_array['id'];
			$thumbnail_status = set_post_thumbnail($post_id, (int)$attach_id);
            if(! empty($thumbnail_status)){
	            $status = true;
	            $html = $this->get_post_featured_html($post_id);
            }
			$response = array(
				'status' => $status,
				'message' => $message,
				'html' => $html,
			);
			wp_send_json( $response );

		}
    }

    new BFIE_Admin_Fields();
}