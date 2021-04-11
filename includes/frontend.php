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
				if($delivery_from->format('Ymd') != $delivery_to->format('Ymd')) {
					$delivery_text = sprintf(__('Get it by <strong>%s<sup>%s</sup> of %s</strong>', 'wcrendr'), $delivery_to->format('l, j'), $delivery_to->format('S'), $delivery_to->format('F'));
				} else {
					if($delivery_to->format('Ymd') == $today->format('Ymd')) {
						$delivery_text = sprintf(__('Get it <strong>Today</strong> before <strong>%s</strong>', 'wcrendr'), $delivery_to->format('H:i'));
					} else if($delivery_to->format('Ymd') == $tomorrow->format('Ymd')) {
						$delivery_text = sprintf(__('Get it <strong>Tomorrow</strong> by <strong>%s</strong>', 'wcrendr'), $delivery_to->format('H:i'));
					} else {
						$delivery_text = sprintf(__('Get it on <strong>%s<sup>%s</sup>t of %s by %s</strong>', 'wcrendr'), $delivery_to->format('l, j'), $delivery_to->format('S'), $delivery_to->format('F'), $delivery_to->format('H:i'));
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
			$markup = ob_get_clean();
			echo apply_filters('wcrendr_rate_description', $markup, $rate, $index);

		}

	}