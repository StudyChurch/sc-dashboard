<?php

/**
 * Plugin Name: StudyChurch Dashboard
 * Description: Dashboard for StudyChurch
 * Version: 1.0.0
 * Author: StudyChurch
 * Author URI: https://study.church
 * Contributors: tannerm
 * Text Domain: sc-dashboard
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! defined( 'SC_VERSION' ) ) {
	define( 'SC_VERSION', '0.2.0' );
}

if ( ! defined( 'BP_DEFAULT_COMPONENT' ) ) {
	define( 'BP_DEFAULT_COMPONENT', 'profile' );
}

/**
 *
 * @since  1.0.0
 *
 * @return StudyChurch
 * @author Tanner Moushey
 */
function studychurch() {
	return StudyChurch::get_instance();
}

studychurch();

use StudyChurch\Settings;

class StudyChurch {

	/**
	 * @var
	 */
	protected static $_instance;

	/**
	 * @var StudyChurch\Assignments
	 */
	public $assignments;

	/**
	 * @var StudyChurch\Study
	 */
	public $study;

	/**
	 * @var StudyChurch\API
	 */
	public $api;

	/**
	 * @var StudyChurch\Organization
	 */
	public $organization;

	/**
	 * @var StudyChurch\Library
	 */
	public $library;

	protected static $_version = '0.1.0';

	/**
	 * Only make one instance of the StudyChurch
	 *
	 * @return StudyChurch
	 */
	public static function get_instance() {
		if ( ! self::$_instance instanceof StudyChurch ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Add Hooks and Actions
	 */
	protected function __construct() {
		$this->add_includes();
		$this->sc_includes();
		$this->add_filters();
		$this->add_actions();
	}

	protected function add_includes() {
		require_once( $this->get_dir() . '/vendor/autoload.php' );

		/**
		 * Instantiate customizer
		 */
		StudyChurch\Settings::get_instance();

		/**
		 * BP Customizations
		 */
		StudyChurch\BuddyPress::get_instance();

		/**
		 * Join page
		 */
		StudyChurch\Join::get_instance();

		/**
		 * Ajax forms
		 */
		StudyChurch\AjaxForms::get_instance();

		/**
		 * Capability mapping
		 */
		StudyChurch\Capabilities::get_instance();

		/**
		 * Setup Private Studies
		 */
		StudyChurch\PremiumStudies::get_instance();

		/**
		 * Functions for template components
		 */
		require $this->get_dir() . '/inc/template-helpers.php';
		require $this->get_dir() . '/inc/form-classes.php';

		/**
		 * Study functions
		 */
		$this->study = StudyChurch\Study::get_instance();

		/**
		 * Initialize Assignments Component
		 */
		$this->assignments = StudyChurch\Assignments::get_instance();

		/**
		 * Initialize API
		 */
		$this->api = StudyChurch\API::get_instance();

		/**
		 * Initialize Organization customizations
		 */
		$this->organization = StudyChurch\OrganizationSetup::get_instance();

		/**
		 * Initialize Library / EDD customizations
		 */
		$this->library = StudyChurch\Library::get_instance();

		StudyChurch\RCP\Settings::get_instance();

	}

	protected function sc_includes() {
	}

	/**
	 * Wire up filters
	 */
	protected function add_filters() {
		add_filter( 'show_admin_bar', array( $this, 'show_admin_bar' ) );

		add_filter( 'pre_kses', array( $this, 'vimeo_embed_to_shortcode' ), 20 );
		add_filter( 'video_embed_html', array( $this, 'video_embed_html_wrap' ) );

		add_filter( 'slt_fsp_caps_check', array( $this, 'strong_password_check' ) );
		add_filter( 'template_include', array( $this, 'app_template' ), 11 );

		add_filter( 'bp_get_loggedin_user_link', function ( $link ) {
			return get_home_url();
		} );
	}

	/**
	 * Wire up actions
	 */
	protected function add_actions() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue' ) );
		add_action( 'wp_head', array( $this, 'js_globals' ) );
		add_action( 'template_redirect', array( $this, 'maybe_force_login' ), 5 );
		add_action( 'admin_init', array( $this, 'redirect_backend' ) );
		add_action( 'bp_register_activity_actions', array( $this, 'action_answer_update' ) );
		add_action( 'init', [ $this, 'rewrite_rules' ] );
	}

	/**
	 * Register answer_update action
	 *
	 * @author Tanner Moushey
	 */
	public function action_answer_update() {
		bp_activity_set_action(
		// Older avatar activity items use 'profile' for component. See r4273.
			'groups',
			'answer_update',
			__( 'Member adds or updates an answer', 'buddypress' ),
			[ $this, 'action_answer_update_format' ],
			__( 'Added/Updated Answer', 'buddypress' ),
			array( 'group' )
		);
	}

	/**
	 * @param $action
	 * @param $activity
	 *
	 * @return mixed
	 * @author Tanner Moushey
	 */
	public function action_answer_update_format( $action, $activity ) {
		$action = sprintf( __( '%s answered a question in <a href="%s#post-%s">%s</a>' ), bp_core_get_user_displayname( $activity->user_id ), studychurch()->study::get_group_link( $activity->secondary_item_id, absint( $activity->item_id ) ), $activity->secondary_item_id, get_the_title( sc_get_study_id( $activity->secondary_item_id ) ) );

		return apply_filters( 'sc_action_answer_update_format', $action, $activity );
	}

	/**
	 * Enqueue styles and scripts
	 */
	public function enqueue() {
		$this->enqueue_scripts();
		$this->enqueue_styles();
	}

	/**
	 * Enqueue Styles
	 */
	protected function enqueue_styles() {}

	/**
	 * Enqueue scripts
	 */
	protected function enqueue_scripts() {}

	/**
	 * Is this a development environment?
	 *
	 * @return bool
	 */
	public function is_dev() {
		return ( 'studychurch.dev' == $_SERVER['SERVER_NAME'] );
	}

	public function show_admin_bar() {

		if ( is_super_admin() ) {
			return true;
		}

		return false;
	}

	/**
	 * Force user login
	 */
	public function maybe_force_login() {
		/** bale if the user is logged in or is on the login page */
		if ( is_user_logged_in() ) {
			return;
		}

		// must be logged in to view buddypress pages
		if ( ! ( is_buddypress() || is_singular( 'sc_study' ) ) ) {
			return;
		}

		// must be logged in to view studies
		if ( is_singular( 'sc_study' ) ) {
			$study_id = get_the_ID();

			if ( apply_filters( 'sc_guest_can_view_study', false, $study_id ) ) {
				return;
			}
		}

		auth_redirect();
		exit();
	}

	public function redirect_backend() {
		// allow ajax requests
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			return;
		}

		if ( current_user_can( 'edit_pages' ) ) {
			return;
		}

		wp_safe_redirect( bp_loggedin_user_domain() );
		die();
	}

