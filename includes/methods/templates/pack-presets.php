<?php

	if (!defined('ABSPATH') || !defined('WPINC')) { exit;}

?>

<tr valign="top">
	<td colspan="2" style="padding: 0">
		<div class="rendr-presets">
			<table class="widefat">
				<thead>
					<tr>
						<th class="wcrendr-box-remove"></th>
						<th class="wcrendr-box-name">Label</th>
						<th class="wcrendr-box-width">Length (cm)</th>
						<th class="wcrendr-box-length">Width (cm)</th>
						<th class="wcrendr-box-height">Height (cm)</th>
					</tr>
				</thead>
				<tbody>
					<tr class="clone">
						<td class="wcrendr-box-remove"><span class="dashicons dashicons-no-alt"></span></td>
						<td class="wcrendr-box-name">
							<input type="text" name="<?php echo $field_key ?>_clone[0][label]" value="" placeholder="Box label" />
						</td>
						<td class="wcrendr-box-length">
							<input type="number" name="<?php echo $field_key ?>_clone[0][length]" value="" placeholder="Length in cm" />
						</td>
						<td class="wcrendr-box-width">
							<input type="number" name="<?php echo $field_key ?>_clone[0][width]" value="" placeholder="Width in cm" />
						</td>
						<td class="wcrendr-box-height">
							<input type="number" name="<?php echo $field_key ?>_clone[0][height]" value="" placeholder="Height in cm" />
						</td>
					</tr>
					<?php foreach($presets as $index => $preset) : ?>
						<tr>
							<td class="wcrendr-box-remove"><span class="dashicons dashicons-no-alt"></span></td>
							<td class="wcrendr-box-name">
								<input type="text" name="<?php echo $field_key ?>[<?php $index ?>][label]" value="<?php echo esc_attr($preset['label']) ?>" placeholder="Box label" />
							</td>
							<td class="wcrendr-box-length">
								<input type="number" name="<?php echo $field_key ?>[<?php $index ?>][length]" value="<?php echo esc_attr($preset['length']) ?>" placeholder="Length in cm" />
							</td>
							<td class="wcrendr-box-width">
								<input type="number" name="<?php echo $field_key ?>[<?php $index ?>][width]" value="<?php echo esc_attr($preset['width']) ?>" placeholder="Width in cm" />
							</td>
							<td class="wcrendr-box-height">
								<input type="number" name="<?php echo $field_key ?>[<?php $index ?>][height]" value="<?php echo esc_attr($preset['height']) ?>" placeholder="Height in cm" />
							</td>
						</tr>
					<?php endforeach ?>
				</tbody>
				<tfoot>
					<tr>
						<th colspan="5" style="text-align: right">
							<button type="button" class="button">Add Box</button>
						</th>
					</tr>
				</tfoot>
			</table>
		</div>
	</td>
</tr>
