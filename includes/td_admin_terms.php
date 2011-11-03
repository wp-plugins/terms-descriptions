<?php
/**
 * This class creates Terms page in Terms menu
 */
class TD_Admin_Terms {
    private $page = '';
    private $post_types = array();
    //options defaults
    private $td_options = array(
                'terms_per_page' => 20,
                'convert_in_posts' => true,
                'convert_in_comments' => false,
                'convert_first_n_terms' => 3,
                'class' => '',
                'convert_only_single' => true,
                'parser' => 'simple_parser',
            );
    private $terms_ids = array();

    /**
     * Constuctor. Sets the actions handlers.
     */
    public function __construct() {
        register_activation_hook( TD_FILE, array( $this, 'install' ) );
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
        add_action( 'admin_notices', array( $this, 'update_message' ) );
        add_action( 'admin_init', array( $this, 'update_db' ) );
    }

    /**
     * Plugin installation.
     * 
     * @global type $wpdb wordpress database class
     */
    public function install() {
        global $wpdb;
        //creating database table for terms
        $terms_table_name = $wpdb->prefix . 'td_terms';
        if( $wpdb->get_var( 'show tables like "' . $terms_table_name . '"' ) != $terms_table_name ) {
            //creating terms table
            $sql = "CREATE TABLE " . $terms_table_name . " (
                t_id bigint(20) NOT NULL AUTO_INCREMENT,
                t_post_id bigint(20) NOT NULL,
                t_post_title VARCHAR(255),
                t_post_url VARCHAR(255),
                t_post_type VARCHAR(255) NOT NULL,
                t_term TEXT NOT NULL,
                UNIQUE KEY t_id (t_id)
                );";

            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
        }
        
