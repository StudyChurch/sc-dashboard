<?php
namespace StudyChurch;

class Settings {

	/**
	 * @var
	 */
	protected static $_instance;

	/**
	 * Only make one instance of the Settings
	 *
	 * @return Settings
	 */
	public static function get_instance() {
		if ( ! self::$_instance instanceof Settings ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Add Hooks and Actions
	 */
	protected function __construct() {
		add_action( 'cmb2_admin_init', array( $this, 'router' ) );
	}

	/**
	 * CBM2 Settings Router
	 *
	 * @since  1.0.0
	 *
	 * @author Tanner Moushey
	 */
	public function router() {
		$this->answer();
	}

	/**
	 * Define the podcast metaboxes
	 *
	 * @author Tanner Moushey
	 */
	protected function answer() {
		$cmb = new_cmb2_box( array(
			'id'           => 'sc_settings',
			'title'        => __( 'StudyChurch', 'studychurch' ),
			'object_types' => array( 'options-page' ),
			'parent_slug'  => 'options-general.php',
			'option_key'   => '_sc_settings',
		) );

		$cmb->add_field( array(
			'name' => __( 'Study Settings', 'studychurch' ),
			'id'   => 'study_title',
			'type' => 'title',
		) );

		$cmb->add_field( array(
			'name'         => 'Default Image',
			'desc'         => 'The image to use for studies that do not have a thumbnail. Image should be at least 300x225',
			'id'           => 'study_image',
			'type'         => 'file',
			'text'         => array(
				'add_upload_file_text' => 'Add Image' // Change upload button text. Default: "Add or Upload File"
			),
			'query_args' => array(
				'type' => array(
					'image/gif',
					'image/jpeg',
					'image/png',
				),
			),
			'preview_size' => 'large', // Image size to use when previewing in the admin.
		) );

		$cmb->add_field( array(
			'name' => __( 'Froala Settings', 'studychurch' ),
			'id'   => 'froala',
			'type' => 'title',
		) );

		$cmb->add_field( array(
			'name'         => 'Froala Key',
			'desc'         => 'The Froala Key to use for the editors',
			'id'           => 'froala_key',
			'type'         => 'text',
		) );

		$cmb->add_field( array(
			'name' => __( 'AWS Settings', 'studychurch' ),
			'id'   => 'aws_title',
			'type' => 'title',
		) );

		$cmb->add_field( array(
			'name' => __( 'Bucket', 'studychurch' ),
			'desc' => __( 'The bucket that will store the studies folder with all the study images.', 'studychurch' ),
			'id'   => 'aws_bucket',
			'type' => 'text',
		) );

		$cmb->add_field( array(
			'name' => __( 'Directory', 'studychurch' ),
			'desc' => __( 'The directory in the Bucket that will store the studies folder with all the study images.', 'studychurch' ),
			'id'   => 'aws_directory',
			'type' => 'text',
		    'default' => 'studies',
		) );

		$cmb->add_field( array(
			'name' => __( 'Access Key ID', 'studychurch' ),
			'desc' => __( 'The AWS Access Key', 'studychurch' ),
			'id'   => 'aws_access_key',
			'type' => 'text',
		) );

		$cmb->add_field( array(
			'name' => __( 'Access Key Secret', 'studychurch' ),
			'desc' => __( 'The AWS Access Key Secret', 'studychurch' ),
			'id'   => 'aws_access_key_secret',
			'type' => 'text',
		) );

		// Bible API
		$cmb->add_field( array(
			'name' => __( 'Bible API Settings', 'studychurch' ),
			'id'   => 'bible_title',
			'type' => 'title',
		) );

		$cmb->add_field( array(
			'name' => __( 'API URL', 'studychurch' ),
			'desc' => __( 'The url to the ESV.org API', 'studychurch' ),
			'id'   => 'bible_url',
			'type' => 'text',
		) );

		$cmb->add_field( array(
			'name' => __( 'Authorization Token', 'studychurch' ),
			'desc' => __( 'The authorization token from ESV.org', 'studychurch' ),
			'id'   => 'bible_auth_token',
			'type' => 'text',
		) );

	}

	/**
	 * Get BibleAPI Settings
	 *
	 * @param        $meta_key
	 * @param string $default
	 *
	 * @return mixed|string
	 * @author Tanner Moushey
	 */
	public static function get( $meta_key, $default = '' ) {

		$meta = get_option( '_sc_settings', array() );

		if ( ! isset( $meta[ $meta_key ] ) ) {
			return $default;
		}

		return $meta[ $meta_key ];

	}

	/**
	 * Set BibleAPI setting
	 *
	 * @param $meta_key
	 * @param $value
	 *
	 * @author Tanner Moushey
	 */
	public static function set( $meta_key, $value ) {

		$meta = get_option( '_sc_settings', array() );

		$meta[ $meta_key ] = $value;

		update_option( '_sc_settings', $meta );

	}


}