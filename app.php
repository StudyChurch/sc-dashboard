<?php

use StudyChurch\Settings;


//https://app.study.church/studies/hope-chpt-1/week-29/?sc-group=248
if ( ! empty( $_GET[ 'sc-group' ] ) ) {
	$group = groups_get_group( $_GET[ 'sc-group' ] );

	$url = '/groups/' . bp_get_group_slug( $group ) . $_SERVER['REQUEST_URI'];
	$url = remove_query_arg( 'sc-group', $url );
	wp_safe_redirect( $url );
	die();
}

// @todo handle 404 more gracefully
global $wp_query, $wp_the_query;
$wp_query->is_404 = $wp_the_query->is_404 = false;
status_header( 200 );

$secure = ( 'https' === parse_url( wp_login_url(), PHP_URL_SCHEME ) );
setcookie( '_wpnonce', wp_create_nonce( 'wp_rest' ), time() + 2 * DAY_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN, $secure );

remove_action( 'wp_head', '_admin_bar_bump_cb' );
add_filter( 'show_admin_bar', '__return_false' );

// fix 404 title
add_filter( 'pre_get_document_title', function ( $title ) {
	return get_bloginfo( 'name' ) . ' Dashboard';
}, 9999 );

add_action( 'wp_head', function () {
	global $wp_scripts, $wp_styles;

	$wp_scripts->queue = [];
	$wp_styles->queue  = [];

	// important variables that will be used throughout this example
	$bucket   = Settings::get( 'aws_bucket' );
	$region   = 's3';
	$keyStart = Settings::get( 'aws_directory', 'studies' ) . '/member/' . wp_get_current_user()->user_nicename . '/';
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

	<!-- StudyChurch vars -->
	<script>
      window.scVars = {
        sidebarIcon : '<?php echo Settings::get( 'sidebar_icon' ); ?>',
        sidebarTitle: '<?php bloginfo( 'name' ); ?>',
        froalaKey   : '<?php echo $key; ?>'
      };

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
	</script>
	<!-- end StudyChurch vars -->
	<?php
}, 2 );

include( studychurch()->get_dir() . '/app/dist/index.php' );