<?php
/**
 * Plugin Name: WC Products Merger
 * Plugin URI: https://github.com/neevalex/wc-products-merger
 * Description: The WC Products Merger plugin allows users to merge variable woocomerce products into a single product, combining their variations, attributes, categories, tags, and other relevant data. This is particularly useful for store owners who want to consolidate similar products or clean up their product catalog.
 * Version: 1.0.0
 * Author: neevalex
 * Author URI: https://neevalex.com
 * Text Domain: wc-products-merger
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * WC requires at least: 5.0
 * WC tested up to: 8.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define plugin constants
define( 'WC_PRODUCTS_MERGER_VERSION', '1.0.0' );
define( 'WC_PRODUCTS_MERGER_PATH', plugin_dir_path( __FILE__ ) );
define( 'WC_PRODUCTS_MERGER_URL', plugin_dir_url( __FILE__ ) );

class WC_Products_Merger {
    
    private static $instance = null;
    private $bulk_action_handler;
    private $merge_page;

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->init_hooks();
    }

    private function init_hooks() {
        add_action( 'plugins_loaded', [ $this, 'load_dependencies' ] );
        add_action( 'admin_menu', [ $this, 'register_admin_menu' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );
    }

    public function load_dependencies() {
        // Check if WooCommerce is active
        if ( ! class_exists( 'WooCommerce' ) ) {
            add_action( 'admin_notices', [ $this, 'woocommerce_missing_notice' ] );
            return;
        }

        require_once WC_PRODUCTS_MERGER_PATH . 'includes/class-bulk-action-handler.php';
        require_once WC_PRODUCTS_MERGER_PATH . 'includes/class-merge-page.php';
        require_once WC_PRODUCTS_MERGER_PATH . 'includes/class-product-merger.php';

        $this->bulk_action_handler = new BulkActionHandler();
        $this->merge_page = new MergePage( $this->bulk_action_handler );
    }

    public function register_admin_menu() {
        add_submenu_page(
            null, // Hidden from menu
            __( 'Merge Products', 'wc-products-merger' ),
            __( 'Merge Products', 'wc-products-merger' ),
            'edit_products',
            'merge-selection',
            [ $this, 'render_merge_page' ]
        );
    }

    public function render_merge_page() {
        if ( isset( $this->merge_page ) ) {
            $this->merge_page->render();
        }
    }

    public function enqueue_admin_assets( $hook ) {
        if ( 'admin_page_merge-selection' !== $hook ) {
            return;
        }

        wp_enqueue_style(
            'wc-products-merger-admin',
            WC_PRODUCTS_MERGER_URL . 'admin/css/admin-styles.css',
            [],
            WC_PRODUCTS_MERGER_VERSION
        );

        wp_enqueue_script(
            'wc-products-merger-admin',
            WC_PRODUCTS_MERGER_URL . 'admin/js/admin-scripts.js',
            [ 'jquery' ],
            WC_PRODUCTS_MERGER_VERSION,
            true
        );

        wp_localize_script( 'wc-products-merger-admin', 'wcProductsMerger', [
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'nonce' => wp_create_nonce( 'wc_products_merger_nonce' ),
            'i18n' => [
                'selectProduct' => __( 'Please select a product to merge into.', 'wc-products-merger' ),
                'confirmMerge' => __( 'Are you sure you want to merge these products?', 'wc-products-merger' ),
            ]
        ] );
    }

    public function woocommerce_missing_notice() {
        ?>
        <div class="notice notice-error">
            <p><?php esc_html_e( 'WC Products Merger requires WooCommerce to be installed and active.', 'wc-products-merger' ); ?></p>
        </div>
        <?php
    }
}

// Initialize the plugin
WC_Products_Merger::get_instance();