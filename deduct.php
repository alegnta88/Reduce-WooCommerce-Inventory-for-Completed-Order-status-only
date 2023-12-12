<?php
/*
Plugin Name: BACS Stock Reduction
Description: Custom stock reduction behavior for BACS orders.
Version: 1.0
Author: Alegnta Lolamo
*/

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Filter for On-Hold Orders
add_filter( 'woocommerce_can_reduce_order_stock', 'wcs_do_not_reduce_onhold_stock', 10, 2 );
function wcs_do_not_reduce_onhold_stock( $reduce_stock, $order ) {
    if ( $order->has_status( 'on-hold' ) && $order->get_payment_method() == 'bacs' ) {
        $reduce_stock = false;
    }
    return $reduce_stock;
}

// Action Hooks for Processing and Completed Orders
add_action( 'woocommerce_order_status_processing', 'reduce_stock_on_bacs_order_status_change', 10, 1 );
add_action( 'woocommerce_order_status_completed', 'reduce_stock_on_bacs_order_status_change', 10, 1 );

// Function to Reduce Stock on BACS Orders
function reduce_stock_on_bacs_order_status_change( $order_id ) {
    // Log the order ID for debugging
    error_log('Order ID: ' . $order_id);

    // Get the order object
    $order = wc_get_order( $order_id );

    // Log the order status for debugging
    error_log('Order Status: ' . $order->get_status());

    // Check if the order was paid using BACS
    if ( 'bacs' == $order->get_payment_method() ) {
        // Log for debugging
        error_log('BACS payment method detected.');

        // Check if the stock reduction has already been done for this order
        $stock_reduction_done = get_post_meta( $order_id, '_stock_reduction_done', true );
        if ( ! $stock_reduction_done ) {
            // Log for debugging
            error_log('Stock reduction not done yet.');

            // Iterate over the order items
            foreach( $order->get_items() as $item_id => $item ) {
                // Reduce stock for each item
                $product = $item->get_product();
                $qty = $item->get_quantity();

                // Log for debugging
                error_log('Reducing stock for Product ID: ' . $product->get_id() . ', Quantity: ' . $qty);

                // Use wc_reduce_stock_levels instead of reduce_stock
                wc_reduce_stock_levels( $product->get_id(), $qty );
            }

            // Mark the stock reduction as done for this order
            update_post_meta( $order_id, '_stock_reduction_done', true );

            // Log for debugging
            error_log('Stock reduction marked as done.');
        }
    }
}