<?php

namespace App\Http\Controllers;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Enumerations\Business\PluginProvider;


class EcwidController extends Controller {
	

//payment request from ecwid	
public function payment(Request $request) {
       $this->validate($request, [
        'data' => 'required',
    ]);  
   
	
	if(isset($request->data)) { 
    
    $ecwid_payload = $request->data;
    
	
	//update the secret once made live app    
	$client_secret = 'M4bxu6NQTH37UIKJ5DLXryZMz26TVmlC';
    $order = $this->getEcwidPayload($client_secret, $ecwid_payload);
    $storeID=$order['storeId'];
    $access_token = $order['token'];
    
    // get hitpay settings from ecwid
    $url= 'https://app.ecwid.com/api/v3/'.$storeID.'/storage/public?token='.$access_token;	
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL,$url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json;charset=utf-8'));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $settings_output = curl_exec ($ch);
    $settings_results  = json_decode($settings_output);
    $settings = json_decode($settings_results->value);

    $orderNumber=str_replace("transaction_","",$order['cart']['order']['referenceTransactionId']);

    if($settings->mode == "1"){
        $api_key = $settings->testapikey;
        $url= 'https://api.sandbox.hit-pay.com/v1/payment-requests';
        $return_url = "https://sandbox.hit-pay.com/ecwid/redirect?storeId=".$storeID."&order=".$orderNumber."&currency=".$order['cart']['currency'];
        $webhook = "https://sandbox.hit-pay.com/ecwid/hitpay?storeId=".$storeID."&order=".$orderNumber."&currency=".$order['cart']['currency'];	
        
    }else{
        $api_key = $settings->apikey;
        $url= 'https://api.hit-pay.com/v1/payment-requests';
        $return_url = "https://hit-pay.com/ecwid/redirect?storeId=".$storeID."&order=".$orderNumber."&currency=".$order['cart']['currency'];
        $webhook = "https://hit-pay.com/ecwid/hitpay?storeId=".$storeID."&order=".$orderNumber."&currency=".$order['cart']['currency'];	
    }

    $token_query=DB::insert("insert into ecwid (order_id,store_id,token) values (?,?,?)",[$orderNumber,$storeID,$access_token]);   
   
   if(isset($order['cart']['order']['billingPerson'])){
   $bname = $order['cart']['order']['billingPerson']['name'];
   } else {
   $bname = $order['cart']['order']['shippingPerson']['name'];
   }
   
    $data = array(
        "email"=>$order['cart']['order']['email'],
        "redirect_url"=>$return_url,
        "webhook"=>$webhook,
        "amount"=>$order['cart']['order']['total'],
        "reference_number"=>$orderNumber,
        "name"=>$bname,
        "currency"=>$order['cart']['currency'],
        "channel" => PluginProvider::ECWID
    );
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL,$url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('X-BUSINESS-API-KEY: '.$api_key,'X-Requested-With: XMLHttpRequest','Content-Type: application/x-www-form-urlencoded'));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS,http_build_query($data));
    $payment_output = curl_exec ($ch);
    $payment_results  = json_decode($payment_output);   
    curl_close ($ch);
	
	//redirect to gateway
    header('Location: '.$payment_results->url);
 }
}

public function redirect(Request $request) {
    sleep(7);        
	$this->validate($request, [
        'storeId' => 'required',
    ]);
	
	$client_id = 'custom-app-11573331-14';	
    $storeId = $request->storeId;
    $orderNumber = $request->order;
    $status = $request->status;
	
	$token_query=DB::table('ecwid')->where('store_id', $storeId)->where('order_id', $orderNumber)->orderBy('ID', 'desc')->first();	
	$token = $token_query->token;       	

  	$returnUrl ='https://app.ecwid.com/custompaymentapps/'.$storeId.'?orderId='.$orderNumber.'&clientId='.$client_id;
		 
  	header('Location: '.$returnUrl);
	exit;

}

public function save_settings(Request $request) {
	
	$this->validate($request, [
        'storeId' => 'required',
    ]);
    
	$storeid=$request->storeId;
	$access_token=$request->accessToken;
	
	//save app settings in ecwid for each store
	$data = array("apikey"=>$request->apikey,"mode"=>$request->mode,"testapikey"=>$request->testapikey,"secretkey"=>$request->secretkey,"stagingsecretkey"=>$request->stagingsecretkey);	
	

	$url= 'https://app.ecwid.com/api/v3/'.$storeid.'/storage/public?token='.$access_token;
	$data_string = json_encode($data);
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL,$url);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json;charset=utf-8','Content-Length: '.strlen($data_string)));
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS,$data_string);
	$settings_output = curl_exec ($ch);
	$settings_results  = json_decode($settings_output);
	curl_close ($ch);
	
	
	return "Settings Saved";	

}

