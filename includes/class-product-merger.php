<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class ProductMerger {

    /**
     * Merge products into the primary product
     *
     * @param int   $primary_id   The primary product ID
     * @param array $merge_ids    Array of product IDs to merge
     * @return bool
     */
    public function merge( $primary_id, $merge_ids ) {
        $primary_product = wc_get_product( $primary_id );

        if ( ! $primary_product ) {
            return false;
        }

        foreach ( $merge_ids as $merge_id ) {
            $merge_product = wc_get_product( $merge_id );

            if ( ! $merge_product ) {
                continue;
            }

            // Merge product data
            $this->merge_product_data( $primary_product, $merge_product );

            // Delete or trash the merged product
            $this->remove_merged_product( $merge_id );
        }

        // Save the primary product
        $primary_product->save();

        //redirect to the primary product edit page
        wp_safe_redirect( admin_url( 'post.php?post=' . $primary_id . '&action=edit' ) );
        exit;
        // do_action( 'wc_products_merged', $primary_id, $merge_ids );

        // return true;
    }

    /**
     * Merge data from source product into target product
     */
    private function merge_product_data( $target, $source ) {
        // Merge gallery images
        $this->merge_gallery_images( $target, $source );

        // Merge categories
        $this->merge_taxonomies( $target->get_id(), $source->get_id(), 'product_cat' );

        // Merge tags
        $this->merge_taxonomies( $target->get_id(), $source->get_id(), 'product_tag' );

        //make sure that the parent product has all attributes as the merged product variations
        $target_taxonomies = $target->get_attribute_taxonomies();
        $source_taxonomies = $source->get_attribute_taxonomies();

        foreach ( $source_taxonomies as $taxonomy ) {
            if ( ! in_array( $taxonomy, $target_taxonomies ) ) {
                $this->merge_taxonomies( $target->get_id(), $source->get_id(), 'pa_' . $taxonomy->attribute_name );
            }
        }

        // Merge attributes if both are variable products
        if ( $target->is_type( 'variable' ) && $source->is_type( 'variable' ) ) {
            $this->merge_variations( $target, $source );
        }

        // Allow custom merge actions
        do_action( 'wc_products_merger_merge_data', $target, $source );
    }

    /**
     * Merge gallery images from source to target
     */
    private function merge_gallery_images( $target, $source ) {
        $target_gallery = $target->get_gallery_image_ids();
        $source_gallery = $source->get_gallery_image_ids();

        // Add source main image to gallery if different
        $source_image = $source->get_image_id();
        if ( $source_image && $source_image !== $target->get_image_id() ) {
            $target_gallery[] = $source_image;
        }

        // Merge galleries
        $merged_gallery = array_unique( array_merge( $target_gallery, $source_gallery ) );

        $target->set_gallery_image_ids( $merged_gallery );
    }

    /**
     * Merge taxonomy terms from source to target
     */
    private function merge_taxonomies( $target_id, $source_id, $taxonomy ) {
        $source_terms = wp_get_object_terms( $source_id, $taxonomy, [ 'fields' => 'ids' ] );

        if ( ! empty( $source_terms ) && ! is_wp_error( $source_terms ) ) {
            wp_set_object_terms( $target_id, $source_terms, $taxonomy, true );
        }
    }

    /**
     * Merge variations from source variable product to target
     */
    private function merge_variations( $target, $source ) {
        $source_variations = $source->get_children();

        foreach ( $source_variations as $variation_id ) {
            // Update variation parent
            wp_update_post( [
                'ID' => $variation_id,
                'post_parent' => $target->get_id()
            ] );
        }

        // Merge attributes
        $target_attributes = $target->get_attributes();
        $source_attributes = $source->get_attributes();

        foreach ( $source_attributes as $key => $attribute ) {
            if ( ! isset( $target_attributes[ $key ] ) ) {
                $target_attributes[ $key ] = $attribute;
            } else {
                // Merge attribute options
                $target_options = $target_attributes[ $key ]->get_options();
                $source_options = $attribute->get_options();
                $merged_options = array_unique( array_merge( $target_options, $source_options ) );
                $target_attributes[ $key ]->set_options( $merged_options );
            }
        }

        $target->set_attributes( $target_attributes );
    }

    /**
     * Remove the merged product
     */
    private function remove_merged_product( $product_id ) {
        // Move to trash instead of permanent delete
        wp_trash_post( $product_id );

        // Or permanently delete:
        // wp_delete_post( $product_id, true );
    }
}