<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class MergePage {
    private $bulk_action_handler;

    public function __construct( BulkActionHandler $bulk_action_handler ) {
        $this->bulk_action_handler = $bulk_action_handler;
        add_action( 'admin_post_wc_merge_products', [ $this, 'handle_merge_submission' ] );
    }

    public function render() {
        $product_ids = $this->get_product_ids();

        if ( empty( $product_ids ) ) {
            echo '<div class="wrap"><h1>' . esc_html__( 'Merge Products', 'wc-products-merger' ) . '</h1>';
            echo '<div class="notice notice-error"><p>' . esc_html__( 'No products selected. Please go back and select products to merge.', 'wc-products-merger' ) . '</p></div>';
            echo '<a href="' . esc_url( admin_url( 'edit.php?post_type=product' ) ) . '" class="button">' . esc_html__( 'Back to Products', 'wc-products-merger' ) . '</a>';
            echo '</div>';
            return;
        }

        $products = $this->get_products( $product_ids );
        ?>
        <div class="wrap wc-products-merger-wrap">
            <h1><?php esc_html_e( 'Merge Products', 'wc-products-merger' ); ?></h1>
            
            <p class="description">
                <?php esc_html_e( 'Select the primary product that will remain after merging. All other products will be merged into this one.', 'wc-products-merger' ); ?>
            </p>

            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" id="merge-products-form">
                <input type="hidden" name="action" value="wc_merge_products">
                <?php wp_nonce_field( 'wc_merge_products_action', 'wc_merge_products_nonce' ); ?>

                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th class="check-column"><?php esc_html_e( 'Primary', 'wc-products-merger' ); ?></th>
                            <th><?php esc_html_e( 'Image', 'wc-products-merger' ); ?></th>
                            <th><?php esc_html_e( 'Product Name', 'wc-products-merger' ); ?></th>
                            <th><?php esc_html_e( 'SKU', 'wc-products-merger' ); ?></th>
                            <th><?php esc_html_e( 'Price', 'wc-products-merger' ); ?></th>
                            <th><?php esc_html_e( 'Stock', 'wc-products-merger' ); ?></th>
                            <th><?php esc_html_e( 'Variations', 'wc-products-merger' ); ?></th>
                            <th><?php esc_html_e( 'Attributes', 'wc-products-merger' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $products as $product ) : ?>
                            <tr>
                                <td>
                                    <input type="radio" 
                                           name="primary_product" 
                                           value="<?php echo esc_attr( $product->get_id() ); ?>" 
                                           id="product-<?php echo esc_attr( $product->get_id() ); ?>">
                                    <input type="hidden" 
                                           name="merge_products[]" 
                                           value="<?php echo esc_attr( $product->get_id() ); ?>">
                                </td>
                                <td>
                                    <?php echo $product->get_image( [ 50, 50 ] ); ?>
                                </td>
                                <td>
                                    <label for="product-<?php echo esc_attr( $product->get_id() ); ?>">
                                        <strong><?php echo esc_html( $product->get_name() ); ?></strong>
                                    </label>
                                    <div class="row-actions">
                                        <a href="<?php echo esc_url( get_edit_post_link( $product->get_id() ) ); ?>" target="_blank">
                                            <?php esc_html_e( 'Edit', 'wc-products-merger' ); ?>
                                        </a> | 
                                        <a href="<?php echo esc_url( get_permalink( $product->get_id() ) ); ?>" target="_blank">
                                            <?php esc_html_e( 'View', 'wc-products-merger' ); ?>
                                        </a>
                                    </div>
                                </td>
                                <td><?php echo esc_html( $product->get_sku() ?: '—' ); ?></td>
                                <td><?php echo wp_kses_post( $product->get_price_html() ); ?></td>
                                <td>
                                    <?php 
                                    $stock = $product->get_stock_quantity();
                                    echo $stock !== null ? esc_html( $stock ) : esc_html( $product->get_stock_status() );
                                    ?>
                                </td>
                                <td>
                                    <?php 
                                    if ( $product->is_type( 'variable' ) ) {
                                        $variations = $product->get_children();
                                        echo esc_html( count( $variations ) );
                                    } else {
                                        echo '—';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php 
                                    $attributes = $product->get_attributes();
                                    if ( ! empty( $attributes ) ) {
                                        $attr_names = [];
                                        foreach ( $attributes as $attribute ) {
                                            $attr_names[] = wc_attribute_label( $attribute->get_name() );
                                        }
                                        echo esc_html( implode( ', ', $attr_names ) );
                                    } else {
                                        echo '—';
                                    }
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <p class="submit">
                    <button type="submit" class="button button-primary" id="merge-submit-btn">
                        <?php esc_html_e( 'Merge Selected Products', 'wc-products-merger' ); ?>
                    </button>
                    <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=product' ) ); ?>" class="button">
                        <?php esc_html_e( 'Cancel', 'wc-products-merger' ); ?>
                    </a>
                </p>
            </form>
        </div>
        <?php
    }

    private function get_product_ids() {
        // First check URL parameter
        if ( isset( $_GET['products'] ) ) {
            $ids = array_map( 'intval', explode( ',', sanitize_text_field( $_GET['products'] ) ) );
            return array_filter( $ids );
        }

        // Fallback to transient
        return $this->bulk_action_handler->get_selected_products();
    }

    private function get_products( $product_ids ) {
        $products = [];
        foreach ( $product_ids as $id ) {
            $product = wc_get_product( $id );
            if ( $product ) {
                $products[] = $product;
            }
        }
        return $products;
    }

    public function handle_merge_submission() {
        // Verify nonce
        if ( ! isset( $_POST['wc_merge_products_nonce'] ) || 
             ! wp_verify_nonce( $_POST['wc_merge_products_nonce'], 'wc_merge_products_action' ) ) {
            wp_die( __( 'Security check failed.', 'wc-products-merger' ) );
        }

        // Check permissions
        if ( ! current_user_can( 'edit_products' ) ) {
            wp_die( __( 'You do not have permission to perform this action.', 'wc-products-merger' ) );
        }

        // Validate input
        if ( ! isset( $_POST['primary_product'] ) || ! isset( $_POST['merge_products'] ) ) {
            wp_redirect( admin_url( 'edit.php?post_type=product&merge_error=no_selection' ) );
            exit;
        }

        $primary_product_id = intval( $_POST['primary_product'] );
        $merge_product_ids = array_map( 'intval', $_POST['merge_products'] );

        // Remove primary from merge list
        $merge_product_ids = array_diff( $merge_product_ids, [ $primary_product_id ] );

        if ( empty( $merge_product_ids ) ) {
            wp_redirect( admin_url( 'edit.php?post_type=product&merge_error=same_product' ) );
            exit;
        }

        // Perform the merge
        $merger = new ProductMerger();
        $result = $merger->merge( $primary_product_id, $merge_product_ids );

        // Clear transient
        $this->bulk_action_handler->clear_selected_products();

        // Redirect with result
        wp_redirect( admin_url( 'edit.php?post_type=product&merged_count=' . count( $merge_product_ids ) ) );
        exit;
    }
}