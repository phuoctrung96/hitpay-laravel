<div class="a-card a-card--normal">
	<div class="a-card__paddings">
		<form class="hitpay-form">
			<div class="form-area">
				
				<div class="form-area__title">HitPay Live API Key</div>
				<div class="form-area__content">
					<div class="fieldsets-batch fieldsets-batch--horizontal">
						<div class="fieldset fieldset--no-label">
							<div class="field">
								<label class="field__label">API Key</label>
								<input type="text" class="field__input" name="apikey" value="<?php if(isset($settings->apikey)){echo $settings->apikey;} ?>">
								<div class="field__placeholder"></div>
							</div>
						</div>
					</div>
					<p>Get your API key from HitPay business dashboard.</p>
				</div>
				<div class="form-area__content">
					<div class="fieldsets-batch fieldsets-batch--horizontal">
						<div class="fieldset fieldset--no-label">
							<div class="field">
								<label class="field__label">Salt</label>
								<input type="text" class="field__input" name="secretkey" value="<?php if(isset($settings->secretkey)){echo $settings->secretkey;} ?>">
								<div class="field__placeholder"></div>
							</div>
						</div>
					</div>
					<p>Get your API Salt from HitPay business dashboard.</p>
				</div>
				<div class="form-area__title">
					<span class="form-area__title-text">HitPay sandbox</span>
					<label class="checkbox micro">
						<input type="checkbox" id="mode" <?php if(isset($settings->mode) && $settings->mode == "1"){ echo "checked"; } ?> name="mode" value="test">
						<div data-on="enabled" data-off="disabled"><div></div></div>
					</label>
				</div>
				<div class="form-area__title">HitPay Staging API Key</div>
				<div class="form-area__content">
					<div class="fieldsets-batch fieldsets-batch--horizontal">
						<div class="fieldset fieldset--no-label">
							<div class="field">
								<label class="field__label">Staging API Key</label>
								<input type="text" class="field__input" name="testapikey" value="<?php if(isset($settings->testapikey)){echo $settings->testapikey;} ?>">
								<div class="field__placeholder"></div>
							</div>
						</div>
					</div>
					<p>Get your Sandbox API key from HitPay business dashboard.</p>
				</div>
				
				<div class="form-area__content">
					<div class="fieldsets-batch fieldsets-batch--horizontal">
						<div class="fieldset fieldset--no-label">
							<div class="field">
								<label class="field__label">Salt</label>
								<input type="text" class="field__input" name="stagingsecretkey" value="<?php if(isset($settings->stagingsecretkey)){echo $settings->stagingsecretkey;} ?>">
								<div class="field__placeholder"></div>
							</div>
						</div>
					</div>
					<p>Get your Sandbox API Salt from HitPay business dashboard.</p>
				</div>
			</div>
			<div class="form-area__action" style="margin-top: 20px;">
				<input type="hidden" name="storeId" value="<?php echo $settings->storeId; ?>">
				<input type="hidden" name="accessToken" value="<?php echo $settings->accessToken; ?>">
				<button type="button" class="btn btn-primary btn-medium btn-setting-save" tabindex="5">Save</button>
				<button style="display: none;" class="btn btn-primary btn-medium btn-loading btn_setting_loading"><span>Save</span></button>
			</div>
		</form>
	</div>
</div>
<script type="text/javascript">
	jQuery(document).ready(function(){
		jQuery('body').on('click','.btn-setting-save',function(e){
	    	e.preventDefault();
	    	jQuery('.btn_setting_loading').show();
            jQuery(this).hide();
	    	var mode;
	    	if(jQuery("#mode").is(":checked")){var mode=1;}else{var mode=0;}
	    	jQuery.ajax({
                url:'/ecwid/save_settings',
                type: 'post',
                data:  jQuery('.hitpay-form').serialize()+'&mode='+mode,
                success: function(data){
                    jQuery('.btn-setting-save').show();
                    jQuery('.btn_setting_loading').hide();
					}
            });
	    });
	});
	</script>