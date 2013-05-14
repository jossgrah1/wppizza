<?php
/*
*
*	 WPPizza Order Page
*
*/
/*********************************************************************************************************
*
*	[get cart contents with some variables]
*
*	[$cart['items'] = contains cart contents, grouped and sorted]
*	[$cart['order_value'] = contains subtotal, deliver charges, discounts and grand total]
*	[$txt array= localized variables from settings->localization]
*	[$formelements = form elements from settings->order form]
*
*********************************************************************************************************/
?>
<form id='wppizza-send-order' method='post' action=''>

	<fieldset id="wppizza-cart-contents">
		<legend><?php echo $txt['your_order']['lbl'] ?></legend>
		<?php if(count($cart['items'])>0){/*make sure there's stuff to order***/?>
			<ul>
			<?php foreach($cart['items'] as $item){ ?>
				<li><?php echo''.$item['count'].'x '.$item['name'].' '.$item['size'].' ['.$cart['currency'].' '.$item['price'].']' ?> <span><?php echo''.$cart['currency'].' '.$item['pricetotal'].''; ?></span></li>
				<?php if(is_array($item['additionalinfo']) && count($item['additionalinfo'])>0){foreach($item['additionalinfo'] as $additionalInfo){?>
					<span><?php echo $additionalInfo ?></span>
				<?php }} ?>
			<?php } ?>
			</ul>

			<ul id="wppizza-cart-subtotals">

				<li><?php echo $txt['order_items']['lbl'] ?><span><?php echo $cart['currency'].' '.$cart['order_value']['total_price_items']['val']; ?></span></li>

			<?php if($cart['order_value']['discount']['val']>0){/*discount applies*/?>
				<li><?php echo $txt['discount']['lbl'] ?><span><?php echo $cart['currency'].' '.$cart['order_value']['discount']['val']; ?></span></li>
			<?php } ?>

			<?php if(!isset($cart['self_pickup_enabled']) ||  $cart['selfPickup']==0){ /*no self pickup enabled or chosen :conditional  added in v1.4.1*/ ?>
				<?php if($cart['order_value']['delivery_charges']['val']!='' ){/*delivery charges if any*/?>
					<li><?php echo $txt['delivery_charges']['lbl'] ?><span><?php echo $cart['currency'].' '.$cart['order_value']['delivery_charges']['val']; ?></span></li>
				<?php }else{ ?>
					<li><?php echo $txt['delivery_charges']['lbl'] ?><span><?php echo $txt['free_delivery']['lbl'] ?></span></li>
				<?php } ?>
			<?php } ?>
				<li id="wppizza-cart-total"><?php echo $txt['order_total']['lbl'] ?><span><?php echo $cart['currency'].' '.$cart['order_value']['total']['val']; ?></span></li>

			<?php if(isset($cart['self_pickup_enabled']) &&  $cart['selfPickup']==1){ /*self pickup conditional-> no delivery charges : added in v1.4.1**/ ?>
				<li id="wppizza-self-pickup"><?php echo $txt['order_page_self_pickup']['lbl'] ?></li>
			<?php } ?>
			</ul>

			<?php if(isset($cart['self_pickup_enabled']) && isset($cart['self_pickup_order_page'])){ /*allow self pickup and display on order page: added in v1.4.1**/ ?>
				<div class="wppizza-order-pickup-choice">
					<label><input type='checkbox' id='<?php echo $cart['selfPickupId'] ?>' name='wppizza-order-pickup' value='1' <?php checked($cart['selfPickup'],1,true) ?> /><?php echo $cart['order_self_pickup'] ?></label>
				</div>
			<?php } ?>

		<?php }else{ ?>
			<p><?php echo $txt['cart_is_empty']['lbl'] ?></p>
		<?php } ?>
	</fieldset>

	<?php if(count($cart['items'])>0){/*make sure there's stuff to order***/?>
	<fieldset>
		<legend><?php echo $txt['order_form_legend']['lbl'] ?></legend>
		<?php foreach($formelements as $elm){if($elm['enabled']){?>
			<label for="<?php echo $elm['key'] ?>"><?php echo $elm['lbl'] ?><?php echo !empty($elm['required'])?'*':'' ?></label>
			<?php if($elm['type']=='text'){?>
				<input id="<?php echo $elm['key'] ?>" name="<?php echo $elm['key'] ?>" type="text" value="" <?php echo !empty($elm['required'])?'required':'' ?>/>
			<?php } ?>
			<?php if($elm['type']=='email'){?>
				<input id="<?php echo $elm['key'] ?>" name="<?php echo $elm['key'] ?>" type="email" value="" <?php echo !empty($elm['required'])?'required':'' ?>/>
			<?php } ?>
			<?php if($elm['type']=='textarea'){?>
				<textarea id="<?php echo $elm['key'] ?>" name="<?php echo $elm['key'] ?>" <?php echo !empty($elm['required'])?'required':'' ?>></textarea>
			<?php } ?>
			<?php if($elm['type']=='select'){?>
				<select id="<?php echo $elm['key'] ?>" name="<?php echo $elm['key'] ?>" <?php echo !empty($elm['required'])?'required':'' ?>>
					<option value="">--------</option>
					<?php foreach($elm['value'] as $a=>$b){?>
					<option value="<?php echo wppizza_validate_string($b) ?>"><?php echo $b ?></option>
					<?php } ?>
				</select>
			<?php } ?>
		<?php }}?>
		<input class="submit" type="submit" style="display:block" value="<?php echo $txt['send_order']['lbl'] ?>"/>
	</fieldset>
	<?php } ?>
</form>
