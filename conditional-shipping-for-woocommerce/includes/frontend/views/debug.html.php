<div id="wcs-debug">
	<div id="wcs-debug-header">
		<div class="wcs-debug-title"><?php esc_html_e( 'Conditional Shipping Debug', 'conditional-shipping-for-woocommerce' ); ?></div>
		<div class="wcs-debug-toggle"></div>
	</div>

	<div id="wcs-debug-contents">
		<?php if ( isset( $debug['shipping_zone'] ) && $debug['shipping_zone'] ) { ?>
			<h3><?php esc_html_e( 'Shipping zone', 'conditional-shipping-for-woocommerce' ); ?></h3>

			<p><?php esc_html_e( 'Matched shipping zone: ', 'conditional-shipping-for-woocommerce' ); ?><?php echo $debug['shipping_zone']['name_with_url']; ?></p>
			<p class="wcs-debug-tip"><?php esc_html_e( "WooCommerce will find the first matching zone and skip the rest. Make sure you don't have duplicate zones for the same region.", 'conditional-shipping-for-woocommerce' ); ?></p>
		<?php } ?>

		<h3><?php esc_html_e( 'Shipping methods', 'conditional-shipping-for-woocommerce' ); ?></h3>

		<table class="wcs-debug-table wcs-debug-table-fixed">
			<thead>
				<tr>
					<th>
						<?php esc_html_e( 'Before filtering', 'conditional-shipping-for-woocommerce' ); ?>
					</th>
					<th>
						<?php esc_html_e( 'After filtering', 'conditional-shipping-for-woocommerce' ); ?>
					</th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td>
						<?php if ( isset( $debug['shipping_methods']['before'] ) ) { ?>
							<?php echo implode( '<br>', $debug['shipping_methods']['before'] ); ?>
							<?php if ( empty( $debug['shipping_methods']['before'] ) ) { ?>
								<em><?php esc_html_e( 'No shipping methods', 'conditional-shipping-for-woocommerce' ); ?></em>
							<?php } ?>
						<?php } else { ?>
							<em><?php esc_html_e( 'Not available', 'conditional-shipping-for-woocommerce' ); ?></em>
						<?php } ?>
					</td>
					<td>
						<?php if ( isset( $debug['shipping_methods']['after'] ) ) { ?>
							<?php echo implode( '<br>', $debug['shipping_methods']['after'] ); ?>
							<?php if ( empty( $debug['shipping_methods']['after'] ) ) { ?>
								<em><?php esc_html_e( 'No shipping methods', 'conditional-shipping-for-woocommerce' ); ?></em>
							<?php } ?>
						<?php } else { ?>
							<em><?php esc_html_e( 'Not available', 'conditional-shipping-for-woocommerce' ); ?></em>
						<?php } ?>
					</td>
				</tr>
			</tbody>
		</table>

		<p class="wcs-debug-tip"><?php esc_html_e( "If shipping method is not listed above or is not available as expected, another plugin might be affecting its visibility or its settings do not allow it to be available for the cart or customer address.", 'conditional-shipping-for-woocommerce' ); ?></p>

		<h3><?php esc_html_e( 'Rulesets', 'conditional-shipping-for-woocommerce' ); ?></h3>

		<?php if ( ! isset( $debug['rulesets'] ) || empty( $debug['rulesets'] ) ) { ?>
			<p><?php esc_html_e( 'No rulesets were run.', 'conditional-shipping-for-woocommerce' ); ?></p>
		<?php } else { ?>
			<?php foreach ( $debug['rulesets'] as $ruleset_id => $data ) { ?>
				<div class="wcs-debug-<?php echo esc_attr( $ruleset_id ); ?>">
					<h3 class="ruleset-title">
						<a href="<?php echo esc_attr( wcs_get_ruleset_admin_url( $data['ruleset_id'] ) ); ?>" target="_blank">
							<?php echo esc_html( $data['ruleset_title'] ); ?>
						</a>
					</h3>

					<table class="wcs-debug-table wcs-debug-conditions">
						<thead>
							<tr>
								<th colspan="2"><?php esc_html_e( 'Conditions', 'conditional-shipping-for-woocommerce' ); ?> - <?php echo esc_html( wcs_get_ruleset_operator_label( $data['ruleset_id'] ) ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $data['conditions'] as $condition ) { ?>
								<tr>
									<td><?php echo esc_html( $condition['desc'] ); ?></td>
									<td class="align-right">
										<span class="wcs-debug-result-label wcs-debug-result-label-<?php echo ( $condition['result'] ? 'fail' : 'pass' ); ?>">
											<?php echo esc_html( ( $condition['result'] ? __( 'Fail', 'conditional-shipping-for-woocommerce' ) : __( 'Pass', 'conditional-shipping-for-woocommerce' ) ) ); ?>
										</span>
									</td>
								</tr>
							<?php } ?>
						</tbody>
						<tfoot>
							<tr>
								<th><?php esc_html_e( 'Result', 'conditional-shipping-for-woocommerce' ); ?></th>
								<th class="align-right">
									<span class="wcs-debug-result-label wcs-debug-result-label-<?php echo ( $data['result'] ? 'pass' : 'fail' ); ?>">
										<?php echo esc_html( ( $data['result'] ? __( 'Pass', 'conditional-shipping-for-woocommerce' ) : __( 'Fail', 'conditional-shipping-for-woocommerce' ) ) ); ?>
									</span>
								</th>
							</tr>
						</tfoot>
					</table>

					<table class="wcs-debug-table wcs-debug-actions">
						<thead>
							<tr>
								<th colspan="2"><?php esc_html_e( 'Actions', 'conditional-shipping-for-woocommerce' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $data['actions'] as $action ) { ?>
								<tr class="status-<?php echo esc_attr( $action['status'] ); ?>">
									<td>
										<?php echo esc_html( implode( ' - ', $action['cols'] ) ); ?>

										<?php if ( $action['desc'] ) { ?>
											<br><small><?php echo esc_html( $action['desc'] ); ?></small>
										<?php } ?>
									</td>
									<td class="align-right">
										<span class="wcs-debug-result-label wcs-debug-result-label-<?php echo esc_attr( $action['status'] ); ?>">
											<?php echo esc_html( ( $action['status'] === 'pass' ? __( 'Run', 'conditional-shipping-for-woocommerce' ) : __( 'Fail', 'conditional-shipping-for-woocommerce' ) ) ); ?>
										</span>
									</td>
								</tr>
							<?php } ?>
							<?php if ( empty( $data['actions'] ) ) { ?>
								<tr>
									<td colspan="2"><?php esc_html_e( 'No actions were run for this ruleset', 'conditional-shipping-for-woocommerce' ); ?></td>
								</tr>
							<?php } ?>
						</tbody>
					</table>
				</div>
			<?php } ?>
		<?php } ?>
	</div>
</div>
