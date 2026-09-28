<div class="attr-modal attr-fade" id="elementskit_headerfooter_modal" tabindex="-1" role="dialog"
	aria-labelledby="elementskit_headerfooter_modalLabel">
	<div class="attr-modal-dialog attr-modal-dialog-centered" role="document">
		<form action="" method="get" id="elementskit-template-modalinput-form" data-open-editor="0"
			data-editor-url="<?php echo esc_url(get_admin_url()); ?>" data-nonce="<?php echo esc_attr(wp_create_nonce('wp_rest'));?>">
			<div class="attr-modal-content">
				<div class="attr-modal-header">
					<button type="button" class="attr-close" data-dismiss="modal" aria-label="Close">
						<span aria-hidden="true">&times;</span>
					</button>
					<h4 class="attr-modal-title" id="elementskit_headerfooter_modalLabel">
						<?php esc_html_e('Template Settings', 'elementskit'); ?>
					</h4>
				</div>
				<div class="attr-modal-body" id="elementskit_headerfooter_modal_body">
					<div class="ekit-input-group">
						<label class="attr-input-label"><?php esc_html_e('Title:', 'elementskit'); ?></label>
						<input required type="text" name="title" class="ekit-template-modalinput-title attr-form-control">
					</div>
					<br />
					<div class="ekit-input-group">
						<label class="attr-input-label"><?php esc_html_e('Type:', 'elementskit'); ?></label>
						<select name="type" class="ekit-template-modalinput-type attr-form-control">
							<option value="header"><?php esc_html_e('Header', 'elementskit'); ?></option>
							<option value="footer"><?php esc_html_e('Footer', 'elementskit'); ?></option>
						</select>
					</div>
					<br />

					<div class="ekit-template-headerfooter-option-container">
						<!-- Condition Options -->
						<div class="ekit-input-group">
							<label class="attr-input-label"><?php esc_html_e('Conditions:', 'elementskit'); ?></label>
							<select name="condition_a" class="ekit-template-modalinput-condition_a attr-form-control">
								<option value="entire_site"><?php esc_html_e('Entire Site', 'elementskit'); ?></option>
								<?php if ( class_exists( 'WooCommerce' ) ) : ?>
									<option value="entire_shop"><?php esc_html_e('Entire Shop', 'elementskit'); ?></option>
								<?php endif; ?>
								<option value="singular"><?php esc_html_e('Singular', 'elementskit'); ?></option>
								<option value="archive"><?php esc_html_e('Archive', 'elementskit'); ?></option>
							</select>
						</div>
						<br>

						<!-- Singular Condition Options -->
						<div class="ekit-template-modalinput-condition_singular-container" style="display: none;">
							<div class="ekit-input-group">
								<label class="attr-input-label"></label>
								<select name="condition_singular" class="ekit-template-modalinput-condition_singular attr-form-control">
									<option value="all"><?php esc_html_e('All Singulars', 'elementskit'); ?></option>
									<option value="front_page"><?php esc_html_e('Front Page', 'elementskit'); ?></option>
									<option value="all_posts"><?php esc_html_e('All Posts', 'elementskit'); ?></option>
									<option value="all_pages"><?php esc_html_e('All Pages', 'elementskit'); ?></option>
									<option value="selective"><?php esc_html_e('Selective Singular', 'elementskit'); ?></option>
									<option value="404page"><?php esc_html_e('404 Page', 'elementskit'); ?></option>
								</select>
							</div>
							<br>

							<div class="ekit-template-modalinput-condition_singular_id-container ekit_multipile_ajax_search_filed">
								<div class="ekit-input-group">
									<label class="attr-input-label"></label>
									<select multiple name="condition_singular_id[]" class="ekit-template-modalinput-condition_singular_id"></select>
								</div>
								<br />
							</div>
							<br>
						</div>

						<!-- Archive Condition Options -->
						<div class="ekit-template-modalinput-condition_archive-container" style="display: none;">
							<div class="ekit-input-group">
								<label class="attr-input-label"></label>
								<select name="condition_archive" class="ekit-template-modalinput-condition_archive attr-form-control">
									<option value="all_archive"><?php esc_html_e('All Archives', 'elementskit'); ?></option>
									<option value="date_archive"><?php esc_html_e('Date Archives', 'elementskit'); ?></option>
									<option value="search_result"><?php esc_html_e('Search Results', 'elementskit'); ?></option>
									<option value="author_archive"><?php esc_html_e('Author Archives', 'elementskit'); ?></option>

									<optgroup label="<?php esc_html_e('Post Archives', 'elementskit'); ?>">
										<option value="post_archive"><?php esc_html_e('All Post Archives', 'elementskit'); ?></option>
										<option value="category"><?php esc_html_e('Categories', 'elementskit'); ?></option>
										<option value="post_tag"><?php esc_html_e('Tags', 'elementskit'); ?></option>
									</optgroup>

									<?php if ( class_exists( 'WooCommerce' ) ) : ?>
										<optgroup label="<?php esc_html_e('Product Archives', 'elementskit'); ?>">
											<option value="product_archive"><?php esc_html_e('All Product Archives', 'elementskit'); ?></option>
											<option value="shop_page"><?php esc_html_e('Shop Page', 'elementskit'); ?></option>
											<option value="product_search"><?php esc_html_e('Search Results', 'elementskit'); ?></option>
											<option value="product_brand"><?php esc_html_e('Brands', 'elementskit'); ?></option>
											<option value="product_cat"><?php esc_html_e('Product Categories', 'elementskit'); ?></option>
											<option value="product_tag"><?php esc_html_e('Product Tags', 'elementskit'); ?></option>
										</optgroup>
									<?php endif; ?>
								</select>
							</div>
							<br>

							<div class="ekit-template-modalinput-condition_archive_id-container ekit_multipile_ajax_search_filed">
								<div class="ekit-input-group">
									<label class="attr-input-label"></label>
									<select multiple name="condition_archive_id[]" class="ekit-template-modalinput-condition_archive_id"></select>
								</div>
								<br />
							</div>
						</div>

						<!-- Activation Option -->
						<div class="ekit-switch-group">
							<label class="attr-input-label"><?php esc_html_e('Activate/Deactivate:', 'elementskit'); ?></label>
							<div class="ekit-admin-input-switch">
								<input checked="" type="checkbox" value="yes"
									class="ekit-admin-control-input ekit-template-modalinput-activition"
									name="activation" id="ekit_activation_modal_input">
								<label class="ekit-admin-control-label" for="ekit_activation_modal_input">
									<span class="ekit-admin-control-label-switch" data-active="ON"
										data-inactive="OFF"></span>
								</label>
							</div>
						</div>
					</div>
					<br>
				</div>

				<!-- Modal Footer -->
				<div class="attr-modal-footer">
					<button type="button" class="attr-btn attr-btn-default elementskit-template-save-btn-editor"><img src="<?php echo \ElementsKit_Lite::lib_url(); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>framework/assets/images/ekit_icon.svg" alt="Ekit Icon"><?php esc_html_e('Edit with Elementor', 'elementskit'); ?></button>
					<button type="submit" class="attr-btn attr-btn-primary elementskit-template-save-btn"><i class="ekit-admin-save-icon fa fa-check-circle"></i><?php esc_html_e('Save changes', 'elementskit'); ?></button>
				</div>
				<div class="ekit-spinner"></div>
			</div>
		</form>
	</div>
</div>
