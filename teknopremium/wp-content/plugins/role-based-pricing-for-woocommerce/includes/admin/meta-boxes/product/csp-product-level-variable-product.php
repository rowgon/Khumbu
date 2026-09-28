<?php
/**
 * Product Level variable settings
 *
 * @package role-based-pricing
 */

?>

<div id='addify_csp_panelvar'>
	<div class="options_groups">

		<p><strong><?php echo esc_html__('Important Notes:', 'addify_role_price'); ?></strong></p>
		<ol>
			<li><strong><?php echo esc_html__('Pricing Priority:', 'addify_role_price'); ?></strong>
				<ul>
					<li>I - <?php echo esc_html__('Price Specific to a Customer', 'addify_role_price'); ?></li>
					<li>II - <?php echo esc_html__('Price Specific to a Role', 'addify_role_price'); ?></li>
					<li>III - <?php echo esc_html__('Regular Product Price', 'addify_role_price'); ?></li>
				</ul>
			</li>
		</ol>

		<div class="cspcontainer">

			<div class="af_price_div">
				<h3><?php echo esc_html__('Role Based Pricing(By Customers)', 'addify_role_price'); ?></h3>
				<p><?php echo esc_html__('If more than one rule is applied on same customer then rule that is added first will be applied.', 'addify_role_price'); ?></p>
				<div class="cdiv">
					<table cellspacing="0" cellpadding="0" border="1" width="1250">
						<thead>
							<tr>
								<th align="center" class="cname"><?php echo esc_html__('Customer', 'addify_role_price'); ?></th>
								<th align="center" class="cname"><?php echo esc_html__('Adjustment Type', 'addify_role_price'); ?></th>
								<th align="center" class="cname"><?php echo esc_html__('Value', 'addify_role_price'); ?></th>
								<th align="center" class="cname"><?php echo esc_html__('Min Qty', 'addify_role_price'); ?></th>
								<th align="center" class="cname"><?php echo esc_html__('Max Qty', 'addify_role_price'); ?></th>
								<th align="center" class="cname"><?php echo esc_html__('Start Date', 'addify_role_price'); ?></th>
								<th align="center" class="cname"><?php echo esc_html__('End Date', 'addify_role_price'); ?></th>
								<th align="center" class="cname"><?php echo esc_html__('Replace Original Price?', 'addify_role_price'); ?>
									<div class="tooltip">?
										<span class="tooltiptext"><?php echo esc_html__('This will only work for Fixed Price, Fixed Decrease and Percentage Decrease.', 'addify_role_price'); ?></span>
									</div>
								</th>
								<th align="center" class="cname"><?php echo esc_html__('Remove', 'addify_role_price'); ?></th>
							</tr>
						</thead>

						<tbody>

							<?php

							$a = 1;
							if (!empty($cus_base_prices)) {

								foreach ($cus_base_prices as $cus_price) {

									if (!empty($cus_price['replace_orignal_price'])) {

										$replace_orignal_price = 'yes';
									} else {
										$replace_orignal_price = 'no';
									}

									if (!empty($cus_price['discount_type'])) {

										$cus_dt = $cus_price['discount_type'];
									} else {
										$cus_dt = '';
									}

									if (!empty($cus_price['discount_value'])) {

										$cus_dv = $cus_price['discount_value'];
									} else {
										$cus_dv = '';
									}

									if (!empty($cus_price['min_qty'])) {

										$cus_miq = $cus_price['min_qty'];
									} else {
										$cus_miq = '';
									}

									if (!empty($cus_price['max_qty'])) {

										$cus_maq = $cus_price['max_qty'];
									} else {
										$cus_maq = '';
									}
									if (!empty($cus_price['start_date'])) {

										$cus_sd = $cus_price['start_date'];
									} else {
										$cus_sd = '';
									}
									if (!empty($cus_price['end_date'])) {

										$cus_ed = $cus_price['end_date'];
									} else {
										$cus_ed = '';
									}


									?>


									<tr id="filter-row-rule<?php echo intval($a); ?>">

										<td align="center" class="cname">

											<select class="sel22" name="cus_base_price[<?php echo intval($variation->ID); ?>][<?php echo intval($a); ?>][customer_name]">

												<?php
												$author_obj = get_user_by('id', $cus_price['customer_name']);
												?>
												<option value="<?php echo intval($cus_price['customer_name']); ?>" selected="selected"><?php echo esc_attr($author_obj->display_name); ?>(<?php echo esc_attr($author_obj->user_email); ?>)</option>

											</select>

										</td>

										<td align="center" class="cname">

											<select name="cus_base_price[<?php echo intval($variation->ID); ?>][<?php echo intval($a); ?>][discount_type]">

												<option value="fixed_price" <?php echo selected('fixed_price', $cus_dt); ?>><?php echo esc_html__('Fixed Price', 'addify_role_price'); ?></option>
												<option value="fixed_increase" <?php echo selected('fixed_increase', $cus_dt); ?>><?php echo esc_html__('Fixed Increase', 'addify_role_price'); ?></option>
												<option value="fixed_decrease" <?php echo selected('fixed_decrease', $cus_dt); ?>><?php echo esc_html__('Fixed Decrease', 'addify_role_price'); ?></option>
												<option value="percentage_decrease" <?php echo selected('percentage_decrease', $cus_dt); ?>><?php echo esc_html__('Percentage Decrease', 'addify_role_price'); ?></option>
												<option value="percentage_increase" <?php echo selected('percentage_increase', $cus_dt); ?>><?php echo esc_html__('Percentage Increase', 'addify_role_price'); ?></option>

											</select>

										</td>

										<td align="center" class="cname">

											<input value="<?php echo esc_attr($cus_dv); ?>" class="csp_input" type="text" name="cus_base_price[<?php echo intval($variation->ID); ?>][<?php echo intval($a); ?>][discount_value]">

										</td>

										<td align="center" class="cname">

											<input value="<?php echo esc_attr($cus_miq); ?>" class="csp_input" type="number" min="0" value="0" name="cus_base_price[<?php echo intval($variation->ID); ?>][<?php echo intval($a); ?>][min_qty]">

										</td>

										<td class="cname">

											<input value="<?php echo esc_attr($cus_maq); ?>" class="csp_input" align="center" type="number" min="0" value="0" name="cus_base_price[<?php echo intval($variation->ID); ?>][<?php echo intval($a); ?>][max_qty]">

										</td>
										<td class="cname">
											<input value="<?php echo esc_attr($cus_sd); ?>" type="date" min="0" class="csp_input" name="cus_base_price[<?php echo intval($variation->ID); ?>][<?php echo intval($a); ?>][start_date]">
										</td>
										<td class="cname">
											<input value="<?php echo esc_attr($cus_ed); ?>" type="date" min="0" class="csp_input" name="cus_base_price[<?php echo intval($variation->ID); ?>][<?php echo intval($a); ?>][end_date]">
										</td>


										<td class="cname rep_price_td" align="center">

											<input value="yes" align="center" class="rep_price" type="checkbox" name="cus_base_price[<?php echo intval($variation->ID); ?>][<?php echo intval($a); ?>][replace_orignal_price]" <?php echo checked('yes', $replace_orignal_price); ?>>

										</td>


										<td align="center" class="cname">

											<a onclick="jQuery(this).closest('tr').find('input').trigger('change');jQuery(this).closest('tr').remove();" class="button button-danger"><?php esc_html_e('X', 'addify_role_price'); ?></a>

										</td>

									</tr>


									<?php

									++$a;
								}
							}

							?>

						</tbody>

						<tfoot>
							<tr class="topfilters" id="beforetffVari<?php echo intval($variation->ID); ?>"></tr>
						</tfoot>
					</table>

					<div class="add_rule_bt_div">
						<input type="button" class="btt2 button button-primary button-large" value="<?php echo esc_html__('Add Rule', 'addify_role_price'); ?>" onClick="addVariationRule('<?php echo intval($variation->ID); ?>');">
					</div>
				</div>
			</div>

			<!-- user role -->

			<div class="af_price_div">
				<h3><?php echo esc_html__('Role Based Pricing(By User Roles)', 'addify_role_price'); ?></h3>
				<div class="afrbp_div">

					<table class="addify-table-optoin" width="950">
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

								$role_base_prices = get_post_meta($variation->ID, '_role_base_price_' . $key, true);
								$afrbp_prices     = maybe_unserialize($role_base_prices);

								if (!empty($afrbp_prices['replace_orignal_price'])) {

									$replace_orignal_price = 'yes';
								} else {
									$replace_orignal_price = 'no';
								}

								if (!empty($afrbp_prices['discount_type'])) {

									$role_dt = $afrbp_prices['discount_type'];
								} else {
									$role_dt = '';
								}

								if (!empty($afrbp_prices['discount_value'])) {

									$rol_dv = $afrbp_prices['discount_value'];
								} else {
									$rol_dv = '';
								}

								if (!empty($afrbp_prices['min_qty'])) {

									$rol_miq = $afrbp_prices['min_qty'];
								} else {
									$rol_miq = '';
								}

								if (!empty($afrbp_prices['max_qty'])) {

									$rol_maq = $afrbp_prices['max_qty'];
								} else {
									$rol_maq = '';
								}
								if (!empty($afrbp_prices['start_date'])) {

									$rol_sd = $afrbp_prices['start_date'];
								} else {
									$rol_sd = '';
								}
								if (!empty($afrbp_prices['end_date'])) {

									$rol_ed = $afrbp_prices['end_date'];
								} else {
									$rol_ed = '';
								}


								?>
								<tr>
									<td><b><?php echo esc_attr($value); ?></b></td>
									<td>
										<select name="role_price[<?php echo esc_attr($loop); ?>][<?php echo esc_attr($key); ?>][discount_type]">
											<option value=""><?php echo esc_html__('---Select Adjustment Type---', 'addify_role_price'); ?></option>
											<option value="fixed_price" <?php echo selected('fixed_price', $role_dt); ?>><?php echo esc_html__('Fixed Price', 'addify_role_price'); ?></option>
											<option value="fixed_increase" <?php echo selected('fixed_increase', $role_dt); ?>><?php echo esc_html__('Fixed Increase', 'addify_role_price'); ?></option>
											<option value="fixed_decrease" <?php echo selected('fixed_decrease', $role_dt); ?>><?php echo esc_html__('Fixed Decrease', 'addify_role_price'); ?></option>
											<option value="percentage_decrease" <?php echo selected('percentage_decrease', $role_dt); ?>><?php echo esc_html__('Percentage Decrease', 'addify_role_price'); ?></option>
											<option value="percentage_increase" <?php echo selected('percentage_increase', $role_dt); ?>><?php echo esc_html__('Percentage Increase', 'addify_role_price'); ?></option>
										</select>
									</td>
									<td><input class="afrbp_inp_field" type="text" name="role_price[<?php echo esc_attr($loop); ?>][<?php echo esc_attr($key); ?>][discount_value]" value="<?php echo esc_attr($rol_dv); ?>" /></td>
									<td><input class="afrbp_num_field" type="number" min="0" name="role_price[<?php echo esc_attr($loop); ?>][<?php echo esc_attr($key); ?>][min_qty]" value="<?php echo esc_attr($rol_miq); ?>" /></td>
									<td><input class="afrbp_num_field" type="number" min="0" name="role_price[<?php echo esc_attr($loop); ?>][<?php echo esc_attr($key); ?>][max_qty]" value="<?php echo esc_attr($rol_maq); ?>" /></td>
									<td> <input type="date" min="0" name="role_price[<?php echo esc_attr($loop); ?>][<?php echo esc_attr($key); ?>][start_date]" value="<?php echo esc_attr($rol_sd); ?>">
									</td>
									<td> <input type="date" min="0" name="role_price[<?php echo esc_attr($loop); ?>][<?php echo esc_attr($key); ?>][end_date]" value="<?php echo esc_attr($rol_ed); ?>">
									</td>
									<td align="center" class="rep_price_td"><input align="center" class="rep_price" type="checkbox" name="role_price[<?php echo esc_attr($loop); ?>][<?php echo esc_attr($key); ?>][replace_orignal_price]" value="yes" <?php echo checked('yes', $replace_orignal_price); ?> /></td>

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

									$role_base_prices_guest = get_post_meta($variation->ID, '_role_base_price_guest', true);
									$afrbp_prices_guest     = maybe_unserialize($role_base_prices_guest);

									if (!empty($afrbp_prices_guest['replace_orignal_price'])) {

										$replace_orignal_price_guest = 'yes';
									} else {
										$replace_orignal_price_guest = 'no';
									}

									if (!empty($afrbp_prices_guest['discount_type'])) {

										$role_guest_dt = $afrbp_prices_guest['discount_type'];
									} else {
										$role_guest_dt = '';
									}

									if (!empty($afrbp_prices_guest['discount_value'])) {

										$rol_guest_dv = $afrbp_prices_guest['discount_value'];
									} else {
										$rol_guest_dv = '';
									}


									if (!empty($afrbp_prices_guest['min_qty'])) {

										$role_guest_miq = $afrbp_prices_guest['min_qty'];
									} else {
										$role_guest_miq = '';
									}

									if (!empty($afrbp_prices_guest['max_qty'])) {

										$role_guest_maq = $afrbp_prices_guest['max_qty'];
									} else {
										$role_guest_maq = '';
									}
									if (!empty($afrbp_prices_guest['start_date'])) {

										$role_guest_sd = $afrbp_prices_guest['start_date'];
									} else {
										$role_guest_sd = '';
									}
									if (!empty($afrbp_prices_guest['end_date'])) {

										$role_guest_ed = $afrbp_prices_guest['end_date'];
									} else {
										$role_guest_ed = '';
									}

									?>
									<td><b><?php echo esc_html__('Non LoggedIn/Guest', 'addify_role_price'); ?></b></td>
									<td>
										<select name="role_price[<?php echo esc_attr($loop); ?>][guest][discount_type]">
											<option value=""><?php echo esc_html__('---Select Adjustment Type---', 'addify_role_price'); ?></option>
											<option value="fixed_price" <?php echo selected('fixed_price', $role_guest_dt); ?>><?php echo esc_html__('Fixed Price', 'addify_role_price'); ?></option>
											<option value="fixed_increase" <?php echo selected('fixed_increase', $role_guest_dt); ?>><?php echo esc_html__('Fixed Increase', 'addify_role_price'); ?></option>
											<option value="fixed_decrease" <?php echo selected('fixed_decrease', $role_guest_dt); ?>><?php echo esc_html__('Fixed Decrease', 'addify_role_price'); ?></option>
											<option value="percentage_decrease" <?php echo selected('percentage_decrease', $role_guest_dt); ?>><?php echo esc_html__('Percentage Decrease', 'addify_role_price'); ?></option>
											<option value="percentage_increase" <?php echo selected('percentage_increase', $role_guest_dt); ?>><?php echo esc_html__('Percentage Increase', 'addify_role_price'); ?></option>
										</select>
									</td>
									<td><input class="afrbp_inp_field" type="text" name="role_price[<?php echo esc_attr($loop); ?>][guest][discount_value]" value="<?php echo esc_attr($rol_guest_dv); ?>" /></td>
									<td><input class="afrbp_num_field" type="number" min="0" name="role_price[<?php echo esc_attr($loop); ?>][guest][min_qty]" value="<?php echo esc_attr($role_guest_miq); ?>" /></td>
									<td><input class="afrbp_num_field" type="number" min="0" name="role_price[<?php echo esc_attr($loop); ?>][guest][max_qty]" value="<?php echo esc_attr($role_guest_maq); ?>" /></td>
									<td> <input type="date" min="0" name="role_price[<?php echo esc_attr($loop); ?>][guest][start_date]" value="<?php echo esc_attr($role_guest_sd); ?>">
									</td>
									<td> <input type="date" min="0" name="role_price[<?php echo esc_attr($loop); ?>][guest][end_date]" value="<?php echo esc_attr($role_guest_ed); ?>">
									</td>
									<td align="center" class="rep_price_td"><input align="center" class="rep_price" type="checkbox" name="role_price[<?php echo esc_attr($loop); ?>][guest][replace_orignal_price]" value="yes" <?php echo checked('yes', $replace_orignal_price); ?> /></td>
								</tr>
								<?php
							}

							?>

						</tbody>
					</table>
				</div>
			</div>



		</div>

	</div>
