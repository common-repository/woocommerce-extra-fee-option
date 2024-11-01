<?php
/*
Plugin Name: TT Extra Fee Option for WooCommerce
Plugin URI: https://terrytsang.com/product/tt-woocommerce-extra-fee-option/
Description: Allow you to add an extra fee with a minimum order to WooCommerce
Version: 1.1.1
Author: Terry Tsang
Author URI: https://terrytsang.com/products
*/

/*  Copyright 2012-2023 Terry Tsang (email: terrytsang811@gmail.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

if ( ! defined( 'ABSPATH' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit;
}

// Define plugin name
define('wc_plugin_name_extra_fee_option', 'TT Extra Fee Option for WooCommerce');

// Define plugin version
define('wc_version_extra_fee_option', '1.1.1');


// Checks if the WooCommerce plugins is installed and active.
if(in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))){
	if(!class_exists('WooCommerce_Extra_Fee_Option')){
		class WooCommerce_Extra_Fee_Option{

			public static $plugin_prefix;
			public static $plugin_url;
			public static $plugin_path;
			public static $plugin_basefile;

			var $textdomain;
		    var $types;
		    var $options_extra_fee_option;
		    var $saved_options_extra_fee_option;

			/**
			 * Gets things started by adding an action to initialize this plugin once
			 * WooCommerce is known to be active and initialized
			 */
			public function __construct(){
				load_plugin_textdomain("woocommerce-extra-fee-option", false, dirname(plugin_basename(__FILE__)) . '/languages/');
				
				WooCommerce_Extra_Fee_Option::$plugin_prefix = 'wc_extra_fee_option_';
				WooCommerce_Extra_Fee_Option::$plugin_basefile = plugin_basename(__FILE__);
				WooCommerce_Extra_Fee_Option::$plugin_url = plugin_dir_url(WooCommerce_Extra_Fee_Option::$plugin_basefile);
				WooCommerce_Extra_Fee_Option::$plugin_path = trailingslashit(dirname(__FILE__));
				

				$this->types = array('fixed' => 'Fixed Fee', 'percentage' => 'Cart Percentage(%)');
				
				$this->options_extra_fee_option = array(
					'extra_fee_option_enabled' => '',
					'extra_fee_option_label' => 'Extra Fee',
					'extra_fee_option_type' => 'fixed',
					'extra_fee_option_cost' => 0,
					'extra_fee_option_taxable' => false,
					'extra_fee_option_minorder' => 0,
				);
	
				$this->saved_options_extra_fee_option = array();
				
				add_action('woocommerce_init', array(&$this, 'init'));
			}

			/**
			 * Initialize extension when WooCommerce is active
			 */
			public function init(){
				
				//add menu link for the plugin (backend)
				add_action( 'admin_menu', array( &$this, 'add_menu_extra_fee_option' ) );

				//add admin css3 button stylesheet
				//add_action('admin_init', array( &$this, 'tsang_plugin_admin_init') );
				
				if(get_option('extra_fee_option_enabled'))
				{
					//add_action( 'woocommerce_before_calculate_totals', array( &$this, 'woo_add_extra_fee') );
					add_action( 'woocommerce_cart_calculate_fees', array( &$this, 'woo_add_extra_fee') );
				}
			}
			
			function tsang_plugin_admin_init() {
				/* Register admin stylesheet. */
				wp_register_style( 'tsangPluginStylesheet', plugins_url('css/admin.css', __FILE__) );
			}
			
			function tsang_plugin_admin_styles() {
				/*
				 * It will be called only on your plugin admin page, enqueue our stylesheet here
				*/
				wp_enqueue_style( 'tsangPluginStylesheet' );
			}
		
			/**
			 * Set the extra fee with min order total limit
			 */
			public function woo_add_extra_fee() {
				global $woocommerce;
			
				$extra_fee_option_label		= get_option( 'extra_fee_option_label' ) ? get_option( 'extra_fee_option_label' ) : 'Extra Fee';
				$extra_fee_option_cost		= get_option( 'extra_fee_option_cost' ) ? get_option( 'extra_fee_option_cost' ) : '0';
				$extra_fee_option_type		= get_option( 'extra_fee_option_type' ) ? get_option( 'extra_fee_option_type' ) : 'fixed';
				$extra_fee_option_taxable	= get_option( 'extra_fee_option_taxable' ) ? get_option( 'extra_fee_option_taxable' ) : false;
				$extra_fee_option_minorder	= get_option( 'extra_fee_option_minorder' ) ? get_option( 'extra_fee_option_minorder' ) : '0';
				
				//get cart total
				$total = $woocommerce->cart->subtotal;
				
				//check for fee type (fixed fee or cart %)
				if($extra_fee_option_type == 'percentage'){
					$extra_fee_option_cost = ($extra_fee_option_cost / 100) * $total;
				} 
			
				//round the cost to 2 decimal points - fixed Paypal problem raised by Robbo870
				$extra_fee_option_cost = round($extra_fee_option_cost, 2);
				
				//if cart total less or equal than $min_order, add extra fee
				if($extra_fee_option_minorder > 0){
					if($total <= $extra_fee_option_minorder) {
						$woocommerce->cart->add_fee( __($extra_fee_option_label, 'woocommerce'), $extra_fee_option_cost, $extra_fee_option_taxable );
					}
				} else {
					$woocommerce->cart->add_fee( __($extra_fee_option_label, 'woocommerce'), $extra_fee_option_cost, $extra_fee_option_taxable );
				}
			}
			
			/**
			 * Add a menu link to the woocommerce section menu
			 */
			function add_menu_extra_fee_option() {
				$wc_page = 'woocommerce';
				$comparable_settings_page = add_submenu_page( $wc_page , __( 'TT Extra Fee Option', "woocommerce-extra-fee-option" ), __( 'TT Extra Fee Option', "woocommerce-extra-fee-option" ), 'manage_options', 'wc-extra-fee-option', array(
						&$this,
						'settings_page_extra_fee_option'
				));
				
				add_action( 'admin_print_styles-' . $comparable_settings_page, array( &$this, 'tsang_plugin_admin_styles') );
			}
			
			/**
			 * Create the settings page content
			 */
			public function settings_page_extra_fee_option() {
			
				// If form was submitted
				if ( isset( $_POST['submitted'] ) )
				{
					check_admin_referer( "woocommerce-extra-fee-option" );
	
					$this->saved_options_extra_fee_option['extra_fee_option_enabled'] = ! isset( $_POST['extra_fee_option_enabled'] ) ? '1' : $_POST['extra_fee_option_enabled'];
					$this->saved_options_extra_fee_option['extra_fee_option_label'] = ! isset( $_POST['extra_fee_option_label'] ) ? 'Extra Fee' : $_POST['extra_fee_option_label'];
					$this->saved_options_extra_fee_option['extra_fee_option_cost'] = ! isset( $_POST['extra_fee_option_cost'] ) ? 0 : $_POST['extra_fee_option_cost'];
					$this->saved_options_extra_fee_option['extra_fee_option_type'] = ! isset( $_POST['extra_fee_option_type'] ) ? 'fixed' : $_POST['extra_fee_option_type'];
					$this->saved_options_extra_fee_option['extra_fee_option_taxable'] = ! isset( $_POST['extra_fee_option_taxable'] ) ? false : $_POST['extra_fee_option_taxable'];
					$this->saved_options_extra_fee_option['extra_fee_option_minorder'] = ! isset( $_POST['extra_fee_option_minorder'] ) ? 0 : $_POST['extra_fee_option_minorder'];
						
					foreach($this->options_extra_fee_option as $field => $value)
					{
						$option_extra_fee_option = get_option( $field );
			
						if($option_extra_fee_option != $this->saved_options_extra_fee_option[$field])
							update_option( $field, $this->saved_options_extra_fee_option[$field] );
					}
						
					// Show message
					echo '<div id="message" class="updated fade"><p>' . __( "TT Extra Fee for WooCommerce options saved.", "woocommerce-extra-fee-option" ) . '</p></div>';
				}
			
				$extra_fee_option_enabled	= get_option( 'extra_fee_option_enabled' );
				$extra_fee_option_label		= get_option( 'extra_fee_option_label' ) ? get_option( 'extra_fee_option_label' ) : 'Extra Fee';
				$extra_fee_option_cost		= get_option( 'extra_fee_option_cost' ) ? get_option( 'extra_fee_option_cost' ) : '0';
				$extra_fee_option_type		= get_option( 'extra_fee_option_type' ) ? get_option( 'extra_fee_option_type' ) : 'fixed';
				$extra_fee_option_taxable	= get_option( 'extra_fee_option_taxable' ) ? get_option( 'extra_fee_option_taxable' ) : false;
				$extra_fee_option_minorder	= get_option( 'extra_fee_option_minorder' ) ? get_option( 'extra_fee_option_minorder' ) : '0';
				
				$checked_enabled = '';
				$checked_taxable = '';
			
				if($extra_fee_option_enabled)
					$checked_enabled = 'checked="checked"';
				
				if($extra_fee_option_taxable)
					$checked_taxable = 'checked="checked"';

			
				$actionurl = $_SERVER['REQUEST_URI'];
				$nonce = wp_create_nonce( "woocommerce-extra-fee-option" );
			
			
				// Configuration Page
			
				?>
				<div id="icon-options-general" class="icon32"></div>
				<h3><?php _e( 'TT Extra Fee Option', "woocommerce-extra-fee-option"); ?></h3>

				<form action="<?php echo $actionurl; ?>" method="post">
				<table>
						<tbody>
							<tr>
								<td colspan="2">
									<table class="widefat" cellspacing="2" cellpadding="2" border="0">
										<tr>
													<td width="25%"><?php _e( "Enable", "woocommerce-extra-fee-option" ); ?></td>
													<td>
														<input class="checkbox" name="extra_fee_option_enabled" id="extra_fee_option_enabled" value="0" type="hidden">
														<input class="checkbox" name="extra_fee_option_enabled" id="extra_fee_option_enabled" value="1" <?php echo $checked_enabled; ?> type="checkbox">
													</td>
												</tr>
												<tr>
													<td><?php _e( 'Label', "woocommerce-extra-fee-option" ); ?></td>
													<td>
														<input type="text" id="extra_fee_option_label" name="extra_fee_option_label" value="<?php echo $extra_fee_option_label; ?>" size="30" />
													</td>
												</tr>
												<tr>
													<td><?php _e( 'Amount', "woocommerce-extra-fee-option" ); ?></td>
													<td>
														<input type="text" id="extra_fee_option_cost" name="extra_fee_option_cost" value="<?php echo $extra_fee_option_cost; ?>" size="10" />
													</td>
												</tr>
												<tr>
													<td width="25%"><?php _e( 'Type', "woocommerce-extra-fee-option" ); ?></td>
													<td>
														<select name="extra_fee_option_type">
															<option value="fixed" <?php if($extra_fee_option_type == 'fixed') { echo 'selected="selected"'; } ?>><?php _e( 'Fixed Fee', "woocommerce-extra-fee-option" ); ?></option>
															<option value="percentage" <?php if($extra_fee_option_type == 'percentage') { echo 'selected="selected"'; } ?>><?php _e( 'Cart Percentage(%)', "woocommerce-extra-fee-option" ); ?></option>
														</select>
													</td>
												</tr>
												<tr>
													<td width="25%"><?php _e( 'Taxable', "woocommerce-extra-fee-option" ); ?></td>
													<td>
														<input class="checkbox" name="extra_fee_option_taxable" id="extra_fee_option_taxable" value="0" type="hidden">
														<input class="checkbox" name="extra_fee_option_taxable" id="extra_fee_option_taxable" value="1" <?php echo $checked_taxable; ?> type="checkbox">
													</td>
												</tr>
												<tr>
													<td><?php _e( 'Minumum Order<br><span style="color:#999;">(Optional, apply extra fee when cart total is less or equal than this amount)</span>', "woocommerce-extra-fee-option" ); ?></td>
													<td>
															<?php echo get_woocommerce_currency_symbol(); ?>&nbsp;<input type="text" id="extra_fee_option_minorder" name="extra_fee_option_minorder" value="<?php echo $extra_fee_option_minorder; ?>" size="10" />
													</td>
												</tr>
										<tr><td colspan="2">&nbsp;</td></tr>
									</table>

								</td>
							</tr>
							<tr><td>&nbsp;</td></tr>
							<tr>
								<td colspan=2">
									<input class="button-primary" type="submit" name="Save" value="<?php _e('Save Options', "woocommerce-extra-fee-option"); ?>" id="submitbutton" />
									<input type="hidden" name="submitted" value="1" /> 
									<input type="hidden" id="_wpnonce" name="_wpnonce" value="<?php echo $nonce; ?>" />
								</td>
							</tr>
							
						</tbody>
				</table>
				</form>
				<br />

				<br />
				<hr />
				<div style="height:30px"></div>
				<div class="center woocommerce-BlankState">
					<p><img src="<?php echo plugin_dir_url( __FILE__ ) ?>logo-terrytsang.png" title="Terry Tsang" alt="Terry Tsang" /></p>
					<h2 class="woocommerce-BlankState-message">Hi, I'm Terry from <a href="https://3minimonsters.com" target="_blank">3 Mini Monsters</a>. I have built WooCommerce plugins since 10 years ago and always find ways to make WooCommerce experience better through my products and articles. Thanks for using my plugin and do share around if you love this.</h2>

					<a class="woocommerce-BlankState-cta button" target="_blank" href="https://terrytsang.com/shop">Check out our WooCommerce plugins</a>

					<a class="woocommerce-BlankState-cta button-primary button" href="https://terrytsang.com/product/tt-woocommerce-extra-fee-option-pro" target="_blank">Upgrade to TT Extra Fee Option PRO</a>
					
				</div>
				<br />
				<h3>PRO Version: TT Extra Fee Option for WooCommerce</h3>
				<p><img src="<?php echo plugin_dir_url( __FILE__ ) ?>assets/images/tt-woocommerce-extra-fee-option-pro-screenshot.png" alt="Extra Fee Option PRO for WooCommerce" title="Extra Fee Option PRO for WooCommerce" width="1000" height="auto" />
					

				<br /><br /><br />

				<div class="components-card is-size-medium woocommerce-marketing-recommended-extensions-card woocommerce-marketing-recommended-extensions-card__category-coupons woocommerce-admin-marketing-card">
					<div class="components-flex components-card__header is-size-medium"><div>
						<span class="components-truncate components-text"></span>
						<div style="margin: 20px 20px">Try my other WooCommerce plugins to power up your online store and bring more sales/leads to you.</div>
					</div>
				</div>

				<div class="components-card__body is-size-medium">
					<div class="woocommerce-marketing-recommended-extensions-card__items woocommerce-marketing-recommended-extensions-card__items--count-6">
						<a href="https://terrytsang.com/product/tt-woocommerce-add-to-cart-buy-now-pro/" target="_blank" class="woocommerce-marketing-recommended-extensions-item">
							<div class="woocommerce-admin-marketing-product-icon">
								<img src="<?php echo esc_url( plugin_dir_url( __FILE__ ) ); ?>assets/images/icon-woocommerce-add-to-cart-buy-now.png" title="WooCommerce Add to Cart Buy Now" alt="WooCommerce Add to Cart Buy Now" width="50" height="50" />
							</div>
							<div class="woocommerce-marketing-recommended-extensions-item__text">
								<h4>TT Add to Cart Buy Now</h4>
								<p style="color:#333333;">Customize the "Add to cart" button and add a simple “Buy Now” button to your WooCommerce website.</p>
							</div>
						</a>

						<a href="https://terrytsang.com/product/tt-woocommerce-donation-checkout-pro/" target="_blank" class="woocommerce-marketing-recommended-extensions-item">
							<div class="woocommerce-admin-marketing-product-icon">
								<img src="<?php echo plugin_dir_url( __FILE__ ) ?>assets/images/icon-woocommerce-donation-checkout.png" title="WooCommerce Donation Checkout" alt="WooCommerce Donation Checkout" width="50" height="50" />
							</div>
							<div class="woocommerce-marketing-recommended-extensions-item__text">
								<h4>TT Donation Checkout</h4>
								<p style="color:#333333;">Enable customers to topup their donation/tips at the checkout page.</p>
							</div>
						</a>

						<a href="https://terrytsang.com/product/tt-woocommerce-one-page-checkout-pro/" target="_blank" class="woocommerce-marketing-recommended-extensions-item">
							<div class="woocommerce-admin-marketing-product-icon">
								<img src="<?php echo plugin_dir_url( __FILE__ ) ?>assets/images/icon-woocommerce-onepage-checkout.png" title="WooCommerce One-Page Checkout" alt="WooCommerce One-Page Checkout" width="50" height="50" />
							</div>
							<div class="woocommerce-marketing-recommended-extensions-item__text">
								<h4>TT One-Page Checkout</h4>
								<p style="color:#333333;">Combine cart and checkout at one page to simplify entire WooCommerce checkout process.</p>
							</div>
						</a>

						<a href="https://terrytsang.com/product/tt-woocommerce-discount-option-pro/" target="_blank" class="woocommerce-marketing-recommended-extensions-item">
							<div class="woocommerce-admin-marketing-product-icon">
								<img src="<?php echo plugin_dir_url( __FILE__ ) ?>assets/images/icon-woocommerce-custom-checkout.png" title="WooCommerce Discount Option" alt="WooCommerce Discount Option" width="50" height="50" />
							</div>
							<div class="woocommerce-marketing-recommended-extensions-item__text">
								<h4>TT Discount Option</h4>
								<p style="color:#333333;">Add a fixed fee/percentage discount based on minimum order amount, products, categories, date range and day.</p>
							</div>
						</a>

						<!-- <a href="https://terrytsang.com/product/tt-woocommerce-coming-soon/" target="_blank" class="woocommerce-marketing-recommended-extensions-item">
							<div class="woocommerce-admin-marketing-product-icon">
								<img src="<?php echo plugin_dir_url( __FILE__ ) ?>assets/images/icon-woocommerce-coming-soon-product.png" title="TT Coming Soon for WooCommerce" alt="TT Coming Soon for WooCommerce" width="50" height="50" />
							</div>
							<div class="woocommerce-marketing-recommended-extensions-item__text">
								<h4>TT Coming Soon</h4>
								<p style="color:#333333;">Display countdown clock at coming-soon product page.</p>
							</div>
						</a> -->

						<!-- <a href="https://terrytsang.com/product/tt-woocommerce-product-badge/" target="_blank" class="woocommerce-marketing-recommended-extensions-item">
							<div class="woocommerce-admin-marketing-product-icon">
								<img src="<?php echo plugin_dir_url( __FILE__ ) ?>assets/images/icon-woocommerce-product-badge.png" title="WooCommerce Product Badge" alt="WooCommerce Product Badge" width="50" height="50" />
							</div>
							<div class="woocommerce-marketing-recommended-extensions-item__text">
								<h4>TT Product Badge</h4>
								<p style="color:#333333;">Add product badges liked Popular, Sales, Featured to the product.</p>
							</div>
						</a> -->

						<!-- <a href="https://terrytsang.com/product/tt-woocommerce-product-catalog/" target="_blank" class="woocommerce-marketing-recommended-extensions-item">
							<div class="woocommerce-admin-marketing-product-icon">
								<img src="<?php echo plugin_dir_url( __FILE__ ) ?>assets/images/icon-woocommerce-product-catalog.png" title="TT Product Catalog for WooCommerce" alt="TT Product Catalog for WooCommerce" width="50" height="50" />
							</div>
							<div class="woocommerce-marketing-recommended-extensions-item__text">
								<h4>TT Product Catalog</h4>
								<p style="color:#333333;">Hide Add to Cart / Checkout button and turn your website into product catalog.</p>
							</div>
						</a> -->

					
					</div>
				</div>
				
			<?php
			}
			
			/**
			 * Get the setting options
			 */
			function get_options() {
				
				foreach($this->options_extra_fee_option as $field => $value)
				{
					$array_options[$field] = get_option( $field );
				}
					
				return $array_options;
			}
			
			/**
			 * Load javascript for the page
			 */
			/*public function script_extra_fee_option()
			{
				wp_enqueue_script( 'jquery-ui-datepicker' );
				wp_enqueue_script( 'custom-plugin-script', plugins_url('/js/script.js', __FILE__));
			}*/
				
			/**
			 * Load stylesheet for the page
			 */
			/*public function stylesheet_extra_fee_option() {
				wp_register_style( 'custom-plugin-stylesheet', plugins_url('/css/style.css', __FILE__) );
				wp_enqueue_style( 'custom-plugin-stylesheet' );
			}*/
			
		}//end class
			
	}//if class does not exist
	
	$woocommerce_extra_fee_option = new WooCommerce_Extra_Fee_Option();
}
else{
	add_action('admin_notices', 'wc_extra_fee_option_error_notice');
	function wc_extra_fee_option_error_notice(){
		global $current_screen;
		if($current_screen->parent_base == 'plugins'){
			echo '<div class="error"><p>'.__(wc_plugin_name_extra_fee_option.' requires <a href="http://www.woocommerce.com/" target="_blank">WooCommerce</a> to be activated in order to work. Please install and activate <a href="'.admin_url('plugin-install.php?tab=search&type=term&s=WooCommerce').'" target="_blank">WooCommerce</a> first.').'</p></div>';
		}
	}
}

?>