	public function js_globals() {

		if ( ! $id = sc_get( 'study' ) ) {
			return;
		}

		// important variables that will be used throughout this example
		$bucket   = Settings::get( 'aws_bucket' );
		$region   = 's3';
		$keyStart = Settings::get( 'aws_directory', 'studies' ) . '/' . sanitize_title( get_the_title( $id ) ) . '/';
		$acl      = 'public-read';

		// these can be found on your Account page, under Security Credentials > Access Keys
		$accessKeyId = Settings::get( 'aws_access_key' );
		$secret      = Settings::get( 'aws_access_key_secret' );

		$policy = base64_encode( json_encode( array(
			// ISO 8601 - date('c'); generates uncompatible date, so better do it manually
			'expiration' => date( 'Y-m-d\TH:i:s.000\Z', strtotime( '+1 day' ) ),
			'conditions' => array(
				array( 'bucket' => $bucket ),
				array( 'acl' => $acl ),
				array( 'success_action_status' => '201' ),
				array( 'x-requested-with' => 'xhr' ),
				array( 'starts-with', '$key', $keyStart ),
				array( 'starts-with', '$Content-Type', '' ) // accept all files
			)
		) ) );

		$signature = base64_encode( hash_hmac( 'sha1', $policy, $secret, true ) );

		$key = Settings::get( 'froala_key' );
		$key = apply_filters( 'sc_froala_key', $key ); ?>

		<script>
          jQuery.Editable = jQuery.Editable || {};
          jQuery.Editable.DEFAULTS = jQuery.Editable.DEFAULTS || {};

          jQuery.Editable.DEFAULTS.key = '<?php echo $key; ?>';
          jQuery.Editable.DEFAULTS.pastedImagesUploadURL = '';
          jQuery.Editable.DEFAULTS.imageUploadURL = '';

		  <?php if ( $id ) : ?>

          scFroalaS3 = {
            bucket  : '<?php echo $bucket; ?>',
            region  : '<?php echo $region; ?>',
            keyStart: '<?php echo $keyStart; ?>',
            callback: function (url, key) {
              // The URL and Key returned from Amazon.
              console.log(url);
              console.log(key);
            },
            params  : {
              acl           : '<?php echo $acl; ?>',
              AWSAccessKeyId: '<?php echo $accessKeyId; ?>',
              policy        : '<?php echo $policy; ?>',
              signature     : '<?php echo $signature; ?>'
            }
          };

		  <?php endif; ?>

		</script>
		<?php
	}