</div>

<script type="text/javascript" defer>
	var filter_row_rule = 10000;

	function addVariationRule(variation_id) {

		var aa = jQuery('.sel2').val();


		html = '<tr id="filter-row-rule' + filter_row_rule + '">';

		html += '<td align="center" class="cname">';

		html += '<select class="sel2" name="cus_base_price[' + variation_id + '][' + filter_row_rule + '][customer_name]">';



		html += '</select>';

		html += '</td>';

		html += '<td align="center" class="cname">';

		html += '<select name="cus_base_price[' + variation_id + '][' + filter_row_rule + '][discount_type]">';

		html += '<option value="fixed_price"><?php echo esc_html__('Fixed Price', 'addify_role_price'); ?></option>';
		html += '<option value="fixed_increase"><?php echo esc_html__('Fixed Increase', 'addify_role_price'); ?></option>';
		html += '<option value="fixed_decrease"><?php echo esc_html__('Fixed Decrease', 'addify_role_price'); ?></option>';
		html += '<option value="percentage_decrease"><?php echo esc_html__('Percentage Decrease', 'addify_role_price'); ?></option>';
		html += '<option value="percentage_increase"><?php echo esc_html__('Percentage Increase', 'addify_role_price'); ?></option>';

		html += '</select>';

		html += '</td>';

		html += '<td align="center" class="cname">';

		html += '<input class="csp_input" type="text" name="cus_base_price[' + variation_id + '][' + filter_row_rule + '][discount_value]">';

		html += '</td>';

		html += '<td align="center" class="cname">';

		html += '<input class="csp_input" type="number" min="0" value="0" name="cus_base_price[' + variation_id + '][' + filter_row_rule + '][min_qty]">';

		html += '</td>';

		html += '<td class="cname">';

		html += '<input class="csp_input" align="center" type="number" min="0" value="0" name="cus_base_price[' + variation_id + '][' + filter_row_rule + '][max_qty]">';

		html += '</td>';
		html += '<td class="cname">';
		html += '<input class="csp_input" type="date" min="0" name="cus_base_price[' + variation_id + '][' + filter_row_rule + '][start_date]"';
		html += '</td>';
		html += '<td class="cname">';
		html += '<input class="csp_input" type="date" min="0" name="cus_base_price[' + variation_id + '][' + filter_row_rule + '][end_date]"';
		html += '</td>';

		html += '<td class="cname rep_price_td" align="center">';

		html += '<input align="center" class="rep_price" type="checkbox" value="yes" name="cus_base_price[' + variation_id + '][' + filter_row_rule + '][replace_orignal_price]">';

		html += '</td>';


		html += '<td align="center" class="cname">';

		html += '<a onclick="jQuery(\'#filter-row-rule' + filter_row_rule + '\').remove();" class="button button-danger"><?php esc_html_e('X', 'addify_role_price'); ?></a>';

		html += '</td>';

		html += '</tr>';

		jQuery('#beforetffVari' + variation_id).before(html);

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
