<?php
/**
 * Rule level settings
 *
 * @package role-based-pricing
 */

if (!defined('ABSPATH')) {
	exit;
}
?>

<div class="csp_admin_main">
	<div class="csp_admin_main_left"><label><strong><?php echo esc_html__('Rule Priority', 'addify_role_price'); ?></strong></label></div>
	<div class="csp_admin_main_right">
		<input type="number" name="csp_rule_priority" class="rule_input" min="0" max="100" placeholder="0" value="<?php echo esc_attr($post->menu_order); ?>" />
		<input type="hidden" name="csp_rules" class="rule_input" value="yes" />
		<p class="csp_msg"><?php echo esc_html__('Provide number between 0 and 100, If more than one rules are applied on same item then rule with high priority will be applied. 1 is high and 100 is low.', 'addify_role_price'); ?></p>
	</div>
</div>

<div class="csp_admin_main">
	<div class="csp_admin_main_left"><label><strong><?php echo esc_html__('Apply on All Products', 'addify_role_price'); ?></strong></label></div>
	<div class="csp_admin_main_right">
		<?php
		$applied_on_all_products = get_post_meta($post->ID, 'csp_apply_on_all_products', true);
		?>
		<input type="checkbox" name="csp_apply_on_all_products" id="csp_apply_on_all_products" value="yes" <?php echo checked('yes', $applied_on_all_products); ?>>
		<p class="csp_msg"><?php echo esc_html__('Check this if you want to apply this rule on all products.', 'addify_role_price'); ?></p>
	</div>
</div>

<div class="csp_admin_main hide_all_pro">
	<div class="csp_admin_main_left"><label><strong><?php echo esc_html__('Select Products', 'addify_role_price'); ?></strong></label></div>
	<div class="csp_admin_main_right">
		<?php
		$applied_on = get_post_meta($post->ID, 'csp_applied_on_products', true);
		?>
		<select name="csp_applied_on_products[]" id="csp_applied_on_products" class="applied_on_products sel_pros" multiple="multiple" style="width:100%">

			<?php

			if (!empty($applied_on)) {

				foreach ($applied_on as $pro) {

					$prod_post = get_post($pro);

					?>

					<option value="<?php echo intval($pro); ?>" selected="selected"><?php echo esc_attr($prod_post->post_title); ?></option>

					<?php
				}
			}
			?>

		</select>



	</div>
</div>

<div class="csp_admin_main hide_all_pro">
	<div class="csp_admin_main_left"><label><strong><?php echo esc_html__('Select Categories', 'addify_role_price'); ?></strong></label></div>
	<div class="csp_admin_main_right">
		

	<select class="csp_applied_on_categories" name="csp_applied_on_categories[]" multiple="multiple">
			<?php
			if (!empty($csp_applied_on_categories)) {

				$pre_vals = $csp_applied_on_categories;
			} else {
				$pre_vals = array();
			}
			foreach ( afcsp_get_all_categories() as $afcsp_category_id ) :
				$selected       = in_array( $afcsp_category_id, $pre_vals ) ? 'selected' : '';
				$afcsp_category = get_term_by( 'id', $afcsp_category_id, 'product_cat' );
				if ( $afcsp_category ) :
					?>
				<option value="<?php echo esc_attr( $afcsp_category_id ); ?>"<?php echo esc_attr( $selected ); ?>><?php echo esc_html( $afcsp_category->name ); ?></option>
					<?php
				endif;
			endforeach;
			?>
	</select>
	
	</div>

</div>


