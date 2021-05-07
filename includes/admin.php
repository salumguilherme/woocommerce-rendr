<?php

	namespace WcRendr;

	use WcRendr\Methods\WC_Rendr_Delivery;

	if (!defined('ABSPATH') || !defined('WPINC')) {
		exit;
	}

	/**
	 * Class Admin
	 *
	 * @version 1.0.0
	 * @since   1.0.0
	 * @package WcRendr
	 */
	class Admin {

		private $has_logo_displayed;

		/**
		 * Admin constructor.
		 */
		public function __construct() {

			// scripts
			add_action('admin_enqueue_scripts', [$this, 'admin_scripts']);

			// Ajax
			add_action('wp_ajax_test_rendr_creds', [$this, 'test_rendr_creds']);

			// Books delivery on order processing
			add_action('woocommerce_order_status_processing', [$this, 'request_delivery']);

			add_filter('woocommerce_admin_order_actions', [$this, 'order_actions'], 10, 2);

			add_action('admin_post_book-rendr-delivery', [$this, 'book_delivery']);

			add_action('admin_post_labels-rendr-delivery', [$this, 'labels_delivery']);

			add_filter( 'manage_edit-shop_order_columns', [$this, 'column_delivery_status'] );

			add_action( 'manage_shop_order_posts_custom_column', [$this, 'column_delivery_status_content'], 2 );
			add_action( 'woocommerce_single_product_summary', [$this, 'delivered_by_rendr'], 40 );

			add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);

			add_action('woocommerce_after_add_to_cart_form', [$this, 'delivered_by_rendr'], 45);

			add_action('wcrendr_fetch_delivery_status', [$this, 'get_delivery_status']);

			add_action('woocommerce_checkout_process', [$this, 'enforce_phone_number_length']);

			add_action('woocommerce_admin_order_data_after_shipping_address', [$this, 'delivery_info_order_page']);
			
			add_action('woocommerce_review_order_before_submit', [$this, 'terms_and_conditions'], 1);

			add_filter( 'woocommerce_shipping_calculator_enable_city', '__return_true' );

		}
		
		public function terms_and_conditions() {

			$has_rendr = false;

			$chosen_method = isset($_POST['shipping_method']) ? $_POST['shipping_method'] : wc_get_chosen_shipping_method_ids();

			foreach($chosen_method as $smethoid) {
				if(strpos($smethoid, 'wcrendr') !== false) {
					$has_rendr = true; break;
				}
			}

			if($has_rendr) {
				?>

				<p class="form-row validate-required" style="margin-top: 1em">
					<label class="woocommerce-form__label woocommerce-form__label-for-checkbox checkbox">
						<input type="checkbox" class="woocommerce-form__input woocommerce-form__input-checkbox input-checkbox" name="terms_wcrendr" <?php checked(isset( $_POST['terms_wcrendr'] ) ); // WPCS: input var ok, csrf ok. ?> id="terms_wcrendr" />
						<span class="woocommerce-terms-and-conditions-checkbox-text">By proceeding with Rendr Delivery, you grant Rendr an Authority To Leave (ATL) the goods in a safe place and agree Rendr bears no responsibility for any loss or damage that may occur. See <a href="https://rendr.delivery/pages/terms-and-conditions" target="_blank">full Terms & Conditions</a></span>&nbsp;<span class="required">*</span>
					</label>
					<input type="hidden" name="terms-field" value="1" />
				</p>

				<?php
			}
			
		}

		public function delivery_info_order_page($order) {
			$id = get_post_meta($order->get_id(), 'rendr_delivery_id', true);

			if(empty($id)) {
				return;
			}

			?>
			<h3>Rendr Delivery</h3>
			<p>Delivery ID: <?php echo get_post_meta($order->get_id(), 'rendr_delivery_id', true) ?><br>
				Delivery Status: <?php echo ucwords(get_post_meta($order->get_id(), 'rendr_delivery_status', true)) ?><br>
				<?php if(!empty(get_post_meta($order->get_id(), 'rendr_delivery_ref', true))) : ?>Consignment Number: <?php echo get_post_meta($order->get_id(), 'rendr_delivery_ref', true) ?><br><?php endif; ?>
				<?php if(get_post_meta($order->get_id(), 'rendr_delivery_status', true) == 'requested') : ?><a href="<?php echo wp_nonce_url(add_query_arg(['action' => 'book-rendr-delivery', 'order' => $order->get_id()], admin_url('admin-post.php')), 'wcrendr-book-delivery'); ?>">Book Delivery</a><?php  endif;?>
				<?php if(!empty(get_post_meta($order->get_id(), 'rendr_delivery_ref', true))) : ?>
					<a href="https://retailer.rendr.delivery/<?php echo $this->get_method()->get_option('brand_id') ?>/deliveries/ <?php echo get_post_meta($order->get_id(), 'rendr_delivery_id', true) ?>" target="_blank">View in Rendr portal</a><br><?php endif; ?>
			</p>


			<?php

		}

		public function enforce_phone_number_length() {
			if(((is_array($_POST['shipping_method']) && strpos(serialize($_POST['shipping_method']), 'wcrendr') !== false) || (!is_array($_POST['shipping_method']) && strpos($_POST['shipping_method'], 'wcrendr') !== false)) && !(preg_match('/^[0-9]{10}$/D', $_POST['billing_phone']))){
				wc_add_notice( "Please enter a 10 digit phone number. A mobile or landline with area code."  ,'error' );
			}
		}

		public function delivered_by_rendr() {
			if(WcRendr()->admin->get_method()->get_option('disable_brand') !== 'yes') {
				echo '<div style="display: flex; margin: 1em 0;" class="delivered-by-rendr" class="wcrendr-delivery-powered-by"><a style=" flex: 0 0 100%; display: flex; align-items: center;" href="' . WCRENDR_URL . '/assets/images/RendrWebsitePopUpDesktop.png' . '" data-lightbox="Delivered by Rendr">Delivery powered by <svg style="	width: auto;height: 16px;margin: 2px auto auto 7px;" fill="none" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 841 224"><g clip-path="url(#clip0)" fill="#35AABF"><path d="M186.366 210.172a6.376 6.376 0 01-2.319 8.726 6.368 6.368 0 01-3.193.855h-46.2a6.351 6.351 0 01-5.542-3.243l-32.513-57.725H61.926l-9.906 55.72a6.406 6.406 0 01-6.279 5.248H6.383a6.366 6.366 0 01-4.898-2.27 6.358 6.358 0 01-1.386-5.218l18.384-104.22a10.197 10.197 0 0110.046-8.428h43.8l-2.565 14.563a5.102 5.102 0 004.652 5.975 5.102 5.102 0 002.748-.575l29.275-15.331 30.691-16.1a5.122 5.122 0 002.713-4.297 5.11 5.11 0 00-2.33-4.517l-24-15.418-24.936-16.01a5.086 5.086 0 00-7.754 3.42l-2.859 16.334h-40.2a10.2 10.2 0 01-10.047-11.974l7.262-41.149a6.41 6.41 0 016.28-5.28h82.577c48.585 0 79.541 23.231 79.541 63.15 0 33.754-20.755 62.234-52.624 76.474l35.613 61.29z"/><path d="M290.347 66.215a93.514 93.514 0 00-92.837 78.333c-7.753 44.87 19.782 78.627 68.692 78.627 35.23 0 63.916-16.687 77.978-43.073a5.091 5.091 0 00-.208-4.983 5.1 5.1 0 00-4.362-2.417h-37.818a10.74 10.74 0 00-7.362 3.14c-5.039 4.8-12.322 7.385-21.095 7.385-17.66 0-26.946-10.2-28.8-23.821h98.182a10.171 10.171 0 009.923-7.839 183.46 183.46 0 002.095-10.118c7.134-45.487-20.106-75.234-64.388-75.234zm20.137 63.768h-61.617c6.515-15.478 18.574-25.678 36.233-25.678 16.097 0 26.297 7.753 25.384 25.678zM472.366 65.006c-16.1 0-30.012 5.248-43.956 14.535l.441-2.358a6.37 6.37 0 00-6.25-7.547h-37.115a6.386 6.386 0 00-6.28 5.277l-24.087 137.382a6.38 6.38 0 006.279 7.46h37.206a6.358 6.358 0 006.279-5.277l15.478-89.122c10.525-9.906 21.05-14.859 30.336-14.859 16.422 0 25.708 10.2 21.669 31.574l-12.294 70.224a6.333 6.333 0 00.117 2.756 6.328 6.328 0 003.467 4.119c.844.39 1.765.59 2.695.585h37.5a6.358 6.358 0 006.28-5.277l13.945-80.455c7.429-42.718-13.62-69.017-51.71-69.017zM720.746 0h-37.47a6.359 6.359 0 00-6.279 5.277l-12.707 72.73c-9.9-6.839-22.583-11.174-39-11.174-45.49 0-89.771 39.623-89.771 90.39 0 38.09 26 66.244 62.236 66.244a87.231 87.231 0 0022.877-3.006 92.005 92.005 0 0019.841-8.138 6.369 6.369 0 003.597 6.838c.841.39 1.756.592 2.682.591h37.261a6.361 6.361 0 006.28-5.248L727.027 7.487a6.362 6.362 0 00-1.385-5.216A6.352 6.352 0 00720.746 0zm-72.553 168.693c-11.145 10.525-22.583 13.619-33.432 13.619-17.955 0-29.393-13.619-29.393-31.28 0-23.526 19.486-43.336 42.718-43.336 10.82 0 21.05 3.419 28.479 13.943l-8.372 47.054zM840.293 74.941l-6.1 34.905a6.33 6.33 0 01-5.748 5.248c-22.111 1.562-37.532 7.961-50.236 18.927l-4.954 28.066-8.726 49.263a10.184 10.184 0 01-10.023 8.4h-29.452a10.19 10.19 0 01-7.805-3.63 10.185 10.185 0 01-2.044-3.906 10.182 10.182 0 01-.205-4.403l.737-4.187a.792.792 0 01.03-.265l13.326-76.061 9.375-53.451a5.125 5.125 0 015.041-4.215h33.787a10.199 10.199 0 0110.053 11.939l-.736 4.186-.029.266a90.17 90.17 0 0121.551-12.56 86.15 86.15 0 0125.5-5.984 6.34 6.34 0 016.658 7.462z"/></g><defs><clipPath id="clip0"><path fill="#fff" d="M0 0h840.396v223.467H0z"/></clipPath></defs></svg></a></div>';
			}
		}

		public function enqueue_scripts() {
			if(is_product()) {
				wp_enqueue_script('wcrendr_lightbox', WCRENDR_URL.'/assets/js/lightbox.min.js', ['jquery'], '1.0');
				wp_enqueue_style('wcrendr_lightbox-css', WCRENDR_URL.'/assets/css/lightbox.min.css');
			}
		}

		public function get_method() {
			if(!isset($this->method)) {
				$this->method = new WC_Rendr_Delivery();
			}
			return $this->method;
		}

		public function column_delivery_status_content($column) {
			global $post;
			if($column == 'delivery_status') {
				if(!empty(get_post_meta($post->ID, 'rendr_delivery_id', true))) {
					echo ucwords(get_post_meta($post->ID, 'rendr_delivery_status', true));
				}
				if(!empty(get_post_meta($post->ID, 'rendr_delivery_ref', true))) {
					echo '<br>Consignment No: '.get_post_meta($post->ID, 'rendr_delivery_ref', true).'<br><a href="https://retailer.rendr.delivery/'.$this->get_method()->get_option('brand_id').'/deliveries/'.get_post_meta($post->ID, 'rendr_delivery_id', true).'" target="_blank">View in Rendr portal</a>';
				}
			}
		}

		/**
		 * admin_scripts function
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 */
		public function admin_scripts() {
			$screen = get_current_screen();

			if($screen->base === 'woocommerce_page_wc-settings' && isset($_GET['section']) && $_GET['section'] === 'wcrendr') {
				wp_register_script('jquery-inputmask', WCRENDR_URL.'/assets/lib/inputmask/jquery.inputmask.min.js', ['jquery'], '5.0.6', true);
				wp_register_script('wcrendr-settings', WCRENDR_URL.'/assets/js/wcrendr-settings.js', ['jquery', 'jquery-ui-datepicker', 'jquery-inputmask'], WCRENDR_VERSION, true);
				wp_localize_script('wcrendr-settings', 'wcrendr_settings', [
					'ajax_url' => admin_url('admin-ajax.php'),
					'verify_creds' => wp_create_nonce('verify_creds_'.wp_get_current_user()->ID),
				]);
				wp_enqueue_script('wcrendr-settings');

			}
			wp_enqueue_style('wcrendr-admin', WCRENDR_URL.'/assets/css/admin.css', [], WCRENDR_VERSION);
		}

		public function column_delivery_status($columns) {

			$new_columns = [];

			foreach($columns as $id => $col) {
				if($id == 'wc_actions') {
					$new_columns['delivery_status'] = 'Delivery Status';
				}
				$new_columns[$id] = $col;
			}
			return $new_columns;
		}

		/**
		 * test_rendr_creds function
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 */
		public function test_rendr_creds() {

			try {

				if(!wp_verify_nonce($_POST['nonce'], 'verify_creds_'.wp_get_current_user()->ID)) {
					throw new Error(__('Invalid or expired security token. Please refresh the page and try again', 'wcrendr'));
				}

				Plugin::instance()->get_method()->verify_credentials([
					'brand_id'      => sanitize_text_field($_POST['creds']['brand_id']),
					'store_id'      => sanitize_text_field($_POST['creds']['store_id']),
					'client_id'     => sanitize_text_field($_POST['creds']['client_id']),
					'client_secret' => sanitize_text_field($_POST['creds']['client_secret']),
				]);

				wp_send_json_success(['message' => __('Test successful.', 'wcrendr'),]);

			} catch(\Exception $e) {

				wp_send_json_error(['message' => __('Test failed. Message: ', 'wcrendr').$e->getMessage()]);

			}

		}

		public function get_delivery_status($order_id) {

			$order = wc_get_order($order_id);

			if(!$order){
				throw new \Exception('Order ID '.$order_id.'not found.');
			}

			$methods = $order->get_shipping_methods();
			foreach($methods as $method) {
				/** @var \WC_Order_Item_Shipping $method  */
				if( strpos($method->get_method_id(), 'wcrendr') === false && $method->get_method_id() !== 'wcrendr') {
					continue;
				}

				if(!method_exists($method, 'get_instance_id')) {
					$instance_id = explode(':', $method->get_method_id());
					$instance_id = $instance_id[1];
				} else {
					$instance_id = $method->get_instance_id();
				}
				$rendr = new WC_Rendr_Delivery($instance_id);
				$rendr->fetch_delivery_status($order, $method);
			}

		}

		public function request_delivery($order_id) {

			$order = wc_get_order($order_id);

			$methods = $order->get_shipping_methods();

			foreach($methods as $method) {
				/** @var \WC_Order_Item_Shipping $method  */
				if( strpos($method->get_method_id(), 'wcrendr') === false && $method->get_method_id() !== 'wcrendr') {
					continue;
				}
				if(!method_exists($method, 'get_instance_id')) {
					$instance_id = explode(':', $method->get_method_id());
					$instance_id = $instance_id[1];
				} else {
					$instance_id = $method->get_instance_id();
				}

				$rendr = new WC_Rendr_Delivery($instance_id);
				$rendr->request_delivery($order, $method->get_name(), $method);
			}

		}

		public function book_delivery() {

			if(!current_user_can('manage_options') || !wp_verify_nonce($_GET['_wpnonce'], 'wcrendr-book-delivery')) {
				wp_die('Busted!');
			}

			$rendr = new WC_Rendr_Delivery();
			$order = wc_get_order($_GET['order']);

			try {
				$rendr->book_delivery(get_post_meta($order->get_id(), 'rendr_delivery_id', true), $order);
			} catch(Exception $e) {

			}
			wp_redirect((!empty($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : admin_url()), 302);
			exit;

		}

		public function labels_delivery() {

			if(!current_user_can('manage_options') || !wp_verify_nonce($_GET['_wpnonce'], 'wcrendr-book-delivery')) {
				wp_die('Busted!');
			}

			$rendr = new WC_Rendr_Delivery();
			$order = wc_get_order($_GET['order']);

			try {
				$rendr->labels_delivery(get_post_meta($order->get_id(), 'rendr_delivery_id', true));
			} catch(Exception $e) {

			}

		}

		public function order_actions($actions, $order) {

			if(!empty(get_post_meta($order->get_id(), 'rendr_delivery_id', true)) && (get_post_meta($order->get_id(), 'rendr_delivery_status', true) == 'requested')) {
				$actions['book'] = [
					'url' => wp_nonce_url(add_query_arg(['action' => 'book-rendr-delivery', 'order' => $order->get_id()], admin_url('admin-post.php')), 'wcrendr-book-delivery'),
					'name' => 'Book Delivery',
					'action' => 'book',
				];
			} else if(!empty(get_post_meta($order->get_id(), 'rendr_delivery_id', true)) && (get_post_meta($order->get_id(), 'rendr_delivery_status', true) == 'booked')) {
				if(!get_transient('rendr_order_status_'.$order->get_id())) {
					$rendr = new WC_Rendr_Delivery();
				}
			}
			return $actions;

		}
	}