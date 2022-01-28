<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Check BFIE_Admin_Fields class exsits or not.
 */
if( !class_exists('BFIE_Admin_Fields')) {

	class BFIE_Admin_Fields extends BFIE_Admin {
        
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

        public function general_settings() {

            $bfi_get_settings = bfi_get_settings( 'general');
            $post_types = bfi_post_type_lists();

            ?>
            <table class="form-table">
                <tbody>
                    <tr>
                        <th><label for="bfi_per_page"><?php _e( 'Post Per Page', 'bulk-featured-image' ); ?></label></th>
                        <td><input type="number" min="10" max="100" name="bfi_per_page" id="bfi_per_page" class="regular-text" value="<?php echo bfi_get_per_page(); ?>"></td>
                    </tr>
                    <tr>
                        <th><label for="bfi_posttyps"><?php _e( 'Post Types', 'bulk-featured-image' ); ?></label></th>
                        <td>
                            <select name="bfi_posttyps[]" id="bfi_posttyps" multiple class="bfie-select2 regular-text">
                                <?php
                                
                                if( !empty($post_types) && is_array($post_types)) {

                                    foreach ( $post_types as $post_type ) {

                                        $selected = '';
                                        if( !empty($bfi_get_settings['bfi_posttyps']) && is_array( $bfi_get_settings['bfi_posttyps'] ) && in_array( sanitize_text_field( $post_type ) , $bfi_get_settings['bfi_posttyps'])) {
                                            $selected = 'selected';
                                        }
                                        ?>
                                        <option <?php echo $selected; ?> value="<?php echo esc_attr( $post_type ); ?>"><?php echo ucfirst( esc_attr( $post_type ) ); ?></option>
                                        <?php
                                    }
                                }
                                ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="bfi_per_page"><?php _e( 'Enable default thumbnail for ', 'bulk-featured-image' ); ?></label></th>
                        <td>
                        <?php 
                            if( !empty($post_types) && is_array($post_types)) {
                                $enable_default_image = !empty($bfi_get_settings['enable_default_image']) ? $bfi_get_settings['enable_default_image'] : '';
                                foreach ( $post_types as $e_post_type ) {
                                    $id = 'enable_default_image_'.esc_attr($e_post_type);

                                    $checked = '';
                                    if( !empty($enable_default_image) && in_array( esc_attr($e_post_type), $enable_default_image )) {
                                        $checked = 'checked';
                                    }
                                    ?>
                                    <label for="<?php echo $id; ?>">
                                        <input <?php echo $checked; ?> type="checkbox" id="<?php echo $id; ?>" class="enable-default-image" name="enable_default_image[]" value="<?php echo esc_attr($e_post_type); ?>"</td>
                                        <?php echo ucfirst(esc_attr( $e_post_type )); ?>
                                    </label>
                                    <?php
                                }
                            }
                            ?>
                    </tr>              
                </tbody>
            </table>
            <?php

        }

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

            if( isset( $_FILES['bfi_upload_file'] ) && !empty( $_FILES['bfi_upload_file'] ) && is_array($_FILES['bfi_upload_file'])) {
                $image_url = !empty( $_FILES['bfi_upload_file']['tmp_name'] ) ? sanitize_text_field( $_FILES['bfi_upload_file']['tmp_name'] ) : '';
				$image_name = !empty( $_FILES['bfi_upload_file']['name'] ) ? sanitize_text_field( $_FILES['bfi_upload_file']['name'] ) : '';
                
                if( !empty($image_url) && !empty($image_name)) {

                    $attach_id = $this->process_attachment( $image_url, $image_name);
                    
                    if( !empty($attach_id) && $attach_id > 0 ) {
                        $settings['bfi_upload_file'] = (int)sanitize_text_field($attach_id);
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

            self::add_message( sprintf(__( 'Your <strong>%s</strong> featured image updated successfully.', 'bulk-featured-image' ), ucwords($current_sub_section) ) );
        }

        public function add_default_post_type_thumb( $section ) {

            if( empty($section) ) {
                return;
            }

            $bfi_get_settings = bfi_get_settings( 'general');

            $enable_default_image = !empty($bfi_get_settings['enable_default_image']) ? $bfi_get_settings['enable_default_image'] : '';

            if( !empty($enable_default_image) && is_array($enable_default_image) && in_array($section, $enable_default_image) ) {

                $get_pt_settings = bfi_get_settings('post_types');
                $get_sub_pt_setting = !empty( $get_pt_settings[$section] ) ? $get_pt_settings[$section] : '';
                $bfi_upload_file = !empty( $get_sub_pt_setting['bfi_upload_file'] ) ? sanitize_text_field( $get_sub_pt_setting['bfi_upload_file'] ): '';
                ob_start();
                ?>
                <div class="bfi-image-uploader-wrap">
                    <div class="row">
                        <div class="uploader-outer col-md-4">
                            <div class="dragBox">
                                <span class="d-block"><?php _e('Darg and Drop image here','bulk-featured-image'); ?>
                                    <input type="file" onChange="bfi_drag_drop(event)" name="bfi_upload_file"  ondragover="bfi_drag()" ondrop="bfi_drop()" id="bfi_upload_file" accept=".png,.jpg,.jpeg"  />
                                </span>
                                <strong class="d-block my-2"><?php _e('OR','bulk-featured-image'); ?></strong>
                                <label for="bfi_upload_file" class="btn btn-primary"><?php _e('Upload Image','bulk-featured-image'); ?></label>
                            </div>
                            <div class="description"><?php _e('If enable default thumbnail settings','bulk-featured-image'); ?></div>
                        </div>
                        <div id="bfi_upload_preview" class="uploader-preview col-md-4">
                            <?php if( !empty($bfi_upload_file) && $bfi_upload_file > 0 ) {  ?>
                                <img src="<?php echo wp_get_attachment_url( $bfi_upload_file ); ?>" alt="Preview Image" />
                                <input type="hidden" name="bfi_upload_file" value="<?php echo esc_attr($bfi_upload_file); ?>" >
                            <?php } ?>
                        </div>
                    </div>
                </div>
                <?php
                $html = ob_get_contents();
                ob_get_clean();
                
                echo $html;
            }
        }

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

        public function uninstall_settings() {

            $uninstall_settings = bfi_get_settings( 'uninstall');
            $bfi_uninstall = !empty( $uninstall_settings['bfi_uninstall'] ) ? sanitize_text_field($uninstall_settings['bfi_uninstall']) : '';
            
            ?>
            <table class="form-table">
                <tbody>
                    <tr>
                        <th><label for="bfi_uninstall"><?php _e( 'Remove Data on Uninstall?', 'bulk-featured-image' ); ?></label></th>
                        <td>
                            <label>
                            <input type="checkbox" <?php checked($bfi_uninstall, '1'); ?> name="bfi_uninstall" id="bfi_uninstall" class="regular-text" value="1">
                            <?php _e( 'Check this box if you would completely remove all of its data when the plugin is deleted.', 'bulk-featured-image' ); ?>
                            </label>
                        </td>
                    </tr>
                </tbody>
            </table>
            <?php
        }

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

		public function set_custom_edit_book_columns($columns) {

			$columns['featured_image'] = __( 'Featured Image', 'bulk-featured-image' );

			return $columns;
		}

        public function get_post_featured_html( $post_id ) {

            ob_start();
	        $BFI_List_Table = new BFI_List_Table();?>
	        <div class='post-bfi'>
                <?php
	            echo $BFI_List_Table->get_thumbnail_html($post_id);
                ?>
	        </div>
	        <?php
	        if(has_post_thumbnail($post_id)){
	        ?>
		        <div class="post-bfi-option">
		        <a class="bfi-img-uploader" data-id="<?php echo $post_id;?>">Update Featured Image</a>
		        </div>
		        <?php
	        }else{
	        ?>
		        <div class="post-bfi-option">
		        <a class="bfi-img-uploader" data-id="<?php echo $post_id;?>">Add Featured Image</a>
		        </div>
		        <?php
	        }
	        $featured_html = ob_get_contents();
            ob_get_clean();

            return $featured_html;
        }

		public function  custom_featured_image_column( $column, $post_id ) {
			switch ( $column ) {

				case 'featured_image' :
                    echo $this->get_post_featured_html($post_id);
					break;
                default : ;
			}
		}
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