<div class="csp_admin_main">
	<h3><?php echo esc_html__('Role Based Pricing(By Customers)', 'addify_role_price'); ?></h3>
	<p><?php echo esc_html__('If more than one rule is applied on same customer then rule that is added first will be applied.', 'addify_role_price'); ?></p>
	<div class="cdiv">
		<table cellspacing="0" cellpadding="0" border="1" width="1225">
			<thead>
				<tr>
					<th align="center" class="rcname"><?php echo esc_html__('Customer', 'addify_role_price'); ?></th>
					<th align="center" class="rcname1"><?php echo esc_html__('Adjustment Type', 'addify_role_price'); ?></th>
					<th align="center" class="rcname2"><?php echo esc_html__('Value', 'addify_role_price'); ?></th>
					<th align="center" class="rcname3"><?php echo esc_html__('Min Qty', 'addify_role_price'); ?></th>
					<th align="center" class="rcname4"><?php echo esc_html__('Max Qty', 'addify_role_price'); ?></th>
					<th align="center" class="rcname5"><?php echo esc_html__('Start Date', 'addify_role_price'); ?></th>
					<th align="center" class="rcname6"><?php echo esc_html__('End Date', 'addify_role_price'); ?></th>
					<th align="center" class="rcname7"><?php echo esc_html__('Replace?', 'addify_role_price'); ?>
						<div class="tooltip">?
							<span class="tooltiptext"><?php echo esc_html__('This will only work for Fixed Price, Fixed Decrease and Percentage Decrease.', 'addify_role_price'); ?></span>
						</div>
					</th>
					<th align="center" class="rcname8"><?php echo esc_html__('X', 'addify_role_price'); ?></th>
				</tr>
			</thead>

			<tbody>
				<?php

				$a = 1;
				if (!empty($rcus_base_price)) {
					foreach ($rcus_base_price as $cus_price) {

						if (!empty($cus_price['replace_orignal_price'])) {

							$replace_orignal_price = 'yes';
						} else {
							$replace_orignal_price = 'no';
						}

						?>

						<tr id="filter-row-rule<?php echo intval($post->ID); ?><?php echo intval($a); ?>">
							<td align="center" class="rcname rheight">
								<select class="sel22" name="rcus_base_price[<?php echo intval($a); ?>][customer_name]">

									<?php
									$author_obj = get_user_by('id', $cus_price['customer_name']);
									?>
									<option value="<?php echo intval($cus_price['customer_name']); ?>" selected="selected"><?php echo esc_attr($author_obj->display_name); ?>(<?php echo esc_attr($author_obj->user_email); ?>)</option>

								</select>
							</td>

							<?php
							if (isset($cus_price['discount_type'])) {
								$cus_price_dt = $cus_price['discount_type'];
							} else {
								$cus_price_dt = '';
							}

							if (isset($cus_price['discount_type'])) {
								$cus_price_dt = $cus_price['discount_type'];
							} else {
								$cus_price_dt = '';
							}

							if (isset($cus_price['discount_value'])) {
								$cus_price_dv = $cus_price['discount_value'];
							} else {
								$cus_price_dv = '';
							}

							if (isset($cus_price['min_qty'])) {
								$cus_price_miq = $cus_price['min_qty'];
							} else {
								$cus_price_miq = '';
							}

							if (isset($cus_price['max_qty'])) {
								$cus_price_maq = $cus_price['max_qty'];
							} else {
								$cus_price_maq = '';
							}

							if (isset($cus_price['start_date'])) {
								$cus_price_start_date = $cus_price['start_date'];
							} else {
								$cus_price_start_date = '';
							}

							if (isset($cus_price['end_date'])) {
								$cus_price_end_date = $cus_price['end_date'];
							} else {
								$cus_price_end_date = '';
							}




							?>
							<td align="center" class="rcname1 rheight">
								<select name="rcus_base_price[<?php echo intval($a); ?>][discount_type]">

									<option value="fixed_price" <?php echo selected('fixed_price', $cus_price_dt); ?>><?php echo esc_html__('Fixed Price', 'addify_role_price'); ?></option>
									<option value="fixed_increase" <?php echo selected('fixed_increase', $cus_price_dt); ?>><?php echo esc_html__('Fixed Increase', 'addify_role_price'); ?></option>
									<option value="fixed_decrease" <?php echo selected('fixed_decrease', $cus_price_dt); ?>><?php echo esc_html__('Fixed Decrease', 'addify_role_price'); ?></option>
									<option value="percentage_decrease" <?php echo selected('percentage_decrease', $cus_price_dt); ?>><?php echo esc_html__('Percentage Decrease', 'addify_role_price'); ?></option>
									<option value="percentage_increase" <?php echo selected('percentage_increase', $cus_price_dt); ?>><?php echo esc_html__('Percentage Increase', 'addify_role_price'); ?></option>

								</select>
							</td>
							<td align="center" class="rcname2 rheight">
								<input type="text" name="rcus_base_price[<?php echo intval($a); ?>][discount_value]" value="<?php echo esc_attr($cus_price_dv); ?>" />
							</td>
							<td align="center" class="rcname3 rheight">
								<input type="number" min="0" name="rcus_base_price[<?php echo intval($a); ?>][min_qty]" value="<?php echo esc_attr($cus_price_miq); ?>" />
							</td>
							<td align="center" class="rcname4 rheight">
								<input type="number" min="0" name="rcus_base_price[<?php echo intval($a); ?>][max_qty]" value="<?php echo esc_attr($cus_price_maq); ?>" />
							</td>
							<td align="center" class="rcname5 rheight">
								<input type="date" min="0" style="width:100%" name="rcus_base_price[<?php echo intval($a); ?>][start_date]" value="<?php echo esc_attr($cus_price_start_date); ?>" />
							</td>
							<td align="center" class="rcname6 rheight">
								<input type="date" min="0" style="width:100%" name="rcus_base_price[<?php echo intval($a); ?>][end_date]" value="<?php echo esc_attr($cus_price_end_date); ?>" />
							</td>

							<td align="center" class="rcname7 rheight">
								<input type="checkbox" name="rcus_base_price[<?php echo intval($a); ?>][replace_orignal_price]" value="yes" <?php echo checked('yes', $replace_orignal_price); ?> />
							</td>

							<td align="center" class="rcname8 rheight">
								<a onclick="jQuery('#filter-row-rule<?php echo intval($post->ID); ?><?php echo intval($a); ?>').remove();" class="button button-danger button-large">X</a>
							</td>
						</tr>

						<?php
						++$a;
					}
				}
				?>

			</tbody>

			<tfoot>
				<tr class="topfilters" id="beforerulectf"></tr>
			</tfoot>
		</table>

		<div class="add_rule_bt_div">
			<input type="button" class="btt2 button button-primary button-large" value="<?php echo esc_html__('Add Rule', 'addify_role_price'); ?>" onClick="addGlobalRule();">
		</div>

	</div>

