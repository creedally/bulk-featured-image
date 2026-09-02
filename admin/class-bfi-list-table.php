<?php

require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );

/**
 * BFI List Table class.
 *
 * Handles the display and management of posts in the
 * Bulk Featured Image admin list table.
 *
 * @since 1.0.0
 */
class BFI_List_Table extends WP_List_Table {

    /**
	 * Current post type.
	 *
	 * @var string
	 */
    public $posttype = 'post';
    
    /**
	 * Number of items displayed per page.
	 *
	 * @var int
	 */
    public $per_page = 10;

    /**
	 * Current page number.
	 *
	 * @var int
	 */
    public $paged = 1;

    /**
	 * Initialize the list table.
	 *
	 * @since 1.0.0
	 *
	 * @param array $newargs Arguments used to configure the list table.
	 */
    public function __construct( $newargs = array() ) {
        $this->posttype = !empty( $newargs['posttype'] ) ? sanitize_text_field( $newargs['posttype'] ) : 'post';
        $this->per_page = function_exists( 'bfi_get_per_page' ) ? bfi_get_per_page() : 10;
        $this->paged    = !empty( $_REQUEST['paged'] ) ? (int)sanitize_text_field( $_REQUEST['paged'] ) : 1;

        parent::__construct( array(
            'singular' => 'post',
            'plural'   => 'posts',
            'ajax'     => false
        ) );
    }

    /**
	 * Prepare items for display.
	 *
	 * Retrieves the table data, sets pagination arguments,
	 * and prepares the column headers.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
    public function prepare_items() {
        $columns  = $this->get_columns();
        $hidden   = $this->get_hidden_columns();
        $sortable = $this->get_sortable_columns();
        $table_data = $this->get_post_data();
        $totalItems = !empty( $table_data['total_items'] ) ? $table_data['total_items'] : 0;

        $this->set_pagination_args( array(
            'total_items' => $totalItems,
            'per_page'    => $this->per_page
        ) );

        $this->_column_headers = array( $columns, $hidden, $sortable );
        $this->items           = !empty( $table_data['data'] ) ? $table_data['data'] : array();
    }

    /**
	 * Get table columns.
	 *
	 * @since 1.0.0
	 *
	 * @return array Table columns.
	 */
    public function get_columns() {
        return array(
            'id'                => __( 'ID', 'bulk-featured-image' ),
            'title'             => __( 'Title', 'bulk-featured-image' ),
            'featured-image'    => __( 'FEATURED IMAGE', 'bulk-featured-image' ),
            'new-featured-mage' => __( 'NEW FEATURED IMAGE', 'bulk-featured-image' ),
            'author'            => __( 'AUTHOR', 'bulk-featured-image' ),
            'date'              => __( 'Date', 'bulk-featured-image' )
        );
    }

    /**
	 * Get hidden columns.
	 *
	 * @since 1.0.0
	 *
	 * @return array Hidden columns.
	 */
    public function get_hidden_columns() {
        return array();
    }

    /**
	 * Get sortable columns.
	 *
	 * @since 1.0.0
	 *
	 * @return array Sortable columns.
	 */
    public function get_sortable_columns() {
        return array(
            'title' => array( 'title', false ),
            'date'  => array( 'date', false )
        );
    }

