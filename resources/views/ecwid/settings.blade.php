<!DOCTYPE html>
<html>
    <head>
    	<meta http-equiv="content-type" content="text/html; charset=UTF-8">
    	<meta http-equiv="X-UA-Compatible" content="IE=edge">
    	<meta name="viewport" content="height=device-height, width=device-width, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0, user-scalable=no">

        <link rel="stylesheet" href="https://d35z3p2poghz10.cloudfront.net/ecwid-sdk/css/1.3.9/ecwid-app-ui.css"/>
        <link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
        <script src="https://djqizrxa6f10j.cloudfront.net/ecwid-sdk/js/1.0.0/ecwid-app.js"></script>

        <script src="https://code.jquery.com/jquery-2.1.3.min.js"></script>
        <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>

    </head>
    <body>
        <script>
            // Initialize the application
            EcwidApp.init({
              app_id: "custom-app-11573331-14", // your application namespace
              autoloadedflag: true,
              autoheight: true
            });
            // Get the store ID and access token
            var storeData = EcwidApp.getPayload();
            var storeId = storeData.store_id;
            var accessToken = storeData.access_token;
        	var lang = storeData.lang;
        </script>
        <script>
            jQuery(document).ready(function(){
                jQuery.ajax({
                    url:'/ecwid/load_form',
                    type: 'post',
                    data:  {'storeId': storeId,'accessToken': accessToken },
                    success: function(data){
                        jQuery('.app_setting_container').html(data);
                    }
					});
            });
        </script>
        <div id='appContent' class="container app_setting_container"></div>
        <script src="https://d35z3p2poghz10.cloudfront.net/ecwid-sdk/css/1.3.9/ecwid-app-ui.min.js"></script>
    </body>
	</html>