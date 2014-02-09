<?php settings_errors(); ?>
<?php
		if($v['id']=='global'){
			echo '<h4>'.__('Set Options as required', $this->pluginLocale).'</h4>';
		}
		if($v['id']=='layout'){
			echo '<h4>'.__('Set Options as required', $this->pluginLocale).'</h4>';
		}
		if($v['id']=='opening_times'){
			echo '<h4>'.__('Set your opening times. It will not be possible to place an order outside these times - USE 24 HOUR CLOCK.', $this->pluginLocale).'<br/>'.__('If you are closed on a given day set both times to be the same, if you are open 24 hours set times from 0:00 to 24:00', $this->pluginLocale).'<br/>'.__('Ensure that the Wordpress timezone setting in Settings->Timezone is correct', $this->pluginLocale).'</h4>';
		}
		if($v['id']=='order'){
			echo '<h4>'.__('Set currency, minimum delivery prices and email addresses', $this->pluginLocale).'</h4>';
		}
		if($v['id']=='order_form'){
			echo '<h4>'.__('Set the form fields you would like to show when a customer places an order', $this->pluginLocale).'</h4>';
		}		
		if($v['id']=='sizes'){
			echo '<h4>'.__('Define a selection of sizes that might be available per item.', $this->pluginLocale).'</h4>';
		}	
		if($v['id']=='additives'){
			echo '<h4>'.__('Add any additives (or other notes) that a meal may have and tick the relevant box(es) of any meal that contains these additives ', $this->pluginLocale).'</h4>';
			echo"<p class='description'>".__('by default, additives will be sorted alphabetically.<br/>However, you can use the "sort" field to customise the sortorder. If you do, your choosen sort id will be used to identify your choosen additives in the frontend so you want to make sure to have unique identifiers/sort id\'s', $this->pluginLocale)."</p>";	
		}
		if($v['id']=='localization'){
			//currently not in use
			//echo '<h4>'.__('', $this->pluginLocale).'</h4>';
		}		
		if($v['id']=='gateways'){
			//currently not in use
			//echo '<h4>'.__('', $this->pluginLocale).'</h4>';
		}			
		if($v['id']=='history'){
			//currently not in use
			//echo '<h4>'.__('', $this->pluginLocale).'</h4>';
		}
?>