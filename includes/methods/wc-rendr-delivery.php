<?php

	namespace WcRendr\Methods;

	use DVDoug\BoxPacker\Packer;
	use DVDoug\BoxPacker\Test\TestBox;
	use DVDoug\BoxPacker\Test\TestItem;
	use WcRendr\Plugin;

	if (!defined('ABSPATH') || !defined('WPINC')) {
		exit;
	}

	/**
	 * Class WC_Rendr_Delivery
	 *
	 * @version 1.0.0
	 * @since   1.0.0
	 * @package WcRendr\Methods
	 */
	class WC_Rendr_Delivery extends \WC_Shipping_Method {

		/**
		 * WC_Rendr_Delivery constructor.
		 *
		 * @param int $instance_id
		 */
		public function __construct($instance_id = 0) {

			$this->id = 'wcrendr';
			$this->instance_id = absint($instance_id);
			$this->method_title = __('Rendr Delivery', 'wcrendr');
			$this->method_description = __('Let customers choose Rendr as their delivery method.', 'wcrendr');
			$this->supports = array(
				'shipping-zones',
				'settings',
				'instance-settings',
				'instance-settings-modal',
			);

			$this->init();

			add_action('woocommerce_update_options_shipping_' . $this->id, [$this, 'process_admin_options']);
			add_action('woocommerce_update_options_shipping_' . $this->id, [$this, 'process_opening_hours'], 20);

		}

		/**
		 * init function
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 */
		public function init() {
			$this->instance_form_fields = [
/*				'label_fast' => [
					'type' => 'text',
					'title' => 'Fast Delivery Title',
					'default' => 'Fastest',
					'description' => 'Label shown to user for the fast rendr delivery type',
					'desc_tip' => true,
				],*/
				'disable_fast' => [
					'type' => 'checkbox',
					'title' => 'Disable Fast Delivery',
					'label' => 'Do not display "fast" delivery option to customers.',
					'description' => 'Check this to ignore any delivery options of type fast.',
					'desc_tip' => true,
				],
/*				'label_flexible' => [
					'type' => 'text',
					'title' => 'Flex Delivery Title',
					'default' => 'Flex',
					'description' => 'Label shown to user for the flex rendr delivery type',
					'desc_tip' => true,
				],*/
				'disable_flexible' => [
					'type' => 'checkbox',
					'title' => 'Disable Flex Deliveries',
					'label' => 'Do not display "flex" delivery options  cto customers.',
					'description' => 'Check this to ignore any delivery options of type flex.',
					'desc_tip' => true,
				],
/*				'label_standard' => [
					'type' => 'text',
					'title' => 'Standard Delivery Title',
					'default' => 'Standard',
					'description' => 'Label shown to user for the fast standard delivery type',
					'desc_tip' => true,
				],*/
				'disable_standard' => [
					'type' => 'checkbox',
					'title' => 'Disable Standard Delivery',
					'label' => 'Do not display "standard" delivery options to customers.',
					'description' => 'Check this to ignore any delivery options of type standard.',
					'desc_tip' => true,
				],
			];
			$this->form_fields = include(WCRENDR_DIR.'/includes/methods/wc-rendr-delivery-settings.php');
			$this->title = __('Rendr Delivery', 'wcrendr');
			$this->tax_status = 'taxable';
			$this->label_fast = 'Rendr Fast';
			$this->label_flexible = 'Rendr Flexible';
			$this->label_standard = 'Rendr Standard';
			

		}

		/**
		 * generate_opening_hours_html function
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 *
		 * @param $key
		 * @param $data
		 *
		 * @return false|string
		 */
		public function generate_opening_hours_html($key, $data) {
			$field_key = $this->get_field_key($key);
			$opening_hours = $this->settings['opening_hours'];
			ob_start();
			?>
			<tr valign="top" style="display: none !important;">
				<td colspan="2" style="padding: 0">
					<div class="wcrendr-oh-table">
						<table cellpadding="0" cellspacing="0">
							<thead>
								<tr>
									<th>Available?</th>
									<th>Day</th>
									<th>From</th>
									<th>To</th>
								</tr>
							</thead>
							<tbody>
								<?php
									foreach(['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'] as $day) :
								?>
									<tr>
										<td>
											<input <?php if(is_array($opening_hours) && !empty($opening_hours[$day])) { echo 'checked="checked"'; } ?> type="checkbox" name="<?php echo esc_attr( $field_key ); ?>_<?php echo $day ?>" />
										</td>
										<td><?php echo ucwords($day) ?></td>
										<td>
											<input type="text" name="<?php echo esc_attr( $field_key ); ?>_<?php echo $day ?>_from" placeholder="09:00" value="<?php if(is_array($opening_hours) && !empty($opening_hours[$day]) && !empty($opening_hours[$day]['from'])) { echo esc_attr($opening_hours[$day]['from']); } ?>" />
										</td>
										<td>
											<input type="text" name="<?php echo esc_attr( $field_key ); ?>_<?php echo $day ?>_to" placeholder="17:00" value="<?php if(is_array($opening_hours) && !empty($opening_hours[$day]) && !empty($opening_hours[$day]['to'])) { echo esc_attr($opening_hours[$day]['to']); } ?>" />
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				</td>
			</tr>
			</table>
			<table class="form-table">
			<?php
			return ob_get_clean();
		}

		/**
		 * generate_pack_presets_html function
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 *
		 * @param $key
		 * @param $data
		 *
		 * @return false|string
		 */
		public function generate_pack_presets_html($key, $data) {
			$field_key = $this->get_field_key($key);
			try {
				if(!empty($this->settings['packing_presets'])) {
					$presets = json_decode($this->settings['packing_presets'], true);
					if(!is_array($presets)) {
						$presets = [];
					}
				} else {
					$presets = [];
				}
			} catch(\Exception $e) {
				$presets = [];
			}
			ob_start();
			include(WCRENDR_DIR.'/includes/methods/templates/pack-presets.php');
			return ob_get_clean();
		}

		/**
		 * get_post_data function
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 * @return array
		 */
		public function get_post_data() {
			$data = parent::get_post_data();
			if(isset($data['woocommerce_wcrendr_packing_presets_clone'])) {
				unset($data['woocommerce_wcrendr_packing_presets_clone']);
			}
			if(isset($data['woocommerce_wcrendr_packing_presets']) && is_array($data['woocommerce_wcrendr_packing_presets'])) {
				$data['woocommerce_wcrendr_packing_presets'] = json_encode($data['woocommerce_wcrendr_packing_presets']);
			}
			return $data;
		}

		/**
		 * generate_button_html function
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 *
		 * @param $key
		 * @param $data
		 *
		 * @return false|string
		 */
		public function generate_button_html( $key, $data ) {

			ob_start();
			?>
			<tr valign="top">
				<th scope="row" class="titledesc">
				</th>
				<td class="forminp">
					<fieldset><button type="button" class="button" id="<?php echo $data['id']; ?>"><?php echo $data['title']; ?></button>
					</fieldset>
				</td>
			</tr>
			<?php

			return ob_get_clean();
		}

		/**
		 * has_valid_credentials function
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 * @return false
		 */
		public function has_valid_credentials() {
			return false;
		}

		/**
		 * process_opening_hours function
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 * @return bool|void
		 */
		public function process_opening_hours() {

			if($this->instance_id) {
				return;
			}

			$post_data = $this->get_post_data();
			
			$opening_hours = [];
			
			foreach($post_data as $index => $value) {
				if(strpos($index, 'woocommerce_wcrendr_opening_hours') === 0) {
					$day = str_replace(['woocommerce_wcrendr_opening_hours_', '_from', '_to'], '', $index);
					if($index == 'woocommerce_wcrendr_opening_hours_'.$day.'_from') {
						$field = 'from';
					} elseif($index == 'woocommerce_wcrendr_opening_hours_'.$day.'_to') {
						$field = 'to';
					} else {
						$field = 'on';
					}
					if(!isset($opening_hours[$day])) {
						$opening_hours[$day] = [];
					}
					$opening_hours[$day][$field] = sanitize_text_field($value);
				}
			}

			foreach($opening_hours as $day => $values) {
				if(empty($values['from'])) {
					$opening_hours[$day]['from'] = '00:00';
				}
				if(empty($values['to'])) {
					$opening_hours[$day]['to'] = '23:59';
				}
			}

			$this->settings['opening_hours'] = $opening_hours;

			return update_option($this->get_option_key(), apply_filters( 'woocommerce_settings_api_sanitized_fields_' . $this->id, $this->settings ), 'yes' );
			
		}

		/**
		 * verify_credentials function
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 *
		 * @param array $args
		 *
		 * @throws \Exception
		 */
		public function verify_credentials(array $args) {

			if(empty($args['brand_id'])) {
				throw new \Exception(__('Brand ID is required.', 'wcrendr'));
			}

			if(empty($args['store_id'])) {
				throw new \Exception(__('Store ID is required.', 'wcrendr'));
			}

			if(empty($args['client_id'])) {
				throw new \Exception(__('Client ID is required.', 'wcrendr'));
			}

			if(empty($args['client_secret'])) {
				throw new \Exception(__('Client secret is required.', 'wcrendr'));
			}
			
			$request = wp_remote_post('https://api.rendr.delivery/'.$args['brand_id'].'/auth/token', [
				'headers' => [
					'content-type' => 'application/json',
				],
				'body' => json_encode([
					'grant_type' => 'client_credentials',
					'client_id' => $args['client_id'],
					'client_secret' => $args['client_secret'],
				]),
				'timeout' => 10000,
			]);

			if(wp_remote_retrieve_response_code($request) !== 200) {
				throw new \Exception(wp_remote_retrieve_response_message($request).' - Status code: '.wp_remote_retrieve_response_code($request).'<br>Please contact <a href="mailto:support@rendr.delivery">support@rendr.delivery</a> to confirm Rendr Credentials');
			}

			return true;
			
		}

		/**
		 * get_ready_pickup_date function
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 * @throws \Exception
		 */
		private function get_ready_pickup_date($day = false) {
			
			$now = new \DateTime('now', new \DateTimeZone(get_option('timezone_string')));

			if(!empty($this->get_option('handling_time_hours'))) {
				$now->add(new \DateInterval('PT'.$this->get_option('handling_time_hours').'H'));
			}

			if(!empty($this->get_option('handling_time_days'))) {
				$now->add(new \DateInterval('P'.$this->get_option('handling_time_days').'D'));
			}

			if($day) {
				// Next business day
				$now->modify('+1 day');
				while($now->format('N') >= 6) {
					$now->modify('+1 day');
				}
				$now = new \DateTime($now->format('Y-m-d').' 10:00:00', new \DateTimeZone(get_option('timezone_string')));
				$same_day = false;
			} else {
				$same_day = true;
			}

			$now->modify('+30 minutes');

			return $now;

			$c = 0;
			// while day is not available or if day is available but we are past the closing time or  -> try the following day
			while(empty($this->settings['opening_hours'][strtolower($now->format('l'))])
				||
				(
					!empty($this->settings['opening_hours'][strtolower($now->format('l'))])
					&&
					$same_day
					&&
					$now->format('Gi') >= ltrim(str_replace(':', '', $this->settings['opening_hours'][strtolower($now->format('l'))]['to']), '0')
				)
			) {
				$now->add(new \DateInterval('P1D'));
				$same_day = false;
			}

			// If its aday in the future ensure pick up time is earliest in the day as we have already applied our handling time.
			if(!$same_day || $now->format('Hi') < $this->settings['opening_hours'][strtolower($now->format('l'))]['from']) {
				$now = new \DateTime($now->format('Y-m-d').' '.$this->settings['opening_hours'][strtolower($now->format('l'))]['from'].':00', new \DateTimeZone(get_option('timezone_string')));
			}

			return $now->format('c');

		}

		/**
		 * get_box_presets function
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 * @return array|mixed
		 */
		public function get_box_presets() {

			try {
				if(!empty($this->settings['packing_presets'])) {
					$presets = json_decode($this->settings['packing_presets'], true);
					if(!is_array($presets)) {
						$presets = [];
					}
				} else {
					$presets = [];
				}
			} catch(\Exception $e) {
				$presets = [];
			}

			return $presets;

		}

		/**
		 * get_endpoint function
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 *
		 * @param $endpoint
		 *
		 * @return string
		 * @throws \Exception
		 */
		public function get_endpoint($endpoint) {
			if(empty($this->get_option('brand_id')) || empty($this->get_option('store_id'))) {
				throw new \Exception('Empty credentials.');
			}
			return 'https://api.rendr.delivery/'.$this->settings['brand_id'].'/'.ltrim($endpoint, "/");
		}

		/**
		 * get_auth_token function
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 * @return mixed
		 * @throws \Exception
		 */
		private function get_auth_token() {


			if(($token = get_transient('wcvrendr_auth_token')) != false) {
				return $token;
			}
			if(empty($this->get_option('client_id')) || empty($this->get_option('client_secret'))) {
				throw new \Exception('Empty or invalid credentials.');
			}

			$request = wp_remote_post($this->get_endpoint('/auth/token'), [
				'headers' => [
					'Content-Type' => 'application/json',
				],
				'body' => json_encode([
					'grant_type' => 'client_credentials',
					'client_id' => $this->get_option('client_id'),
					'client_secret' => $this->get_option('client_secret'),
				]),
				'timeout' => 10000,
			]);

			if(wp_remote_retrieve_response_code($request) != 200) {
				throw new \Exception('Error retrieving authorization code. Message: '.wp_remote_retrieve_response_message($request));
			}

			$body = json_decode(wp_remote_retrieve_body($request), true);

			if(empty($body['data']['access_token'])) {
				throw new \Exception('Error retrieving authorization code.');
			}

			set_transient('wcvrendr_auth_token', $body['data']['access_token'], HOUR_IN_SECONDS);

			return $body['data']['access_token'];

		}

		/**
		 * get_package_line_items function
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 *
		 * @param $package
		 *
		 * @return array
		 */
		private function get_package_line_items($package) {

			$items = [];

			foreach($package['contents'] as $key => $item) {

				$items[] = [
					'code' => empty($item['data']->get_sku()) ? 'DEFAULTSKU'.substr(md5(time()), 0, 8) : $item['data']->get_sku(),
					'name' => $item['data']->get_name(),
					'price_cents' => round($item['line_total']*100),
					'quantity' => $item['quantity'],
				];

			}

			return $items;

		}

		/**
		 * get_item_shipping_attribute function
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 *
		 * @param $item
		 * @param $attr
		 *
		 * @return mixed
		 * @throws \Exception
		 */
		private function get_item_shipping_attribute($item, $attr) {

			$func = "get_{$attr}";
			$value = $item['data']->$func();

			if(empty($value)) {
				$value = $this->get_option('default_'.$attr);
			}

			if(empty($value)) {
				throw new \Exception('Cannot calculate parcels. '.$attr.' is invalid.');
			}

			if($attr == 'weight') {
				return wc_get_weight($value, 'kg');
			}

			return wc_get_dimension($value, 'cm');

		}

		/**
		 * get_package_parcels function
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 *
		 * @param $package
		 *
		 * @return array
		 * @throws \Exception
		 */
		private function get_package_parcels($package) {

			$parcels = [];

			if($this->get_option('packing_preference') == 'together') {

				$parcel_w = 0;
				$parcel_h = 0;
				$parcel_l = 0;
				$parcel_weight = 0;

				foreach($package['contents'] as $i => $lineitem) {

					$height = (int)$this->get_item_shipping_attribute($lineitem, 'height');
					$width = (int)$this->get_item_shipping_attribute($lineitem, 'width');
					$length = (int)$this->get_item_shipping_attribute($lineitem, 'length');
					$weight = (float)$this->get_item_shipping_attribute($lineitem, 'weight');

					if($i === 0) {
						$parcel_h = $height;
						$parcel_l = $length;
						$parcel_w = $width;
						$parcel_weight = $weight;
					} else {
						if($height <= $width && $height <= $length) {
							$parcel_h += $height;
						} else if($width <= $height && $width <= $length) {
							$parcel_w += $width;
						} else {
							$parcel_l += $length;
						}
						$parcel_weight += $weight;
					}

				}

				if($parcel_weight < 1) {
					$parcel_weight = 1;
				}

				$parcels[] = [
					'reference' => "Parcel #0001",
					'description' => '',
					'length_cm' => (int)$parcel_l,
					'height_cm' => (int)$parcel_h,
					'width_cm' => (int)$parcel_w,
					'weight_kg' => (int)$parcel_weight,
					'quantity' => 1,
				];

			} else if($this->get_option('packing_preference') == 'separate') {
				$i2 = 1;
				foreach($package['contents'] as $i => $lineitem) {

					$height = (int)$this->get_item_shipping_attribute($lineitem, 'height');
					$width = (int)$this->get_item_shipping_attribute($lineitem, 'width');
					$length = (int)$this->get_item_shipping_attribute($lineitem, 'length');
					$weight = (float)$this->get_item_shipping_attribute($lineitem, 'weight') < 1 ? 1 : (float)$this->get_item_shipping_attribute($lineitem, 'weight');


					$parcels[] = [
						'reference' => "Parcel #".str_pad($i2, 4, '0', STR_PAD_LEFT),
						'description' => 'Contents: '.$lineitem['data']->get_name(),
						'length_cm' => (int)$length,
						'height_cm' => (int)$height,
						'width_cm' => (int)$width,
						'weight_kg' => (int)$weight,
						'quantity' => 1,
					];
					$i2++;
				}

			} else {

				$packer = new Packer();

				if(empty($this->get_box_presets())) {
					throw new \Exception('Preset of box sizes not defined. Cannot calculate order parcels.');
				}

				$max_height = 0;
				$max_width = 0;
				$max_length = 0;
				$box_index = 1;

				// Adds box sizes
				foreach($this->get_box_presets() as $box) {

					$max_width = $box['width'] > $max_width ? $box['width'] : $max_width;
					$max_height = $box['height'] > $max_height ? $box['height'] : $max_height;
					$max_length = $box['length'] > $max_length ? $box['length'] : $max_length;

					$packer->addBox(new TestBox($box['label'], $box['width'], $box['length'], $box['height'], 0, $box['width']-4, $box['length']-4, $box['height']-4, 100000));
				}

				// Adds our line items
				foreach($package['contents'] as $lineitem) {

					$height = (int)$this->get_item_shipping_attribute($lineitem, 'height');
					$width = (int)$this->get_item_shipping_attribute($lineitem, 'width');
					$length = (int)$this->get_item_shipping_attribute($lineitem, 'length');
					$weight = (float)$this->get_item_shipping_attribute($lineitem, 'weight') < 1 ? 1 : $this->get_item_shipping_attribute($lineitem, 'weight');

					if($height <= $max_height && $length < $max_length && $width < $max_width) {

						$packer->addItem(new TestItem($lineitem['data']->get_name(), $width, $length, $height, $weight, false), $lineitem['quantity']);

					} else {

						$parcels[] = [
							'reference' => "Parcel #".str_pad($box_index, 4, '0', STR_PAD_LEFT),
							'description' => 'Type: '."Parcel #".str_pad($box_index, 4, '0', STR_PAD_LEFT).' | Item: '.$lineitem['data']->get_name(),
							'length_cm' => (int)$length,
							'height_cm' => (int)$height,
							'width_cm' => (int)$width,
							'weight_kg' => (int)$this->get_item_shipping_attribute($lineitem, 'weight'),
							'quantity' => $lineitem['quantity'],
						];

					}

				}

				$packedBoxes = $packer->pack();

				foreach ($packedBoxes as $packedBox) {

					$_packedItems = $packedBox->getItems();
					$packedItems = [];
					foreach($_packedItems as $packedItem) {
						if(!isset($packedItems[$packedItem->getDescription()])) {
							$packedItems[$packedItem->getDescription()] = 1;
						} else {
							$packedItems[$packedItem->getDescription()]++;
						}
					}
					$packedItemsTitle = [];
					foreach($packedItems as $item => $qty) {
						$packedItemsTitle[] = $qty.'x '.$item;
					}

					$parcels[] = [
						'reference' => "Parcel #".str_pad($box_index, 4, '0', STR_PAD_LEFT),
						'description' => 'Type: '.$packedBox->getBox()->getReference().' | Items: '.implode(', ', $packedItemsTitle),
						'length_cm' => (int)$packedBox->getBox()->getOuterLength(),
						'height_cm' => (int)$packedBox->getBox()->getOuterDepth(),
						'width_cm' => (int)$packedBox->getBox()->getOuterWidth(),
						'weight_kg' => (int)($packedBox->getWeight()) < 1 ? 1 : (int)($packedBox->getWeight()),
						'quantity' => 1,
					];

					$box_index++;
				}

			}
				return $parcels;

		}

		/**
		 * can_calculate_shipping function
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 *
		 * @param $package
		 *
		 * @return bool
		 * @throws \Exception
		 */
		private function can_calculate_shipping($package) {

			if(empty($package['destination'])) {
				throw new \Exception('Cannot calculate postage. destination empty.');
			}

			if(empty($package['destination']['city'])) {
				throw new \Exception('Cannot calculate postage. city empty.');
			}

			if(empty($package['destination']['state'])) {
				throw new \Exception('Cannot calculate postage. state empty.');
			}

			if(empty($package['destination']['postcode'])) {
				throw new \Exception('Cannot calculate postage. postcode empty.');
			}

			return true;

		}

				/**
		 * get_package_rates_for_day function
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		*
		* @param $package
		* @param false $day
		 *
		 * @return mixed
		* @throws \Exception
		*/
		private function get_package_rates_for_day($package, $day = false) {

			$request = wp_remote_post($this->get_endpoint('/deliveries/quote'), [
				'headers' => [
					'Authorization' => 'Bearer '.$this->get_auth_token(),
					'Content-Type' => 'application/json',
				],
				'body' => json_encode([
					'store_id' => $this->get_option('store_id'),
					'ready_for_pickup_at' => $this->get_ready_pickup_date($day)->format('c'),
					'address' => [
						'city' => $package['destination']['city'],
						'state' => $package['destination']['state'],
						'post_code' => $package['destination']['postcode'],
					],
					'line_items' => $this->get_package_line_items($package),
					'parcels' => $this->get_package_parcels($package),
				]),
				'timeout' => 10000,
			]);
			if(wp_remote_retrieve_response_code($request) != 200) {
				throw new \Exception('Invalid response code when fetching available rates.');
			}

			$data = json_decode(wp_remote_retrieve_body($request), true);

			if(empty($data) || empty($data['data'])) {
				//throw new \Exception('Empty response received when fetching available rates');
				return [];
			}

			return $data;

		}

		/**
		 * get_package_rates function
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 *
		 * @param $package
		 *
		 * @return array
		 * @throws \Exception
		 */
		private function get_package_rates($package) {

			$data = $this->get_package_rates_for_day($package);
			$rates = [];

			if(!empty($data['data'])) {
				foreach($data['data'] as $delivery_type => $rate) {
					if($this->get_instance_option('disable_'.$delivery_type) == 'yes') {
						continue;
					}
					$rates[$delivery_type] = $rate;
					$rates[$delivery_type]['ready_for_pickup'] = $this->get_ready_pickup_date()->format('c');
				}
			}

			if(count($data['data']) < 3) {
				$data = $this->get_package_rates_for_day($package, true);
			} else {
				return $rates;
			}

			if(!empty($data['data'])) {

				foreach($data['data'] as $delivery_type => $rate) {
					if($this->get_instance_option('disable_'.$delivery_type) == 'yes') {
						continue;
					}
					if(!isset($rates[$delivery_type])) {
						$rates[$delivery_type] = $rate;
						$rates[$delivery_type]['ready_for_pickup'] = $this->get_ready_pickup_date(true)->format('c');
					}
				}
			}

			return $rates;

		}

		public function request_delivery($order, $method_name, $method) {

			/** @var \WC_Order_Item_Shipping $method */

			/** @var \WC_Order $order */
			$package = array(
				'contents'        => [],
				'destination'     => array(
					'country'   => $order->get_shipping_country(),
					'state'     => $order->get_shipping_state(),
					'postcode'  => $order->get_shipping_postcode(),
					'city'      => $order->get_shipping_city(),
					'address'   => $order->get_shipping_address_1(),
					'address_2' => $order->get_shipping_address_2(),
				)
	        );

			foreach($order->get_items() as $item) {
			/** @var \WC_Order_Item_Product $item */
				$package['contents'][] = [
					'data' => ($item->get_variation_id() > 0 ? wc_get_product($item->get_variation_id()) : wc_get_product($item->get_product_id())),
					'quantity' => ($item->get_quantity()),
					'line_total' => $item->get_total(),

				];
			}

			$type = '';
			if($method_name == $this->label_fast) {
				$type = 'fast';
			} else if($method_name == $this->label_flexible) {
				$type = 'flexible';
			} else {
				$type = 'standard';
			}

			try {

				$request = wp_remote_post($this->get_endpoint('/deliveries'), [
					'headers' => [
						'Authorization' => 'Bearer '.$this->get_auth_token(),
						'Content-Type' => 'application/json',
					],
					'body' => json_encode([
						'store_id' => $this->get_option('store_id'),
						'ready_for_pickup_at' => !empty($method->get_meta('ready_for_pickup')) ? $method->get_meta('ready_for_pickup') :  $this->get_ready_pickup_date()->format('c'),
						'delivery_type' => $type,
						'reference' => 'Order #'.$order->get_id(),
						'reference_origin' => 'woocommerce',
						'woocommerce_version' => WCRENDR_VERSION,
						'address' => [
							'business' => false,
							'address' => $order->get_shipping_address_1(),
							'city' => $order->get_shipping_city(),
							'state' => $order->get_shipping_state(),
							'post_code' => $order->get_shipping_postcode(),
						],
						'customer' => [
							'first_name' => $order->get_shipping_first_name(),
							'last_name' => $order->get_shipping_last_name(),
							'phone' => $order->get_billing_phone(),
							'email' => $order->get_billing_email(),
						],
						'line_items' => $this->get_package_line_items($package),
						'parcels' => $this->get_package_parcels($package),
					]),
					'timeout' => 10000,
				]);

				$body = json_decode(wp_remote_retrieve_body($request), true);

				if(!empty($body['data']['id'])) {
					update_post_meta($order->get_id(), 'rendr_delivery_id', $body['data']['id']);
					update_post_meta($order->get_id(), 'rendr_delivery_status', 'requested');
				}
				
			
			} catch(\Exception $e) {
			}
			
		}

		public function fetch_delivery_status($order, $method) {
			$request = wp_remote_get($this->get_endpoint('/deliveries/'.get_post_meta($order->get_id(), 'rendr_delivery_id', true)), [
				'headers' => [
					'Authorization' => 'Bearer '.$this->get_auth_token(),
				]
]);

			try {
				$body = json_decode(wp_remote_retrieve_body($request), true);
				if(!empty($body['data'])) {
					if(!empty($body['data']['consignment_number'])) {
						update_post_meta($order->get_id(), 'rendr_delivery_ref', $body['data']['consignment_number']);
					}
				}
			} catch(\Exception $e) {
			}
			if(in_array(get_post_meta($order->get_id(), 'rendr_delivery_status', true), ['booked', 'in_transit', 'cancelled'])) {
				as_schedule_single_action((time()+600), 'wcrendr_fetch_delivery_status', [$order->get_id()]);
			}
		}
		
		public function book_delivery($ref_id, $order) {
            
           $request = wp_remote_request($this->get_endpoint('/deliveries/'.$ref_id.'/book'), [
                'headers' => [
						'Authorization' => 'Bearer '.$this->get_auth_token(),
						'Content-Type' => 'application/json',
					],
					'method' => 'PATCH',
					'timeout' => 20000,
            ]);
			update_post_meta($order->get_id(), 'rendr_delivery_status', 'booked');
			as_schedule_single_action(time(), 'wcrendr_fetch_delivery_status', [$order->get_id()]);
            //update_post_meta($order->get_id(), 'rendr_delivery_ref', 'RENDR000021');
            //update_post_meta($order->get_id(), 'rendr_delivery_status', 'cancelled');
            
		}

		/**
		 * calculate_shipping function
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 *
		 * @param array $package
		 */
		public function calculate_shipping($package = array()) {

			$rates = [];

			try {
				
				$this->can_calculate_shipping($package);

				foreach($this->get_package_rates($package) as $type => $rate) {

					$attr = "label_{$type}";
					$label = $this->$attr;

					if(empty($label)) {
						$label = ucwords($type);
					}

					$_rate = [
						'id' => $this->get_rate_id().':'.$type,
						'label' => $label,
						'cost' => $rate['price_cents']/100,
						'package' => $package,
						'meta_data' => [
							'delivery_from' => $rate['from_datetime'],
							'delivery_to' => $rate['to_datetime'],
							'num_days' => $rate['num_days'],
							'type' => $type,
							'ready_for_pickup' => $rate['ready_for_pickup'],
						],
					];

					if($this->is_taxable()) {
						$_rate['taxes'] = \WC_Tax::calc_tax(($rate['price_cents']/100), \WC_Tax::get_shipping_tax_rates(), get_option('woocommerce_prices_include_tax') == 'yes');
						foreach($_rate['taxes'] as $taxrate) {
							$_rate['cost'] -= $taxrate;
						}
					}

					$rates[] = $_rate;
					
				}
				
			} catch(\Exception $e) {

			}
			foreach($rates as $r) {
				$this->add_rate($r);
			}
			
			/*try {
				
				$ready_for_pickup = $this->get_ready_pickup_date();
				
				$packer = new Packer();
				
				foreach($this->get_box_presets() as $box) {
					$packer->addBox(new TestBox($box['label'], $box['width'], $box['length'], $box['height'], 0, $box['width']-4, $box['length']-4, $box['height']-4, 100));
				}
				
				foreach($package['contents'] as $lineitem) {
					$packer->addItem(new TestItem($lineitem['data']->get_title(), $lineitem['data']->get_width(), $lineitem['data']->get_length(), $lineitem['data']->get_height(), $lineitem['data']->get_weight(), false), $lineitem['quantity']);
				}

				$packedBoxes = $packer->pack();

				echo "<pre>These items fitted into " . count($packedBoxes) . " box(es)" . PHP_EOL;
				foreach ($packedBoxes as $packedBox) {
					$boxType = $packedBox->getBox(); // your own box object, in this case TestBox
					echo "This box is a {$boxType->getReference()}, it is {$boxType->getOuterWidth()}mm wide, {$boxType->getOuterLength()}mm long and {$boxType->getOuterDepth()}mm high" . PHP_EOL;
					echo "The combined weight of this box and the items inside it is {$packedBox->getWeight()}g" . PHP_EOL;

					echo "The items in this box are:" . PHP_EOL;
					$packedItems = $packedBox->getItems();
					foreach ($packedItems as $packedItem) { // $packedItem->getItem() is your own item object, in this case TestItem
						echo $packedItem->getItem()->getDescription() . PHP_EOL;
					}
				}
				exit;
				
				//echo '<pre>'; echo print_r($package, true); exit;
				
			//	echo '<pre>'; echo print_r($ready_for_pickup, true); exit;
				
			} catch(\Exception $e) {
				
				//echo '<pre>'; echo print_r($e->getMessage(), true); exit;
				
			}*/
			
		}

		/**
		 * has_product_without_dimensions_or_weight function
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 * @return mixed
		 */
		private function has_product_without_dimensions_or_weight() {

			if(get_transient('wcrendr_prods_with_no_dimensions')) {
				return get_transient('wcrendr_prods_with_no_dimensions');
			} else {
				if(count(get_posts([
					'post_type' => ['product', 'product_variation'],
					'posts_per_page' => -1,
					'fields' => 'ids',
					'post_status' => 'publish',
					'meta_query' => [
						'relation' => 'OR',
						[
							'key' => '_weight',
							'compare' => 'NOT EXISTS',
						],
						[
							'key' => '_weight',
							'compare' => '=',
							'value' => '',
						],
						[
							'key' => '_length',
							'compare' => 'NOT EXISTS',
						],
						[
							'key' => '_length',
							'compare' => '=',
							'value' => '',
						],
						[
							'key' => '_width',
							'compare' => 'NOT EXISTS',
						],
						[
							'key' => '_width',
							'compare' => '=',
							'value' => '',
						],
						[
							'key' => '_height',
							'compare' => 'NOT EXISTS',
						],
						[
							'key' => '_height',
							'compare' => '=',
							'value' => '',
						],
					]
				])) > 0) {
					set_transient('wcrendr_prods_with_no_dimensions', true, 0);
				} else {
					set_transient('wcrendr_prods_with_no_dimensions', false, 0);
				}
			}
		}
	}