</div>

<div class="csp_admin_main">


	<div class="options_group">

		<h3><?php echo esc_html__('Role Based Pricing(By User Roles)', 'addify_role_price'); ?></h3>
		<div class="afrbp_div">

			<table class="addify-table-optoin" width="100%">
				<thead>
					<tr>
						<td class="afrpb_head_first"><?php echo esc_html__('User Role', 'addify_role_price'); ?></td>
						<td class="afrpb_head"><?php echo esc_html__('Adjustment Type', 'addify_role_price'); ?></td>
						<td class="afrpb_head"><?php echo esc_html__('Value', 'addify_role_price'); ?></td>
						<td class="afrpb_head"><?php echo esc_html__('Min Qty', 'addify_role_price'); ?></td>
						<td class="afrpb_head"><?php echo esc_html__('Max Qty', 'addify_role_price'); ?></td>
						<td class="afrpb_head"><?php echo esc_html__('Start Date', 'addify_role_price'); ?></td>
						<td class="afrpb_head"><?php echo esc_html__('End Date', 'addify_role_price'); ?></td>
						<td class="afrpb_head"><?php echo esc_html__('Replace Original Price?', 'addify_role_price'); ?>
							<div class="tooltip">?
								<span class="tooltiptext"><?php echo esc_html__('This will only work for Fixed Price, Fixed Decrease and Percentage Decrease.', 'addify_role_price'); ?></span>
							</div>
						</td>
					</tr>
					<tr>
						<td>&nbsp;</td>
					</tr>
				</thead>
				<tbody>
					<?php
					global $wp_roles;
					$roles = $wp_roles->get_names();
					foreach ($roles as $key => $value) {

						// Check for excluded roles.
						$excluded_roles = get_option('csp_exclude_user_roles');
						if (!empty($excluded_roles)) {
							if (in_array($key, $excluded_roles)) {
								continue;
							}
						}


						if (isset($_GET['post']) && '' != $_GET['post']) {
							$p_id = intval($_GET['post']);
						} else {
							$p_id = '';
						}

						$rrole_base_price = get_post_meta($p_id, 'rrole_base_price_' . $key, true);
						$afrbp_prices     = unserialize($rrole_base_price);

						if (is_bool($afrbp_prices)) {
							$afrbp_prices                   = array();
							$afrbp_prices['discount_type']  = '';
							$afrbp_prices['discount_value'] = '';
							$afrbp_prices['min_qty']        = '';
							$afrbp_prices['max_qty']        = '';
							$afrbp_prices['start_date']     = '';
							$afrbp_prices['end_date']       = '';
						}
						if (!empty($afrbp_prices['replace_orignal_price'])) {

							$replace_orignal_price = 'yes';
						} else {
							$replace_orignal_price = 'no';
						}


						?>
						<tr>
							<td><b><?php echo esc_attr($value); ?></b></td>
							<td>
								<select name="rrole_base_price[<?php echo esc_attr($key); ?>][discount_type]">
									<option value=""><?php echo esc_html__('---Select Adjustment Type---', 'addify_role_price'); ?></option>
									<option value="fixed_price" <?php echo selected('fixed_price', $afrbp_prices['discount_type']); ?>><?php echo esc_html__('Fixed Price', 'addify_role_price'); ?></option>
									<option value="fixed_increase" <?php echo selected('fixed_increase', $afrbp_prices['discount_type']); ?>><?php echo esc_html__('Fixed Increase', 'addify_role_price'); ?></option>
									<option value="fixed_decrease" <?php echo selected('fixed_decrease', $afrbp_prices['discount_type']); ?>><?php echo esc_html__('Fixed Decrease', 'addify_role_price'); ?></option>
									<option value="percentage_decrease" <?php echo selected('percentage_decrease', $afrbp_prices['discount_type']); ?>><?php echo esc_html__('Percentage Decrease', 'addify_role_price'); ?></option>
									<option value="percentage_increase" <?php echo selected('percentage_increase', $afrbp_prices['discount_type']); ?>><?php echo esc_html__('Percentage Increase', 'addify_role_price'); ?></option>
								</select>
							</td>
							<td><input class="afrbp_inp_field" type="text" name="rrole_base_price[<?php echo esc_attr($key); ?>][discount_value]" value="<?php echo esc_attr($afrbp_prices['discount_value']); ?>" /></td>
							<td><input class="afrbp_num_field" type="number" min="0" name="rrole_base_price[<?php echo esc_attr($key); ?>][min_qty]" value="<?php echo esc_attr($afrbp_prices['min_qty']); ?>" /></td>
							<td><input class="afrbp_num_field" type="number" min="0" name="rrole_base_price[<?php echo esc_attr($key); ?>][max_qty]" value="<?php echo esc_attr($afrbp_prices['max_qty']); ?>" /></td>
							<td><input class="afrbp_date_field" type="date" name="rrole_base_price[<?php echo esc_attr($key); ?>][start_date]" value="<?php echo esc_attr($afrbp_prices['start_date']); ?>" /></td>
							<td><input class="afrbp_date_field" type="date" name="rrole_base_price[<?php echo esc_attr($key); ?>][end_date]" value="<?php echo esc_attr($afrbp_prices['end_date']); ?>" /></td>
							<td align="center"><input class="" type="checkbox" name="rrole_base_price[<?php echo esc_attr($key); ?>][replace_orignal_price]" value="yes" <?php echo checked('yes', $replace_orignal_price); ?> /></td>

						</tr>
						<tr>
							<td>&nbsp;</td>
						</tr>
						<?php 
					}

					// Check for excluded roles for guest users.
					$excluded_roles =  get_option('csp_exclude_user_roles');

					if (empty($excluded_roles) || !in_array('guest', $excluded_roles)) {


						?>

						<tr>
							<?php

							$rrole_base_price_guest = get_post_meta($p_id, 'rrole_base_price_guest', true);
							$afrbp_prices_guest     = (array) unserialize($rrole_base_price_guest);

							if (!empty($afrbp_prices_guest['replace_orignal_price'])) {

								$replace_orignal_price_guest = 'yes';
							} else {
								$replace_orignal_price_guest = 'no';
							}

							if (empty($afrbp_prices_guest['discount_type'])) {
								$afrbp_prices_guest['discount_type'] = '';
							}

							if (empty($afrbp_prices_guest['discount_value'])) {
								$afrbp_prices_guest['discount_value'] = '';
							}

							if (empty($afrbp_prices_guest['min_qty'])) {
								$afrbp_prices_guest['min_qty'] = '';
							}

							if (empty($afrbp_prices_guest['max_qty'])) {
								$afrbp_prices_guest['max_qty'] = '';
							}

							if (empty($afrbp_prices_guest['start_date'])) {
								$afrbp_prices_guest['start_date'] = '';
							}

							if (empty($afrbp_prices_guest['end_date'])) {
								$afrbp_prices_guest['end_date'] = '';
							}

							?>
							<td><b><?php echo esc_html__('Non LoggedIn/Guest', 'addify_role_price'); ?></b></td>
							<td>
								<select name="rrole_base_price[guest][discount_type]">
									<option value=""><?php echo esc_html__('---Select Adjustment Type---', 'addify_role_price'); ?></option>
									<option value="fixed_price" <?php echo selected('fixed_price', $afrbp_prices_guest['discount_type']); ?>><?php echo esc_html__('Fixed Price', 'addify_role_price'); ?></option>
									<option value="fixed_increase" <?php echo selected('fixed_increase', $afrbp_prices_guest['discount_type']); ?>><?php echo esc_html__('Fixed Increase', 'addify_role_price'); ?></option>
									<option value="fixed_decrease" <?php echo selected('fixed_decrease', $afrbp_prices_guest['discount_type']); ?>><?php echo esc_html__('Fixed Decrease', 'addify_role_price'); ?></option>
									<option value="percentage_decrease" <?php echo selected('percentage_decrease', $afrbp_prices_guest['discount_type']); ?>><?php echo esc_html__('Percentage Decrease', 'addify_role_price'); ?></option>
									<option value="percentage_increase" <?php echo selected('percentage_increase', $afrbp_prices_guest['discount_type']); ?>><?php echo esc_html__('Percentage Increase', 'addify_role_price'); ?></option>
								</select>
							</td>
							<td><input class="afrbp_inp_field" type="text" name="rrole_base_price[guest][discount_value]" value="<?php echo esc_attr($afrbp_prices_guest['discount_value']); ?>" /></td>
							<td><input class="afrbp_num_field" type="number" min="0" name="rrole_base_price[guest][min_qty]" value="<?php echo esc_attr($afrbp_prices_guest['min_qty']); ?>" /></td>
							<td><input class="afrbp_num_field" type="number" min="0" name="rrole_base_price[guest][max_qty]" value="<?php echo esc_attr($afrbp_prices_guest['max_qty']); ?>" /></td>
							<td><input class="afrbp_date_field" type="date" name="rrole_base_price[guest][start_date]" value="<?php echo esc_attr($afrbp_prices_guest['start_date']); ?>" /></td>
							<td><input class="afrbp_date_field" type="date" name="rrole_base_price[guest][end_date]" value="<?php echo esc_attr($afrbp_prices_guest['end_date']); ?>" /></td>
							<td align="center"><input class="" type="checkbox" name="rrole_base_price[guest][replace_orignal_price]" value="yes" <?php echo checked('yes', $replace_orignal_price_guest); ?> /></td>
						</tr>
						<?php
					}
					?>


				</tbody>
			</table>
		</div>

	</div>

