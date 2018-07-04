<?php

/*
Plugin Name: SVG Inliner
Plugin URI: https://wordpress.org/plugins/svg-inliner/
Description: Automatically inlines SVG images
Version: 0.1.0
Author: Rich Jenks
Author URI: https://richjenks.com
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

			// Get SVG file
			$path = get_attached_file($id);
			$file = file_get_contents($path);

			// Inline SVG content
			$uri = 'data:image/svg+xml;base64,' . base64_encode($file);
			$img->setAttribute('src', $uri);

		}
	}

	// Return new content
	$content = $dom->saveHTML();
	return $content;

} );