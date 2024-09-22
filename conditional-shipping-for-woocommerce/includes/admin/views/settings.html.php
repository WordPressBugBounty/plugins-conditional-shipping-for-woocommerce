<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<h2 class="woo-conditional-shipping-heading">
	<?php _e( 'Conditions', 'conditional-shipping-for-woocommerce' ); ?>
</h2>

<table class="form-table">
	<tbody>
		<tr valign="top" class="">
			<th scope="row" class="titledesc">
				<label>
					<?php esc_html_e( 'Rulesets', 'conditional-shipping-for-woocommerce' ); ?>
				</label>
			</th>
			<td class="forminp">
				<table class="wcs-rulesets widefat">
					<thead>
						<tr>
							<th class="wcs-ruleset-order">
								<?php echo wc_help_tip( __( 'Drag and drop to re-order your rulesets. This is the order in which they will be evaluated.', 'conditional-shipping-for-woocommerce' ) ); ?>
							</th>
							<th class="wcs-ruleset-name"><?php esc_html_e( 'Ruleset', 'conditional-shipping-for-woocommerce' ); ?></th>
							<th class="wcs-ruleset-status"><?php esc_html_e( 'Enabled', 'conditional-shipping-for-woocommerce' ); ?></th>
						</tr>
					</thead>
					<tbody class="wcs-ruleset-rows">
						<?php foreach ( $rulesets as $ruleset ) { ?>
							<tr>
								<td width="1%" class="wcs-ruleset-sort">
									<input type="hidden" name="wcs_ruleset_order[]" value="<?php echo esc_attr( $ruleset->get_id() ); ?>">
								</td>
								<td class="wcs-ruleset-name">
									<a href="<?php echo esc_attr( $ruleset->get_admin_edit_url() ); ?>">
										<?php echo esc_html( $ruleset->get_title() ); ?>
									</a>
									<div class="wcs-row-actions">
										<?php foreach ( $ruleset->get_row_actions() as $key => $action ) { ?>
											<span>
												<a href="<?php echo esc_attr( $action['url'] ); ?>" class="<?php echo esc_attr( $action['class'] ); ?>">
													<?php echo esc_html( $action['title'] ); ?>
												</a>
											</span>

											<?php if ( $key !== 'clone' ) { ?>
												<span>|</span>
											<?php } ?>
										<?php } ?>
									</div>
								</td>
								<td class="wcs-ruleset-status">
									<?php $class = $ruleset->get_enabled() ? 'enabled' : 'disabled'; ?>
									<span class="woocommerce-input-toggle woocommerce-input-toggle--<?php echo esc_attr( $class ); ?>" data-id="<?php echo esc_attr( $ruleset->get_id() ); ?>"></span>
								</td>
							</tr>
						<?php } ?>
						<?php if ( empty( $rulesets ) ) { ?>
							<tr>
								<td width="1%"></td>
								<td colspan="2" class="">
									<?php esc_html_e( 'No rulesets defined yet.', 'conditional-shipping-for-woocommerce' ); ?> <a href="<?php echo esc_attr( $add_ruleset_url ); ?>"><?php esc_html_e( 'Add new', 'conditional-shipping-for-woocommerce' ); ?> &raquo;</a>
								</td>
							</tr>
						<?php } ?>
					</tbody>
					<tfoot>
						<tr>
							<td colspan="3">
								<a href="<?php echo esc_attr( admin_url( 'admin.php?page=wc-settings&tab=shipping&section=woo_conditional_shipping&ruleset_id=new' ) ); ?>" class="button"><?php esc_html_e( 'Add ruleset', 'conditional-shipping-for-woocommerce' ); ?></a>
							</td>
						</tr>
					</tfoot>
				</table>
			</td>
		</tr>

		<tr valign="top" class="">
			<th scope="row" class="titledesc">
				<label>
					<?php esc_html_e( 'Disable all', 'conditional-shipping-for-woocommerce' ); ?>
				</label>
			</th>
			<td class="forminp">
				<label for="wcs_disable_all">
					<input type="checkbox" name="wcs_disable_all" id="wcs_disable_all" value="1" <?php checked( get_option( 'wcs_disable_all', false ) ); ?> />
					<?php esc_html_e( 'Disable all rulesets', 'conditional-shipping-for-woocommerce' ); ?>
				</label>

				<p class="description"><?php esc_html_e( 'Helpful for finding out if Conditional Shipping or another plugin is affecting shipping methods for troubleshooting.', 'conditional-shipping-for-woocommerce' ); ?></p>
			</td>
		</tr>

		<tr valign="top" class="">
			<th scope="row" class="titledesc">
				<label>
					<?php esc_html_e( 'Debug mode', 'conditional-shipping-for-woocommerce' ); ?>
				</label>
			</th>
			<td class="forminp">
				<label for="wcs_debug_mode">
					<input type="checkbox" name="wcs_debug_mode" id="wcs_debug_mode" value="1" <?php checked( get_option( 'wcs_debug_mode', false ) ); ?> />
					<?php esc_html_e( 'Debug mode', 'conditional-shipping-for-woocommerce' ); ?>
				</label>

				<p class="description"><?php esc_html_e( 'Debug mode shows passed conditions and which actions were run in the checkout.', 'conditional-shipping-for-woocommerce' ); ?></p>
			</td>
		</tr>

		<?php do_action( 'woo_conditional_shipping_after_settings' ); ?>
	</tbody>
