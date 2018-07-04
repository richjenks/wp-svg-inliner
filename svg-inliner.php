<?php

/*
Plugin Name: SVG Inliner
Plugin URI: https://wordpress.org/plugins/svg-inliner/
Description: Automatically inlines SVG images
Version: 0.1.0
Author: Rich Jenks
Author URI: https://richjenks.com
*/

/**
 * Show admin notice if Safe SVG not installed
 */
add_filter('admin_init', function() {
	if (!is_plugin_active('safe-svg/safe-svg.php')) {
		add_action('admin_notices', function() {
			$plugin = 'safe-svg';
			$url = esc_url( network_admin_url('plugin-install.php?tab=plugin-information&plugin=' . $plugin . '&TB_iframe=true&width=600&height=550' ) );
			$pattern = '
				<div class="notice notice-warning">
					<p>
						Please install and enable the <a href="%s" class="thickbox open-plugin-details-modal">Safe SVG</a> plugin so SVG Inliner to work properly
					</p>
				</div>
			';
			echo sprintf($pattern, $url);
		});
	}
});

/**
 * Inline SVG images
 */
add_filter('the_content', function($content) {

	// Away we go...
	$dom = new DOMDocument;
	$dom->loadHTML($content);

	// Iterate over SVG images
	foreach ($dom->getElementsByTagName('img') as $img) {
		$src = $img->getAttribute('src');
		if ('svg' === pathinfo($src, PATHINFO_EXTENSION)) {

			// Get attachment ID
			$class = $img->getAttribute('class');
			preg_match('/wp-image-([0-9]+)/', $class, $match);
			$id = $match[1];

			// Get SVG data
			$transient = 'svg_' . $id;
			if (false === ($data = get_transient($transient))) {
				$path = get_attached_file($id);
				$file = file_get_contents($path);
				$data = base64_encode($file);

				set_transient($transient, $data, YEAR_IN_SECONDS);
			}

			// Inline SVG content
			$uri = 'data:image/svg+xml;base64,' . $data;
			$img->setAttribute('src', $uri);

		}
	}

	// Return new content
	$content = $dom->saveHTML();
	return $content;

} );

/**
 * Clear cache on deactivate
 */
register_deactivation_hook(__FILE__, function() {
	global $wpdb;
	$wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '%_transient_%' and option_name LIKE '%_svg_%';");
});