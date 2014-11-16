<?
/*
Plugin Name: DYS bootstrap loader
Description: Load Bootstrap on wordpress blog; MS compatible (Alpha Version)
Version: 0.a
Author: Damien PIQUET & Bootstrap Team
Author URI: https://github.com/dpiquet/
*/

if (! defined('ABSPATH')) {
	die('');
}

define('DYS_BOOTSTRAP_LOADER_LATEST_VERSION', '3.3.0');
define('DYS_BOOTSTRAP_PLUGIN_NAME', 'dys-bootstrap-loader');

/** Associative array for easy maintenance */
$bootstrap_versions = Array(
	'disabled' => '',
	'latest'   => '',
	'3.0.3'    => 'resources/bootstrap-3.0.3',
	'3.1.0'    => 'resources/bootstrap-3.1.0',
	'3.2.0'    =>'resources/bootstrap-3.2.0',
	'3.3.0'    => 'resources/bootstrap-3.3.0'
	);

function set_bootstrap_version($version) {
	/** use wordpress set_option */
	return update_option( 'dys-bootstrap-loader-status', $version);
}

function get_bootstrap_version() {
	/** use wordpress get option */
	return get_option( 'dys-bootstrap-loader-status' );
}

function get_bootstrap_minified() {
	$minified = get_option( 'dys-bootstrap-loader-use-minified' );

	if ($minified == 'yes') { return true; }
	else { return false; }
}

function get_bootstrap_parts() {
	return get_option('dys-bootstrap-loader-parts');
}

function set_bootstrap_parts($parts) {
	if ($parts == get_bootstrap_parts()) { return true; }

	return update_option('dys-bootstrap-loader-parts', $parts);
}

function enable_bootstrap_minified() {
	if (get_bootstrap_minified()) { return true; }

	return update_option( 'dys-bootstrap-loader-use-minified', 'yes');
}

function disable_bootstrap_minified() {
	if (! get_bootstrap_minified()) { return true; }

	return update_option( 'dys-bootstrap-loader-use-minified', 'no');
}

function twitterbootstrap_header() {
	/* Don't load bootstrap on admin side */
	if ( !is_admin() ) {
		global $bootstrap_versions;

		if (get_bootstrap_parts() == 'jsonly') { return; }

		$version = get_bootstrap_version();
		$useMinified = get_bootstrap_minified();

		if ($useMinified) { $cssFile = 'bootstrap.min.css'; }
		else { $cssFile = 'bootstrap.css'; }

		if ($version && ($version != 'disabled')) {

			if ($version == 'latest')
				$version = DYS_BOOTSTRAP_LOADER_LATEST_VERSION;

			wp_register_style( 'bootstrap',
				plugins_url( DYS_BOOTSTRAP_PLUGIN_NAME ) . '/' . $bootstrap_versions[$version] . '/css/' . $cssFile,
				Array(),
				$version
			);
			wp_enqueue_style( 'bootstrap' );
		}
	}
}

function twitterbootstrap_footer() {
	global $bootstrap_versions;
	$version = get_bootstrap_version();

	if (get_bootstrap_parts() == 'cssonly') { return; }

	if ($version == 'disabled')
		return;
	if($version == 'latest')
		$version = DYS_BOOTSTRAP_LOADER_LATEST_VERSION;

	$useMinified = get_bootstrap_minified();

	if ($useMinified) { $jsFile = 'bootstrap.min.js'; }
	else { $jsFile = 'bootstrap.js'; }

	wp_enqueue_script( 'bootstrap',
		plugins_url( DYS_BOOTSTRAP_PLUGIN_NAME ) . '/'. $bootstrap_versions[$version] . '/js/'. $jsFile, array( 'jquery' ), $version, true );
}


function deactivate_bootstrap_loader() {
	/** remove options from database */
	delete_option('dys-bootstrap-loader-status');
	delete_option('dys-bootstrap-loader-use-minified');
	delete_option('dys-bootstrap-loader-parts');
}

function activate_bootstrap_loader() {
	/** add options to database **/
	add_option('dys-bootstrap-loader-status', 'disabled', '', 'yes');
	add_option('dys-bootstrap-loader-use-minified', 'yes', '', 'yes');
	add_option('dys-bootstrap-loader-parts', 'both', '', 'yes');
}

