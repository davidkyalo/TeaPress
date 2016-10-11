<?php



if ( !function_exists('wp_redirect') ) :
/**
 * Redirects to another page.
 *
 * @since 1.5.1
 *
 * @global bool $is_IIS
 *
 * @param string $location The path to redirect to.
 * @param int    $status   Status code to use.
 * @return bool False if $location is not provided, true otherwise.
 */
function wp_redirect($location, $status = null, $send = true) {
	$nargs = func_num_args();
	if($nargs === 1){
		$status = 302;
		$send = true;
	}
	elseif ($nargs === 2) {
		$send = is_bool($status) ? $status : $send;
	}

	$status = in_array((int) $status, [201, 301, 302, 303, 307, 308]) ? (int) $status : 302;

	if(!function_exists('responses')){
		return _default_wp_redirect_func( $location, $status );
	}

	$response = responses()->redirect($location, $status);
	if($send){
		$response->send();
		return true;
	}

	return $response;
}
endif;

if ( !function_exists('wp_safe_redirect') ) :
/**
 * Performs a safe (local) redirect, using wp_redirect().
 *
 * Checks whether the $location is using an allowed host, if it has an absolute
 * path. A plugin can therefore set or remove allowed host(s) to or from the
 * list.
 *
 * If the host is not allowed, then the redirect defaults to wp-admin on the siteurl
 * instead. This prevents malicious redirects which redirect to another host,
 * but only used in a few places.
 *
 * @since 2.3.0
 */
function wp_safe_redirect($location, $status = 302, $send = true) {
	$nargs = func_num_args();
	if($nargs === 1){
		$status = 302;
		$send = true;
	}
	elseif ($nargs === 2) {
		$send = is_bool($status) ? $status : $send;
	}

	$status = in_array((int) $status, [201, 301, 302, 303, 307, 308]) ? (int) $status : 302;

	if(!function_exists('responses')){
		return _default_wp_safe_redirect_func( $location, $status );
	}

	$response = responses()->safeRedirect($location, $status);
	if($send){
		$response->send();
		return true;
	}

	return $response;

}
endif;

function _default_wp_redirect_func($location, $status = 302) {
	global $is_IIS;

	$location = apply_filters( 'wp_redirect', $location, $status );

	$status = apply_filters( 'wp_redirect_status', $status, $location );

	if ( ! $location )
		return false;

	$location = wp_sanitize_redirect($location);


	if ( !$is_IIS && PHP_SAPI != 'cgi-fcgi' )
		status_header($status); // This causes problems on IIS and some FastCGI setups

	header("Location: $location", true, $status);

	return true;
}

function _default_wp_safe_redirect_func( $location, $status = 302 ){
	$location = wp_sanitize_redirect($location);

	$location = wp_validate_redirect( $location, apply_filters( 'wp_safe_redirect_fallback', home_url(), $status ) );

	return _default_wp_redirect_func($location, $status);
}