<?php
/**
 * Plugin Name:         Uncanny Groups name field
 * Description:         Add Group name field in a custom checkout
 * Author:              Uncanny Owl
 * Author URI:          https://www.uncannyowl.com
 * Plugin URI:          https://www.uncannyowl.com/uncanny-learndash-groups/
 * License:             GPLv3
 * License URI:         https://www.gnu.org/licenses/gpl-3.0.html
 * Version:             1.1
 * Requires at least:   5.0
 * Requires PHP:        7.0
 */

/******************************************************************/
/**************************GROUP FIELD*****************************/
/**************SNIPPET FOR CUSTOM WOO TEMPLATE*********************/
/******************************************************************/


/**
 * @param $fields
 *
 * @return mixed
 */
function woocommerce_checkout_field_editor( $fields ) {
	if ( class_exists( '\uncanny_learndash_groups\InitializePlugin' ) ) {
		$has_license_product      = uo_check_if_license_product_in_cart_override();
		$has_license_subscription = \uncanny_learndash_groups\WoocommerceLicenseSubscription::check_if_course_subscription_in_cart();
		$show_field               = true;
		if ( is_user_logged_in() ) {
			$user_id       = wp_get_current_user()->ID;
			$get_transient = get_transient( '_ulgm_user_buy_courses_' . $user_id . '_order' );
			if ( $get_transient ) {
				$show_field = false;
			}
		}
		if ( true === $has_license_subscription['status'] && function_exists( 'wcs_cart_contains_resubscribe' ) ) {
			$resub_exists = wcs_cart_contains_resubscribe( WC()->cart );
		} else {
			$resub_exists = '';
		}

		if ( ( true === $has_license_product['status'] || true === $has_license_subscription['status'] ) && ( true === $show_field && empty( $resub_exists ) ) ) {
			if ( key_exists( 'product_id', $has_license_product ) ) {
				$product_id = $has_license_product['product_id'];
			} elseif ( key_exists( 'product_id', $has_license_subscription ) ) {
				$product_id = $has_license_subscription['product_id'];
			}
			$group_name = get_post_meta( $product_id, '_group_name', true );
			$custom_buy = get_post_meta( $product_id, '_uo_custom_buy_product', true );
			if ( empty( $group_name ) ) {
				$group_name = '';
			}
			if ( 'yes' === $custom_buy ) {
				$classes = array( 'ulgm-woo-group-settings form-row-wide form-uo-hidden' );
				$class   = 'form-uo-hidden';
			} else {
				$classes = array( 'ulgm-woo-group-settings form-row-wide' );
				$class   = '';
			}

			$fields['billing']['ulgm_group_name'] = array(
				'type'         => 'text',
				'class'        => $classes,
				'label'        => esc_html__( 'Group Name', 'uncanny-learndash-groups' ),
				'maxlength'    => 80,
				'default'      => $group_name,
				'required'     => true,
				'autocomplete' => false,
			);
		}
	}

	return $fields;
}

add_filter( 'woocommerce_checkout_fields', 'woocommerce_checkout_field_editor' );

/**
 * @return array
 */
function uo_check_if_license_product_in_cart_override() {
	$items  = WC()->cart->get_cart();
	$return = array( 'status' => false );
	if ( $items ) {
		foreach ( $items as $item ) {
			$product = $item['data'];
			//$product = wc_get_product( $pid );
			if ( $product instanceof WC_Product && $product->is_type( 'license' ) ) {
				$return = array( 'status' => true, 'product_id' => $product->get_id() );
				break;
			}
		}
	}

	return $return;
}

/**
 * @param $error
 *
 * @return mixed
 */
function uo_groups_customize_wc_errors( $error ) {
	if ( strpos( $error, 'Billing Group Name' ) !== false ) {
		$error = str_replace( "Billing ", "", $error );
	}

	return $error;
}

add_filter( 'woocommerce_add_error', 'uo_groups_customize_wc_errors', 999 );

/**
 * @param $order_id
 */
function uo_update_order_meta( $order_id ) {
	if ( ! class_exists( '\uncanny_learndash_groups\InitializePlugin' ) ) {
		return;
	}
	if ( isset( $_POST['ulgm_group_name'] ) && ! empty( trim( $_POST['ulgm_group_name'] ) ) ) {
		update_post_meta( $order_id, \uncanny_learndash_groups\SharedFunctions::$group_name_field, sanitize_text_field( $_POST['ulgm_group_name'] ) );
	} elseif ( isset( $_POST['billing']['ulgm_group_name'] ) && ! empty( trim( $_POST['billing']['ulgm_group_name'] ) ) ) {
		update_post_meta( $order_id, \uncanny_learndash_groups\SharedFunctions::$group_name_field, sanitize_text_field( $_POST['billing']['ulgm_group_name'] ) );
	}
}

add_action( 'woocommerce_checkout_update_order_meta', 'uo_update_order_meta' );
