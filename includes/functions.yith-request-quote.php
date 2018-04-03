<?php
if ( !defined( 'ABSPATH' ) || ! defined( 'YITH_YWRAQ_VERSION' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Implements helper functions for YITH Woocommerce Request A Quote
 *
 * @package YITH Woocommerce Request A Quote
 * @since   1.0.0
 * @author  Yithemes
 */

if ( !function_exists( 'yith_ywraq_locate_template' ) ) {
	/**
	 * Locate the templates and return the path of the file found
	 *
	 * @param string $path
	 * @param array  $var
	 *
	 * @return string
	 * @since 1.0.0
	 */
	function yith_ywraq_locate_template( $path, $var = NULL ) {

		if ( function_exists( 'WC' ) ) {
			$woocommerce_base = WC()->template_path();
		}
		elseif ( defined( 'WC_TEMPLATE_PATH' ) ) {
			$woocommerce_base = WC_TEMPLATE_PATH;
		}
		else {
			$woocommerce_base = WC()->plugin_path() . '/templates/';
		}

		$template_woocommerce_path = $woocommerce_base . $path;
		$template_path             = '/' . $path;
		$plugin_path               = YITH_YWRAQ_DIR . 'templates/' . $path;

		$located = locate_template( array(
			$template_woocommerce_path, // Search in <theme>/woocommerce/
			$template_path,             // Search in <theme>/
			$plugin_path                // Search in <plugin>/templates/
		) );

		if ( !$located && file_exists( $plugin_path ) ) {
			return apply_filters( 'yith_ywraq_locate_template', $plugin_path, $path );
		}

		return apply_filters( 'yith_ywraq_locate_template', $located, $path );
	}
}

if ( !function_exists( 'yith_ywraq_get_product_meta' ) ) {
	/**
	 * Return the product meta in a varion product
	 *
	 * @param array $raq
	 * @param bool  $echo
	 *
	 * @return string
	 * @since 1.0.0
	 */
	function yith_ywraq_get_product_meta( $raq, $echo = true, $show_price = true ) {

		$item_data = array();

		// Variation data
		if ( !empty( $raq['variation_id'] ) && is_array( $raq['variations'] ) ) {

			foreach ( $raq['variations'] as $name => $value ) {

				if ( '' === $value ) {
					continue;
				}

				$taxonomy = wc_attribute_taxonomy_name( str_replace( 'attribute_pa_', '', urldecode( $name ) ) );

				// If this is a term slug, get the term's nice name
				if ( taxonomy_exists( $taxonomy ) ) {
					$term = get_term_by( 'slug', $value, $taxonomy );
					if ( !is_wp_error( $term ) && $term && $term->name ) {
						$value = $term->name;
					}
					$label = wc_attribute_label( $taxonomy );

				}else {
					if( strpos($name, 'attribute_') !== false ) {
						$custom_att = str_replace( 'attribute_', '', $name );
						if ( $custom_att != '' ) {
							$label = wc_attribute_label( $custom_att );
						}
						else {
							$label = apply_filters( 'woocommerce_attribute_label', wc_attribute_label( $name ), $name );
							// $label = $name;
						}
					}
				}

				$item_data[] = array(
					'key'   => $label,
					'value' => $value
				);


			}
		}

		$item_data = apply_filters( 'ywraq_item_data', $item_data, $raq, $show_price );

		$carrets = apply_filters('ywraq_meta_data_carret', "\n" );

		$out = $echo ? $carrets : "";

		// Output flat or in list format
		if ( sizeof( $item_data ) > 0 ) {
			foreach ( $item_data as $data ) {
				if ( $echo ) {
					$out .= esc_html(  $data['key'] ) . ': ' . wp_kses_post( $data['value'] ) . $carrets;
				}
				else {
					$out .= ' - ' . esc_html( $data['key'] ) . ': ' . wp_kses_post( $data['value'] ) . ' ';
				}
			}
		}

		if ( $echo ) {
			echo $out;
		}else{
			return $out;
		}

		return '';
	}


}

if ( !function_exists( 'yith_ywraq_get_product_meta_from_order_item' ) ) {
	/**
	 * @param      $item_meta
	 * @param bool $echo
	 *
	 * @return string
	 */
	function yith_ywraq_get_product_meta_from_order_item( $item_meta, $echo = true ) {
		/**
		 * Return the product meta in a varion product
		 *
		 * @param array $raq
		 * @param bool  $echo
		 *
		 * @return string
		 * @since 1.0.0
		 */
		$item_data = array();

		// Variation data
		if ( !empty( $item_meta ) ) {

			foreach ( $item_meta as $name => $val ) {

				if ( empty( $val ) ) {
					continue;
				}

				if ( in_array( $name, apply_filters( 'woocommerce_hidden_order_itemmeta', array(
					'_qty',
					'_tax_class',
					'_product_id',
					'_variation_id',
					'_line_subtotal',
					'_line_subtotal_tax',
					'_line_total',
					'_line_tax',
					'_parent_line_item_id',
					'_commission_id',
					'_woocs_order_rate',
					'_woocs_order_base_currency',
					'_woocs_order_currency_changed_mannualy'
				) ) ) ) {
					continue;
				}

				// Skip serialised meta
				if ( is_serialized( $val[0] ) ) {
					continue;
				}


				$taxonomy = $name;

				// If this is a term slug, get the term's nice name
				if ( taxonomy_exists( $taxonomy ) ) {
					$term = get_term_by( 'slug', $val[0], $taxonomy );
					if ( !is_wp_error( $term ) && $term && $term->name ) {
						$value = $term->name;
					}else {
						$value = $val[0];
					}
					$label = wc_attribute_label( $taxonomy );

				} else {
					$label =  apply_filters( 'woocommerce_attribute_label', wc_attribute_label( $name ), $name );
					$value = $val[0];
				}

				if( $label!= '' && $val[0] != ''){
					$item_data[] = array(
						'key'   => $label,
						'value' => $value
					);
				}
			}
		}


		$item_data = apply_filters( 'ywraq_item_data', $item_data );
		$out = "";
		// Output flat or in list format
		if ( sizeof( $item_data ) > 0 ) {
			foreach ( $item_data as $data ) {
				if ( $echo ) {
					echo esc_html( $data['key'] ) . ': ' . wp_kses_post( $data['value'] ) . "\n";
				}
				else {
					$out .= ' - ' . esc_html( $data['key'] ) . ': ' . wp_kses_post( $data['value'] ) . ' ';
				}
			}
		}

		return $out;

	}
}


if ( !function_exists( 'yith_ywraq_notice_count' ) ) {
	/****** NOTICES *****/
	/**
	 * Get the count of notices added, either for all notices (default) or for one
	 * particular notice type specified by $notice_type.
	 *
	 * @since 2.1
	 *
	 * @param string $notice_type The name of the notice type - either error, success or notice. [optional]
	 *
	 * @return int
	 */
	function yith_ywraq_notice_count( $notice_type = '' ) {
		$session      = YITH_Request_Quote()->session_class;
		$notice_count = 0;
		$all_notices  = $session->get( 'yith_ywraq_notices', array() );

		if ( isset( $all_notices[ $notice_type ] ) ) {
			$notice_count = absint( sizeof( $all_notices[ $notice_type ] ) );
		} elseif ( empty( $notice_type ) ) {
			$notice_count += absint( sizeof( $all_notices ) );
		}

		return $notice_count;
	}
}

if ( !function_exists( 'yith_ywraq_add_notice' ) ) {
	/**
	 * Add and store a notice
	 *
	 * @since 2.1
	 *
	 * @param string $message The text to display in the notice.
	 * @param string $notice_type The singular name of the notice type - either error, success or notice. [optional]
	 */
	function yith_ywraq_add_notice( $message, $notice_type = 'success' ) {

		$session = YITH_Request_Quote()->session_class;
		$notices = $session->get( 'yith_ywraq_notices', array() );

		// Backward compatibility
		if ( 'success' === $notice_type ) {
			$message = apply_filters( 'yith_ywraq_add_message', $message );
		}

		$notices[ $notice_type ][] = apply_filters( 'yith_ywraq_add_' . $notice_type, $message );

		$session->set( 'yith_ywraq_notices', $notices );

	}
}

if ( !function_exists( 'yith_ywraq_print_notices' ) ) {
	/**
	 * Prints messages and errors which are stored in the session, then clears them.
	 *
	 * @since 2.1
	 */
	function yith_ywraq_print_notices() {

		if ( get_option( 'ywraq_activate_thank_you_page' ) == 'yes' ) {
			return '';
		}

		$session      = YITH_Request_Quote()->session_class;
		$all_notices  = $session->get( 'yith_ywraq_notices', array() );
		$notice_types = apply_filters( 'yith_ywraq_notice_types', array( 'error', 'success', 'notice' ) );

		foreach ( $notice_types as $notice_type ) {
			if ( yith_ywraq_notice_count( $notice_type ) > 0 ) {
				if ( count( $all_notices ) > 0 && $all_notices[ $notice_type ] ) {
					wc_get_template( "notices/{$notice_type}.php", array(
						'messages' => $all_notices[ $notice_type ]
					) );
				}
			}
		}

		yith_ywraq_clear_notices();
	}
}

if ( !function_exists( 'yith_ywraq_clear_notices' ) ) {
	/**
	 * Unset all notices
	 *
	 * @since 2.1
	 */
	function yith_ywraq_clear_notices() {
		$session = YITH_Request_Quote()->session_class;
		$session->set( 'yith_ywraq_notices', null );
	}
}


/****** HOOKS *****/
function yith_ywraq_show_button_in_single_page(){
    $general_show_btn = get_option('ywraq_show_btn_single_page');
    if ( $general_show_btn == 'yes' ){  //check if the product is in exclusion list
        global $product;
	       $hide_quote_button = yit_get_prop( $product, '_ywraq_hide_quote_button', true );

        if ( $hide_quote_button == 1 ) return 'no';
    }

    return $general_show_btn;
}



function yith_ywraq_email_custom_tags( $text, $tag, $html){

    if( $tag == 'yith-request-a-quote-list' ){
        return yith_ywraq_get_email_template($html);
    }
}

function yith_ywraq_get_email_template( $html ) {
    $raq_data['raq_content'] = YITH_Request_Quote()->get_raq_return();
    ob_start();
    if ( $html ) {
        wc_get_template( 'emails/request-quote-table.php', array(
            'raq_data' => $raq_data
        ) );
    }
    else {
        wc_get_template( 'emails/plain/request-quote-table.php', array(
            'raq_data' => $raq_data
        ) );
    }
    return ob_get_clean();
}



function ywraq_get_token( $action, $order_id, $email){
    return wp_hash( $action.'|'. $order_id .'|'. $email, 'yith-woocommerce-request-a-quote' );
}

function ywraq_verify_token( $token, $action, $order_id, $email){
    $expected = wp_hash( $action.'|'. $order_id .'|'. $email, 'yith-woocommerce-request-a-quote' );
    if ( hash_equals( $expected, $token ) ) {
        return 1;
    }
    return 0;
}

function ywraq_get_browse_list_message(){
    return apply_filters( 'ywraq_product_added_view_browse_list' , __( 'Browse the list', 'yith-woocommerce-request-a-quote' ) );
}