<?php

	namespace WcRendr;

	/**
	 * Class Frontend
	 *
	 * @version 1.0.0
	 * @since   1.0.0
	 * @package WcRendr
	 */
	class Frontend {

		/**
		 * Frontend constructor.
		 */
		public function __construct() {

			add_action('woocommerce_after_shipping_rate', [$this, 'method_description'], 10, 2);

		}

		/**
		 * method_description function
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 *
		 * @param \WC_Shipping_Rate $rate
		 * @param                   $index
		 */
		public function method_description(\WC_Shipping_Rate $rate, $index) {

			$meta = $rate->get_meta_data();

			if(empty($meta['delivery_from']) || empty($meta['delivery_to']) || (empty($meta['num_days']) && $meta['num_days'] != 0)) {
				return;
			}

			try {

				$delivery_from = new \DateTime($meta['delivery_from']);
				$today = new \DateTime('now', new \DateTimeZone(get_option('timezone_string')));
				$tomorrow = new \DateTime('tomorrow', new \DateTimeZone(get_option('timezone_string')));
				$delivery_to = new \DateTime($meta['delivery_to']);

				if($meta['type'] == 'standard') {

					$delivery_text = sprintf(__('Delivered by <strong>%s</strong>', 'wcrendr'), $delivery_to->format('l'));

				} else {

					$hour = clone $delivery_to;
					if($delivery_to->format('i') > 0) {
						$hour->modify('+1 hour');
					}
					$hour = $hour->format('g').$hour->format('a');

					if($meta['type'] == 'flexible') {
						$hour = '5pm';
					}

					if($delivery_to->format('Ymd') == $today->format('Ymd')) {
						$delivery_text = sprintf(__('Delivered <strong>Today</strong> by <strong>%s</strong>', 'wcrendr'), $hour);
					} else if($delivery_to->format('Ymd') == $tomorrow->format('Ymd')) {
						$delivery_text = sprintf(__('Delivered by <strong>Tomorrow</strong> <strong>%s</strong>', 'wcrendr'), $hour);
					} else {
						$delivery_text = sprintf(__('Delivered by <strong>%s</strong> <strong>%s</strong>', 'wcrendr'), $delivery_to->format('l'), $hour);
					}
				}

			} catch(\Exception $e) {

				return;

			}

			ob_start(); ?>
			<div class="wcrendr-rate-description">
				<small><?php echo $delivery_text ?></small>
			</div>

			<?php
			if(WcRendr()->admin->get_method()->get_option('disable_brand') !== 'yes') {
			$has_rendr = true;

			$chosen_method = isset($_POST['shipping_method']) ? $_POST['shipping_method'] : wc_get_chosen_shipping_method_ids();

			foreach($chosen_method as $smethoid) {
				if(strpos($smethoid, 'wcrendr') !== false) {
					$has_rendr = true; break;
				}
			}
			if($has_rendr) {
				?>
				<script type="text/javascript">
					jQuery(function() {
						if(jQuery('.delivered-by-rendr').length == 0 && jQuery('#shipping_method') .length > 0) {
							jQuery('#shipping_method').before('<div style="display: flex; margin: 0 0 1em 0;" class="delivered-by-rendr" class="wcrendr-delivery-powered-by"><a style=" font-size: 12px; white-space: nowrap; flex: 0 0 100%; display: flex; align-items: center;" href="<?php echo WCRENDR_URL; ?>/assets/images/RendrWebsitePopUpDesktop.png" data-lightbox="Delivered by Rendr">Delivery powered by <svg style="	width: auto;height: 16px;margin: 2px auto auto 7px;" fill="none" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 841 224"><g clip-path="url(#clip0)" fill="#35AABF"><path d="M186.366 210.172a6.376 6.376 0 01-2.319 8.726 6.368 6.368 0 01-3.193.855h-46.2a6.351 6.351 0 01-5.542-3.243l-32.513-57.725H61.926l-9.906 55.72a6.406 6.406 0 01-6.279 5.248H6.383a6.366 6.366 0 01-4.898-2.27 6.358 6.358 0 01-1.386-5.218l18.384-104.22a10.197 10.197 0 0110.046-8.428h43.8l-2.565 14.563a5.102 5.102 0 004.652 5.975 5.102 5.102 0 002.748-.575l29.275-15.331 30.691-16.1a5.122 5.122 0 002.713-4.297 5.11 5.11 0 00-2.33-4.517l-24-15.418-24.936-16.01a5.086 5.086 0 00-7.754 3.42l-2.859 16.334h-40.2a10.2 10.2 0 01-10.047-11.974l7.262-41.149a6.41 6.41 0 016.28-5.28h82.577c48.585 0 79.541 23.231 79.541 63.15 0 33.754-20.755 62.234-52.624 76.474l35.613 61.29z"/><path d="M290.347 66.215a93.514 93.514 0 00-92.837 78.333c-7.753 44.87 19.782 78.627 68.692 78.627 35.23 0 63.916-16.687 77.978-43.073a5.091 5.091 0 00-.208-4.983 5.1 5.1 0 00-4.362-2.417h-37.818a10.74 10.74 0 00-7.362 3.14c-5.039 4.8-12.322 7.385-21.095 7.385-17.66 0-26.946-10.2-28.8-23.821h98.182a10.171 10.171 0 009.923-7.839 183.46 183.46 0 002.095-10.118c7.134-45.487-20.106-75.234-64.388-75.234zm20.137 63.768h-61.617c6.515-15.478 18.574-25.678 36.233-25.678 16.097 0 26.297 7.753 25.384 25.678zM472.366 65.006c-16.1 0-30.012 5.248-43.956 14.535l.441-2.358a6.37 6.37 0 00-6.25-7.547h-37.115a6.386 6.386 0 00-6.28 5.277l-24.087 137.382a6.38 6.38 0 006.279 7.46h37.206a6.358 6.358 0 006.279-5.277l15.478-89.122c10.525-9.906 21.05-14.859 30.336-14.859 16.422 0 25.708 10.2 21.669 31.574l-12.294 70.224a6.333 6.333 0 00.117 2.756 6.328 6.328 0 003.467 4.119c.844.39 1.765.59 2.695.585h37.5a6.358 6.358 0 006.28-5.277l13.945-80.455c7.429-42.718-13.62-69.017-51.71-69.017zM720.746 0h-37.47a6.359 6.359 0 00-6.279 5.277l-12.707 72.73c-9.9-6.839-22.583-11.174-39-11.174-45.49 0-89.771 39.623-89.771 90.39 0 38.09 26 66.244 62.236 66.244a87.231 87.231 0 0022.877-3.006 92.005 92.005 0 0019.841-8.138 6.369 6.369 0 003.597 6.838c.841.39 1.756.592 2.682.591h37.261a6.361 6.361 0 006.28-5.248L727.027 7.487a6.362 6.362 0 00-1.385-5.216A6.352 6.352 0 00720.746 0zm-72.553 168.693c-11.145 10.525-22.583 13.619-33.432 13.619-17.955 0-29.393-13.619-29.393-31.28 0-23.526 19.486-43.336 42.718-43.336 10.82 0 21.05 3.419 28.479 13.943l-8.372 47.054zM840.293 74.941l-6.1 34.905a6.33 6.33 0 01-5.748 5.248c-22.111 1.562-37.532 7.961-50.236 18.927l-4.954 28.066-8.726 49.263a10.184 10.184 0 01-10.023 8.4h-29.452a10.19 10.19 0 01-7.805-3.63 10.185 10.185 0 01-2.044-3.906 10.182 10.182 0 01-.205-4.403l.737-4.187a.792.792 0 01.03-.265l13.326-76.061 9.375-53.451a5.125 5.125 0 015.041-4.215h33.787a10.199 10.199 0 0110.053 11.939l-.736 4.186-.029.266a90.17 90.17 0 0121.551-12.56 86.15 86.15 0 0125.5-5.984 6.34 6.34 0 016.658 7.462z"/></g><defs><clipPath id="clip0"><path fill="#fff" d="M0 0h840.396v223.467H0z"/></clipPath></defs></svg></a></div>')
						}
					})
				</script>
				<?php
			}
			}
			$markup = ob_get_clean();
			echo apply_filters('wcrendr_rate_description', $markup, $rate, $index);

		}

	}