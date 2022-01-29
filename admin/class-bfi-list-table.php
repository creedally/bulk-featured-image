<?php

require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );

class BFI_List_Table extends WP_List_Table {

    public $posttype = 'post';

    public $per_page = BFIE_PER_PAGE;
    
    public $paged = 1;

    public function __construct( $newargs = array() ) {
        $this->posttype = !empty( $newargs['posttype'] ) ? sanitize_text_field( $newargs['posttype'] ) : 'post';
        $this->per_page = bfi_get_per_page();
        $this->paged = !empty( $_REQUEST['paged'] ) ? (int)sanitize_text_field( $_REQUEST['paged'] ) : 1;

        parent::__construct();
    }

    public function prepare_items() {

        $columns = $this->get_columns();

        $hidden = $this->get_hidden_columns();

        $sortable = $this->get_sortable_columns();

        $data = $this->table_data();
        
        usort( $data, array( &$this, 'sort_data' ) );

        $table_data = $this->get_post_data();

        $totalItems = !empty( $table_data['total_items'] ) ? $table_data['total_items'] : 0;

        $this->set_pagination_args( array(
            'total_items' => $totalItems,
            'per_page'    => $this->per_page
        ) );

        $this->_column_headers = array( $columns, $hidden, $sortable );
        
        $this->items = $data;
    }

    public function get_columns() {

        $columns = array(
            'id'            => __( 'ID', 'bulk-featured-image'),
            'title'         => __( 'Title', 'bulk-featured-image'),
            'featured-image' => __( 'Featured Image', 'bulk-featured-image'),
            'new-featured-mage' => __( 'New Featured Image', 'bulk-featured-image'),
            'author'    => __( 'Author', 'bulk-featured-image'),
            'date'      => __( 'Date', 'bulk-featured-image')
        );

        return $columns;
    }

    public function get_hidden_columns() {
        return array();
    }

    public function get_sortable_columns() {
        return array(
            'title' => array('title', false),
            'date' => array('date', false)
        );
    }

    public function get_post_data() {

        $orderby = 'date';
        $order = 'desc';

        if( isset($_GET['orderby']) && !empty( $_GET['orderby'] ) ) {
            $orderby = sanitize_text_field($_GET['orderby']);
        }

        if( isset($_GET['order']) && !empty( $_GET['order'] ) ) {
            $order = sanitize_text_field($_GET['order']);
        }

        $posts_args = array(
            'post_type' => sanitize_text_field( $this->posttype ),
            'post_status' => 'publish',
            'posts_per_page' => sanitize_text_field( $this->per_page ),
            'paged' => $this->paged,
            'order' => $order,
            'orderby' => $orderby,
        );

        if ( isset($_REQUEST['author']) && !empty( $_REQUEST['author'] ) && sanitize_text_field($_REQUEST['author']) > 0 ) {
            $posts_args['author'] = sanitize_text_field( $_REQUEST['author'] );
        }
        
        $data = array();
        $posts_results = new WP_Query( $posts_args );
        if ( $posts_results->have_posts() ) {
            
            $temp_data = array();

            $posttype_link = admin_url( 'admin.php?page='.BFIE_MENU_SLUG);
            if ( isset($_REQUEST['tab']) && !empty( $_REQUEST['tab'] ) ) {
                $posttype_link .='&tab='.sanitize_text_field( $_REQUEST['tab'] );
            }

            if ( isset( $_REQUEST['section'] ) && !empty( $_REQUEST['section'] ) ) {
                $posttype_link .='&section='.sanitize_text_field( $_REQUEST['section'] );
            }

            while ( $posts_results->have_posts() ) { 
                $posts_results->the_post();
                global $post;

                $posttype_link .='&author='.$post->post_author;

                $temp_data[] = array(
                    'id'          => get_the_ID(),
                    'title'       => '<a href="'.get_edit_post_link().'">'.get_the_title().'</a>',
                    'featured-image' => $this->get_thumbnail_html(get_the_ID()),
                    'new-featured-mage' => $this->get_image_uploader_html(get_the_ID()),
                    'author'    => sprintf( '%s%s%s', '<a href="'.$posttype_link.'">', get_the_author(),'</a>'),
                    'date'      => get_the_date()
                );
            }

            $data['data'] = $temp_data;
        }

        $data['total_items'] = !empty($posts_results->found_posts) ? $posts_results->found_posts : 0;

        wp_reset_postdata();

        return $data;
    }

