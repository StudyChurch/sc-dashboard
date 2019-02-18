<?php

namespace StudyChurch;

class PremiumStudies {

	/**
	 * @var
	 */
	protected static $_instance;

	/**
	 * @var
	 */
	public static $_tax = 'sc_premium';

	/**
	 * Only make one instance of the PremiumStudies
	 *
	 * @return PremiumStudies
	 */
	public static function get_instance() {
		if ( ! self::$_instance instanceof PremiumStudies ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Add Hooks and Actions
	 */
	protected function __construct() {
		add_action( 'init', [ $this, 'premium_cat' ], 5 );
		add_action( 'edd_complete_purchase', [ $this, 'save_premium_access' ], 10, 3 );
		add_action( 'edd_after_price_option', [ $this, 'price_description' ], 10, 3 );
		add_filter( 'edd_purchase_variable_prices', [ $this, 'variable_prices' ], 10, 2 );
		add_filter( 'sc_study_restrictions', [ $this, 'get_restrictions' ], 10, 2 );
	}

	/**
	 * Register premium categories taxonomy
	 *
	 * @author Tanner Moushey
	 */
	public function premium_cat() {
		register_taxonomy( self::$_tax, [ 'sc_study', 'download' ], [
			'label'        => 'Premium Categories',
			'hierarchical' => true
		] );
	}

	public function price_description( $key, $price, $download_id ) {
		$member = new Member();
		if ( 1 === $key ) : ?>
		<p class="description">
			Purchase this for individual use in your personal library.
		</p>
		<?php elseif ( 2 === $key ) : ?>
		<p class="description">
			Purchase this for use in <?php echo bp_get_group_name( $member->can_purchase_group_licenses()[0] ); ?>.
		</p>
		<?php endif;
	}

	/**
	 * Filter variable prices to remove options already purchased
	 *
	 * @param $prices | 1 is for personal license, 2 is for group license
	 * @param $download_id
	 *
	 * @return mixed
	 * @author Tanner Moushey
	 */
	public function variable_prices( $prices, $download_id ) {
		$member = new Member();

		// if the user cannot purchase a group license, remove it from the list
		if ( ! $member->can_purchase_group_licenses() ) {
			unset( $prices[2] );
		}

		// if the user has already purchased the personal option, remove it from the list
		if ( edd_has_user_purchased( get_current_user_id(), $download_id, 1 ) ) {
			unset( $prices[ 1 ] );
		}

		if ( 1 === count( $prices ) ) {
			add_filter( 'edd_price_option_checked', function( $checked_key, $download_id, $key ) {
				return $key;
			}, 10, 3 );
		} elseif ( empty( $prices ) ) {
			add_filter( 'edd_purchase_download_form', function() {
				return '';
			} );
		}

		return $prices;

	}

	/**
	 * @param int          $payment_id Payment ID.
	 * @param \EDD_Payment  $payment    EDD_Payment object containing all payment data.
	 * @param \EDD_Customer $customer   EDD_Customer object containing all customer data.
	 *
	 *
	 * @since  1.0.0
	 *
	 * @author Tanner Moushey
	 */
	public function save_premium_access( $payment_id, $payment, $customer ) {
		$org_cats = $member_cats = []; // get_the_terms();
		$member = new Member( $customer->user_id );

		foreach( $payment->downloads as $download ) {
			if ( '2' !== $download['options']['price_id'] ) {
				$member_cats = array_merge( wp_list_pluck( get_the_terms( $download['id'], self::$_tax ), 'slug' ), $member_cats );
			} else {
				$org_cats = array_merge( wp_list_pluck( get_the_terms( $download['id'], 'sc_premium' ), 'slug' ), $org_cats );
			}
		}

		if ( ! empty( $member_cats ) ) {
			$member->update_premium_access( $member_cats );
		}

		if ( ! empty( $org_cats ) && $member->can_purchase_group_licenses() ) {
			$org = new Organization( bp_get_group_id( $member->can_purchase_group_licenses()[0] ) );
			$org->update_premium_access( $org_cats );
		}
	}

	/**
	 * Get restrictions for this study
	 *
	 * @param $restrictions
	 * @param $study_id
	 *
	 * @return array
	 * @author Tanner Moushey
	 */
	public function get_restrictions( $restrictions, $study_id ) {
		$study_id = studychurch()->study::get_study_id( $study_id );
		return array_merge( wp_list_pluck( get_the_terms( $study_id, self::$_tax ), 'slug' ), $restrictions );
	}

}