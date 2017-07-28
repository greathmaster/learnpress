<?php

/**
 * Class LP_Gateways
 *
 * @author  ThimPress
 * @package LearnPress/Classes
 * @version 1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class LP_Gateways
 */
class LP_Gateways {

	/**
	 * @var null
	 */
	protected static $_instance = null;

	/**
	 * @var array
	 */
	protected $payment_gateways = array();

	/**
	 * LP_Gateways constructor.
	 */
	public function __construct() {
		$this->init();
	}

	/**
	 *
	 */
	public function init() {
		if ( ! $this->payment_gateways ) {
			$gateways = array(
				'paypal' => 'LP_Gateway_Paypal'
			);
			// Filter
			$gateways = apply_filters( 'learn_press_payment_method', $gateways );
			if ( $gateways ) {
				foreach ( $gateways as $k => $gateway ) {
					if ( is_string( $gateway ) && class_exists( $gateway ) ) {
						$gateway = new $gateway();
					}
					$this->payment_gateways[ $k ] = apply_filters( 'learn_press_payment_method_init', $gateway );
				}
			}
		}
	}

	/**
	 * Get all registered payments.
	 *
	 * @param boolean $with_order If true sort payments with the order saved in admin
	 *
	 * @return array
	 */
	public function get_gateways( $with_order = false ) {
		$gateways = array();
		if ( count( $this->payment_gateways ) ) {
			foreach ( $this->payment_gateways as $gateway ) {
				if ( is_string( $gateway ) && class_exists( $gateway ) ) {
					$gateway = new $gateway();
				}
				if ( ! is_object( $gateway ) ) {
					continue;
				}
				$gateways[ $gateway->id ] = $gateway;
			}
		}

		if ( $with_order && $ordered = get_option( 'learn_press_payment_order' ) ) {
			// Sort gateways by the keys stored.
			usort( $gateways, array( $this, '_sort_gateways_callback' ) );
		}

		return $gateways;
	}


	/**
	 * Callback function for sorting payment gateways.
	 *
	 * @param $a
	 * @param $b
	 *
	 * @return bool|int
	 */
	public function _sort_gateways_callback( $a, $b ) {
		if ( $ordered = get_option( 'learn_press_payment_order' ) ) {
			return array_search( $a->id, $ordered ) > array_search( $b->id, $ordered );
		}

		return 0;
	}

	/**
	 * Get payment gateways are available for use.
	 *
	 * @return mixed
	 */
	public function get_available_payment_gateways() {
		$this->init();
		$_available_gateways = array();
		$is_selected         = false;

		foreach ( $this->payment_gateways as $slug => $gateway ) {

			// Let custom addon can define how is enable/disable
			if ( apply_filters( 'learn_press_payment_gateway_available_' . $slug, true, $gateway ) ) {

				// If gateway has already selected before
				if ( LP()->session->get( 'chosen_payment_method' ) == $gateway->id ) {
					$gateway->is_selected = true;
					$is_selected          = $gateway;
				}
				$_available_gateways[ $slug ] = $gateway;
			};

		}

		// Set default payment if there is no payment is selected
		if ( $_available_gateways && ! $is_selected ) {
			$gateway              = reset( $_available_gateways );
			$gateway->is_selected = true;
		}

		return apply_filters( 'learn_press_available_payment_gateways', $_available_gateways );
	}

	/**
	 * @return array
	 */
	public function get_availabe_gateways() {
		return $this->payment_gateways;
	}

	/**
	 * Ensure that only one instance of LP_Gateways is loaded
	 * @return LP_Gateways|null
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}
}