    private function table_data() {

        $table_data = $this->get_post_data();

        return !empty( $table_data['data'] ) ? $table_data['data'] : array();
    }

    public function column_default( $item, $column_name ) {
        switch( $column_name ) {
            case 'id':
            case 'title':
            case 'featured-image':
            case 'new-featured-mage':
            case 'author':
            case 'date':
                return $item[ $column_name ];
            default:
                return $column_name;
        }
    }

    public function single_row( $item ) {

        $post_id = !empty( $item['id'] ) ? $item['id'] : 0;
		echo '<tr class="bfi-row-'.$post_id.'">';
		$this->single_row_columns( $item );
		echo '</tr>';
	}

    private function sort_data( $a, $b ) {

        $orderby = 'date';
        $order = 'desc';

        if( isset( $_GET['orderby'] ) && !empty( $_GET['orderby'] ) ) {
            $orderby = sanitize_text_field( $_GET['orderby'] );
        }

        if( isset( $_GET['order'] ) && !empty( $_GET['order'] ) ) {
            $order = sanitize_text_field( $_GET['order'] );
        }

        $result = strcmp( $a[$orderby], $b[$orderby] );

        if( $order === 'asc' ) {
            return $result;
        }

        return $result;
    }

    public function get_thumbnail_html( $post_id ) {

        if( empty( $post_id ) ){
            return '';
        }
        ob_start();
        $thumb = get_the_post_thumbnail_url( $post_id );

        $current_page = !empty( $_GET['page']) ? esc_attr($_GET['page']) : get_post_type($post_id);

        ?>
        <div class="bfi-image-uploader-wrap">
            <?php if( !empty($thumb)) { ?>
                <div class="uploader-preview">
                    <img id="post_thumbnail_url_<?php echo $post_id; ?>" src="<?php echo esc_url($thumb); ?>" alt="<?php echo get_the_title(); ?>" width="50" height="50" />
                </div>
            <?php } else { ?>
                <div id="no_thumbnail_url_<?php echo $post_id; ?>"><?php _e( 'Thumbnail not exists', 'bulk-featured-image' ); ?></div>
            <?php } ?>
            <div class="uploader-preview" id="bfi_upload_preview_<?php echo $post_id; ?>"></div>
        </div>
	    <?php if( !empty($thumb)) { ?>
        <div class="bfi-remove-image">
            <a id="remove-featured-image" class="remove-featured-image" data-current_page="<?php echo $current_page; ?>" data-id="<?php echo $post_id; ?>">Remove image</a>
        </div>
        <?php } ?>
        <?php 
        $html = ob_get_contents();
        ob_get_clean();
    
        return $html;
    }

    public function get_image_uploader_html( $post_id ) {

        if( empty( $post_id ) ){
            return '';
        }

        ob_start();
        ?>
        <div class="bfi-image-uploader-wrap">
            <div class="row">
                <div class="uploader-outer col-md-10">
                    <div class="dragBox p-3">
                        <span class="d-block"><?php _e('Darg and Drop image here','bulk-featured-image'); ?>
                            <input type="file" onChange="bfi_drag_drop(event,<?php echo $post_id; ?>)" data-id="<?php echo $post_id; ?>" name="bfi_upload_file_<?php echo $post_id; ?>"  ondragover="bfi_drag(event,<?php echo $post_id; ?>)" ondrop="bfi_drop(event,<?php echo $post_id; ?>)" id="bfi_upload_file_<?php echo $post_id; ?>" accept=".png,.jpg,.jpeg"  />
                            <input type="hidden" name="bfi_upload_post_id[]" value="<?php echo $post_id; ?>" />
                        </span>
                        <strong class="d-block my-2"><?php _e('OR','bulk-featured-image'); ?></strong>
                        <label for="bfi_upload_file_<?php echo $post_id; ?>" class="btn btn-primary"><?php _e('Upload Image','bulk-featured-image'); ?></label>
                    </div>
                </div>
            </div>
        </div>
        <?php
        $html = ob_get_contents();
        ob_get_clean();
    
        return $html;
    }
}