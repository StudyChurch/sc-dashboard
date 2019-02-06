<?php

namespace StudyChurch;

use RCP_Levels;

class Join {

	/**
	 * @var
	 */
	protected static $_instance;

	protected $valid;
	protected $group;

	/**
	 * Only make one instance of the Join
	 *
	 * @return Join
	 */
	public static function get_instance() {
		if ( ! self::$_instance instanceof Join ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Add Hooks and Actions
	 */
	protected function __construct() {
		add_action( 'template_redirect', [ $this, 'setup' ] );
	}

	public function setup() {
		if ( ! is_page( 'join' ) ) {
			return;
		}

		global $rcp_load_css;

		$rcp_load_css = true;

		add_action( 'wp_enqueue_scripts', [ $this, 'scripts' ] );
		add_filter( 'the_content', [ $this, 'content' ] );

		$valid = $group_id = false;
		$key   = sc_get( 'key' );

		if ( $group = sc_get( 'group' ) ) {
			$group_id = groups_get_id( $group );
			$group    = groups_get_group( 'group_id=' . $group_id );
		}

		/**
		 * Make sure this is a valid link
		 */
		if ( $group_id && sc_get_group_invite_key( $group_id ) == $key ) {
			$valid = 'group';
		}

		if ( $key == md5( 'StudyChurch Beta' ) ) {
			$valid = 'beta';
		}

		/**
		 * if the user is already logged in, add them to the group and carry on
		 */
		if ( is_user_logged_in() && 'group' == $valid ) {
			groups_join_group( $group_id, get_current_user_id() );

			wp_safe_redirect( bp_get_group_permalink( $group ) );
			die();
		}

		if ( is_user_logged_in() ) {
			wp_safe_redirect( bp_get_loggedin_user_link() );
			die();
		}

		$this->group = $group;
		$this->valid = $valid;

	}

	public function scripts() {
		wp_enqueue_script( 'sc_dashboard_join', studychurch()->get_url() . 'assets/join.js', [
			'jquery',
			'wp-util'
		], SC_VERSION, true );
	}

	public function content( $content ) {
		$valid = $this->valid;
		$group = $this->group;

		ob_start(); ?>
		<div id="restricted-message" class="rcp-form">

			<?php if ( 'group' == $valid ) : ?>
				<div>
					<h1><?php _e( 'You have been invited to join ', 'sc' ); ?><?php echo bp_get_group_name( $group ); ?></h1>

					<p><?php _e( sprintf( 'Please log in to join %s. Don\'t have an account? You can register for free!', bp_get_group_name( $group ) ), 'sc' ); ?></p>
				</div>

				<div>
					<div id="login-body" style="display:none;">
						<p>
							<a href="#" class="switch"><?php _e( "Need an account? You can register for free", 'sc' ); ?> &rarr;</a>
						</p>
						<?php echo $this->template_login(); ?>
					</div>

					<div id="start-now-body">
						<p><a href="#" class="switch"><?php _e( 'Already have an account? Login' ); ?> &rarr;</a></p>
						<?php echo $this->template_register(); ?>
					</div>
				</div>
			<?php else : ?>
				<div>
					<div class="container">
						<h1><?php _e( 'Looks like you got the wrong link.', 'sc' ); ?></h1>

						<p><?php _e( 'If you are trying to join a group, contact the admin of the group you are trying to join and ask him to verify the link you received.', 'sc' ); ?></p>
					</div>
				</div>
			<?php endif; ?>

		</div>
		<?php
		return $content . ob_get_clean();
	}

	private function template_register() {
		ob_start();
		?>
		<form action="" method="post" class="ajax-form">

			<p class="text-center spinner hide"><i class="fa fa-circle-o-notch fa-spin"></i></p>

			<p>
				<label for="first-name"><?php _e( 'First Name', 'sc' ); ?></label>
				<input type="text" name="rcp_user_first" id="first-name" placeholder="John" required />
			</p>

			<p>
				<label for="last-name"><?php _e( 'Last Name', 'sc' ); ?></label>
				<input type="text" name="rcp_user_last" id="last-name" placeholder="Doe" required />
			</p>

			<p>
				<label for="rcp-login"><?php _e( 'Username', 'sc' ); ?></label>
				<input type="text" name="rcp_user_login" id="rcp-login" placeholder="johndoe" required />
			</p>

			<p>
				<label for="email"><?php _e( 'Email', 'sc' ); ?></label>
				<input type="email" name="rcp_user_email" id="email" placeholder="john.doe@gmail.com" required />
			</p>

			<p style="display:none;">
				<label for="fax"><?php _e( 'Fax', 'sc' ); ?></label>
				<input type="text" name="fax" id="fax" placeholder="1234567890" />
			</p>

			<p>
				<label for="password"><?php _e( 'Password', 'sc' ); ?></label>
				<input type="password" name="rcp_user_pass" id="password" placeholder="**********" required />
			</p>

			<p>
				<label for="password-conf"><?php _e( 'Confirm Password', 'sc' ); ?></label>
				<input type="password" name="rcp_user_pass_confirm" id="password-conf" placeholder="**********" required />
			</p>

			<p class="text-center row">
				<?php
				$levels = new RCP_Levels();
				$free   = $levels->get_level_by( 'price', 0 );

				if ( isset( $free->id ) ) : ?>
					<input type="hidden" name="rcp_level" value="<?php echo $free->id; ?>" />
				<?php endif; ?>

				<input id="rcp_mailchimp_pro_signup" name="rcp_mailchimp_pro_signup" type="hidden" value="true">
				<input type="hidden" name="action" value="sc_register" />
				<?php wp_nonce_field( 'sc-register-nonce', 'sc_register_nonce' ); ?>
				<input type="submit" value="<?php _e( 'Register', 'sc' ); ?>" class="button secondary expand" />
				<?php do_action( 'sc_register_form_end' ); ?>
			</p>

			<p class="description">By registering you are agreeing to the
				<a href="<?php echo network_home_url(); ?>privacy-policy">Privacy Policy</a> and
				<a href="<?php echo network_home_url(); ?>terms">Terms and Conditions</a>.</p>

		</form>
		<?php
		return ob_get_clean();
	}

	private function template_login() {
		ob_start();
		?>
		<form action="" method="post" class="ajax-form">

			<p class="text-center spinner hide"><i class="fa fa-circle-o-notch fa-spin"></i></p>


			<p>
				<label for="sc-login"><?php _e( 'Username or Email', 'sc' ); ?></label>
				<input type="text" name="user_login" id="sc-login" placeholder="john.doe@gmail.com" required />
			</p>


			<p>
				<label for="sc-password"><?php _e( 'Password', 'sc' ); ?></label>
				<input type="password" name="user_password" id="sc-password" placeholder="**********" required />
			</p>


			<p>
				<input type="hidden" name="action" value="sc_login" />
				<?php wp_nonce_field( 'sc-login', 'sc_login_key' ); ?>
				<input type="submit" value="Login" class="button secondary expand" />
				<?php do_action( 'sc_login_form_end' ); ?>
			</p>

		</form>
		<?php
		return ob_get_clean();
	}

}