</table>

<p class="submit">
	<button type="submit" name="submit" id="submit" class="button button-primary button-large" value="<?php esc_attr_e( 'Save changes', 'conditional-shipping-for-woocommerce' ); ?>"><?php esc_html_e( 'Save changes', 'conditional-shipping-for-woocommerce' ); ?></button>

	<input type="hidden" value="1" name="save" />
	<input type="hidden" value="1" name="wcs_settings" />

	<?php wp_nonce_field( 'woocommerce-settings' ); ?>
</p>

<?php if ( ! empty( $health['enables'] ) || ! empty( $health['disables'] ) ) { ?>
	<div class="woo-conditional-shipping-health-check">
		<h3><?php esc_html_e( 'Health check', 'conditional-shipping-for-woocommerce' ); ?></h3>

		<?php foreach ( $health['enables'] as $instance_id => $ruleset_ids ) { ?>
			<div class="issue-container">
				<div class="title">
					<?php echo wp_kses_post( sprintf(
						__( '<code>Enable shipping methods - %1$s</code> in multiple rulesets', 'conditional-shipping-for-woocommerce' ),
						woo_conditional_shipping_get_method_title( $instance_id )
					) ); ?>

					<span class="toggle-indicator"></span>
				</div>

				<div class="details">
					<div class="issue">
						<?php printf( __( 'You have <code>Enable shipping methods - %1$s</code> in multiple rulesets (%2$s). <code>Enable shipping methods</code> will disable the methods if conditions do not pass. It can cause unexpected behaviour when used in multiple rulesets for the same shipping method (<code>%1$s</code>).', 'conditional-shipping-for-woocommerce' ), woo_conditional_shipping_get_method_title( $instance_id ), woo_conditional_shipping_format_ruleset_ids( $ruleset_ids ) ); ?>
					</div>

					<div class="fix">
						<div><strong><?php esc_html_e( 'How to fix', 'conditional-shipping-for-woocommerce' ); ?></strong></div>
						<div>
							<ul>
								<li><?php echo wp_kses_post( __( 'Check if you can use <code>Disable shipping methods</code> instead. It\'s usually easier to work with.', 'conditional-shipping-for-woocommerce' ) ); ?></li>
								<li><?php echo wp_kses_post( sprintf( __( 'Remove <code>Enable shipping methods - %s</code> from all but one ruleset.', 'conditional-shipping-for-woocommerce' ), woo_conditional_shipping_get_method_title( $instance_id ) ) ); ?></li>
							</ul>
						</div>
					</div>
				</div>
			</div>
		<?php } ?>

		<?php foreach ( $health['disables'] as $data ) { ?>
			<div class="issue-container">
				<div class="title">
					<?php echo wp_kses_post( sprintf(
						__( '<code>%s</code> disabled in the shipping zone', 'conditional-shipping-for-woocommerce' ),
						woo_conditional_shipping_get_method_title( $data['instance_id'] )
					) ); ?>

					<span class="toggle-indicator"></span>
				</div>

				<div class="details">
					<div class="issue">
						<?php echo wp_kses_post( sprintf(
							__( 'You have <code>%1$s - %2$s</code> in %3$s but <code>%2$s</code> is disabled in <a href="%4$s" target="_blank">the shipping zone</a>. Conditional Shipping can only process shipping methods which are enabled.', 'conditional-shipping-for-woocommerce' ),
							woo_conditional_shipping_action_title( $data['action']['type'] ),
							woo_conditional_shipping_get_method_title( $data['instance_id'] ),
							woo_conditional_shipping_format_ruleset_ids( array( $data['ruleset']->get_id() ) ),
							woo_conditional_shipping_get_zone_url( $data['zone_id'] )
						) ); ?>
					</div>

					<div class="fix">
						<div><strong><?php esc_html_e( 'How to fix', 'conditional-shipping-for-woocommerce' ); ?></strong></div>
						<div>
							<ul>
								<li>
									<?php echo wp_kses_post( sprintf( __( 'Enable <code>%s</code> in <a href="%s" target="_blank">the shipping zone</a>', 'conditional-shipping-for-woocommerce' ),
										woo_conditional_shipping_get_method_title( $data['instance_id'] ),
										woo_conditional_shipping_get_zone_url( $data['zone_id'] )
									) ); ?>
								</li>
								<li>
									<?php echo wp_kses_post( sprintf(
										__( 'Remove <code>%1$s - %2$s</code> from %3$s.', 'conditional-shipping-for-woocommerce' ),
										woo_conditional_shipping_action_title( $data['action']['type'] ),
										woo_conditional_shipping_get_method_title( $data['instance_id'] ),
										woo_conditional_shipping_format_ruleset_ids( array( $data['ruleset']->get_id() ) )
									) ); ?>
								</li>
							</ul>
						</div>
					</div>
				</div>
			</div>
		<?php } ?>
	</div>
<?php } ?>