        //if there is no plugin options data from new and previous versions
        if ( false === get_option( 'td_terms' ) && false === get_option( 'td_options' ) ) {
            //setting default options values
            add_option( 'td_options', $this->td_options );
        }
    }
    
    /**
     * Returns default options values 
     *
     * @return array default options values 
     */
    public function get_default_options() {
        return $this->td_options;
    }
    
    /**
     * This method shows warning message if previous plugin version is lower than 1.2.0
     */
    public function update_message() {
        if ( false !== ( $terms = get_option( 'td_terms' ) ) ) {
?>
<div id="message" class="updated">
    <p>
        <?php _e( 'Terms Descriptions plugin is almost updated. Please, BACKUP YOUR DATABASE and press following button to', TD_TEXTDOMAIN ); ?>
        <a href="<?php echo wp_nonce_url( 'admin.php?page=' . TD_TEXTDOMAIN . '&action=update_db', 'update_db' ); ?>" class="button-secondary"><?php _e( 'Update DB', TD_TEXTDOMAIN ); ?></a>
    </p>
</div>
<?php
        }
    }
    
    /**
     * This method converts data to v.1.2.0 format.
     *
     * @global type $wpdb wordpress database class
     */
    public function update_db() {
        if ( isset( $_GET[ 'action' ] ) && $_GET[ 'action' ] === 'update_db'
                && isset( $_GET[ '_wpnonce' ] )
                && wp_verify_nonce( $_GET[ '_wpnonce' ], 'update_db' ) ) {
            //importing data from plugin previous versions
            if ( false !== ( $terms = get_option( 'td_terms' ) ) ) {
                global $wpdb;
                $terms_table_name = $wpdb->prefix . 'td_terms';
                
                //creating database
                $this->install();
                
                if ( is_array( $terms ) ) {
                    $insert_sql = 'INSERT INTO ' . $terms_table_name
                            . ' (t_post_id,t_post_title,t_post_url,t_post_type,t_term) VALUES ';
                    $terms_values = array();
                    foreach ( $terms as $term ) {
                        if ( $term[ 'pageid' ] === '0' ) {
                            $post_type = 'ext_link';
                        }
                        else {
                            $post_type = get_post_type( $term[ 'pageid' ] );
                        }
                        $terms_values[] = '(' . $term[ 'pageid' ] . ',"' . $wpdb->escape( $term[ 'title' ] )
                                . '","' . $wpdb->escape( $term[ 'url' ] ) . '","' . $post_type
                                . '","' . $wpdb->escape( $term[ 'term' ] ) . '")';
                    }
                    $wpdb->query( $insert_sql . implode(',', $terms_values) );
                    delete_option( 'td_terms' );
                }
            }
            
            //importing options from plugin previous versions
            if ( false === get_option( 'td_options' ) ) {
                if ( false !== get_option('td_class' ) ) {
                    $this->td_options[ 'class' ] = get_option( 'td_class' );
                }
                if ( false !== get_option( 'td_count' ) ) {
                    $this->td_options[ 'convert_first_n_terms' ] = get_option( 'td_count' );
                }
                if ( false !== get_option( 'td_convert_only_single' ) ) {
                    $this->td_options[ 'convert_only_single' ] = get_option( 'td_convert_only_single' );
                }
                if ( false !== ( $targets = get_option( 'td_target' ) ) ) {
                    if ( in_array( 'posts', $targets ) ) {
                        $this->td_options[ 'convert_in_posts' ] = true;
                    }
                    if ( in_array( 'comments', $targets ) ) {
                        $this->td_options[ 'convert_in_comments' ] = true;
                    }
                }
                
                add_option( 'td_options', $this->td_options );
                
                delete_option( 'td_target' );
                delete_option( 'td_class' );
                delete_option( 'td_count' );
                delete_option( 'td_convert_only_single' );
            }
            
            wp_redirect( trailingslashit( site_url() ) . 'wp-admin/admin.php?page=' . TD_TEXTDOMAIN );
            die();
        }
    }
    
    /**
     * Creating admin menu
     *
     * @global type $wpdb wordpress database class
     */
    public function admin_menu() {
		load_plugin_textdomain( TD_TEXTDOMAIN, false, TD_TEXTDOMAIN . '/lang' );
		$this->page = add_menu_page( __( 'Terms', TD_TEXTDOMAIN )
				, __( 'Terms', TD_TEXTDOMAIN )
				, 'manage_options'
				, TD_TEXTDOMAIN
				, array( $this, 'terms_page' ) );
		add_action( 'admin_print_scripts-' . $this->page, array( $this, 'load_scripts' ) );
		add_action( 'admin_print_styles-' . $this->page, array( $this, 'load_styles' ) );
        
        $this->post_types = get_post_types( array(
            'public' => true,
            'show_ui' => true,
        ), 'objects' );
        
        //getting all terms ids (used for permalinks updates)
        global $wpdb;
        $this->terms_ids = $wpdb->get_col('SELECT t_id FROM ' . $wpdb->prefix . 'td_terms', 0 );
    }

    /**
     * Loading JS files
     *
     * @global type $wpdb wordpress database class
     */
	public function load_scripts() {
		wp_enqueue_script( 'td_autocomplete', TD_URL . '/js/jquery.autocomplete.min.js'
				, array( 'jquery' ), '1.0', true );
		wp_enqueue_script( 'td_template', TD_URL . '/js/jquery.tmpl.min.js'
				, array( 'jquery' ), '1.0', true );
        wp_enqueue_script( 'td_terms', TD_URL . '/js/terms.js'
				, array( 'td_autocomplete', 'jquery-ui-dialog' ), '1.0', true );
        //translations for use in JS code and array of terms ids
        wp_localize_script( 'td_terms', 'td_messages', array(
            'enter_term' => __( 'Enter the term, please', TD_TEXTDOMAIN ),
            'enter_link' => __( 'Enter the link, please', TD_TEXTDOMAIN ),
            'url_save' => get_bloginfo( 'wpurl' ) . '/wp-admin/admin-ajax.php',
            'edit' => __( 'Edit', TD_TEXTDOMAIN ),
            'remove' => __( 'Delete', TD_TEXTDOMAIN ),
            'confirm_delete' => __( 'Are you sure?', TD_TEXTDOMAIN ),
            'add_term' => __( 'Add term', TD_TEXTDOMAIN ),
            'edit_term' => __( 'Update term', TD_TEXTDOMAIN ),
            'cancel_edit_term' => __( 'Cancel', TD_TEXTDOMAIN ),
            'nonce' => wp_create_nonce( 'td_delete_term' ),
            'nonce_update_permalink' => wp_create_nonce( 'td_update_permalink' ),
            'term_add' => __( 'New term was added', TD_TEXTDOMAIN ),
            'term_update' => __( 'The term was updated', TD_TEXTDOMAIN ),
            'updating_permalinks' => __( 'Updating...', TD_TEXTDOMAIN ),
            'done' => __( 'Done!', TD_TEXTDOMAIN ),
            'terms_ids' => json_encode( $this->terms_ids ),
        ) );
        
        global $wpdb;
        $types_names = array();
        foreach ( $this->post_types as $type_name => $type ) {
            $types_names[] = '"' . $type_name . '"';
        }
        //getting blog posts for use in JS code (autocomplete field)
        $posts = $wpdb->get_results( 'SELECT ID, post_title, post_type FROM '
                . $wpdb->posts . ' WHERE post_type IN (' . implode( ',', $types_names )
                . ') AND post_status IN ("draft", "publish")' );
        echo '<script type="text/javascript">' . "\n"
            . '//<![CDATA[' . "\n"
            . 'var td_posts=' . json_encode( $posts ) . "\n"
            . '//]]>' . "\n"
            . '</script>';
    }
    
    /**
     * Including CSS files
     */
    public function load_styles() {
        wp_enqueue_style( 'td_autocomplete_css', TD_URL . '/css/jquery.autocomplete.css' );
        wp_enqueue_style( 'td_jquery_ui_smoothness', TD_URL . '/css/smoothness/jquery-ui-1.8.16.custom.css' );
        wp_enqueue_style( 'td_css', TD_URL . '/css/td_styles.css' );
    }
    
    /**
     * Terms page HTML
     *
     * @global type $wpdb wordpress database class
     */
    public function terms_page() {
?>
<div class="wrap">
	<h2><?php _e( 'Terms', TD_TEXTDOMAIN ); ?></h2>
    <form action="#" method="post" id="td_add_term_form">
    <?php wp_nonce_field( 'td_add_term' ); ?>
    <table class="form-table">
        <tr>
            <th scope="row"><label for="td_term"><?php _e( 'Term', TD_TEXTDOMAIN ); ?></label></th>
            <td>
                <textarea name="td_term" id="td_term" cols="50" rows="3" class="large-text code"></textarea>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="td_link"><?php _e( 'Link', TD_TEXTDOMAIN ); ?></label></th>
            <td>
                <label for="td_content_type"><?php _e( 'Link to', TD_TEXTDOMAIN ); ?></label>
                <select name="td_content_type" id="td_content_type">
<?php
                foreach ( $this->post_types as $type_name => $type ) {
                    echo '<option value="'.$type_name.'">'.$type->labels->singular_name.'</option>';
                }
?>
                    <option value="ext_link"><?php _e( 'External link', TD_TEXTDOMAIN ); ?></option>
                    <option value="post_id"><?php _e( 'Post ID', TD_TEXTDOMAIN ); ?></option>
                </select>
                <input type="text" name="td_link" id="td_link" class="regular-text" />
                <input type="hidden" name="td_post_id" id="td_post_id" />
            </td>
        </tr>
    </table>
        <p class="submit">
            <input type="submit" name="td_add_term" id="td_add_term" class="button-primary" value="<?php _e( 'Add term', TD_TEXTDOMAIN ); ?>">
        </p>
    </form>
    
    <form action="#" method="post" id="td_update_permalinks">
        <input type="submit" class="button-primary" name="td_update_permalinks_btn" value="<?php _e( 'Update permalinks', TD_TEXTDOMAIN ); ?>" />
    </form>
    
    <?php
    global $wpdb;
    //getting terms data
    $options = get_option( 'td_options' );
    
    $terms_count = $wpdb->get_var( 'SELECT COUNT(*) FROM ' . $wpdb->prefix . 'td_terms' );
    //preparing pagination
    $cur_page = 1;
    if ( isset( $_GET[ 'term_page' ] ) && ( int )$_GET[ 'term_page' ] > 0 ) {
        $cur_page = ( int )$_GET[ 'term_page' ];
    }
    
    $terms_per_page = $options[ 'terms_per_page' ];
    if ( false === $terms_per_page ) {
        $terms_per_page = 10;
    }

    $pagination = $this->pagination( $terms_count, $cur_page, ( int )$terms_per_page );
    echo $pagination;
    
    //creating terms table
    ?>
    
    <table class="wp-list-table widefat fixed" cellspacing="0">
        <thead>
            <tr>
                <th scope="col" class="short"><?php _e( 'Term ID', TD_TEXTDOMAIN ); ?></th>
                <th scope="col"><?php _e( 'Term', TD_TEXTDOMAIN ); ?></th>
                <th scope="col"><?php _e( 'Term Link', TD_TEXTDOMAIN ); ?></th>
            </tr>
        </thead>
        <tfoot>
            <tr>
                <th scope="col" class="short"><?php _e( 'Term ID', TD_TEXTDOMAIN ); ?></th>
                <th scope="col"><?php _e( 'Term', TD_TEXTDOMAIN ); ?></th>
                <th scope="col"><?php _e( 'Term Link', TD_TEXTDOMAIN ); ?></th>
            </tr>
        </tfoot>
        <tbody>
<?php
    $nonce = wp_create_nonce( 'td_delete_term' );
    
    $from = ( $cur_page - 1 ) * $terms_per_page;
    $to = $terms_per_page;
    $terms = $wpdb->get_results( 'SELECT * FROM ' . $wpdb->prefix . 'td_terms ORDER BY t_id DESC LIMIT ' . $from . ',' . $to );
    
    if ( is_array( $terms ) ) {
        foreach ( $terms as $term ) {
?>
            <tr id="term_<?php echo $term->t_id; ?>">
                <th scope="row" class="short"><?php echo $term->t_id; ?></th>
                <td>
                    <strong><?php echo stripcslashes( $term->t_term ); ?></strong>
                    <div class="row-actions">
                        <span class="edit"><a href="?action=td_edit_term&amp;term_id=<?php echo $term->t_id; ?>"><?php _e( 'Edit', TD_TEXTDOMAIN ); ?></a> | </span>
                        <span class="trash"><a href="?action=td_delete_term&amp;term_id=<?php echo $term->t_id; ?>&amp;_wpnonce=<?php echo $nonce; ?>"><?php _e( 'Delete', TD_TEXTDOMAIN ); ?></a></span>
                    </div>
                </td>
                <td><?php echo '<a href="' . $term->t_post_url . '" target="_blank">' . stripcslashes( $term->t_post_title ) . '</a>'; ?></td>
            </tr>
<?php
        }
    }
?>
        </tbody>
    </table>
    <?php echo $pagination; ?>
    <div style="display: none;" id="td_update_permalinks_dialog"><p><?php _e( 'Premalinks updated', TD_TEXTDOMAIN ); ?>: <span id="td_update_progress">0</span>%</p></div>
</div>
<?php        
    }
    
    /**
     * This methos creates pagination links for terms table
     *
     * @param int $terms_count number of the terms
     * @param int $cur_page current page number
     * @param int $terms_per_page number of the terms on each page
     * @return string 
     */
    public function pagination( $terms_count, $cur_page, $terms_per_page ) {
        $links = paginate_links( array(
            'base'         => @add_query_arg( 'term_page', '%#%' ),
            'format'       => '',
            'total'        => ceil( $terms_count / $terms_per_page ),
            'current'      => $cur_page,
            'show_all'     => false,
            'end_size'     => 3,
            'mid_size'     => 2,
            'prev_next'    => true,
            'prev_text'    => __( '&laquo; Previous', TD_TEXTDOMAIN ),
            'next_text'    => __( 'Next &raquo;', TD_TEXTDOMAIN ),
            'type'         => 'plain',
            'add_args'     => false,
        ));
        
        $html = '<div class="tablenav">';
        $html .= '<div class="tablenav-pages">';
        $html .= '<span class="pagination-links">' . $links . '</span>';
        $html .= '</div>';
        $html .= '</div>';
        return $html;
    }
}

$tdat = new TD_Admin_Terms();