// load admin settings page
public function settings(Request $request) {		    		
	return view('ecwid.settings');
}

// load admin settings page
public function load_form(Request $request) {
		    
	$this->validate($request, [
        'storeId' => 'required',
    ]);
	
	$storeid=$request->input('storeId');	
	$access_token=$request->input('accessToken');
	
	$url= 'https://app.ecwid.com/api/v3/'.$storeid.'/storage/public?token='.$access_token;	
    
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL,$url);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json;charset=utf-8'));
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	$settings_output = curl_exec ($ch);
	$settings_results  = json_decode($settings_output);	    
    if(isset($settings_results->value)){
	    $result = json_decode($settings_results->value);
    } else {		
		$result  = (object)array('apikey'=>'','mode'=>'','testapikey'=>'','secretkey'=>'');
	}
    
    
	$result->storeId = $storeid;
	$result->accessToken = $access_token;    
	$settings = $result;   
    
	return view('ecwid.loadform',compact('settings'));   


}


// update order status in ecwid 
public function hitpay(Request $request) {
		    
	$this->validate($request, [
        'storeId' => 'required',
    ]);
	
	
	$storeId=$request->input('storeId');
	$orderNumber = $request->input('order');
	$token_query=DB::table('ecwid')->where('store_id', $storeId)->where('order_id', $orderNumber)->orderBy('ID', 'desc')->first();	
	$access_token= $token_query->token; 	
	
	$url= 'https://app.ecwid.com/api/v3/'.$storeId.'/storage/public?token='.$access_token;
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL,$url);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json;charset=utf-8'));
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	$settings_output = curl_exec ($ch);
	$settings_results  = json_decode($settings_output);
	$settings = json_decode($settings_results->value);   	
    $status = $request->input('status');
	
	if($settings->mode == "1"){
        $secret = $settings->stagingsecretkey;       
        
    }else{
        $secret = $settings->secretkey;       
    }
	
	$hmacSource = [];		
	$amt = $request->input('amount');
	$phone = '';
	$vals = [
    "payment_id" => $request->input('payment_id'),
    "payment_request_id" => $request->input('payment_request_id'),
    "phone" => $request->input('phone'),
    "currency" => $request->input('currency'),
    "amount" => $amt,
    "status" => $request->input('status'),
    "reference_number" => $request->input('reference_number'),
	];
	
	$exected = $request->input('hmac');
	$signature  = $this->ecwidSignatureArray($secret,$vals);
	    
    	
    if ($signature !== $exected){
		exit;
	}

	$client_id = 'custom-app-11573331-14';

	if($status=='completed'){
	    $status = 'PAID';
	}else{
	    $status = 'CANCELLED';
	}
 
    $json = json_encode(array(
        "paymentStatus" => $status        
    ));

    $url = "https://app.ecwid.com/api/v3/$storeId/orders/transaction_$orderNumber?token=".$access_token;	
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json','Content-Length: ' . strlen($json)));
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
    curl_setopt($ch, CURLOPT_POSTFIELDS,$json);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);
	

}	

	
//function to decode request from ecwid
private	function getEcwidPayload($app_secret_key, $data) {
        $encryption_key = substr($app_secret_key, 0, 16);
        $json_data = $this->aes_128_decrypt($encryption_key, $data);
        $json_decoded = json_decode($json_data, true);
        return $json_decoded;
}

//addtional function to decode request from ecwid
 private function aes_128_decrypt($key, $data) {
        $base64_original = str_replace(array('-', '_'), array('+', '/'), $data);
        $decoded = base64_decode($base64_original);        
        $iv = substr($decoded, 0, 16);
        $payload = substr($decoded, 16);
        $json = openssl_decrypt($payload, "aes-128-cbc", $key, OPENSSL_RAW_DATA, $iv);
        return $json;
    }

private function ecwidSignatureArray($secret, array $args) {   

        $hmacSource = [];        
        foreach ($args as $key => $val) {
            $hmacSource[$key] = "{$key}{$val}";
        } 
        ksort($hmacSource);
        $sig            = implode("", array_values($hmacSource));        
        $calculatedHmac = hash_hmac('sha256', $sig, $secret); 
        return $calculatedHmac;
}

}