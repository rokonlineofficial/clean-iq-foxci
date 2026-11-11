<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CBC_Admin_Settings {

    public static function init() {
        add_action( 'admin_menu', [ __CLASS__, 'register_admin_menus' ] );
        add_action( 'admin_init', [ __CLASS__, 'register_settings' ] );
        add_action( 'admin_post_cbc_update_status', [ __CLASS__, 'handle_status_update' ] );
        add_action( 'admin_post_cbc_delete_entry', [ __CLASS__, 'handle_entry_deletion' ] );
    }

    public static function register_admin_menus() {
        add_menu_page(
            __( 'Booking Requests', 'cleaning-booking-calculator' ),
            __( 'Booking Requests', 'cleaning-booking-calculator' ),
            'manage_options',
            'cbc-entries',
            [ __CLASS__, 'render_entries_page' ],
            'dashicons-clipboard',
            26
        );

        add_submenu_page(
            'cbc-entries',
            __( 'All Entries', 'cleaning-booking-calculator' ),
            __( 'Entries', 'cleaning-booking-calculator' ),
            'manage_options',
            'cbc-entries',
            [ __CLASS__, 'render_entries_page' ]
        );

        add_submenu_page(
            'cbc-entries',
            __( 'Calculator Settings', 'cleaning-booking-calculator' ),
            __( 'Settings', 'cleaning-booking-calculator' ),
            'manage_options',
            'cbc-settings',
            [ __CLASS__, 'render_settings_page' ]
        );
    }

    public static function get_services_url() {
        $url = get_option( 'cbc_services_url', '' );
        return $url ? $url : CBC_DEFAULT_SERVICES_URL;
    }

    public static function get_terms_url() {
        $url = get_option( 'cbc_terms_url', '' );
        return $url ? $url : '#terms-and-conditions';
    }

    public static function register_settings() {
        register_setting( 'cbc_settings_group', 'cbc_admin_email', [
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_email',
            'default'           => get_option( 'admin_email' )
        ] );

        register_setting( 'cbc_settings_group', 'cbc_services_url', [
            'type'              => 'string',
            'sanitize_callback' => 'esc_url_raw',
            'default'           => CBC_DEFAULT_SERVICES_URL
        ] );

        register_setting( 'cbc_settings_group', 'cbc_terms_url', [
            'type'              => 'string',
            'sanitize_callback' => 'esc_url_raw',
            'default'           => ''
        ] );

        register_setting( 'cbc_settings_group', 'cbc_email_logo_url', [
            'type'              => 'string',
            'sanitize_callback' => 'esc_url_raw',
            'default'           => ''
        ] );

        register_setting( 'cbc_settings_group', 'cbc_redirect_url', [
            'type'              => 'string',
            'sanitize_callback' => 'esc_url_raw',
            'default'           => ''
        ] );
    }

    public static function render_entries_page() {
        include CBC_PLUGIN_DIR . 'views/admin-entries-table.php';
    }

    public static function render_settings_page() {
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Booking Calculator Settings', 'cleaning-booking-calculator' ); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields( 'cbc_settings_group' );
                do_settings_sections( 'cbc_settings_group' );
                ?>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row"><?php esc_html_e( 'Admin Notification Email', 'cleaning-booking-calculator' ); ?></th>
                        <td>
                            <input type="email" name="cbc_admin_email" class="regular-text" value="<?php echo esc_attr( get_option( 'cbc_admin_email', get_option( 'admin_email' ) ) ); ?>" required />
                            <p class="description"><?php esc_html_e( 'Incoming quote requests will be sent to this email address.', 'cleaning-booking-calculator' ); ?></p>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><?php esc_html_e( 'Form Redirect URL (Optional)', 'cleaning-booking-calculator' ); ?></th>
                        <td>
                            <input type="url" name="cbc_redirect_url" class="regular-text" value="<?php echo esc_attr( get_option( 'cbc_redirect_url', '' ) ); ?>" placeholder="https://example.com/thank-you" />
                            <p class="description"><?php esc_html_e( 'Leave empty to display default inline success notification.', 'cleaning-booking-calculator' ); ?></p>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><?php esc_html_e( 'Detailed Service Description URL', 'cleaning-booking-calculator' ); ?></th>
                        <td>
                            <input type="url" name="cbc_services_url" class="regular-text" value="<?php echo esc_attr( self::get_services_url() ); ?>" placeholder="<?php echo esc_attr( CBC_DEFAULT_SERVICES_URL ); ?>" />
                            <p class="description"><?php esc_html_e( 'Target of the "Click HERE for detailed service description" link on the quote form. Leave empty to use the default services page.', 'cleaning-booking-calculator' ); ?></p>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><?php esc_html_e( 'Terms and Conditions URL (Optional)', 'cleaning-booking-calculator' ); ?></th>
                        <td>
                            <input type="url" name="cbc_terms_url" class="regular-text" value="<?php echo esc_attr( get_option( 'cbc_terms_url', '' ) ); ?>" placeholder="https://example.com/terms" />
                            <p class="description"><?php esc_html_e( 'Link target for the "Terms and Condition" text next to the agreement checkbox.', 'cleaning-booking-calculator' ); ?></p>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><?php esc_html_e( 'Email Logo URL (Optional)', 'cleaning-booking-calculator' ); ?></th>
                        <td>
                            <input type="url" name="cbc_email_logo_url" class="regular-text" value="<?php echo esc_attr( get_option( 'cbc_email_logo_url', '' ) ); ?>" placeholder="https://example.com/logo.png" />
                            <p class="description"><?php esc_html_e( 'Shown in the header of the booking emails (use a white or light logo, it sits on the navy header). Leave empty to show the site name.', 'cleaning-booking-calculator' ); ?></p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>

            <hr>
            <h2><?php esc_html_e( 'Shortcode Usage', 'cleaning-booking-calculator' ); ?></h2>
            <p><?php esc_html_e( 'Paste this shortcode anywhere inside your pages, posts, or page builders:', 'cleaning-booking-calculator' ); ?></p>
            <code>[cleaning_booking_form]</code>
        </div>
        <?php
    }

    public static function handle_status_update() {
        if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'cbc_update_status_nonce' ) ) {
            wp_die( esc_html__( 'Unauthorized request', 'cleaning-booking-calculator' ) );
        }

        $allowed_statuses = [ 'Pending', 'Approved', 'Rejected' ];
        $id               = isset( $_POST['entry_id'] ) ? absint( $_POST['entry_id'] ) : 0;
        $status           = isset( $_POST['status'] ) ? sanitize_text_field( wp_unslash( $_POST['status'] ) ) : 'Pending';

        if ( $id > 0 && in_array( $status, $allowed_statuses, true ) ) {
            CBC_DB_Handler::update_status( $id, $status );
        }

        wp_safe_redirect( wp_get_referer() ? wp_get_referer() : admin_url( 'admin.php?page=cbc-entries' ) );
        exit;
    }

    public static function handle_entry_deletion() {
        if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'cbc_delete_entry_nonce' ) ) {
            wp_die( esc_html__( 'Unauthorized request', 'cleaning-booking-calculator' ) );
        }

        $id = isset( $_POST['entry_id'] ) ? absint( $_POST['entry_id'] ) : 0;
        if ( $id > 0 ) {
            CBC_DB_Handler::delete_entry( $id );
        }

        wp_safe_redirect( wp_get_referer() ? wp_get_referer() : admin_url( 'admin.php?page=cbc-entries' ) );
        exit;
    }
}