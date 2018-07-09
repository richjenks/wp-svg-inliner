<?php

/*
Plugin Name: SVG Inliner
Plugin URI: https://wordpress.org/plugins/svg-inliner/
Description: Automatically inlines SVG images
Version: 1.0.0
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
						Please install and enable the <a href="%s" class="thickbox open-plugin-details-modal">Safe SVG</a> plugin so SVG Inliner can work properly
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

	/**
	 * Iterate over images in reverse
	 * @see http://php.net/manual/en/domnode.replacechild.php#50500
	 */
	$imgs = $dom->getElementsByTagName('img');
	$i = $imgs->length - 1;
	while ($i > -1) {

		// Get current image
		$img = $imgs[$i];
		$src = $img->getAttribute('src');

		// Check if image in as SVG
		if ('svg' === pathinfo($src, PATHINFO_EXTENSION)) {

			// Get attachment ID
			$class = $img->getAttribute('class');
			preg_match('/wp-image-([0-9]+)/', $class, $match);
			$id = $match[1];

			// Get SVG file
			$path = get_attached_file($id);
			$file = file_get_contents($path);

			// Get image dimensions
			$width  = $img->getAttribute('width');
			$height = $img->getAttribute('height');

			// Generate XML
			$svg = simplexml_load_string($file);
			$svg->addAttribute('class', $class);
			if ($width) $svg['width'] = $width;
			if ($height) $svg['height'] = $height;
			$file = $svg->asXML();

			// Remove doctype
			$file = explode("\n", $file);
			array_shift($file);
			$file = implode("\n", $file);

			// Replace image with SVG
			$svg = $dom->createDocumentFragment();
			$svg->appendXML($file);
			$img->parentNode->replaceChild($svg, $img);

		}

		// Decrement
		$i--;

	}

	// Return new content
	$content = $dom->saveHTML();
	return $content;

} );