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
						$delivery_text = sprintf(__('Delivered by <strong>%s</strong>', 'wcrendr'), $delivery_to->format('l'));
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