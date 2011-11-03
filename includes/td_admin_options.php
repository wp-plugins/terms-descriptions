<?php
/**
 * This class creates Options page in Terms menu
 */
class TD_Admin_Options {
    private $page = '';
    
    /**
     * Constuctor. Sets the actions handlers.
     */
    public function __construct() {
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
        add_action( 'admin_init', array( $this, 'admin_init' ) );
    }
    
    /**
     * Creates option page.
     */
    public function admin_menu() {
        add_submenu_page( TD_TEXTDOMAIN, __( 'Options', TD_TEXTDOMAIN)
                , __( 'Options', TD_TEXTDOMAIN), 'manage_options', 'td-options'
                , array( $this, 'options_page' ));
    }
    
    /**
     * Register plugin options
     */
    public function admin_init() {
        register_setting( 'td_settings_options', 'td_options', array( $this, 'validate_options' ) );
    }
    
    /**
     * Options page HTML
     */
    public function options_page() {
        //reading current options values
        $options = get_option('td_options');
        //if there is no options using default values
        if ( false === $options ) {
            $terms_class = new TD_Admin_Terms();
            $options = $terms_class->get_default_options();
            add_option( 'td_options', $options );
        }
?>
<div class="wrap">
	<h2><?php _e( 'Options', TD_TEXTDOMAIN); ?></h2>
    <?php if ( isset( $_GET[ 'settings-updated' ] ) && 'true' === $_GET[ 'settings-updated' ] ) { ?>
        <div id="setting-error-settings_updated" class="updated settings-error"> 
            <p><strong><?php _e( 'Options saved', TD_TEXTDOMAIN ); ?></strong></p>
        </div>
    <?php } ?>
    <form method="post" action="options.php">
        <?php settings_fields('td_settings_options'); ?>
        <table class="form-table">
            <tbody>
                <tr valign="middle">
                    <th scope="row"><?php _e( 'Convert terms', TD_TEXTDOMAIN ); ?></th>
                    <td>
                        <label><input name="td_options[convert_in_posts]" type="checkbox" id="convert_in_posts" <?php checked( $options[ 'convert_in_posts' ], 'on' ); ?> /> <?php _e( 'in posts', TD_TEXTDOMAIN ); ?></label><br />
                        <label><input name="td_options[convert_in_comments]" type="checkbox" id="convert_in_comments" <?php checked( $options[ 'convert_in_comments' ], 'on' ); ?> /> <?php _e( 'in comments', TD_TEXTDOMAIN ); ?></label>
                    </td>
                </tr>
                <tr valign="middle">
                    <th scope="row"><?php _e( 'Convert first', TD_TEXTDOMAIN ); ?></th>
                    <td>
                        <input name="td_options[convert_first_n_terms]" type="text" id="convert_first_n_terms" value="<?php echo $options[ 'convert_first_n_terms' ]; ?>" class="small-text" /> <?php _e( 'term occurrences.', TD_TEXTDOMAIN ); ?>
                        <span class="description"><?php _e( 'Set "-1" if you want to convert all terms.', TD_TEXTDOMAIN ); ?></span>
                    </td>
                </tr>
                <tr valign="middle">
                    <th scope="row"><?php _e( 'Add CSS class', TD_TEXTDOMAIN ); ?></th>
                    <td>
                        <input name="td_options[class]" type="text" id="class" value="<?php echo $options[ 'class' ]; ?>" class="small-text" /> <?php _e( 'to terms links.', TD_TEXTDOMAIN ); ?>
                    </td>
                </tr>
                <tr valign="middle">
                    <th scope="row"><?php _e( 'Convert terms only on single pages', TD_TEXTDOMAIN ); ?></th>
                    <td>
                        <input name="td_options[convert_only_single]" type="checkbox" id="convert_only_single" <?php checked( $options[ 'convert_only_single' ], 'on' ); ?> />
                        <span class="description"><?php _e( 'Terms will not be converted on home, categories and archives pages.', TD_TEXTDOMAIN ); ?></span>
                    </td>
                </tr>
                <tr valign="middle">
                    <th scope="row"><?php _e( 'Show', TD_TEXTDOMAIN ); ?></th>
                    <td>
                        <select name="td_options[terms_per_page]" id="terms_per_page">
                            <option value="10" <?php selected( $options[ 'terms_per_page' ], 10); ?>>10</option>
                            <option value="20" <?php selected( $options[ 'terms_per_page' ], 20); ?>>20</option>
                            <option value="50" <?php selected( $options[ 'terms_per_page' ], 50); ?>>50</option>
                            <option value="100" <?php selected( $options[ 'terms_per_page' ], 100); ?>>100</option>
                            <option value="200" <?php selected( $options[ 'terms_per_page' ], 200); ?>>200</option>
                            <option value="500" <?php selected( $options[ 'terms_per_page' ], 500); ?>>500</option>
                        </select>
                        <span class="description"><?php _e( 'terms on a page (in admin area)', TD_TEXTDOMAIN ); ?></span>
                    </td>
                </tr>
                <tr valign="middle">
                    <th scope="row"><?php _e( 'Parser', TD_TEXTDOMAIN ); ?></th>
                    <td>
                        <label><input type="radio" name="td_options[parser]" id="simple_parser" value="simple_parser"
                            <?php checked( $options[ 'parser' ], 'simple_parser' ); ?> />
                            <?php _e( 'Simple parser', TD_TEXTDOMAIN ); ?></label><br />
                        <label><input type="radio" name="td_options[parser]" id="quotes_parser" value="quotes_parser"
                            <?php checked( $options[ 'parser' ], 'quotes_parser' ); ?> />
                            <?php _e( 'Simple parser with quotes support', TD_TEXTDOMAIN ); ?></label>
                    </td>
                </tr>
            </tbody>
        </table>
        <p class="submit">
            <input type="submit" name="submit" id="submit" class="button-primary" value="<?php _e( 'Update', TD_TEXTDOMAIN ); ?>">
        </p>
    </form>
</div>
<?php        
    }
    
    /**
     * Options validation
     *
     * @param array $input new options values
     * @return array validated options
     */
    public function validate_options( $input ) {
        //reading current values
        $old_options = get_option( 'td_options' );
        //checking new values
        if ( (int)$input[ 'convert_first_n_terms' ] <= 0 ) {
            $input[ 'convert_first_n_terms' ] = '-1';
        }
        else {
            $input[ 'convert_first_n_terms' ] = (int)$input[ 'convert_first_n_terms' ];
        }
        if ( (int)$input[ 'terms_per_page' ] <= 0 ) {
            $input[ 'terms_per_page' ] = 20;
        }
        if ( !isset( $input[ 'convert_in_posts' ] ) ) {
            $input[ 'convert_in_posts' ] = false;
        }
        if ( !isset( $input[ 'convert_in_comments' ] ) ) {
            $input[ 'convert_in_comments' ] = false;
        }
        if ( !isset( $input[ 'convert_only_single' ] ) ) {
            $input[ 'convert_only_single' ] = false;
        }
        if ( !isset( $input[ 'parser' ] ) ) {
            $input[ 'parser' ] = 'simple_parser';
        }
        if ( false !== $old_options ) {
            return array_merge( $old_options, $input );
        }
        else {
            return $input;
        }
    }
}

$tdao = new TD_Admin_Options();