	/**
	 * Get post data for the list table.
	 *
	 * Retrieves posts based on the selected post type,
	 * pagination, sorting, and author filters.
	 *
	 * @since 1.0.0
	 *
	 * @return array Post data and total item count.
	 */
    public function get_post_data() {
        $orderby = 'date';
        $order   = 'desc';

        if ( isset( $_GET['orderby'] ) && !empty( $_GET['orderby'] ) ) {
            $orderby = sanitize_text_field( $_GET['orderby'] );
        }
        if ( isset( $_GET['order'] ) && !empty( $_GET['order'] ) ) {
            $order = sanitize_text_field( $_GET['order'] );
        }

        $posts_args = array(
            'post_type'      => sanitize_text_field( $this->posttype ),
            'post_status'    => array( 'publish', 'draft', 'pending', 'private' ),
            'posts_per_page' => sanitize_text_field( $this->per_page ),
            'paged'          => $this->paged,
            'order'          => $order,
            'orderby'        => $orderby,
        );

        if ( isset( $_REQUEST['author'] ) && !empty( $_REQUEST['author'] ) && sanitize_text_field( $_REQUEST['author'] ) > 0 ) {
            $posts_args['author'] = sanitize_text_field( $_REQUEST['author'] );
        }

        $data          = array();
        $posts_results = new WP_Query( $posts_args );

        if ( $posts_results->have_posts() ) {
            $temp_data = array();

            $base_link = admin_url( 'admin.php?page=' . ( defined( 'BFIE_MENU_SLUG' ) ? BFIE_MENU_SLUG : 'bfi_menu' ) );
            if ( isset( $_REQUEST['tab'] ) && !empty( $_REQUEST['tab'] ) ) {
                $base_link .= '&tab=' . sanitize_text_field( $_REQUEST['tab'] );
            }
            if ( isset( $_REQUEST['section'] ) && !empty( $_REQUEST['section'] ) ) {
                $base_link .= '&section=' . sanitize_text_field( $_REQUEST['section'] );
            }

            while ( $posts_results->have_posts() ) {
                $posts_results->the_post();
                global $post;

                $author_link = $base_link . '&author=' . $post->post_author;
                $post_id     = get_the_ID();
                $status      = get_post_status();

                $title_html  = '<div class="bfi-title-cell">';
                $title_html .= '<div class="bfi-title-cell__header">';
                $title_html .= '<a class="bfi-title-cell__link" href="' . get_edit_post_link() . '">' . get_the_title() . '</a>';
                $title_html .= '<span class="bfi-title-cell__status bfi-title-cell__status--' . esc_attr( $status ) . '">';
                $title_html .= '<span class="bfi-title-cell__dot"></span>' . ucfirst( $status );
                $title_html .= '</span>';
                $title_html .= '</div>';
                $title_html .= '<div class="bfi-title-cell__actions">';
                $title_html .= '<a href="' . get_edit_post_link() . '">' . __( 'Edit', 'bulk-featured-image' ) . '</a>';
                $title_html .= '<span class="bfi-title-cell__sep">•</span>';
                $title_html .= '<a href="' . get_permalink() . '" target="_blank">' . __( 'View', 'bulk-featured-image' ) . '</a>';
                $title_html .= '</div></div>';

                $temp_data[] = array(
                    'id'                => '<span class="bfi-id-tag">' . $post_id . '</span>',
                    'title'             => $title_html,
                    'featured-image'    => $this->get_thumbnail_html( $post_id ),
                    'new-featured-mage' => $this->get_image_uploader_html( $post_id ),
                    'author'            => '<a class="bfi-author-link" href="' . esc_url( $author_link ) . '">' . get_the_author() . '</a>',
                    'date'              => '<span class="bfi-date-text">' . get_the_date( 'M j, Y' ) . '</span>'
                );
            }

            $data['data'] = $temp_data;
        }

        $data['total_items'] = !empty( $posts_results->found_posts ) ? $posts_results->found_posts : 0;
        wp_reset_postdata();

        return $data;
    }