function bootstrap_loader_configuration_interface() {
	global $bootstrap_versions;

	$message = '';

	$updateMinifiedOption = false;
	$activeVersion = get_bootstrap_version();
	$useMinified = get_bootstrap_minified();

	if( isset($_GET['dys-set-bootstrap-loader-version'])) {
		// update active bootstrap version
		$wantedVersion = $_GET['dys-set-bootstrap-loader-version'];

		if (array_key_exists($wantedVersion, $bootstrap_versions)) {
			if ($wantedVersion != $activeVersion) {
				if (set_bootstrap_version($wantedVersion)) {
					$message = __('Option successfully updated');
					$activeVersion = $wantedVersion;
				} else {
					$message = __('Updated failed !');
				}
			}
		}
		else { $message = __('The version you requested does not exists'); }

		if ( isset($_GET['dys-loader-use-minified']) && $_GET['dys-loader-use-minified'] == 'on') {
			$updateMinifiedOption = true;

			if(enable_bootstrap_minified()) {
				$useMinifiedUpdated = true;
				$useMinified = true;
			}
			else { $useMinifiedUpdated = false; }
		}
		else {
			if (disable_bootstrap_minified()) {
				$useMinifiedUpdated = true;
				$useMinified = false;
			}
			else { $useMinifiedUpdated = false; }
		}

		if ( $updateMinifiedOption && ! $useMinifiedUpdated ) {
			$message .= '<p>'. __('Warning: could not set option "use minify"') . '</p>';
		}

		if (isset($_GET['dys-loader-parts'])) {
			$loaderParts = $_GET['dys-loader-parts'];

			if (($loaderParts == 'both' || $loaderParts == 'jsonly') || $loaderParts == 'cssonly') {
				if (! set_bootstrap_parts($loaderParts)) {
					$message .= '<p>' . __('Warning: Could not set option parts') . '</p>';
				}
			}
		}
	}


	$title = "<h1>Bootstrap Selector</h1>";
	$configurationForm =  '<form>' .
		'<input type="hidden" name="dys-bootstrap-loader-action" value="update-option">' .
		'<input type="hidden" name="page" value="configure-dys-bootstrap">' .
		'<select name="dys-set-bootstrap-loader-version">';

	foreach ($bootstrap_versions as $version => $path) {
		$configurationForm .= '<option value="'.$version.'" ';
		if ($activeVersion == $version)
			$configurationForm .= 'SELECTED';
		$configurationForm .= '>Bootstrap '.$version.'</option>';
	}

	$configurationForm .= '<input type="checkbox" name="dys-loader-use-minified"';

	if ($useMinified) {
		$configurationForm .= 'CHECKED';
	}

	
	$activeParts = get_bootstrap_parts();
	if($activeParts == 'both') { $both = 'SELECTED'; }
	else { $both = ''; }

	if($activeParts == 'jsonly') { $jsonly = 'SELECTED'; }
	else { $jsonly = ''; }

	if($activeParts == 'cssonly') { $cssonly = 'SELECTED'; }
	else { $cssonly = ''; }

	$configurationForm .= '></select><label for="dys-loader-use-minified">'.__('Use minified') . '</label>' .
				'<select name="dys-loader-parts">' .
				' <option value="both" '.$both.'>'.__('Both').'</option>' .
				' <option value="jsonly" '.$jsonly.'>'.__('JS only').'</option>' .
				' <option value="cssonly" '.$cssonly.'>'.__('CSS only').'</option>' .
				'</select>' .
				'<br><input type="submit">' .
				'</form>';

	echo $title.$message.$configurationForm;

}

function admin_init_dys_bootstrap_loader() {
	add_menu_page( 'Bootstrap', 
			'Bootstrap', 
			'manage-dys-bootstrap', 
			'configure-dys-bootstrap', 
			'bootstrap_loader_configuration_interface',
			plugins_url( DYS_BOOTSTRAP_PLUGIN_NAME ) . '/icon/bootstrap.png',
			79);
}

add_action('wp_head','twitterbootstrap_header');
add_action('wp_footer','twitterbootstrap_footer');
add_action('admin_menu', 'admin_init_dys_bootstrap_loader');

register_activation_hook(__FILE__, 'activate_bootstrap_loader');
register_deactivation_hook(__FILE__, 'deactivate_bootstrap_loader');

?>