	function vimeo_embed_to_shortcode( $content ) {
		if ( false === stripos( $content, 'player.vimeo.com/video/' ) ) {
			return $content;
		}

		$regexp = '!<iframe\s+src=[\'"](https?:)?//player\.vimeo\.com/video/(\d+)[\w=&;?]*[\'"]((?:\s+\w+=[\'"][^\'"]*[\'"])*)((?:[\s\w]*))></iframe>!i';
		$regexp = '!<iframe((?:\s+\w+="[^"]*")*?)\s+src="(https?:)?//player\.vimeo\.com/video/(\d+)".*?</iframe>!i';

		$regexp_ent = str_replace( '&amp;#0*58;', '&amp;#0*58;|&#0*58;', htmlspecialchars( $regexp, ENT_NOQUOTES ) );

		foreach ( array( 'regexp', 'regexp_ent' ) as $reg ) {
			if ( ! preg_match_all( $$reg, $content, $matches, PREG_SET_ORDER ) ) {
				continue;
			}

			foreach ( $matches as $match ) {
				$id = (int) $match[3];

				$params = $match[1];

				if ( 'regexp_ent' == $reg ) {
					$params = html_entity_decode( $params );
				}

				$params = wp_kses_hair( $params, array( 'http' ) );

				$width  = isset( $params['width'] ) ? (int) $params['width']['value'] : 0;
				$height = isset( $params['height'] ) ? (int) $params['height']['value'] : 0;

				$wh = '';
				if ( $width && $height ) {
					$wh = ' w=' . $width . '&h=' . $height;
				}

				$shortcode = '[vimeo ' . $id . $wh . ']';
				$content   = str_replace( $match[0], $shortcode, $content );
			}
		}

		return $content;
	}

	public function video_embed_html_wrap( $html ) {
		$html = str_replace( '<div', '<span', $html );
		$html = str_replace( '</div>', '</span>', $html );

		return $html;
	}

	/**
	 * Define which caps require strong passwords
	 *
	 * @param $caps
	 *
	 * @return string
	 */
	public function strong_password_check( $caps ) {
		return 'upload_files,edit_published_posts';
	}

	/**
	 * ID for this theme. Used in translation functions.
	 *
	 * @return string
	 * @author Tanner Moushey
	 */
	public function get_id() {
		return 'studychurch';
	}

	/**
	 * Get the name for this theme
	 *
	 * @return string
	 * @author Tanner Moushey
	 */
	public function get_name() {
		return 'StudyChurch';
	}

	/**
	 * Alias for get_name
	 *
	 * @return string
	 * @author Tanner Moushey
	 */
	public function get_plugin_name() {
		return $this->get_name();
	}

	/**
	 * Get the version for this theme
	 *
	 * @return string
	 * @author Tanner Moushey
	 */
	public function get_version() {
		return SC_VERSION;
	}

	/**
	 * Get the API namespace to use
	 *
	 * @return string
	 * @author Tanner Moushey
	 */
	public function get_api_namespace() {
		return $this->get_id() . '/v1';
	}

	public function get_url() {
		return plugin_dir_url( __FILE__ );
	}

	public function get_dir() {
		return plugin_dir_path( __FILE__ );
	}

	protected function includes() {
	}

	public function app_template( $template ) {
		if ( ! is_user_logged_in() ) {
			return $template;
		}

		if ( in_array( $_SERVER['REQUEST_URI'], array( '/', '/settings/', '/notifications/' ) ) ) {
			return $this->get_dir() . 'app.php';
		}

		if ( strpos( $_SERVER['REQUEST_URI'], 'groups/' ) ) {
			return $this->get_dir() . 'app.php';
		}

		if ( strpos( $_SERVER['REQUEST_URI'], 'organizations/' ) ) {
			return $this->get_dir() . 'app.php';
		}

		if ( strpos( $_SERVER['REQUEST_URI'], 'studies/' ) ) {
			return $this->get_dir() . 'app.php';
		}

		if ( strpos( $_SERVER['REQUEST_URI'], 'assignments/' ) ) {
			return $this->get_dir() . 'app.php';
		}

		if ( strpos( $_SERVER['REQUEST_URI'], 'studio/' ) ) {
			return $this->get_dir() . 'app.php';
		}

		return $template;
	}

	public function rewrite_rules() {
		add_rewrite_rule( 'organizations/(.?.+?)(?:/([0-9]+))?/?$', 'index.php?pagename=groups', 'top' );
		flush_rewrite_rules( true );
	}

}