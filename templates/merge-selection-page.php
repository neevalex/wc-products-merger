<?php
// This file renders the merge selection page for the selected products.

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

global $wpdb;

// Fetch selected product IDs from the request.
$selected_products = isset( $_GET['selected_products'] ) ? explode( ',', sanitize_text_field( $_GET['selected_products'] ) ) : [];

// Fetch product details for the selected IDs.
$product_details = [];
if ( ! empty( $selected_products ) ) {
    foreach ( $selected_products as $product_id ) {
        $product = wc_get_product( $product_id );
        if ( $product ) {
            $product_details[] = $product;
        }
    }
}

?>

<div class="wrap">
    <h1><?php esc_html_e( 'Merge Selected Products', 'wc-products-merger' ); ?></h1>

    <?php if ( ! empty( $product_details ) ) : ?>
        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
            <input type="hidden" name="action" value="process_product_merge">
            <input type="hidden" name="selected_products" value="<?php echo esc_attr( implode( ',', $selected_products ) ); ?>">

            <table class="form-table">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Product ID', 'wc-products-merger' ); ?></th>
                        <th><?php esc_html_e( 'Product Name', 'wc-products-merger' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $product_details as $product ) : ?>
                        <tr>
                            <td><?php echo esc_html( $product->get_id() ); ?></td>
                            <td><?php echo esc_html( $product->get_name() ); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <p class="submit">
                <input type="submit" class="button button-primary" value="<?php esc_attr_e( 'Submit', 'wc-products-merger' ); ?>">
            </p>
        </form>
    <?php else : ?>
        <p><?php esc_html_e( 'No products selected.', 'wc-products-merger' ); ?></p>
    <?php endif; ?>
</div>