</div>


<script type="text/javascript" defer>
	var filter_row_rule = 10000;

	function addGlobalRule() {

		var aa = jQuery('.sel2').val();


		html = '<tr id="filter-row-rule' + filter_row_rule + '">';

		html += '<td align="center" class="rcname rheight">';

		html += '<select class="sel2" name="rcus_base_price[' + filter_row_rule + '][customer_name]">';



		html += '</select>';

		html += '</td>';

		html += '<td align="center" class="rcname1 rheight">';

		html += '<select name="rcus_base_price[' + filter_row_rule + '][discount_type]">';

		html += '<option value="fixed_price"><?php echo esc_html__('Fixed Price', 'addify_role_price'); ?></option>';
		html += '<option value="fixed_increase"><?php echo esc_html__('Fixed Increase', 'addify_role_price'); ?></option>';
		html += '<option value="fixed_decrease"><?php echo esc_html__('Fixed Decrease', 'addify_role_price'); ?></option>';
		html += '<option value="percentage_decrease"><?php echo esc_html__('Percentage Decrease', 'addify_role_price'); ?></option>';
		html += '<option value="percentage_increase"><?php echo esc_html__('Percentage Increase', 'addify_role_price'); ?></option>';

		html += '</select>';

		html += '</td>';

		html += '<td align="center" class="rcname2 rheight">';

		html += '<input class="csp_input" type="text" name="rcus_base_price[' + filter_row_rule + '][discount_value]">';

		html += '</td>';

		html += '<td align="center" class="rcname3 rheight">';

		html += '<input class="csp_input" type="number" min="0" value="0" name="rcus_base_price[' + filter_row_rule + '][min_qty]">';

		html += '</td>';

		html += '<td class="rcname4 rheight">';

		html += '<input class="csp_input" align="center" type="number" min="0" value="0" name="rcus_base_price[' + filter_row_rule + '][max_qty]">';

		html += '</td>';

		html += '<td class="rcname5 rheight">';

		html += '<input class="csp_input" align="center" type="date" min="0" value="0" name="rcus_base_price[' + filter_row_rule + '][start_date]">';

		html += '</td>';

		html += '<td class="rcname6 rheight">';

		html += '<input class="csp_input" align="center" type="date" min="0" value="0" name="rcus_base_price[' + filter_row_rule + '][end_date]">';

		html += '</td>';

		html += '<td class="rcname7 rheight" align="center">';

		html += '<input class="" align="center" type="checkbox" value="yes" name="rcus_base_price[' + filter_row_rule + '][replace_orignal_price]">';

		html += '</td>';


		html += '<td align="center" class="rcname8 rheight">';

		html += '<a onclick="jQuery(\'#filter-row-rule' + filter_row_rule + '\').remove();" class="button button-danger"><?php esc_html_e('X', 'addify_role_price'); ?></a>';

		html += '</td>';

		html += '</tr>';

		jQuery('#beforerulectf').before(html);

		var ajaxurl = '<?php echo esc_url(admin_url('admin-ajax.php')); ?>';
		var nonce = '<?php echo esc_attr(wp_create_nonce('afrolebase-ajax-nonce')); ?>';

		jQuery('.sel2').select2({

			ajax: {
				url: ajaxurl, // AJAX URL is predefined in WordPress admin
				dataType: 'json',
				type: 'POST',
				delay: 250, // delay in ms while typing when to perform a AJAX search
				data: function(params) {
					return {
						q: params.term, // search query
						action: 'cspsearchUsers', // AJAX action for admin-ajax.php
						nonce: nonce // AJAX nonce for admin-ajax.php
					};
				},
				processResults: function(data) {
					var options = [];
					if (data) {

						// data is the array of arrays, and each of them contains ID and the Label of the option
						jQuery.each(data, function(index, text) { // do not forget that "index" is just auto incremented value
							options.push({
								id: text[0],
								text: text[1]
							});
						});

					}
					return {
						results: options
					};
				},
				cache: true
			},
			multiple: false,
			placeholder: 'Choose Users',
			minimumInputLength: 3 // the minimum of symbols to input before perform a search

		});

		filter_row_rule++;

	}
</script>
