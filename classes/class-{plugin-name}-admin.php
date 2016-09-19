<?php
/**
 * LSX Currency Main Class
 */
class LSX_Currency_Admin extends LSX_Currency{	

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->set_defaults();
		add_action('lsx_framework_dashboard_tab_content',array($this,'settings'),11);
		add_action('lsx_framework_dashboard_tab_bottom',array($this,'settings_scripts'),11);
		
		add_filter('lsx_price_field_pattern',array($this,'fields'),10,1);
	}
	/**
	 * outputs the dashboard tabs settings
	 */
	public function settings() {
	?>	
		<tr class="form-field banner-wrap">
			<th class="table_heading" style="padding-bottom:0px;" scope="row" colspan="2">
			<label><h3 style="margin-bottom:0px;"><?php _e('Currency Settings',$this->plugin_slug); ?></h3></label>			
			</th>
		</tr>
		<tr class="form-field">
			<th scope="row">
				<label for="openexchange_api">Open Exchange Rate API Key</label>
			</th>
			<td>
				<input type="text" {{#if openexchange_api}} value="{{openexchange_api}}" {{/if}} name="openexchange_api" />
				<br /><small><?php _e('Get your free API key here',$this->plugin_slug); ?> - <a target="_blank" href="https://openexchangerates.org/signup/free">openexchangerates.org</a></small>
			</td>
		</tr>			
		<tr data-trigger="additional_currencies" class="lsx-select-trigger form-field-wrap">
			<th scope="row">
				<label for="currency"><?php _e('Base Currency',$this->plugin_slug);?></label>
			</th>
			<td>
				<select value="{{currency}}" name="currency">
					<?php
					foreach($this->available_currencies as $currency_id => $currency_label ){ 

						$selected = '';
						if($currency_id === $this->base_currency){
							$selected='selected="selected"';
						}
						echo '<option value="'.$currency_id.'" '.$selected.'>'.$currency_label.'</option>';
					} ?>
				</select>
			</td>
		</tr>
		<tr data-trigger="currency" class="lsx-checkbox-action form-field-wrap">
			<th scope="row">
				<label for="modules"><?php _e('Additional Currencies',$this->plugin_slug);?></label>
			</th>
			<td><ul>
			<?php 	
			foreach($this->available_currencies as $slug => $label){
				$checked = $hidden = '';
				if(array_key_exists($slug,$this->additional_currencies) || $slug === $this->base_currency){
					$checked='checked="checked"';
				}
				
				if($slug === $this->base_currency){
					$hidden = 'style="display:none;" class="hidden"';
				}
				?>
				<li <?php echo $hidden; ?>>
					<input type="checkbox" <?php echo $checked; ?> data-name="additional_currencies" data-value="<?php echo $slug; ?>" name="additional_currencies[<?php echo $slug; ?>]" /> <label for="additional_currencies"><?php echo $this->get_currency_flag($slug).$label; ?></label> 
				</li>
			<?php }
			?>
			</ul></td>
		</tr> 
		<tr class="form-field">
			<th scope="row">
				<label for="multi_price"><?php _e('Enable Multiple Prices',$this->plugin_slug); ?></label>
			</th>
			<td>
				<input type="checkbox" {{#if multi_price}} checked="checked" {{/if}} name="multi_price" />
				<small><?php _e('Allowing you to add specific prices per active currency.',$this->plugin_slug); ?></small>
			</td>
		</tr>	
		<tr class="form-field banner-wrap">
			<th class="table_heading" style="padding-bottom:0px;" scope="row" colspan="2">
			<label><h3 style="margin-bottom:0px;"><?php _e('Currency Switcher',$this->plugin_slug); ?></h3></label>			
			</th>
		</tr>	

		<tr class="form-field-wrap">
			<th scope="row">
				<label for="currency_menu_switcher"><?php _e('Display in Menu',$this->plugin_slug); ?></label>
			</th>
			<td><ul>
			<?php 	
			$all_menus = get_registered_nav_menus();
			if(is_array($all_menus) && !empty($all_menus)){
				foreach($all_menus as $slug => $label){
					$checked = $hidden = '';
					if(is_array($this->menus) && array_key_exists($slug,$this->menus)){
						$checked='checked="checked"';
					}
					?>
					<li>
						<input type="checkbox" <?php echo $checked; ?> name="currency_menu_switcher[<?php echo $slug; ?>]" /> <label for="additional_currencies"><?php echo $label; ?></label> 
					</li>
				<?php }
			}else{
				echo '<li><p>'.__('You have no menus set up.',$this->plugin_slug).'</p></li>';
			}
			?>
			</ul></td>
		</tr>

		<tr class="form-field">
			<th scope="row">
				<label for="display_flags"><?php _e('Display Flags',$this->plugin_slug); ?></label>
			</th>
			<td>
				<input type="checkbox" {{#if display_flags}} checked="checked" {{/if}} name="display_flags" />
				<small><?php _e('Displays a small flag in front of the name.',$this->plugin_slug); ?></small>
			</td>
		</tr>
		<tr class="form-field">
			<th scope="row">
				<label for="flag_position"><?php _e('Flag Position',$this->plugin_slug); ?></label>
			</th>
			<td>
				<input type="checkbox" {{#if flag_position}} checked="checked" {{/if}} name="flag_position" />
				<small><?php _e('This moves the flag to the right (after the symbol).',$this->plugin_slug); ?></small>
			</td>
		</tr>				

		<tr class="form-field">
			<th scope="row">
				<label for="currency_switcher_position"><?php _e('Symbol Position',$this->plugin_slug); ?></label>
			</th>
			<td>
				<input type="checkbox" {{#if currency_switcher_position}} checked="checked" {{/if}} name="currency_switcher_position" />
				<small><?php _e('This moves the symbol for the switcher to the left (before the flag).',$this->plugin_slug); ?></small>
			</td>
		</tr>
		<?php	
	}

	/**
	 * outputs the dashboard tabs settings scripts
	 */
	public function settings_scripts() {
		?>
		<script>
			var LSX_Select_Checkbox = {
				initThis: function() {
					if('undefined' != jQuery('.lsx-select-trigger') && 'undefined' != jQuery('.lsx-checkbox-action') ){
						this.watchSelect();
						//this.watchCheckbox();
					}
				},
				watchSelect: function() {
					jQuery('.lsx-select-trigger select').change(function(event){
						event.preventDefault();
						var name = jQuery(this).attr('name');
						var value = jQuery(this).val();
						jQuery('[data-trigger="'+name+'"] li.hidden input[checked="checked"]').removeAttr("checked").parents('li').show().removeClass('hidden');
						jQuery('[data-trigger="'+name+'"] input[name="additional_currencies['+value+']"]').attr('checked','checked').parents('li').hide().addClass('hidden');
					});
				},
				watchCheckbox: function() {
					jQuery('.lsx-checkbox-action input').change(function(event){
						event.preventDefault();
						var name = jQuery(this).attr('data-name');
						var value = jQuery(this).attr('data-value');
						console.log(value);

						jQuery('[data-trigger="'+name+'"] option[selected="selected"]').removeAttr('selected');
						jQuery('[data-trigger="'+name+'"] option[value="'+value+'"]').attr('selected','selected');
					});					
				}
			};	
			jQuery(document).ready(function() {
				LSX_Select_Checkbox.initThis();
			});
		</script>
		<?php
	}	

	/**
	 * outputs the dashboard tabs settings
	 */
	public function fields($field) {
		if(true === $this->multi_prices && !empty($this->additional_currencies)){
			$currency_options = array();
			foreach($this->additional_currencies as $key => $values){
				if($key === $this->base_currency){continue;}
				$currency_options[$key] = $this->available_currencies[$key];
			}

			return array(
				array( 'id' => 'price_title',  'name' => __('Prices',$this->plugin_slug), 'type' => 'title' ),
				array( 'id' => 'price',  'name' => 'Base Price ('.$this->base_currency.')', 'type' => 'text' ),
				array(
						'id' => 'additional_prices',
						'name' => '',
						'single_name' => 'Price',
						'type' => 'group',
						'repeatable' => true,
						'sortable' => true,
						'fields' => array(
								array( 'id' => 'amount',  'name' => 'Amount', 'type' => 'text' ),
								array( 'id' => 'currency', 'name' => 'Currency', 'type' => 'select', 'options' => $currency_options ),
						)
				)			
			);	
		}else{
			return array(array( 'id' => 'price',  'name' => 'Price ('.$this->base_currency.')', 'type' => 'text' ));
		}	
	}	
}
new LSX_Currency_Admin();