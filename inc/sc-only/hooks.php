<?php

SC_Main_Hooks::get_instance();
class SC_Main_Hooks {

	/**
	 * @var
	 */
	protected static $_instance;

	/**
	 * Only make one instance of the SC_Main_Hooks
	 *
	 * @return SC_Main_Hooks
	 */
	public static function get_instance() {
		if ( ! self::$_instance instanceof SC_Main_Hooks ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Add Hooks and Actions
	 */
	protected function __construct() {
//		add_action( 'wp_footer', array( $this, 'bug_report_form' ) );
		add_action( 'wp_footer', array( $this, 'front_page_modal' ) );
//		add_action( 'before_header', array( $this, 'beta_flag' ) );
	}

	public function bug_report_form() {
		if ( is_user_logged_in() ) : ?>
			<div class="bug-report-cont">
					<div class="handle clearfix">
							<a href="#" class="no-margin button small bug-report-button"><?php _e( 'Need Help?', 'sc' ); ?></a>
						</div>
					<div class="form hide">
							<?php gravity_form( 4, false, false, false, false, true ); ?>
						</div>
				</div>
		<?php endif;
	}

	public function front_page_modal() {
		if ( is_front_page() ) : ?>
			<div id="watch-video" class="reveal-modal" data-reveal>
				<div class="flex-video widescreen youtube">
					<iframe width="853" height="480" src="https://www.youtube.com/embed/gQWzPer9qG8" frameborder="0" allowfullscreen></iframe>
				</div>
				<a class="close-reveal-modal">&#215;</a>
			</div>
		<?php endif;
	}

	public function beta_flag() {
		if ( is_user_logged_in() ) : ?>
			<div class="corner-ribbon top-left sticky red shadow">Beta</div>
		<?php endif;
	}
}