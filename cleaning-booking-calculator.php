<?php
/**
 * Plugin Name: Cleaning Booking Calculator
 * Plugin URI:  https://rokonline.com/
 * Description: Custom Cleaning Service Booking & Live Quote Calculator with admin entry management, email alerts, and custom database storage.
 * Version:     1.2.0
 * Author:      Rok Online
 * Author URI:  https://rokonline.com/
 * Text Domain: cleaning-booking-calculator
 * License:     GPL-2.0+
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Plugin Constants
define( 'CBC_VERSION', '1.2.0' );
define( 'CBC_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'CBC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'CBC_DEFAULT_SERVICES_URL', 'https://cleaniqfoxcities.com/services/' );

// Include Core Classes
require_once CBC_PLUGIN_DIR . 'includes/class-pricing.php';
require_once CBC_PLUGIN_DIR . 'includes/class-email-template.php';
require_once CBC_PLUGIN_DIR . 'includes/class-db-handler.php';
require_once CBC_PLUGIN_DIR . 'includes/class-admin-settings.php';
require_once CBC_PLUGIN_DIR . 'includes/class-form-handler.php';

/**
 * Main Plugin Bootstrap Class
 */
final class Cleaning_Booking_Calculator {

    private static $instance = null;

    public static function instance() {
        if ( is_null( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        register_activation_hook( __FILE__, [ 'CBC_DB_Handler', 'create_table' ] );

        // Initialize components
        CBC_Admin_Settings::init();
        CBC_Form_Handler::init();

        // Register Scripts & Styles
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_frontend_assets' ] );

        // Shortcode
        add_shortcode( 'cleaning_booking_form', [ $this, 'render_booking_form' ] );
    }

    public function enqueue_frontend_assets() {
        wp_register_style(
            'cbc-style',
            CBC_PLUGIN_URL . 'assets/css/style.css',
            [],
            CBC_VERSION
        );

        wp_register_script(
            'cbc-calculation',
            CBC_PLUGIN_URL . 'assets/js/calculation.js',
            [],
            CBC_VERSION,
            true
        );
    }

    public function render_booking_form() {
        wp_enqueue_style( 'cbc-style' );
        wp_enqueue_script( 'cbc-calculation' );

        // Same pricing file the server uses to re-calculate the total on submit.
        wp_add_inline_script(
            'cbc-calculation',
            'window.CBC_PRICING = ' . wp_json_encode( CBC_Pricing::get_config() ) . ';',
            'before'
        );

        ob_start();
        include CBC_PLUGIN_DIR . 'views/frontend-form.php';
        return ob_get_clean();
    }
}

function cbc_run_plugin() {
    return Cleaning_Booking_Calculator::instance();
}
cbc_run_plugin();
