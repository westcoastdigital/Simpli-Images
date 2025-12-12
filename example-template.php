<!DOCTYPE html>
<html <?php language_attributes(); ?>>

<head>
	<meta charset="<?php bloginfo('charset'); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<style>
		html,
		body {
			min-height: 100%;
			font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
			font-size: 13px;
			line-height: 1.4em;
			margin: 0
		}

		body {
			background-color: #f5f5f5;
			background-size: cover;
			display: flex;
			flex-direction: column;
			justify-content: center;
			align-items: center;
		}

		img {
			margin-bottom: 40px;
		}
	</style>
	<?php wp_head(); ?>
</head>

<body <?php body_class(); ?>><?php wp_body_open(); ?><div id="page">
		<?php if ($blank_show_header) : ?>
			<div class="site-title">
				<div class="site-title-bg">
					<h1><a href="<?php echo esc_url(home_url('/')); ?>" rel="home"><?php bloginfo('name'); ?></a></h1>
					<?php if ($blank_description || is_customize_preview()) : ?>
						<p class="site-description"><?php echo esc_html($blank_description); ?></p>
					<?php endif; ?>
				</div>
			</div>
		<?php endif; ?>

		<?php
		$image_id = 64; /// REPLACE WITH IMAGE ID
		?>

		<h3>// EXAMPLE 1: Square thumbnail (150x150px, cropped center)</h3>

		<img src="<?php simplimg($image_id, '150px', '150px', 'crop'); ?>" alt="">



		<h3>// EXAMPLE 2: Scale to width, auto height (maintains aspect ratio)</h3>

		<img src="<?php simplimg($image_id, '150px', 'auto'); ?>" alt="">

		<h3>// EXAMPLE 3: 16:9 Aspect Ratio (cropped center)</h3>

		<p>// Note: For aspect ratios, use the ratio numbers directly (16, 9)</p>
		<p>// This will crop to 16:9 at the original resolution, then scale down if needed</p>
		<img src="<?php simplimg($image_id, 16, 9, 'crop'); ?>" alt="">


		<h3>// EXAMPLE 4: 16:9 Aspect Ratio (cropped from top)</h3>

		<p>// Perfect for hero banners where you want to keep the top of the image</p>
		<img src="<?php simplimg($image_id, 16, 9, 'top'); ?>" alt="">


		<h3>// EXAMPLE 5: 16:9 Aspect Ratio (cropped from bottom)</h3>

		<img src="<?php simplimg($image_id, 16, 9, 'bottom'); ?>" alt="">

		<h3>// EXAMPLE 6: Fixed width with proportional height</h3>

		<p>// 300px wide, height calculated to maintain aspect ratio</p>
		<img src="<?php simplimg($image_id, 300, 'auto'); ?>" alt="">


		<h3>// EXAMPLE 7: Specific dimensions without cropping</h3>

		<p>// Will scale to fit within 800x600, maintaining aspect ratio</p>
		<img src="<?php simplimg($image_id, 800, 600, false); ?>" alt="">

		<h2>// COMMON ASPECT RATIOS</h2>

		<h3>// 16:9 (Widescreen)</h3>
		<img src="<?php simplimg($image_id, 16, 9, 'crop'); ?>" alt="">

		<h3>// 4:3 (Traditional)</h3>
		<img src="<?php simplimg($image_id, 4, 3, 'crop'); ?>" alt="">

		<h3>// 1:1 (Square)</h3>
		<img src="<?php simplimg($image_id, 1, 1, 'crop'); ?>" alt="">

		<h3>// 21:9 (Ultrawide)</h3>
		<img src="<?php simplimg($image_id, 21, 9, 'crop'); ?>" alt="">

		<h3>// 3:2 (Classic 35mm)</h3>
		<img src="<?php simplimg($image_id, 3, 2, 'crop'); ?>" alt="">

	<?php wp_footer(); ?></body>

</html>