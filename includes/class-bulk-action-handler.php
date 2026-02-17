<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class BulkActionHandler {
    private $selected_products = [];

    public function __construct() {
        // Register bulk action in the dropdown
        add_filter( 'bulk_actions-edit-product', [ $this, 'register_bulk_action' ] );
        
        // Handle the bulk action
        add_filter( 'handle_bulk_actions-edit-product', [ $this, 'handle_bulk_action' ], 10, 3 );
        
        // Display admin notice after processing
        add_action( 'admin_notices', [ $this, 'bulk_action_admin_notice' ] );
    }

    /**
     * Register the bulk action in the dropdown
     */
    public function register_bulk_action( $bulk_actions ) {
        $bulk_actions['merge_products'] = __( 'Merge Products', 'wc-products-merger' );
        return $bulk_actions;
    }

    /**
     * Handle the bulk action when submitted
     */
    public function handle_bulk_action( $redirect_to, $doaction, $post_ids ) {
        if ( $doaction !== 'merge_products' ) {
            return $redirect_to;
        }

        if ( empty( $post_ids ) ) {
            return $redirect_to;
        }

        // Store selected products in transient for the merge page
        set_transient( 'wc_merge_selected_products', $post_ids, HOUR_IN_SECONDS );

        // Redirect to the merge selection page
        $redirect_to = admin_url( 'admin.php?page=merge-selection&products=' . implode( ',', $post_ids ) );
        
        return $redirect_to;
    }

    /**
     * Get selected products from transient
     */
    public function get_selected_products() {
        $products = get_transient( 'wc_merge_selected_products' );
        return $products ? $products : [];
    }

    /**
     * Clear selected products
     */
    public function clear_selected_products() {
        delete_transient( 'wc_merge_selected_products' );
    }

    /**
     * Display admin notice
     */
    public function bulk_action_admin_notice() {
        if ( ! empty( $_REQUEST['merged_count'] ) ) {
            $count = intval( $_REQUEST['merged_count'] );
            printf(
                '<div class="notice notice-success is-dismissible"><p>' .
                _n(
                    '%s product merged successfully.',
                    '%s products merged successfully.',
                    $count,
                    'wc-products-merger'
                ) . '</p></div>',
                $count
            );
        }
    }
}