    /**
	 * Display a default column.
	 *
	 * @since 1.0.0
	 *
	 * @param array  $item        Current item.
	 * @param string $column_name Column name.
	 * @return string Column content.
	 */
    public function column_default( $item, $column_name ) {
        switch ( $column_name ) {
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

    /**
	 * Display a single table row.
	 *
	 * @since 1.0.0
	 *
	 * @param array $item Current table item.
	 * @return void
	 */
    public function single_row( $item ) {
        $raw_id = preg_replace( '/[^0-9]/', '', $item['id'] );
        echo '<tr class="bfi-table-row bfi-row-' . esc_attr( $raw_id ) . '">';
        $this->single_row_columns( $item );
        echo '</tr>';
    }

    /**
	 * Get featured image thumbnail HTML.
	 *
	 * @since 1.0.0
	 *
	 * @param int $post_id Post ID.
	 * @return string Thumbnail HTML.
	 */
    public function get_thumbnail_html( $post_id ) {
        if ( empty( $post_id ) ) {
            return '';
        }
        ob_start();
        $thumb        = get_the_post_thumbnail_url( $post_id );
        $current_page = !empty( $_GET['page'] ) ? esc_attr( $_GET['page'] ) : get_post_type( $post_id );
        ?>
        <div class="bfi-thumb-preview">
            <?php if ( !empty( $thumb ) ) { ?>
                <div class="bfi-thumb-preview__img-wrap">
                    <img id="post_thumbnail_url_<?php echo $post_id; ?>" src="<?php echo esc_url( $thumb ); ?>" alt="<?php echo esc_attr( get_the_title( $post_id ) ); ?>" />
                    <a id="remove-featured-image" class="remove-featured-image bfi-thumb-preview__remove" data-current_page="<?php echo $current_page; ?>" data-id="<?php echo $post_id; ?>">
                        <?php esc_html_e( 'Remove image', 'bulk-featured-image' ); ?>
                    </a>
                </div>
            <?php } else { ?>
                <div class="bfi-badge-no-thumb" id="no_thumbnail_url_<?php echo $post_id; ?>">
                    <svg class="bfi-badge-no-thumb__icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                        <circle cx="8.5" cy="8.5" r="1.5"></circle>
                        <polyline points="21 15 16 10 5 21"></polyline>
                    </svg>
                    <span class="bfi-badge-no-thumb__text"><?php _e( 'No thumbnail', 'bulk-featured-image' ); ?></span>
                </div>
            <?php } ?>
            <div class="uploader-preview" id="bfi_upload_preview_<?php echo $post_id; ?>"></div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
	 * Get image uploader HTML.
	 *
	 * @since 1.0.0
	 *
	 * @param int $post_id Post ID.
	 * @return string Image uploader HTML.
	 */
    public function get_image_uploader_html( $post_id ) {
        if ( empty( $post_id ) ) {
            return '';
        }

        ob_start();
        ?>
        <div class="bfi-uploader-cell">
            <div class="bfi-dropzone-box">
                <div class="bfi-dropzone-box__area" ondragover="bfi_drag(event,<?php echo $post_id; ?>)" ondrop="bfi_drop(event,<?php echo $post_id; ?>)">
                    <div class="bfi-dropzone-box__icon">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                            <polyline points="17 8 12 3 7 8"></polyline>
                            <line x1="12" y1="3" x2="12" y2="15"></line>
                        </svg>
                    </div>
                    <span class="bfi-dropzone-box__text"><?php _e( 'Drag and drop image here', 'bulk-featured-image' ); ?></span>
                    <span class="bfi-dropzone-box__or"><?php _e( 'OR', 'bulk-featured-image' ); ?></span>
                    
                    <input type="file" class="bfi-dropzone-box__file" onChange="bfi_drag_drop(event,<?php echo $post_id; ?>)" data-id="<?php echo $post_id; ?>" name="bfi_upload_file_<?php echo $post_id; ?>" id="bfi_upload_file_<?php echo $post_id; ?>" accept=".png,.jpg,.jpeg" />
                    <input type="hidden" name="bfi_upload_post_id[]" value="<?php echo $post_id; ?>" />
                    
                    <label for="bfi_upload_file_<?php echo $post_id; ?>" class="button button-primary">
                        <?php _e( 'Upload image', 'bulk-featured-image' ); ?>
                    </label>
                </div>
            </div>

            <div class="bfi-url-input-wrap">
                <label for="bfi_image_url_<?php echo $post_id; ?>" class="bfi-url-input-wrap__label"><?php _e( 'OR ENTER IMAGE URL', 'bulk-featured-image' ); ?></label>
                <input type="url" id="bfi_image_url_<?php echo $post_id; ?>" name="bfi_image_url_<?php echo $post_id; ?>" class="bfi-url-input-wrap__field" placeholder="https://example.com/image.jpg" />
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

	/**
	 * Display the list table.
	 *
	 * Overrides the default WordPress list table display method
	 * to remove the default footer.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
    public function display() {
        $singular = $this->_args['singular'];

        $this->display_tablenav( 'top' );

        ?>
        <table class="wp-list-table <?php echo implode( ' ', array_map( 'sanitize_html_class', $this->get_table_classes() ) ); ?>">
            <thead>
                <tr>
                    <?php $this->print_column_headers(); ?>
                </tr>
            </thead>

            <tbody id="the-list"
                <?php
                if ( $singular ) {
                    echo ' data-wp-lists="list:' . esc_attr( $singular ) . '"';
                }
                ?>
            >
                <?php $this->display_rows_or_placeholder(); ?>
            </tbody>
        </table>
        <?php
    }
}