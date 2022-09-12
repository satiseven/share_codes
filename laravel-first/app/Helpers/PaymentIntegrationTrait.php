<?php

namespace App\Http\Controllers\Traits;

use App\Models\BankReferenceInformation;
use App\Models\Commission;
use App\Models\Deposit;
use App\Models\DepositeMethod;
use App\Models\Merchant;
use App\Models\MerchantCardBlacklist;
use App\Models\MerchantCommission;
use App\Models\MerchantIpAssaignment;
use App\Models\MerchantPosCommission;
use App\Models\MerchantPosPFSetting;
use App\Models\MerchantSettings;
use App\Models\PaymentRecOption;
use App\Models\PurchaseRequest;
use App\Models\Sale;
use App\Models\SaleCurrencyConversion;
use App\Models\SalesPFRecords;
use App\Models\Settlement;
use App\Models\SubMerchantPF;
use App\Models\TemporaryPaymentRecord;
use App\Models\TmpSaleAutomation;
use App\Models\TmpWixForm;
use App\Models\TransactionState;
use App\User;
use App\Models\Bank;
use App\Models\Currency;
use App\Models\PaymentProvider;
use App\Models\Pos;
use App\Utils\CommonFunction;
use Carbon\Carbon;
use common\integration\BankTarim;
use common\integration\CardProgram;
use common\integration\CraftgateApi;
use common\integration\DCCIntegration;
use common\integration\GlobalFunction;
use common\integration\TeqPay;
use Craftgate\Model\CardAssociation;
use Hamcrest\Type\IsObject;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Payconn\Common\CreditCard;
use Payconn\Common\AbstractRequest;
use Payconn\QNBFinansbank\Token;
use Payconn\QNBFinansbank\Model\Authorize;
use Payconn\QNBFinansbank;

/**
 * Created by PhpStorm.
 * User: user
 * Date: 3/1/2019
 * Time: 3:46 PM
 */
trait PaymentIntegrationTrait
{
    public function redirectToBank($info,$payByCardToken = false)
    {
        // plz do not assign any extras variable here.. use common method for extras.
        $extras = $info["extras"];


        $redirectToBankUrl = route('redirect_to_bank');
        $purchaseRequestObj = GlobalFunction::getBrandSession('PurchaseRequest', $extras['order_id']);



        $bankObj = $info['bankObj'];

        $extras['payment_provider'] = $bankObj->payment_provider;


        if (isset($info['payment_type']) && $info['payment_type'] == 'PAY_3D') {
            GlobalFunction::setBrandSession('leave_application',1, $extras['order_id']);
            $tmpPaymentRecord = new TemporaryPaymentRecord();
            $tmpPaymentRecordObj = $tmpPaymentRecord->getTmpPaymentTransactionByOrderId($extras['order_id']);
            if (empty($tmpPaymentRecordObj)) {
                $tmpPrms = $extras;
                $tmpPrms['ip'] = $purchaseRequestObj->ip;
                $tmpPrms['invoice_details'] = json_encode($purchaseRequestObj->data);
                $tmpPrms['merchant_key'] = $purchaseRequestObj->merchant_key;
                $tmpPrms['name'] = $purchaseRequestObj->name;
                $tmpPrms['surname'] = $purchaseRequestObj->surname;
                $tmpPrms['currency_id'] = $purchaseRequestObj->currency_id;
                $tmpPrms['currency_code'] = $purchaseRequestObj->currency_code;
                $tmpPrms['commission'] = $info['commissionData'] ?? '';
                $tmpPrms['bankObj'] = $info['bankObj'];
                $tmpPrms['dpl'] = GlobalFunction::getBrandSession('dpl', $extras['order_id']);
                $tmpPrms['session_data'] = $extras;
                $tmpPrms['session_data']['userObj'] = GlobalFunction::getBrandSession('userObj', $extras['order_id']);
                $tmpPrms['session_data']['mobile'] = GlobalFunction::getBrandSession('mobile', $extras['order_id']);

                $tmpPaymentRecordObj = $tmpPaymentRecord->saveTmpPaymentRecord($tmpPrms, $purchaseRequestObj);

            }else{
                abort(404, 'RESUBMIT_WITH_SAME_ORDER_ID');
            }

        }


        GlobalFunction::setBrandSession('3d_data', $extras, $extras['order_id']);


        $clientId = $this->customEncryptionDecryption($bankObj->client_id, \config('app.brand_secret_key'), 'decrypt'); //"700667135511";

        $storekey = $this->customEncryptionDecryption($bankObj->store_key, \config('app.brand_secret_key'), 'decrypt'); //"SiPay1!!!";
        $user_name = $this->customEncryptionDecryption($bankObj->user_name, \config('app.brand_secret_key'), 'decrypt'); //"SiPay1!!!";
        $password = $this->customEncryptionDecryption($bankObj->password, \config('app.brand_secret_key'), 'decrypt'); //"SiPay1!!!";
        $api_user_name = $this->customEncryptionDecryption($bankObj->api_user_name, \config('app.brand_secret_key'), 'decrypt'); //"SiPay1!!!";
        $api_password = $this->customEncryptionDecryption($bankObj->api_password, \config('app.brand_secret_key'), 'decrypt'); //"SiPay1!!!";




        $info['product_price'] = $purchaseRequestObj->data->total ?? 0;


        if (GlobalFunction::isTestTransaction()) {
            $info['amount'] = '0.10'; //"9.95";
            $info['product_price'] = '0.10';
            $extras['payable_amount'] = '0.10';
        }

        $info['amount']  = number_format($info['amount'] , 2, '.', '');
        $info['product_price'] = number_format($info['product_price'] , 2, '.', '');



        if ($bankObj->payment_provider == config('constants.PAYMENT_PROVIDER.VAKIF')) {

            list($form, $actionUrl, $respnseLogData) = $this->redirectToVakifBank($info, $clientId,
                $api_password, $storekey);

        } elseif ($bankObj->payment_provider == config('constants.PAYMENT_PROVIDER.ESNEKPOS')) {

            list($form, $actionUrl, $respnseLogData) = $this->redirectToEsnekPos($info, $user_name, $password);

        } elseif ($bankObj->payment_provider == config('constants.PAYMENT_PROVIDER.ALBARAKA')) {

            list($form, $actionUrl, $respnseLogData) = $this->redirectToAlBarakaBank($info, $clientId, $user_name, $password);

        } elseif ($bankObj->payment_provider == config('constants.PAYMENT_PROVIDER.MSU')) {

            list($form, $actionUrl, $respnseLogData) = $this->redirectToMsu($info, $clientId, $user_name, $password,$payByCardToken);

        }elseif ($bankObj->payment_provider == config('constants.PAYMENT_PROVIDER.TURKPOS')) {

            list($form, $actionUrl, $respnseLogData) = $this->redirectToTurkpos($info, $clientId, $user_name, $password, $storekey);

        }elseif ($bankObj->payment_provider == config('constants.PAYMENT_PROVIDER.PAYALL')) {

            list($form, $actionUrl, $respnseLogData) = $this->redirectToPayAll($info, $clientId, $password);

        }elseif ($bankObj->payment_provider == config('constants.PAYMENT_PROVIDER.YAPI_VE_KREDI')) {

            list($form, $actionUrl, $respnseLogData) = $this->redirectToYapiKredi($info, $clientId,$user_name, $password);

        } elseif ($bankObj->payment_provider == config('constants.PAYMENT_PROVIDER.DENIZ_PTT')){

            list($form, $actionUrl, $respnseLogData) = $this->redirectToDenizAndPtt($info, $clientId, $user_name,
                $password,  $api_user_name, $api_password);

        } elseif ($bankObj->payment_provider == config('constants.PAYMENT_PROVIDER.DUMMY_PAYMENT')){

            list($form, $actionUrl, $respnseLogData) = $this->redirectToDummyPayment($info, $clientId);

        }elseif ($bankObj->payment_provider == config('constants.PAYMENT_PROVIDER.PAYMIX')){

           list($form, $actionUrl, $respnseLogData) = $this->redirectToPaymix($info, $clientId, $user_name, $password);

        }elseif ($bankObj->payment_provider == config('constants.PAYMENT_PROVIDER.KUVEYT_TURK_KATILIM')){

           list($form, $actionUrl, $respnseLogData) = $this->redirectToKuveytTurk($info, $clientId, $storekey, $user_name, $password, $api_password);

        }
        elseif ($bankObj->payment_provider == config('constants.PAYMENT_PROVIDER.SOFTROBOTICS')){

           list($form, $actionUrl, $respnseLogData) = $this->redirectToSoftrobotics($info, $clientId, $password);

        }elseif ($bankObj->payment_provider == config('constants.PAYMENT_PROVIDER.QNB_FINANSBANK')) {

            list($form, $actionUrl, $respnseLogData) = $this->redirectToQNBFinansBank($info, $clientId, $storekey, $api_user_name, $api_password);
        }
        elseif ($bankObj->payment_provider == config('constants.PAYMENT_PROVIDER.CRAFT_GATE')){

            list($form, $actionUrl, $respnseLogData) = $this->redirectToCraftGate($info, $api_user_name, $api_password, $payByCardToken);

        }elseif ($bankObj->payment_provider == config('constants.PAYMENT_PROVIDER.TEQPAY')){

            list($form, $actionUrl, $respnseLogData) = $this->redirectToTeqPay($extras, $api_user_name, $api_password, $bankObj);

        }
        else {

            list($form, $actionUrl, $respnseLogData) = $this->redirectToNestPay3d($info, $clientId, $storekey);

        }

//        log data
        $logData['action'] = "3D Payment By Credit Card";
        $logData['payment_provider'] = $bankObj->payment_provider;
        $logData["info"] = $info;
        $logData['invoice_id'] = $extras["invoice_id"];
        $logData['localization_code'] = app()->getLocale();
        $logData['instalment'] = $extras["installment"];
        $logData['dpl'] = GlobalFunction::getBrandSession('dpl', $extras["order_id"]);
        $logData['mobile'] = GlobalFunction::getBrandSession('mobile', $extras["order_id"]);
//       $logData['form'] = $form;
        $logData['api_url'] = $actionUrl;
//        $logData = $this->unsetLogData($logData);

        $user = new User();
        $logData = $this->remove_success_fail_url($logData);
        $logData = array_merge($logData, $respnseLogData);
        $user->createLog($this->_getCommonLogData($this->unsetKeys($logData)));


       $posObj = $extras['posObj'] ?? null;
       $merchantObj = $info['merchantObj'] ?? null;

       $form_start = '<form id="the-form" method="post" action="' . $redirectToBankUrl . '">';

        $form_close = '</form>';
        $script = '<script type="text/javascript">

                        window.onload = function(){
                            document.getElementById("the-form").action ="' . @$actionUrl . '";
                            document.getElementById("the-form").submit();
                        }
                    </script>';


        if(isset($info['isWix']) && $info['isWix'] == 1){

            $data['merchant_id']= $info['merchant_id'];
            $data['invoice_id'] = $info['invoice_id'];
            $data['order_id'] = $info["oder_id"];
            $data['key_name'] = $info['wix_payment_key'];
           
            $form_string =  $form_start . $form . $form_close . $script;
            $form_string_enc = $this->customEncryptionDecryption($form_string, \config('app.brand_secret_key'), 'encrypt');
            //$data['form_obj'] = $form_start . $form . $form_close . $script;
            $data['form_obj'] = $form_string_enc;

            try{

                $tmpWixform = new TmpWixForm();
                $tmpWixform->saveWixFormData($data);

                return [100, $extras];
            }catch (Exception $ex){
                return 99;
            }
       } elseif (!empty($posObj) && $posObj->is_allow_dcc && $posObj->allow_foreign_card && !empty($merchantObj)) {

           $merchant_id = $merchantObj['id'];
           $merchantSetting = new MerchantSettings();
           $merchantSettingObj = $merchantSetting->getMerchantSettingByMerchantId($merchant_id);

           if (!empty($merchantSettingObj) && $merchantSettingObj->is_allow_dcc) {

              $dccIntegration = new DCCIntegration($info, $form_start, $form, $form_close, $script);
              $dccIntegration->processDCC();

              if (isset($dccIntegration->status) && $dccIntegration->status && isset($dccIntegration->user_decision_form) &&
                !empty($dccIntegration->user_decision_form)) {

                 echo $dccIntegration->user_decision_form;
                 exit();

              }
           }

        }elseif($bankObj->payment_provider == config('constants.PAYMENT_PROVIDER.KUVEYT_TURK_KATILIM')
        || $bankObj->payment_provider == config('constants.PAYMENT_PROVIDER.CRAFT_GATE')){
           if(!empty($actionUrl)){
              echo $form_start . $form . $form_close . $script;
           }else{
              echo $form;
           }
           exit();
        }

/*
        elseif ($bankObj->payment_provider == config('constants.PAYMENT_PROVIDER.QNB_FINANSBANK')) {
            echo $form;
            exit;
        }
*/

       echo $form_start . $form . $form_close . $script;
    }

    private function redirectToTeqPay($extras, $api_username, $api_password, $bankObj){

        $purchaseRequestObj = GlobalFunction::getBrandSession('PurchaseRequest', $extras['order_id']);

        $callbackUrl = route('3d.teqpay3dResponse').'?brand_order_id='.$extras['order_id'];
        $teqpay = new TeqPay($api_username, $api_password, $bankObj->api_url);
        $teqpay->brandedSolution($extras, $purchaseRequestObj, $callbackUrl);

        $form = '';
        if (isset($teqpay->response_data['PaymentData']['PaymentUrl'])){
            $actionUrl = $teqpay->response_data['PaymentData']['PaymentUrl'];
        }else{
            $error = $teqpay->response_data['ResultMessage'] ?? 'unknown Error';
            $error_code = $teqpay->response_data['ResultCode']?? '';
            $form .= '<input type="hidden" name="conversationId" value="'.$extras['order_id'].'">';
            $form .= '<input type="hidden" name="ResultMessage" value="'.$error.'">';
            $form .= '<input type="hidden" name="ResultCode" value="'.$error_code.'">';

            $actionUrl = $callbackUrl;
        }

        return array($form, $actionUrl, []);
    }

    private function redirectToQNBFinansBank($info, $client_id, $client_password, $api_username, $api_password)
    {


        $bankObj = $info['bankObj'];
        $action_url = $bankObj->gate_3d_url;

        $order_id = $info['extras']['order_id'];
        $payable_amount = $info['extras']['payable_amount'];
        $installment = $info['extras']['installment'];
        /*
        if (strlen($installment) == 1){
            $installment = '0'.$installment;
        }
        */
        if($installment < 2){
            $installment = 0;
        }

        $authorize = new \Payconn\QNBFinansbank\Model\Authorize();
        $authorize->setInstallment((int)$installment);
        $installment = $authorize->getInstallment();

        $currency_code = $info['currency_code'];
        $currency_iso_code = Currency::where('code',$currency_code)->first()->iso_code;

        $credit_card_no = $info['extras']['credit_card_no'];
        $expiry_year = $info['extras']['expiry_year'];
        $expiry_month = $info['extras']['expiry_month'];
        $cvv = $info['extras']['cvv'];

        $success_url = route('3d.qnbFinansSuccess');
        $fail_url = route('3d.qnbFinansFail');

        $token = new Token($client_id, $client_password, $api_username, $api_password);


/*
        //test credentials
        $client_id ="085300000009704";
        $client_password="12345678";
        $api_username="apiyasin";
        $api_password="xS1PA";

        $installment = 0;
        $payable_amount = 1;
*/
       // dd($client_id, $client_password, $api_username, $api_password);

        $mbr_id = '5';
        $txn_type = 'Auth';
        $rnd = microtime();

        $payable_amount = number_format($payable_amount,2);



        $hash_string = $mbr_id.$order_id. $payable_amount. $success_url. $fail_url. $txn_type. $installment
                        .$rnd.$client_password;
        $hash = base64_encode(pack('H*', sha1( $hash_string)));

        $creditCard = new CreditCard($credit_card_no, $expiry_year, $expiry_month, $cvv);

        $Pan = $creditCard->getNumber();
        $Cvv2 = $creditCard->getCvv();
        $Expiry = $creditCard->getExpireMonth().$creditCard->getExpireYear();
        $form_params =
            [
                'MbrId' => $mbr_id,
                'MerchantID' => $client_id,
                'UserCode' => $api_username,
                'UserPass' => $api_password,
                'SecureType' => '3DModel',
                'TxnType' => $txn_type,
                'InstallmentCount' => $installment,
                'Currency' => $currency_iso_code,
                'Pan' => $Pan,
                'Expiry' => $Expiry,
                'Cvv2' => $Cvv2,
                'OkUrl' => $success_url,
                'FailUrl' => $fail_url,
                'OrderId' => $order_id,
                'OrgOrderId' =>'',
                'PurchAmount' => $payable_amount,
                'Lang' => 'TR',
                'Rnd' => $rnd,
                'Hash' => $hash,

            ];


       $form_inputs = '';
        foreach ($form_params as $key => $value){
            $form_inputs .= '<input type="hidden" name="' . $key . '" value="' . $value . '">' . "\r\n";
        }

        $pf_form_inputs = $this->getPFRecords($info);
        $final_form_inputs = $form_inputs.$pf_form_inputs;
        //dd($action_url, $form_inputs, $pf_form_inputs, $final_form_inputs);

        $log_data['rdm'] = $rnd;
        $log_data['transactionType'] = $txn_type;
        $log_data['hash'] = $hash;
        $log_data['pfRequest'] = $pf_form_inputs;

        // mask credit card info from form params log
        $findData = [ $Pan,  $Cvv2, $Expiry ];
        $log_data['tmp_request'] = GlobalFunction::maskCreditCardFormParams($findData, $form_inputs);

        return [$final_form_inputs, $action_url, $log_data];



/*
         $authorize = new Authorize();

        //$isTestTransaction = GlobalFunction::isTestTransaction();
        if($client_id == '085300000009704') {
            $authorize->setTestMode(true);
        }
        $authorize->setCurrency($currency_iso_code);
        $authorize->setAmount($payable_amount);
        $authorize->setInstallment($installment);
        //$authorize->setCreditCard(new CreditCard('4155650100416111', '25', '01', '123'));
        $authorize->setCreditCard(new CreditCard($credit_card_no, $expiry_year, $expiry_month, $cvv));
        $authorize->setSuccessfulUrl($success_url);
        $authorize->setFailureUrl($fail_url);
        $authorize->setOrderId($order_id);

        $response = (new QNBFinansbank($token))->authorize($authorize);

        if ($response->isSuccessful() && $response->isRedirection()) {
            $form = $response->getRedirectForm();
            $log_data['3d_form'] = $form;
        }

        $action_url = null;

        //$log_data['form'] = $form;
        return [$form, $action_url, $log_data];
*/
    }

    private  function redirectToSoftrobotics($info, $clientId, $password){

       $bankObj = $info['bankObj'];

       if (strlen($info['month']) == 1){
          $info['month'] = '0'.$info['month'];
       }
       if (strlen($info['installment']) == 1){
          $info['installment'] = '0'.$info['installment'];
       }

       if (intval($info['installment']) < 2) {
          $info['installment'] = "1";
       }


       $items = [];
       if (isset($info['extras']['items'])) {
          $items = $info['extras']['items'];
          $len = count($items);
          $item_total = 0;
          foreach($items as $item){
              $quantity = $quantity = $item['qty'] ?? $item['quantity'] ?? $item['qnantity'];
              $one_item_total = $item['price']*$quantity;
              $item_total += $one_item_total;
          }
          $extra_fee = $info['extras']['payable_amount'] - $item_total;
          if(abs($extra_fee) > 0) {
              $new_item =
                  [
                      'name' => 'extra fee item',
                      'price' => $extra_fee,
                      'quantity' => 1,
                      'description' => '',
                  ];
              //$items = array_push($items, $new_item);
              $items[$len] = $new_item;
          }
       }


       $instalment = $info['installment'];
       $amount = $info['extras']['payable_amount'];

       $currency_code = $info['currency_code'];
       $invoice_id = $info['invoice_id'];
       $order_id = $info['oder_id'];
       $CardNumber = $info['card_no'];
       //$CardHolderName = $info['cc_holder_name'];
       $CardHolderName = $info['extras']['card_holder_name'];
       $return_url = route('3d.softroboticsSuccessFailResponse').'?brand_order_id='.$info['oder_id'].'&second_hand_request=1';
       
       $hashData = [
         'total' => $amount,
         'installments_number' => $instalment,
         'currency_code' => $currency_code,
         'merchant_key' => $clientId,
         'invoice_id' => $order_id
       ];

       //dd(json_encode($items));

       $hash_key = $this->customEncryptionDecryption($hashData, $password, 'encrypt');

       $purchaseRequestObj = GlobalFunction::getBrandSession('PurchaseRequest', $info["oder_id"]);

       $name =  $surname =   $email =  $phone =   $city =  $state =  $billAddress1 = $billAddress2 = $country =
       $bill_postcode = '';

       if (!empty($purchaseRequestObj)) {
          $billAddress1 = $purchaseRequestObj->data->bill_address1 ?? '';
          $billAddress2 = $purchaseRequestObj->data->bill_address2 ?? '';
          $name = $purchaseRequestObj->name;
          $surname = $purchaseRequestObj->surname;
          $email = $purchaseRequestObj->data->bill_email ?? '';
          $phone = $purchaseRequestObj->data->bill_phone ?? '';
          $city = $purchaseRequestObj->data->bill_city ?? '';
          $state = $purchaseRequestObj->data->bill_state ?? '';
          $bill_postcode = $purchaseRequestObj->data->bill_postcode ?? '';
          $country = $purchaseRequestObj->data->bill_country ?? '';
       }

       $requestData = [
         'merchant_key' => $clientId,
         'invoice_id' => $order_id,
         'total' => $amount,
         'items' => htmlspecialchars(json_encode($items)),
         'currency_code' => $currency_code,
         'cc_holder_name' => $CardHolderName,
         'cc_no' => $CardNumber,
         'expiry_month' => $info['month'],
         'expiry_year' => $info['year'],
         'cvv' => $info['cvv'],
         'installments_number' => $instalment,
         'cancel_url' => $return_url,
         'return_url' => $return_url,
         'hash_key' => $hash_key,
         'name' => $name,
         'surname' => $surname,
         'saved_card' => $info["extras"]['saved_card'],
         'bill_address1' => $billAddress1,
         'bill_address2' => $billAddress2,
         'bill_city' => $city,
         'bill_postcode' => $bill_postcode,
         'bill_state' => $state,
         'bill_country' => $country,
         'bill_phone' => $phone,
         'bill_email' =>$email,
         'is_notification_off' => TmpSaleAutomation::IS_NOTIFICATION_OFF,
         'second_hand_request' => 1
       ];

       $form = '';
       foreach ($requestData as $key => $value){
          if (is_array($value)){
             $value = json_encode($value);
          }
          $form .='<input type="hidden" name="'.$key.'" value="'.$value.'" />';
       }

       $actionUrl = $bankObj->gate_3d_url;


       return array($form, $actionUrl, []);
    }

    private  function redirectToCraftGate($info, $api_user_name, $api_password, $payByCardToken){

        $bankObj = $info['bankObj'];
        $extras = $info['extras'];
        $actionUrl = '';

        $return_url = route('3d.craftGateSuccessFail3d') . '?brand_order_id=' . $extras['order_id'];
        $responseArray = (new CraftgateApi($api_user_name, $api_password, $bankObj->api_url))->pay3d($extras, $return_url, $payByCardToken);
        $form = '';
        if(isset($responseArray['data']['htmlContent'])){

            $form = base64_decode($responseArray['data']['htmlContent']);


        }else{
            $actionUrl = route('3d.craftGateSuccessFail3d').'?brand_order_id='.$extras['order_id'];
            $error = $responseArray['errors']['errorDescription'] ?? '';
            $error_code = $responseArray['errors']['errorCode'] ?? '';


            $form .= '<input type="hidden" name="conversationId" value="'.$extras['order_id'].'">';
            $form .= '<input type="hidden" name="errorDescription" value="'.$error.'">';
            $form .= '<input type="hidden" name="respCode" value="'.$error_code.'">';
        }

        return array($form, $actionUrl, []);
    }


    private function redirectToDummyPayment($info, $client_id){
        $requestData = [
            'invoice_id' => $info['oder_id'],
            'amount' => $info['amount'],
            'client_id' => $client_id,
            'return_url' => route('3d.dummyPaymentResponse').'?brand_order_id='.$info['oder_id']
        ];



        $pf_form = $this->getPFRecords($info);


        $form = '';
        foreach ($requestData as $key => $value){
            if (is_array($value)){
                $value = json_encode($value);
            }
            $form .='<input type="hidden" name="'.$key.'" value="'.$value.'" />';
        }

        $form .= $pf_form;


        $bankObj = $info['bankObj'];
        $actionUrl = $bankObj->gate_3d_url;


        return array($form, $actionUrl, []);
    }


    private function redirectToYapiKredi($info, $clientId, $user_name, $password){

        if (strlen($info['month']) == 1){
            $info['month'] = '0'.$info['month'];
        }
        if (strlen($info['installment']) == 1){
            $info['installment'] = '0'.$info['installment'];
        }

        if (strlen($info['year']) == 4){
            $info['year'] = substr($info['year'],-2);
        }

        if (intval($info['installment']) < 2) {
            $info['installment'] = "00";
        }

        if(empty($info['cvv'])){
            $info['cvv'] = '000';
        }

        $bankObj = $info['bankObj'];
        $actionUrl = $bankObj->gate_3d_url;
        $tokenUrl = $bankObj->token_url;
        $posNetId = $bankObj->store_type;


       $transactionType = 'Sale';
       if(isset($info['extras']['transaction_type'])  && $info['extras']['transaction_type'] == "PreAuth"){
        // $transactionType = $info['extras']['transaction_type'];
           $transactionType = "Auth";
       }


        $currency = new Currency();
        $smallCurrencyCode = $currency->getSmallCodeByIso($info['currency']);

        $merchantObj = null;
        $purchaseRequestObj = GlobalFunction::getBrandSession('PurchaseRequest', $info['oder_id']);
        if (isset($purchaseRequestObj->merchant_key)){
            $merchant = new Merchant();
            $merchantObj = $merchant->getActiveMerchantByKey($purchaseRequestObj->merchant_key);
        }

        $subMerchantPFObj = $info['extras']['subMerchantPFObj']??null;

        $globalFunction = new GlobalFunction();
        $pfParams = $globalFunction->managePFRecords($merchantObj, $bankObj,$info["extras"]['posObj'],
            $info['card_no'], true, $info['oder_id'],$subMerchantPFObj);

        $Xid = $this->getYapKrediOrderId($info['oder_id']);


        $requestData  = [
            'posnetid' => $posNetId,
            'XID' => $Xid,
            'amount' => ($info['amount']*100),
            'currencyCode' => $smallCurrencyCode,
            'installment' => $info['installment'],
            'tranType' => $transactionType,
            'cardHolderName' => mb_substr( $info['extras']['card_holder_name'], 0,60 ),
            'ccno' => $info['card_no'],
            'expDate' => $info['year'] . $info['month'],
            'cvc' => $info['cvv'],
        ];

        $final_request = array_merge($requestData,$pfParams);

        $xmlArray = [
            'mid' => $clientId,
            'tid' => $password,
            'oosRequestData' => $final_request
        ];
//        if (isset($info['cvv']) && !empty($info['cvv'])){
//            $xmlArray['oosRequestData']['cvc'] = $info['cvv'];
//        }

        $xmlRequest = $this->arrayToXML($xmlArray, '<posnetRequest/>');

        //$logData['token_request_data'] = $this->hideKeyValues($xmlArray,[$info['card_no'], $info['year'] . $info['month'], $password]);
        $logData['token_request_url'] = $tokenUrl;
        $ch = curl_init();
        $xmlRequest = urlencode($xmlRequest);
        $getUrl = $tokenUrl."?xmldata=".$xmlRequest;

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded; charset=utf-8'));
        curl_setopt($ch, CURLOPT_URL, $getUrl);
        curl_setopt($ch, CURLOPT_TIMEOUT, 80);

        $response = curl_exec($ch);
        curl_close($ch);

        $logData['curl_response'] = $response;


        $language = 'en';
        if ($smallCurrencyCode == Currency::TRY_SM_CODE){
            $language = 'tr';
        }



        $form = '';
        $error_response_code = '';

        if (!empty($response)){

            try {
                libxml_use_internal_errors(TRUE);
//                $result = simplexml_load_string($response);
                $result = new \SimpleXMLElement($response);
                $result = json_encode($result);
                $result = json_decode($result, true);

                $error_response_code = $result['respCode'] ?? '';
                if (isset($result['approved']) && $result['approved'] == 1){
                    $formData = [
                        'mid' => $clientId,
                        'posnetID' => $posNetId,
                        'posnetData' => $result['oosRequestDataResponse']['data1'],
                        'posnetData2' => $result['oosRequestDataResponse']['data2'],
                        'digest' => $result['oosRequestDataResponse']['sign'],
                        'vftCode' => '',
                        'merchantReturnURL' => route('3d.yapikrediResponse').'?brand_order_id='.$info['oder_id'],
                        'lang' => $language,
                        'url' => route('3d.yapikrediResponse').'?brand_order_id='.$info['oder_id'],
                        'openANewWindow' => false
                    ];

                    $logData['pf_info'] = $pfParams;

                    if (!empty($pfParams)){
                        $formData = $formData + $pfParams;
                    }




                    foreach ($formData as $key=>$value){
                        $form .= '<input type="hidden" name="'.$key.'" value="'.$value.'" >';
                    }

                    $logData['finalFormData'] = $form;
                    $logData['3d_url'] = $actionUrl;


                }else{
                    $actionUrl = route('3d.yapikrediResponse').'?brand_order_id='.$info['oder_id'];
                    $error = 'Bank Refused to connect';
                    if (isset($result['respText']) && !empty($result['respText'])){
                        $error = $result['respText'].'. '.$error;
                    }
                    $form .= '<input type="hidden" name="Xid" value="'.$Xid.'">';
                    $form .= '<input type="hidden" name="errorMessage" value="'.$error.'">';
                    $form .= '<input type="hidden" name="respCode" value="'.$error_response_code.'">';
                }




            }catch (\Exception $exception){
                $actionUrl = route('3d.yapikrediResponse').'?brand_order_id='.$info['oder_id'];
                $error = 'Invalid Response from bank. '.$exception->getMessage();

                $form .= '<input type="hidden" name="Xid" value="'.$Xid.'">';
                $form .= '<input type="hidden" name="errorMessage" value="'.$error.'">';
                $form .= '<input type="hidden" name="respCode" value="'.$error_response_code.'">';
            }

        }



        return array($form, $actionUrl, $logData);

    }

    private function redirectToKuveytTurk($info, $clientId, $storekey, $user_name, $password,$identityTaxNumber){

       if (strlen($info['month']) == 1){
          $info['month'] = '0'.$info['month'];
       }
       if (strlen($info['installment']) == 1){
          $info['installment'] = '0'.$info['installment'];
       }

       if (strlen($info['year']) == 4){
          $info['year'] = substr($info['year'],-2);
       }

       if (intval($info['installment']) < 2) {
          $info['installment'] = "00";
       }

       $bankObj = $info['bankObj'];
       $pay3dGateUrl = $bankObj->gate_3d_url;

       $merchantObj = $info['merchantObj'] ?? null;
       $posObj = $info["extras"]['posObj'];

       $CustomerId = $clientId;
       $MerchantId = $storekey;
       $UserName = $user_name;
       $MerchantOrderId = $info['oder_id'];

       $CardNumber = $info['card_no'];
       $CardHolderName = mb_substr( $info['extras']['card_holder_name'], 0,26 );
       $year = $info['year'];
       $month = $info['month'];
       $cvv2 = $info['cvv'];
       $installment = $info['installment'];


       $subMerchantId = 0;
       $currencyCode = '0'. $info['currency']; // 0949

        $subMerchantPFObj = $info['extras']['subMerchantPFObj']??null;

       $is_3d = true;
       $pfArr = $this->getKuveytPfRecords($merchantObj,$bankObj,$posObj,$CardNumber,$is_3d,$MerchantOrderId,$subMerchantPFObj);

       $description = !empty($pfArr) && isset($pfArr['Description']) ? $pfArr['Description'] : '';
       $identityTaxNumber = !empty($pfArr) && isset($pfArr['IdentityTaxNumber']) ? $pfArr['IdentityTaxNumber'] : '';


       $logdata['PF_Records'] = $pfArr;

//       $language = 'en';
//       if ($info['currency_code'] == Currency::TRY_CODE){
//          $language = 'tr';
//       }


      //  $carType = 'TROY', 'VISA';
       $cardType = strtoupper(PaymentProvider::getCreditCardType($info["card_no"]));

       $Amount = intval($info['amount']*100);

       $merchantReturnUrl = route('3d.kuveytSuccessFailResponse').'?brand_order_id='.$info['oder_id'];

       $HashedPassword = base64_encode(sha1($password, "ISO-8859-9"));
       $hashstr = $MerchantId . $MerchantOrderId . $Amount . $merchantReturnUrl . $merchantReturnUrl . $UserName . $HashedPassword;
       $HashData = base64_encode(sha1($hashstr, "ISO-8859-9"));

       $apiVersion = '1.0.0';
       $transactionType = "Sale";

       $transactionSecurity = 3;
       $batchId = 0;
       $DeferringCount = 3;



       $xml = '<KuveytTurkVPosMessage xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">'
         . '<APIVersion>' . $apiVersion . '</APIVersion>'
         . '<OkUrl>' . $merchantReturnUrl . '</OkUrl>'
         . '<FailUrl>' . $merchantReturnUrl . '</FailUrl>'
         . '<SubMerchantId>' . $subMerchantId . '</SubMerchantId>'
         . '<HashData>' . $HashData . '</HashData>'
         . '<MerchantId>' . $MerchantId . '</MerchantId>'
         . '<CustomerId>' . $CustomerId . '</CustomerId>'
         . '<UserName>' . $UserName . '</UserName>'
         . '<CardNumber>' . $CardNumber . '</CardNumber>'
         . '<CardExpireDateYear>' . $year . '</CardExpireDateYear>'
         . '<CardExpireDateMonth>' . $month . '</CardExpireDateMonth>'
         . '<CardCVV2>' . $cvv2 . '</CardCVV2>'
         . '<CardHolderName>' . $CardHolderName . '</CardHolderName>'
         . '<InstallmentCount>' . $installment . '</InstallmentCount>'
         //  . '<DeferringCount>' . $DeferringCount . '</DeferringCount>'
         . '<CardType>' . $cardType . '</CardType>'
         . '<BatchID>' . $batchId . '</BatchID>'
         . '<TransactionType>' . $transactionType . '</TransactionType>'
         . '<Description>' . $description . '</Description>'
         . '<IdentityTaxNumber>' . $identityTaxNumber . '</IdentityTaxNumber>'
         . '<Amount>' . $Amount . '</Amount>'
         . '<DisplayAmount>' . $Amount . '</DisplayAmount>'
         . '<CurrencyCode>' . $currencyCode . '</CurrencyCode>'
         . '<MerchantOrderId>' . $MerchantOrderId . '</MerchantOrderId>'
         . '<TransactionSecurity>' . $transactionSecurity . '</TransactionSecurity>'
         . '<TransactionSide>' . $transactionType . '</TransactionSide>'
         . '</KuveytTurkVPosMessage>';



       $ch = curl_init();
       curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
//       curl_setopt($ch, CURLOPT_SSLVERSION, 6);
//       curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_MAX_TLSv1_2);
       curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-type: application/xml', 'Content-length: ' . strlen($xml)));
       curl_setopt($ch, CURLOPT_POST, true);
       curl_setopt($ch, CURLOPT_HEADER, false);
       curl_setopt($ch, CURLOPT_URL, $pay3dGateUrl);
       curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
       curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
       $curlOutput = curl_exec($ch);
       $curl_error_msg = '';
       if (curl_errno($ch)) {
          $curl_error_msg = curl_error($ch);
       }
       curl_close($ch);


       $logData['kuveyt_first_xml_request'] = str_replace([
         $CardNumber,$year,$month,$cvv2],
         [CommonFunction::creditCardNoMasking($CardNumber), str_repeat('*', strlen($year)),str_repeat('*', strlen($month)),str_repeat('*', strlen($cvv2))],
         $xml);
       $logData['kuveyt_curl_card_verification_response'] = $curlOutput;

       if(!empty($curlOutput)) {
          // insert into new table

          $bankReferenceInfoObj = new BankReferenceInformation();
          $bankRefInfoData['order_id'] = $info['oder_id'];
          $bankRefInfoData['provider'] = $bankObj->payment_provider;
          $bank_reference_info = [
            "client_id" => $this->customEncryptionDecryption($clientId, config('app.brand_secret_key'),'encrypt'),
            "username" => $this->customEncryptionDecryption($UserName, config('app.brand_secret_key'),'encrypt'),
            "password" => $this->customEncryptionDecryption($password, config('app.brand_secret_key'),'encrypt'),
            "store_key" => $this->customEncryptionDecryption($storekey, config('app.brand_secret_key'),'encrypt')
          ];
          $bankRefInfoData['reference_info'] = $bank_reference_info;

          $insertRef = $bankReferenceInfoObj->add_information($bankRefInfoData);

          return [$curlOutput, '', $logData];

       }else{
          $form = '';
          $actionUrl = route('3d.kuveytSuccessFailResponse').'?brand_order_id='.$info['oder_id'];
          $error = 'Bank Refused to connect';
          if (!empty($curl_error_msg)){
             $error = $curl_error_msg.' -- '.$error;
          }
          $form = '<input type="hidden" name="ResponseCode" value="99">';
          $form .= '<input type="hidden" name="ResponseMessage" value="' . $error . '">';

          return [$form, $actionUrl, $logData];
       }


    }

   public function verifyKuveytTurkPayment($requestData, $sessionData)
   {

      $status = false;
      $message = '';
      $remote_order_id = '';
      $logData['action'] = 'KUVEYT_TURK_BANK_PAYMENT_VERIFICATION';
      $authCode = '';
      $bank_reference_info = [];

      if(isset($requestData["AuthenticationResponse"]) && !empty($requestData["AuthenticationResponse"])){

         $AuthenticationResponse = $requestData["AuthenticationResponse"];
         $RequestContent = urldecode($AuthenticationResponse);

         $result = new \SimpleXMLElement($RequestContent);
         //$xxml = simplexml_load_string($RequestContent);
         $json_string = json_encode($result);
         $request_data = json_decode($json_string, TRUE);

         if (!empty($request_data) && $request_data['ResponseCode'] == "00") {

            $posObj = $sessionData['posObj'];
            $bank = new Bank();
            $bankObj = $bank->findBankByID($posObj->bank_id);
            $customer_id = $this->customEncryptionDecryption($bankObj->client_id, \config('app.brand_secret_key'), 'decrypt');
            $merchant_id = $this->customEncryptionDecryption($bankObj->store_key, \config('app.brand_secret_key'), 'decrypt');
            $user_name = $this->customEncryptionDecryption($bankObj->user_name, \config('app.brand_secret_key'), 'decrypt');
            $Password = $this->customEncryptionDecryption($bankObj->password, \config('app.brand_secret_key'), 'decrypt');
            $identityTaxNumber = $this->customEncryptionDecryption($bankObj->api_password, \config('app.brand_secret_key'), 'decrypt');
            $confirmation_url = $bankObj->link_generate_url;


            $transaction_type = "Sale";
            $installment_count = $request_data['VPosMessage']['InstallmentCount'];
            $display_amount = $request_data['VPosMessage']['Amount'];
            $amount = $request_data['VPosMessage']['Amount'];
            $merchant_order_id = $request_data['MerchantOrderId'];
            $transaction_security = $request_data['VPosMessage']['TransactionSecurity'];
            $currency_code = $request_data['VPosMessage']['CurrencyCode'];
            $md = $request_data['MD'];
            $key = "MD";

            $salesPf = new SalesPFRecords();
            $salesPfObj = $salesPf->findByOrderId($merchant_order_id);

            $description = $identity_tax_number = '';
            if (!empty($salesPfObj) && isset($salesPfObj->sub_merchant_id)
              && isset($salesPfObj->pf_merchant_name)){

               $description = $salesPfObj->pf_merchant_name;
               $identity_tax_number = $salesPfObj->sub_merchant_id;

            }

            $HashedPassword = base64_encode(sha1($Password, "ISO-8859-9"));
            $hashstr = $merchant_id . $merchant_order_id . $amount . $user_name . $HashedPassword;
            $hash_data = base64_encode(sha1($hashstr, "ISO-8859-9"));


            $api_version = '1.0.0';
            $xml = '<KuveytTurkVPosMessage xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema">'
              . '<APIVersion>' . $api_version . '</APIVersion>'
              . '<HashData>' . $hash_data . '</HashData>'
              . '<MerchantId>' . $merchant_id . '</MerchantId>'
              . '<CustomerId>' . $customer_id . '</CustomerId>'
              . '<UserName>' . $user_name . '</UserName>'
              . '<TransactionType>' . $transaction_type . '</TransactionType>'
              . '<InstallmentCount>' . $installment_count . '</InstallmentCount>'
              . '<DisplayAmount>' . $display_amount . ' </DisplayAmount>'
              . '<Amount> ' . $amount . '</Amount>'
              . '<MerchantOrderId>' . $merchant_order_id . '</MerchantOrderId>'
              . '<Description>' . $description . '</Description>'
              . '<IdentityTaxNumber >' . $identity_tax_number . '</IdentityTaxNumber >'
              . '<TransactionSecurity>' . $transaction_security . '</TransactionSecurity>'
              . '<CurrencyCode>' . $currency_code . '</CurrencyCode>'
              . '<KuveytTurkVPosAdditionalData>'
              . '<AdditionalData>'
              . '<Key>' . $key . '</Key>'
              . '<Data>' . $md . '</Data>'
              . '</AdditionalData>'
              . '</KuveytTurkVPosAdditionalData>'
              . '</KuveytTurkVPosMessage>';


            $output = $this->kuveytCommonCurl($xml, $confirmation_url);
            $logData['verification_request_data'] = $request_data;
            $logData['kuveyt_second_xml_request'] = $xml;
            $logData['kuveyt_curl_confirmation_response'] = $output;

            if (isset($output['ResponseCode']) && !empty($output['ResponseCode']) && $output['ResponseCode'] == "00") {
               $status = true;
               $message = isset($output['ResponseMessage']) && !empty($output['ResponseMessage']) ? $output['ResponseMessage'] : '';
               $remote_order_id = '';
               if (isset($output['OrderId']) && !empty($output['OrderId'])) {
                  $remote_order_id = $output['OrderId'];
               }
               $bank_reference_info = [
                 "remote_order_id" => $remote_order_id,
                 "rrn_reference" => isset($output['RRN']) && !empty($output['RRN']) ? $output['RRN'] : '',
                 "stan_reference" => isset($output['Stan']) && !empty($output['Stan']) ? $output['Stan'] : '',
                 "provision_number" => isset($output['ProvisionNumber']) && !empty($output['ProvisionNumber']) ? $output['ProvisionNumber'] : '',
               ];

            } else {
               $status = false;
               $message = 'Unknown Error';
               if (isset($output['ResponseMessage']) && !empty($output['ResponseMessage'])) {
                  $message = $output['ResponseMessage'];
               }
            }

           // $authCode = $result['ResponseCode'] ?? '';

         }else{
            $message = $request_data['ResponseMessage'] ?? 'Unknown Error';
         }
      }else{
         $message = $requestData['ResponseMessage'] ?? 'Authentication failed';
      }
      //create log
      $this->createLog($this->_getCommonLogData($logData));

      return [$status, $message, $remote_order_id, $authCode, $bank_reference_info];


   }

   private function getKuveytPfRecords($merchantObj,$bankObj,$posObj,$CardNumber,$is_3d,$MerchantOrderId,$subMerchantPFObj=null){

      $globalFunction = new GlobalFunction();

      $pfArr = $globalFunction->managePFRecords($merchantObj,$bankObj,$posObj,$CardNumber, $is_3d, $MerchantOrderId,$subMerchantPFObj);
      if(isset($pfArr['IdentityTaxNumber']) && isset($pfArr['Description'])){
         $data = [
           'order_id' => $MerchantOrderId,
           'identity_nin' => $pfArr['IdentityTaxNumber'],
           'sub_merchant_id' => $pfArr['IdentityTaxNumber'],
           'pf_merchant_id' => $merchantObj->id,
           'pf_merchant_name' => $pfArr['Description']
         ];

         $inserted = (new SalesPFRecords)->insert_entry($data);

      }

      return $pfArr;
   }

   private function redirectToPaymix($info, $clientId, $user_name, $password){

      if (strlen($info['month']) == 1){
         $info['month'] = '0'.$info['month'];
      }
      if (strlen($info['installment']) == 1){
         $info['installment'] = '0'.$info['installment'];
      }

      if (strlen($info['year']) == 4){
         $info['year'] = substr($info['year'],-2);
      }

      if (intval($info['installment']) < 2) {
         $info['installment'] = 1;
      }

      $bankObj = $info['bankObj'];
      $actionUrl = $bankObj->gate_3d_url;
      $merchantObj = $info['merchantObj'] ?? null;
      $posObj = $info["extras"]['posObj'];

       $globalFunction = new GlobalFunction();
       list($pfArr, $merchant_id, $merchant_name) = $globalFunction->managePFRecords($merchantObj, $bankObj, $posObj, $info["card_no"], true, $info['oder_id']);

       if (isset($pfArr['password']) && !empty($pfArr['password']) && $pfArr['password'] != $password){
           $password = $pfArr['password'];

       }
       $data = [
           'order_id' => $info['oder_id'],
           'identity_nin' => '',
           'sub_merchant_id' => $password,
           'pf_merchant_id' => $merchant_id,
           'pf_merchant_name' => $merchant_name
       ];

       $inserted = (new SalesPFRecords)->insert_entry($data);


       $headerArray = [
        'api_key:'.$user_name,
        'secret_key:'.$password,
        'Content-Type: application/json',
      ];



      $language = 'TR';
      $currencyCode = $info['currency'];

      $inputDataArray = [
        'md' => $clientId,
        'installmentCount' => $info['installment'],
        'ecommerce' => true,
        'amount' => intval($info['amount']*100),
        'cvv2' => $info['cvv'],
        'cardNo' => $info['card_no'],
        'expiry' => intval($info['year'].$info['month']),
        'currency' => intval($currencyCode),
        'orderId' => $info['oder_id'],
        'lang' => $language,
        'returnUrlParams' => 'brand_order_id='.$info['oder_id']
      ];


      $jsonData = json_encode($inputDataArray);


      $ch = curl_init($actionUrl);
      if ($this->isNonSecureConnection()) {
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
      }
      curl_setopt($ch, CURLOPT_POST, 1);
      curl_setopt($ch, CURLOPT_HTTPHEADER, $headerArray);
      curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
      curl_setopt($ch, CURLOPT_TIMEOUT, 90);

      $curlOutput = curl_exec($ch);
      $curl_error_msg = '';
      if (curl_errno($ch)) {
         $curl_error_msg = curl_error($ch);
      }
         curl_close($ch);


      //dd($curl_error_msg);

      // dd($curlOutput);

      $logData['curl_response'] = $curlOutput;
      $logData['curl_request'] = $jsonData;
      $logData['curl_header'] = $headerArray;



      $form = '';

      $isCurlRequestSuccess = false;

      if (!empty($curlOutput)) {
         $output = json_decode($curlOutput, true);


         if (isset($output['state']) && $output['state'] == 1) {
            $actionUrl = isset($output['result']['htmlResponse']['acsUrl']) ? $output['result']['htmlResponse']['acsUrl'] : '';
            if(!empty($actionUrl)){
               $isCurlRequestSuccess = true;
               $inputFields = [
                 'PaReq' => $output['result']['htmlResponse']['paReq'],
                 'TermUrl' => $output['result']['htmlResponse']['termUrl'],
                 'MD' => $output['result']['htmlResponse']['md']
               ];
               foreach ($inputFields as $key => $value) {
                  $form .= '<input type="hidden" name="' . $key . '" value="' . $value . '">';
               }
            }
         }
      }

      if (!$isCurlRequestSuccess){



         $actionUrl = route('3d.paymixSuccessFailResponse').'?brand_order_id='.$info['oder_id'];
         $error = 'Bank Refused to connect';
         if (isset($output['errors']['$.expiry'][0]) && !empty($output['errors']['$.expiry'][0])){
            $error = $output['errors']['$.expiry'][0].'. '.$error;
         }
         if (!empty($curl_error_msg)){
            $error = $curl_error_msg.' -- '.$error;
         }
         $form = '<input type="hidden" name="resultMessage" value="' . $error . '">';
      }

      return array($form, $actionUrl, $logData);

   }

    public function verifyYapikrediPayment($requestData, $sessionData){

        $status = false;
        $message = '';
        $remote_order_id = '';
        $logData['action'] = 'YAPIKREDI_BANK_PAYMENT_VERIFICATION';
        $logData['order_id'] = $sessionData['ref'] ?? '';
        $authCode = '';

        if (isset($requestData['Xid']) && isset($requestData['BankPacket']) && isset($requestData['MerchantPacket'])){
            $merchantObj = null;
            $purchaseRequestObj = GlobalFunction::getBrandSession('PurchaseRequest', $sessionData['ref']);

            $amount = str_replace(',', '.',$requestData['Amount']);

            if (config('brand.name_code') == config('constants.BRAND_NAME_CODE_LIST.PB')){
                $amount = $sessionData['payable_amount'] * 100;
            }
            $posObj = $sessionData['posObj'];
            $bank = new Bank();
            $bankObj = $bank->findBankByID($posObj->bank_id);

            $clientId = $this->customEncryptionDecryption($bankObj->client_id, \config('app.brand_secret_key'), 'decrypt'); //"700667135511";
//            $user_name = $this->customEncryptionDecryption($bankObj->user_name, \config('app.brand_secret_key'), 'decrypt'); //"SiPay1!!!";
            $password = $this->customEncryptionDecryption($bankObj->password, \config('app.brand_secret_key'), 'decrypt'); //"SiPay1!!!";
            $storekey = $this->customEncryptionDecryption($bankObj->store_key, \config('app.brand_secret_key'), 'decrypt'); //"SiPay1!!!";

            $encKey = $storekey;
            $TID = $password;
            $MID = $clientId;
            $xid = $requestData['Xid'];
//            $amount =  $amount * 100;

            $currencyClass = new Currency();
            $currency = $currencyClass->getSmallCodeByCode($purchaseRequestObj->currency_code);


            $firstHash = $this->generateYapikrediHash($encKey . ";" . $TID);

//            $logData['first_hash'] = $firstHash;
//            $logData['first_hash_param'] = $encKey . ";" . $TID;

            $MAC = $this->generateYapikrediHash($xid . ";" . $amount . ";" . $currency . ";" . $MID . ";" . $firstHash);

            $encryptUrl = $bankObj->api_url;

           $logData['first_mac_param'] = $xid . ";" . $amount . ";" . $currency . ";" . $MID . ";" . $firstHash;
           $logData['first_generated_mac'] = $MAC;

            $xmlArray = [
                'mid' => $MID,
                'tid' => $TID,
                'oosResolveMerchantData' => [
                    'bankData' => $requestData['BankPacket'],
                    'merchantData' => $requestData['MerchantPacket'],
                    'sign' => $requestData['Sign'],
                    'mac' => $MAC,

                ]
            ];

//            $logData['first_request'] = $xmlArray;
            $result = $this->yapikrediCommonCurl($xmlArray, $encryptUrl);
            $logData['first_response'] = $result;


            if (isset($result['oosResolveMerchantDataResponse']['mdStatus'])
                && !empty($result['oosResolveMerchantDataResponse']['mdStatus'])
                && $result['oosResolveMerchantDataResponse']['mdStatus'] == 1) {

                $firstHash = $this->generateYapikrediHash($encKey . ";" . $TID);

//                $logData['second_hash'] = $firstHash;
//                $logData['second_hash_param'] = $encKey . ";" . $TID;

                $xid = '';
                if (isset($result['oosResolveMerchantDataResponse']['xid'])
                    && !empty($result['oosResolveMerchantDataResponse']['xid'])){
                    $xid = $result['oosResolveMerchantDataResponse']['xid'];
                }

                $MAC = $this->generateYapikrediHash($xid . ";" . $amount . ";" . $currency . ";" . $MID . ";" . $firstHash);

               $logData['second_mac_param'] = $xid . ";" . $amount . ";" . $currency . ";" . $MID . ";" . $firstHash;
               $logData['second_generated_mac'] = $MAC;
//---------------------------------------------------------------------------------------------------
                $globalFunction = new GlobalFunction();
                if (empty($merchantObj)){
                    $merchant_id = $sessionData['merchant_id'];
                    $merchant = new Merchant();
                    $merchantObj = $merchant->getActiveMerchantById($merchant_id);
                }
                $first_req = [
                    'bankData' => $requestData['BankPacket'],
                    'wpAmount' => $amount,
                    'mac' => $MAC
                ];

                $subMerchantPFObj = $sessionData['subMerchantPFObj']??null;
                if(is_array($subMerchantPFObj) && !empty($subMerchantPFObj)){
                    $pf_id = $subMerchantPFObj['pf_id'];
                    $merchant_id = $subMerchantPFObj['merchant_id'];
                    $subMerchantPF = new SubMerchantPF();
                    $subMerchantPFObj = $subMerchantPF->findActivePFRecordByMerchantIdAndPFId($merchant_id, $pf_id);
                }

                $pf_params = $globalFunction->managePFRecords($merchantObj, $bankObj,$sessionData['posObj'],
                    '', true, $sessionData['order_id'],$subMerchantPFObj);
                $final_req = array_merge($first_req, $pf_params);

                $xmlArray = [
                    'mid' => $MID,
                    'tid' => $TID,
                    'oosTranData' => $final_req
                ];
//----------------------------------------------------------------------------------------------------

              $logData['final_request'] = $xmlArray;
                $result = $this->yapikrediCommonCurl($xmlArray, $encryptUrl);
                $logData['final_response'] = $result;


                if (isset($result['approved']) && !empty($result['approved']) && $result['approved'] == 1){
                    $status = true;
                    $remote_order_id = '';
                    if (isset($result['hostlogkey']) && !empty($result['hostlogkey'])){
                        $remote_order_id = $result['hostlogkey'];
                    }
                }else{
                    $message = 'Unknown Error';
                    if (isset($result['respText']) && !empty($result['respText'])){
                        $message = $result['respText'];
                    }
                }

                $authCode = $result['authCode'] ?? '';

            }else{
                $message = 'Unknown Error';
                if (isset($result['oosResolveMerchantDataResponse']['mdErrorMessage'])
                    && !empty($result['oosResolveMerchantDataResponse']['mdErrorMessage'])){
                    $message = $result['oosResolveMerchantDataResponse']['mdErrorMessage'];
                }elseif (isset($result['respText']) && !empty($result['respText'])){
                    $message = $result['respText'];
                }

            }
        }else{
            $message = $requestData['errorMessage'] ?? 'Rejected from first Request';
        }
        //create log
        $this->createLog($this->_getCommonLogData($logData));

        return [$status, $message, $remote_order_id, $authCode];


    }

    public function vakifResponseValidation($request, $sessionData)
    {

        $status = false;
        $message = '';
        $remote_order_id = '';
        $logData = [];
        $auth_code = '';

        $identity = '';
        $bank_landing_status = 0;

        try {
            DB::beginTransaction();

        $salePfRecord = new SalesPFRecords();
        $salePfRecordObj  = $salePfRecord->findByOrderId($sessionData['ref'], true);
        if (!empty($salePfRecordObj)){
            if (!empty($salePfRecordObj->identity_nin)){
                $identity = $salePfRecordObj->identity_nin;
            }
            $bank_landing_status = $salePfRecordObj->bank_landing_status;
            if (!empty($salePfRecordObj)){
                $salePfRecord->updateLandingStatus($salePfRecordObj->id);
            }


        }
        if (empty($bank_landing_status)){





        $amount = $sessionData['payable_amount'];
        if (GlobalFunction::isTestTransaction()) {
            $amount = '0.10'; //"9.95";
        }
        $amount = number_format($amount, 2, '.', '');

        $posObj = $sessionData['posObj'];
        $bank = new Bank();
        $bankObj = $bank->findBankByID($posObj->bank_id);

        $clientId = $this->customEncryptionDecryption($bankObj->client_id, \config('app.brand_secret_key'), 'decrypt');
        $storekey = $this->customEncryptionDecryption($bankObj->store_key, \config('app.brand_secret_key'), 'decrypt');
        $api_password = $this->customEncryptionDecryption($bankObj->api_password, \config('app.brand_secret_key'), 'decrypt'); //"SiPay1!!!";

       $transactionType = 'Sale';

       if ($this->isPreAuthTransaction($sessionData)){
          $transactionType = 'Auth';
       }


        $expiry = $request->Expiry;
        if (strlen($expiry) == 4) {
            $now = Carbon::now();
            $first2digit = substr($now->year, 0, 2);
            $expiry = $first2digit . $expiry;
        }

        $installment = $sessionData['installment'];
//
//        if ($installment < 2) {
//            $installment = '';
//        }
//        $paymentProvider = new PaymentProvider();


        if (empty($identity)){
            $merchantObj = null;
            $purchaseRequestObj = GlobalFunction::getBrandSession('PurchaseRequest', $sessionData['ref']);
            if (isset($purchaseRequestObj->merchant_key)){
                $merchant = new Merchant();
                $merchantObj = $merchant->getActiveMerchantByKey($purchaseRequestObj->merchant_key);
            }
            $globalFunction = new GlobalFunction();
            $pfArr = $globalFunction->managePFRecords($merchantObj, $bankObj, $posObj,'', true, $sessionData['ref']);
            $identity = $pfArr['Identity'];
        }


        $requestArr = [
            'MerchantId' => $clientId,
            'Password' => $api_password,
            'MerchantType' => 2,
            'Identity' => $identity,
            'TransactionType' => $transactionType,
            'MpiTransactionId' => $sessionData['ref'],
            'CurrencyAmount' => $amount,
            'CurrencyCode' => $sessionData['currency_iso_code'],
            'Pan' => $this->customEncryptionDecryption($sessionData['credit_card_no'], config('app.brand_secret_key'), 'decrypt'),
            'CAVV' => $request->Cavv,
            'Expiry' => $expiry,
            'TransactionDeviceSource' => 0,
            'ClientIp' => $this->getPurchaseRequestIp($sessionData['ref']),
            'ECI' => $request->Eci,
            'TransactionId' => $request->SiparID,
            'OrderId' => $sessionData['ref']
        ];


        // Tarim Transaction parameter
        if ($this->isTarimTransaction($sessionData, $posObj, $sessionData['card_info'] ?? [])){
            $requestArr['TransactionType'] = 'TKSale';
            $requestArr['CustomInstallments'] = [
                'MaturityPeriod' => $sessionData['maturity_period'],
                'Frequency' => $sessionData['payment_frequency'],
            ];
        }else{
           if ($installment > 1){
              $requestArr['NumberOfInstallments'] = $installment;
           }
        }


        $xmlResult = $this->arrayToXML($requestArr, '<VposRequest/>', false);


//        $xml = new \xmlWriter();
//        $xml->openMemory();
//        $xml->startElement('VposRequest');
//
//        foreach ($requestArr as $key => $value) {
//            $xml->startElement($key);
//            $xml->text($value);
//            $xml->endElement();
//        }
//
//        $xml->endElement();

        $requestData = 'prmstr=' . $xmlResult;

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $bankObj->gate_2d_url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $requestData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 59);
        if ($this->isNonSecureConnection()) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        }

        $output = curl_exec($ch);

        curl_close($ch);

        if (!empty($output)) {

            try {
                libxml_use_internal_errors(TRUE);
                //            $result = simplexml_load_string($output);
                $result = new \SimpleXMLElement($output);
//            $result = json_encode($result);
                if (isset($result->ResultCode) && $result->ResultCode == '0000') {
                    $status = true;
                    $remote_order_id = $result->TransactionId;

                } else {
                    $message = $result->ResultDetail ?? 'Bank could not verify the payment';
                    $remote_order_id = $result->TransactionId ?? '';
                }
                $auth_code = $result->AuthCode ?? '';

            }catch (\Exception $exception){
                $message = $exception->getMessage();
            }



        }

        $logData['action'] = "Vakif payment verification";
        $logData['verification_request_data'] = $this->unsetKeysVakifBank($requestData);
        $logData['verification_response'] = $output;


        $this->createLog($this->_getCommonLogData($logData));
        }
        DB::commit();
        }catch (\PDOException $exception){
        DB::rollBack();
            $logData['action'] = "Vakif payment verification (Exception)";
            $logData['order_id'] = $sessionData['ref'];
            $logData['response'] = $exception->getMessage();
            $this->createLog($this->_getCommonLogData($logData));
        }

        return [$status, $message, $remote_order_id, $logData, $auth_code];

    }

    public function isTarimTransaction($dataArray, $posObj, $cardInfo){
       $is_tarim_payment = false;

        if (!empty($cardInfo) && isset($dataArray['is_tarim_payment']) && $dataArray['is_tarim_payment'] ){

           if(in_array($posObj->program, CardProgram::ALL_TARIM_CARD_PROGRAMS)
             && in_array($cardInfo['card_program'], CardProgram::ALL_TARIM_CARD_PROGRAMS)){
              $is_tarim_payment = true;
           }

        }

        return $is_tarim_payment;

    }

    public function validateTarimTransaction($data,$posObj,$cardInfo){
       $errorCode = '';
       $errorMessage = '';

       if ($this->isTarimTransaction($data,$posObj,$cardInfo)){
          $min = config('constants.MATURITY_PERIOD.MIN');
          $max = config('constants.MATURITY_PERIOD.MAX');
          if (filter_var($data['maturity_period'], FILTER_VALIDATE_INT,
              array("options" => array("min_range"=>$min, "max_range"=>$max))) === false) {
             $errorCode = 1;
             $errorMessage = "Maturity period value should be an integer and between $min to $max";
          }
       }

       return [$errorCode, $errorMessage];
    }



    private function generateYapikrediHash($originalString){
        return base64_encode(hash('sha256',$originalString,true));
    }

   private function kuveytCommonCurl($xml, $url){

      $ch = curl_init();
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
//      curl_setopt($ch, CURLOPT_SSLVERSION, 6);
//      curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_MAX_TLSv1_2);
      curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-type: application/xml', 'Content-length: ' . strlen($xml)));
      curl_setopt($ch, CURLOPT_POST, true);
      curl_setopt($ch, CURLOPT_HEADER, false);
      curl_setopt($ch, CURLOPT_URL, $url);
      curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      $response = curl_exec($ch);
      curl_close($ch);

      try {
         libxml_use_internal_errors(TRUE);
//            $result = simplexml_load_string($response);
         $result = new \SimpleXMLElement($response);
         $result = json_encode($result);
         $result = json_decode($result,true);
      }catch (\Exception $exception){

      }


      return $result;
   }

    private function yapikrediCommonCurl($xmlArray, $url){
        $xmlRequest = $this->arrayToXML($xmlArray, '<posnetRequest/>');

        $ch = curl_init();
        $xmlRequest = urlencode($xmlRequest);
        $getUrl = $url . "?xmldata=" . $xmlRequest;
        $result = null;

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded; charset=utf-8'));
        curl_setopt($ch, CURLOPT_URL, $getUrl);
        curl_setopt($ch, CURLOPT_TIMEOUT, 80);

        $response = curl_exec($ch);

        curl_close($ch);

        try {
            libxml_use_internal_errors(TRUE);
//            $result = simplexml_load_string($response);
            $result = new \SimpleXMLElement($response);
            $result = json_encode($result);
            $result = json_decode($result,true);
        }catch (\Exception $exception){

        }


        return $result;
    }

    private function redirectToVakifBank($info, $clientId, $api_password, $storekey)
    {


        $logdata = [];
        $bankObj = $info['bankObj'];
        $merchantObj = $info['merchantObj'] ?? null;
        $posObj = $info["extras"]['posObj'];
        if (strlen($info["month"]) == 1) {
            $info["month"] = '0' . $info["month"];
        }
        $brandNameValue = 100;
        if (PaymentProvider::getCreditCardType($info["card_no"]) == 'mastercard') {
            $brandNameValue = 200;
        }

        $cardNo = $info["card_no"];
        $merchantType = 2;


        if (strlen($info["month"]) == 1) {
            $info["month"] = '0' . $info["month"];
        }
        $expireDate = substr($info["year"], -2) . $info["month"];

        $amount = $info['amount'];
        $currencyIsoCode = $info["currency"];
        $verifyEnrollmentRequestId = $info['oder_id'];
        $sessionInfo = '';
        $successUrl = route('3d.successPaymentVakifBank');
        $failUrl = route('3d.failPaymentVakifBank').'?brand_order_id='.$info['oder_id'];
        $installment = intval($info["installment"]);
//        if (intval($installment) < 2) {
//            $installment = '';
//        }


        $gate_3d_url = $bankObj->gate_3d_url;
//        $identity = $storekey;
        $subMerchantPFObj = $info['extras']['subMerchantPFObj']??null;
        $globalFunction = new GlobalFunction();
        $pfArr = $globalFunction->managePFRecords($merchantObj,$bankObj,$posObj,$cardNo, true, $info['oder_id'],$subMerchantPFObj);
        if(isset($pfArr['Identity']) && isset($pfArr['SUBMERCHANTID'])){
             $data = [
                 'order_id' => $info['oder_id'],
                 'identity_nin' => $pfArr['Identity'],
                 'sub_merchant_id' => $pfArr['SUBMERCHANTID'],
                 'pf_merchant_id' => $merchantObj->id,
                 'pf_merchant_name' => $merchantObj->name
             ];

           if(!empty($subMerchantPFObj)){
               $data['pf_merchant_name'] = $subMerchantPFObj->name;
           }

           $inserted = (new SalesPFRecords)->insert_entry($data);
        }
        $logdata['PF_Records'] = $pfArr;

        $requestData = [
            'Pan' => $cardNo,
            'MerchantType' => $merchantType,
//            'SubMerchantId' => $subMerchantId,
            'ExpiryDate' => $expireDate,
            'PurchaseAmount' => $amount,
            'Currency' => $currencyIsoCode,
            'BrandName' => $brandNameValue,
            'VerifyEnrollmentRequestId' => $verifyEnrollmentRequestId,
            'SessionInfo' => $sessionInfo,
            'MerchantId' => $clientId,
            'MerchantPassword' => $api_password,
            'SuccessUrl' => $successUrl,
            'FailureUrl' => $failUrl,
            'Identity' => $pfArr['Identity']
        ];

        if($installment > 1)  {

            $requestData['NumberOfInstallments'] =  $installment;
        }




        $xFormRequest = http_build_query($requestData);


        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $gate_3d_url);
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type" => "application/x-www-form-urlencoded"));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

        curl_setopt($ch, CURLOPT_POSTFIELDS, $xFormRequest);

        $resultXml = curl_exec($ch);

        curl_close($ch);



        $resultArray = $this->vakifBankAuthResultManipulate($resultXml);
        $bank_error_code = $resultArray['MessageErrorCode'] ?? '';

        $xFormRequest = str_replace($cardNo,CommonFunction::creditCardNoMasking($cardNo) ,$xFormRequest);
        $xFormRequest = str_replace($expireDate, '****' ,$xFormRequest);
        $xFormRequest = str_replace($api_password, '****' ,$xFormRequest);
        $logdata['bank_response'] = $resultXml;
        $logdata['bank_request'] = $xFormRequest;

        $actionUrl = '';
        if ($resultArray['Status'] == 'Y') {
            $PaReq = $resultArray['PaReq'];
            $termUrl = $resultArray['TermUrl'];
            $md = $resultArray['MerchantData'];
            $actionUrl = $resultArray['ACSUrl'];
            $form = '
        <input type="hidden" name="PaReq" value="' . $PaReq . '">
		<input type="hidden" name="TermUrl" value="' . $termUrl . '">
		<input type="hidden" name="MD" value="' . $md . '">
        ';

        } else {


            $form = '<input type="hidden" name="ErrorMessage" value="Bank refused to connect. '.$resultArray["ErrorMessage"].'">
                    <input type="hidden" name="VerifyEnrollmentRequestId" value="'.$verifyEnrollmentRequestId.'">
                    <input type="hidden" name="ErrorCode" value="'.$bank_error_code.'">
                    ';

            $actionUrl = $failUrl;

        }


        return array($form, $actionUrl, $logdata);
    }

    private function redirectToNestPay3d($info, $clientId, $storekey)
    {

       $actionUrl = $info['url'];

        $extras = $info['extras'];

       $ip_address = '';
       if (isset($extras["request_ip"])) {
          $ip_address = $extras["request_ip"];
       }

        $amount = $info['amount'];

        $currency_iso_code = $extras["currency_iso_code"];
        $oid = $extras["order_id"];
        $okUrl = route('3d.success');//"https://www.teststore.com/success.php";
        $failUrl = route('3d.fail').'?brand_order_id='.$oid;// "https://www.teststore.com/fail.php";
        $rnd = microtime();
        //        $storetype = "3d_pay_hosting";
        $storetype = $info["store_type"];

        $instalment = $extras["installment"];                //Instalment count, if there's no instalment should left blank
        if (intval($instalment) < 2) {
            $instalment = "";
        }

        $transactionType =  $extras['transaction_type'];



        $bolum = $info["bolum"];


        $pan = $extras["credit_card_no"]; //"5406681402122002";
        $cv2 = $extras["cvv"]; // "362";
        $Ecom_Payment_Card_ExpDate_Year = $extras["expiry_year"]; // "2023";
        $Ecom_Payment_Card_ExpDate_Month = $extras["expiry_month"]; // "07";
        if (strlen($Ecom_Payment_Card_ExpDate_Month) == 1) {
            $Ecom_Payment_Card_ExpDate_Month = "0" . $Ecom_Payment_Card_ExpDate_Month;
        }


        $bankObj = $info['bankObj'];
        list($actionUrl, $clientId,$user_name, $password, $storekey) = GlobalFunction::getBankTestCredentials($actionUrl, $clientId, '', '', $storekey,
        '','',$bankObj->code, '3d');


        //  $cardType = $info["card_type"]; //"1";

        $hashstr = $clientId . $oid . $amount . $okUrl . $failUrl . $transactionType . $instalment . $rnd . $storekey;
        $hash = base64_encode(pack('H*', sha1($hashstr)));
        $localization_code = app()->getLocale();


        $submerchant = $this->getPFRecords($info);

        $universal = '
            <input type="hidden" name="token" value="' . csrf_token() . '">
             <input type="hidden" name="bank_url" value="' . $actionUrl . '">
             <input type="hidden" name="pan" value="' . $pan . '">
             <input type="hidden" name="cv2" value="' . $cv2 . '">
             <input type="hidden" name="Ecom_Payment_Card_ExpDate_Year" value="' . $Ecom_Payment_Card_ExpDate_Year . '">
             <input type="hidden" name="Ecom_Payment_Card_ExpDate_Month" value="' . $Ecom_Payment_Card_ExpDate_Month . '">
            <input type="hidden" name="clientid" value="' . $clientId . '">
            <input type="hidden" name="amount" value="' . $amount . '">
            <input type="hidden" name="oid" value="' . $oid . '">
            <input type="hidden" name="okUrl" value="' . $okUrl . '">
            <input type="hidden" name="failUrl" value="' . $failUrl . '">
            <input type="hidden" name="rnd" value="' . $rnd . '" >
            <input type="hidden" name="hash" value="' . $hash . '" >
            <input type="hidden" name="encoding" value="UTF-8">
            <input type="hidden" name="storetype" value="' . $storetype . '" >
            <input type="hidden" name="lang" value="' . $localization_code . '">
            <input type="hidden" name="currency" value="' . $currency_iso_code . '">   
            <input type="hidden" name="islemtipi" value="' . $transactionType . '" />
            <input type="hidden" name="taksit" value="' . $instalment . '" />
            <input type="hidden" name="bolum" value="' . $bolum . '">
            <input type="hidden" name="refreshtime" value="1" />';

        if (!empty($ip_address)){
           $universal .= '<input type="hidden" name="ClientIp" value="' . $ip_address . '">';
        }

        $logData['rmd'] = $rnd;
        $logData['transactionType'] = $transactionType;
        $logData['hash'] = $hash;
        $logData['pf_records'] = $submerchant;

       // mask credit card info from form params log
       $findData = [$pan,  $cv2, $Ecom_Payment_Card_ExpDate_Year, $Ecom_Payment_Card_ExpDate_Month ];
       $logData['tmp_request'] = GlobalFunction::maskCreditCardFormParams($findData, $universal);

        return array($submerchant . $universal, $actionUrl, $logData);

    }


    private function redirectToDenizAndPtt($info, $clientId, $user_name, $password, $api_user_name, $api_password){
//echo "geldi";
//die();


        if (strlen($info["month"]) == 1){
            $info["month"] = '0'.$info["month"];
        }

        if (strlen($info["year"]) == 4){
            $info["year"] = substr($info["year"],-2);
        }

       $extras = $info['extras'];

       $ip_address = '';
       if (isset($info['extras']["request_ip"])) {
          $ip_address = $info['extras']["request_ip"];
       }

       $transactionType = 'Auth';
       if(isset($info['extras']['transaction_type'])){
          $transactionType = $info['extras']['transaction_type'];
       }

        $rnd = microtime();

        if (intval($info["installment"]) < 2) {
            $info["installment"] = "";
        }

        $oid = $info["oder_id"];
        $successUrl = route('3d.denizPttSuccessResponse');
        $failUrl = route('3d.denizPttFailResponse').'?brand_order_id='.$oid;



        $bankObj = $info['bankObj'];

        $language = 'en';
        if ($info['currency_code'] == Currency::TRY_CODE){
            $language = 'tr';
        }


        $cardTypeValue = 1;
        $carType = PaymentProvider::getCreditCardType($info["card_no"]);
        if ($carType == 'visa'){
            $cardTypeValue = 0;
        }

        $globalFunction = new GlobalFunction();
        $tarimArr = [];




        if($globalFunction->isSameBank($bankObj->code, config('constants.BANK_CODE.ODEA_BANK'), 9)
            || $globalFunction->isSameBank($bankObj->code, config('constants.BANK_CODE.FIBABANKA'), 9)

        ) {

           if($globalFunction->isSameBank($bankObj->code, config('constants.BANK_CODE.FIBABANKA'), 9)){
               $MbrId = '11';
               if (GlobalFunction::isTestTransaction()){
                  $info['amount'] = 1.00;
               }
            }else{
               $MbrId = '0';
            }
            if (intval($info["installment"]) < 2) {
                $info["installment"] = 0;
            }

            $credentialDataArray = [
                "MbrId" => $MbrId,
                "MerchantID" => $clientId,
                "UserCode" => $api_user_name,
                "UserPass" => $api_password,
            ];

           $cvv = $info["cvv"];

        }else{

            if (intval($info["installment"]) < 2) {
                $info["installment"] = "";
            }

            $MbrId = $clientId;
            $credentialDataArray = [
                'ShopCode' => $clientId,
                'CardType' => $cardTypeValue,
            ];

           $cvv = $info["cvv"];


           // Tarim Transaction parameter
           if ($this->isTarimTransaction($extras, $extras['posObj'], $extras['card_info'] ?? [])){
              $tarimArr['AgricultureTxnFlag'] = 'T';
              $tarimArr['BonusAmount'] = '';
              $tarimArr['MaturityPeriod'] = $extras['maturity_period'];
              $tarimArr['PaymentFrequency'] = $extras['payment_frequency'];
             // $cvv = '';
           }
        }

        $hashstr = $MbrId . $oid . $info['amount'] . $successUrl . $failUrl . $transactionType . $info["installment"] . $rnd . $password;
        $hash = base64_encode(pack('H*', sha1($hashstr)));

       $expMonthYear = $info["month"].$info["year"];

        $inputDataArray = [
            'Pan' => $info["card_no"],
            'Cvv2' => $cvv,
            'Expiry' => $expMonthYear,
            'PurchAmount' => $info['amount'],
            'Currency' => $info["currency"],
            'OrderId' => $oid,
            'OkUrl' => $successUrl,
            'FailUrl' => $failUrl,
            'Rnd' => $rnd,
            'Hash' => $hash,
            'TxnType' => $transactionType,
            'InstallmentCount' => 1,
            'SecureType' => '3DPay',
            'Lang' => $language,
        ];


       if (!empty($ip_address) && $globalFunction->isSameBank( $bankObj->code , config('constants.BANK_CODE.ODEA_BANK') , 9 )){
          $inputDataArray ['IPAdr']= $ip_address;
       }else if(!empty($ip_address) && $globalFunction->isSameBank( $bankObj->code , config('constants.BANK_CODE.DENIZ_BANK') , 9 )){
         // $inputDataArray ['ClientIp']= $ip_address;
       }

        $inputDataArray = $inputDataArray + $credentialDataArray + $tarimArr;


        $actionUrl = $info['url'];
        $form = '';
        foreach ($inputDataArray as $key => $value){
            $form .= '<input type="hidden" name="'.$key.'" value="'.$value.'">';
        }

echo $form;
die();

        $pfFrom = $this->getPFRecords($info);
       $logData['pf_record'] = $pfFrom;
    //    $form = $form.$pfFrom;

       // mask credit card info from form params log
       $findData = [ $info["card_no"], $cvv, $expMonthYear];
       $logData['deniz_request'] = GlobalFunction::maskCreditCardFormParams($findData, $form);

        return array($form, $actionUrl, $logData);

    }



    private function getPFRecords($info)
    {

        $submerchant = '';

        $merchantObj = $info['merchantObj'] ?? null;
        $bankObj = $info['bankObj'] ?? null;
        $posObj = $info["extras"]['posObj'] ?? null;
        $currency_iso_code = $info['currency'] ?? null;
        $card_no = $info["card_no"] ?? null;
        $subMerchantPFObj = $info['extras']['subMerchantPFObj']??null;

        if (!empty($bankObj) && !empty($posObj) && !empty($currency_iso_code) && !empty($card_no)) {
//            $paymentProvider = new PaymentProvider();
            $globalFunction = new GlobalFunction();
            $pfArr = $globalFunction->managePFRecords($merchantObj, $bankObj, $posObj,  $card_no, true, $info['oder_id'],$subMerchantPFObj);

            if (!empty($pfArr)) {
                foreach ($pfArr as $key => $value) {
                    $submerchant .= '<input type="hidden" name="' . $key . '" value="' . $value . '">' . "\r\n";
                }
            }
        }


        return $submerchant;

    }

    private function redirectToAlBarakaBank($info, $client_id, $user_name, $password)
    {

        $bankObj = $info['bankObj'];
        $amount = $info['amount'];
//        $amount = 100.00;
        $str_arr = explode('.', $amount);
        $solidAmount = $str_arr[0];
        $pointAmount = $str_arr[1] ?? '00';
        $multipliedAmount = intval($amount * 100);


        $month = $info["month"];
        if (strlen($month) == 1) {
            $month = '0' . $month;
        }

        $year = $info["year"];
        if (strlen($year) == 4) {
            $year = substr($year, -2);
        }

        $installment = $info['installment'];


        if (intval($installment) < 2) {
            $installment = 0;
        }
        $merchantNo = $client_id;
        $posNetId = $user_name;
        $terminalNo = $password;


        //order number
        $orderId = $info["oder_id"];
        $orderId = $this->getAlbarakaOrderid($orderId);

        $transactionType = 'Sale';
        $cardNo = $info["card_no"];
        $expireDate = $year . $month;
        $cvv = $info["cvv"];
        $cardHolerName = $info["extras"]['card_holder_name'];
        $merchantReturnUrl = route('3d.albarakaSuccessFailResponse').'?brand_order_id='.$info['oder_id'];

        //production url
        $pay3dGateUrl = $bankObj->gate_3d_url;

//        test url
//        $pay3dGateUrl = 'https://epostest.albarakaturk.com.tr/ALBSecurePaymentUI/SecureProcess/SecureVerification.aspx';

        $language = strtoupper(app()->getLocale());
        $currencyCode = $info['currency'];
        $encryptionKey = config('constants.ALBARAKA_ENCRYPTION_KEY');
        $macParams = $merchantNo . $terminalNo . $cardNo . $cvv . $expireDate . $multipliedAmount . $encryptionKey;

        $mac = $this->getAlbarakaMac($macParams);
        $koiCode = '';
        $useJokerVadaa = 0;
        $openNewWindow = 0;
//        $currencyCode = 'TL';
        $macParams = 'MerchantNo:TerminalNo:CardNo:Cvc2:ExpireDate:Amount';

        $form = '
    <input type="hidden" name="PosnetID" value="' . $posNetId . '">
    <input type="hidden" name="MerchantNo" value="' . $merchantNo . '">
    <input type="hidden" name="TerminalNo" value="' . $terminalNo . '">
    <input type="hidden" name="OrderId" value="' . $orderId . '">
    <input type="hidden" name="TransactionType" value="' . $transactionType . '">
    <input type="hidden" name="CardNo" value="' . $cardNo . '">
    <input type="hidden" name="ExpiredDate" value="' . $expireDate . '">
    <input type="hidden" name="Cvv" value="' . $cvv . '">
    <input type="hidden" name="CardHolderName" value="' . $cardHolerName . '">
    <input type="hidden" name="Amount" value="' . $multipliedAmount . '">
    <input type="hidden" name="PointAmount" value="' . $pointAmount . '">
    <input type="hidden" name="InstallmentCount" value="' . $installment . '">
    <input type="hidden" name="MerchantReturnURL" value="' . $merchantReturnUrl . '">
    <input type="hidden" name="Language" value="' . $language . '">
    <input type="hidden" name="CurrencyCode" value="' . $currencyCode . '">
    <input type="hidden" name="Mac" value="' . $mac . '">
    <input type="hidden" name="MacParams" value="' . $macParams . '">
    <input type="hidden" name="UseJokerVadaa" value="' . $useJokerVadaa . '">
    <input type="hidden" name="OpenNewWindow" value="' . $openNewWindow . '">
    <input type="hidden" name="UseOOS" value="0">
    <input type="hidden" name="TxnState" value="INITIAL">
    ';


        return array($form, $pay3dGateUrl, []);

    }


    private function redirectToTurkpos($info, $client_id, $user_name, $password,$guid)
    {
        $bankObj = $info['bankObj'];
        $form = '';

        $action_url = '';
        $bank_response_code = '';

        $month = $info["month"];
        if (strlen($month) == 1) {
            $month = '0' . $month;
        }
        $year = $this->formatExpiryYear($info["year"]);
        $installment = $info['installment'];
        if (intval($installment) < 2) {
            $installment = 1;
        }
        $posNetId = $bankObj->store_type;

        $posNetArrayByCUrrency = [
            Currency::TRY_ISO_CODE => '1018',
            Currency::USD_ISO_CODE => '1001',
            Currency::EUR_ISO_CODE => '1002'
        ];
        $posNetId = $posNetArrayByCUrrency[$info['currency']];
        $totalAmount = number_format($info['product_price'], 2, ",", "");
        $generalAmount = number_format($info['amount'], 2, ",", "");

        //order number
        $orderId = $info["oder_id"];
        $Islem_ID = $this->GetTurkposOrderId($orderId);

        $cardNo = $info["card_no"];
        $cvv = $info["cvv"];
        $cardHolerName = $info["extras"]['card_holder_name'];
        $merchantReturnUrl = route('3d.turkPosSuccessResponse');
        $failed_url = route('3d.turkPosFailResponse').'?brand_order_id='.$info['oder_id'];
        //

        $pay3dGateUrl = $bankObj->gate_3d_url;

        if ($info['currency_code'] == Currency::TRY_CODE){
            $securityString = $client_id . $guid . $posNetId . $installment . $totalAmount . $generalAmount . $orderId . $failed_url . $merchantReturnUrl;

        }else{
            $securityString = $client_id . $guid . $totalAmount . $generalAmount . $orderId . $failed_url . $merchantReturnUrl;

        }


        $hash_key = $this->GenetateTurkposHash($pay3dGateUrl, $bankObj->token_url, $securityString);
        if (!empty($hash_key)) {
            $request3dInfo = [
                'client_code' => $client_id,
                'client_username' => $user_name,
                'password' => $password,
                'posNetId' => $posNetId,
                'GUID' => $guid,
                'cardHolerName' => $cardHolerName,
                'cardNo' => $cardNo,
                'month' => $month,
                'year' => $year,
                'cvv' => $cvv,
                'failed_url' => $failed_url,
                'successUrl' => $merchantReturnUrl,
                'orderId' => $orderId,
                'order_description' => null,
                'installment' => $installment,
                'totalAmount' => $totalAmount,
                'generalAmount' => $generalAmount,
                'hash_value' => $hash_key,
                'Islem_ID' => $Islem_ID,
                'pay3dGateUrl' => $pay3dGateUrl,
                'soaphost' => 'dmzws.param.com.tr',
                'currency_code' => $info['currency_code']

            ];

            list($action_url ,$message, $bank_response_code)= $this->requestForTurkpos3d($request3dInfo);

        }

        if (empty($action_url)) {

            $message = $message ?? 'Bank refused to connect';
            $form = '
<input type="hidden" name="TURKPOS_RETVAL_Siparis_ID" value="' . $orderId . '">
<input type="hidden" name="TURKPOS_RETVAL_Sonuc_Str" value="'.$message.'">
<input type="hidden" name="Odeme_Sonuc" value="'.$bank_response_code.'">

';
            $action_url = $failed_url;
        }

        return array($form, $action_url, []);

    }

    public function formatExpiryYear($expiry_year){
        if (strlen($expiry_year) == 2) {
            $current_Year = Carbon::now()->format('Y');
            $yearFist2 = substr($current_Year,0,2);
            $yearLast2 = substr($current_Year,-2);
            if ($yearLast2 > $expiry_year){
                $yearFist2 = intval($yearFist2) + 1;
            }
            $expiry_year = $yearFist2.$expiry_year;
        }
        return $expiry_year;
    }

    private function redirectToPayAll($info, $clientId, $password){
        $isFail = false;
        $bankObj = $info['bankObj'];
        $form = '';
        $logData = [];
        $actionUrl = $bankObj->gate_3d_url;

        $token = $this->getPayallToken($bankObj->token_url, $clientId, $password);
        if (!empty($token)){
            list($transactionLinkId, $logData) = $this->createPaymentLink($bankObj->link_generate_url, $token, $info);
            if (!empty($transactionLinkId)){
                TemporaryPaymentRecord::where('order_id', $info['oder_id'])->update(array('remote_order_id'=>$transactionLinkId));
                $form = '';
                if (strlen($info['month']) == 1){
                    $info['month'] = '0'.$info['month'];
                }
                if (strlen($info['year']) > 2){
                    $info['year'] = substr($info['year'],2,2);
                }

                $payWithCard = [
                    "TransactionId"=> $transactionLinkId,
                    "CardNumber"=> $info['card_no'],
                    "cardHolderName"=> $info['extras']['card_holder_name'],
                    "ExpireMonth"=> $info['month'],
                    "ExpireYear"=> $info['year'],
                    "Cvv"=> $info['cvv'],
                    "InstallmentCount"=>$info['installment'],
                    "CustomerComment"=> "",
                    "EditedAmount"=> 0,
                ];
                $logData['payWithCard'] = $payWithCard;
                if (empty($info['installment']) || $info['installment'] < 2){
                    $payWithCard['InstallmentCount'] = 0;
                }

                foreach ($payWithCard as $key => $value){
                    $form .= '<input type="hidden" name="'.$key.'" value="'.$value.'">';
                }
            }else{
                $isFail = true;
            }
        }else{
            $isFail = true;
        }
        if ($isFail){
            $actionUrl = route('3d.payallFailResponse').'?brand_order_id='.$info['oder_id'];
            $form .= '<input type="hidden" name="ReturnStatus" value="20">';
            $form .= '<input type="hidden" name="FriendlyResponse" value="Bank refused to connect">';
        }
        return array($form, $actionUrl, $logData);
    }

    private function createPaymentLink($paymentUrl,$token, $info){
        $logData = [];
        $paymentLinkRequestData = [
            "amount"=> $info['amount'],
            "expireDate"=> Carbon::now()->addHours(10)->format("Y-m-d H:m"),
            "successUrl"=> route('3d.payallSuccessResponse'),
            "errorUrl"=> route('3d.payallFailResponse').'?brand_order_id='.$info['oder_id'],
            "displayIframe"=> false,
            "extraparams"=> null,
            "isPayableAmountEditable"=> false,
            "InstallmentCount" => $info['installment'],
            "sendEmail"=> false,
            "sendSms"=> false,
            "creatorComment"=> "string",
            "customerComment"=> "string",
            "orderId"=> $info['oder_id'],
            "customer"=> null
        ];

        if (empty($info['installment']) || $info['installment'] < 2){
            $paymentLinkRequestData['InstallmentCount'] = 0;
        }

        $acessToken = 'Bearer '.$token;
        $hearder = array(
            "Authorization: ".$acessToken,
            "Content-Type: application/json",
        );
        $logData = $paymentLinkRequestData;
        $paymentLinkRequestData = json_encode($paymentLinkRequestData);

        $curl = curl_init();
        $request_array = array(
            CURLOPT_URL => $paymentUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => $paymentLinkRequestData,
            CURLOPT_HTTPHEADER => $hearder,
        );

        if ($this->isNonSecureConnection()) {

            $request_array["CURLOPT_SSL_VERIFYHOST"] = 2;
            $request_array["CURLOPT_SSL_VERIFYPEER"] = 0;

        }

        curl_setopt_array($curl,$request_array);
        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);
        $response = json_decode($response, true);
        $paymentList = $response['PaymentLink'] ?? '';
        $transactionLinkId = $response['TransactionLinkId'] ?? '';
        return [$transactionLinkId, $logData];
    }


    private function GenetateTurkposHash($url,$soaphost,$security_sting){


        //$client_id.$GUID.$posNetId.$installment.$totalAmount.$generalAmount.$orderId.$failed_url.$successUrl;

        $security_xml = '<?xml version="1.0" encoding="utf-8"?><soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/"><soap:Body><SHA2B64 xmlns="https://turkpos.com.tr/"><Data>' . $security_sting . '</Data></SHA2B64></soap:Body></soap:Envelope>';

        $sha2B64Header = array(
            "Host: dmzws.param.com.tr",
            "Content-type: text/xml;charset=\"utf-8\"",
            "Accept:text/xml",
            "SOAPAction:https://turkpos.com.tr/SHA2B64",
            "Content-length: " . strlen($security_xml),
        );


        // PHP cURL  for https connection with auth
        $ch_header = curl_init();
        curl_setopt($ch_header, CURLOPT_SSL_VERIFYPEER, 1);
        curl_setopt($ch_header, CURLOPT_URL, $url);
        curl_setopt($ch_header, CURLOPT_RETURNTRANSFER, true);
        //curl_setopt($ch_header, CURLOPT_USERPWD, $soapUser.":".$soapPassword);
        curl_setopt($ch_header, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
        curl_setopt($ch_header, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch_header, CURLOPT_POST, true);
        curl_setopt($ch_header, CURLOPT_POSTFIELDS, $security_xml); // the SOAP request
        curl_setopt($ch_header, CURLOPT_HTTPHEADER, $sha2B64Header);

        // converting
        $response_security = curl_exec($ch_header);
        curl_close($ch_header);
        $hash_value = '';
        if (!empty($response_security)) {
            try {
                libxml_use_internal_errors(TRUE);
//                $result_sha = simplexml_load_string(str_replace('soap:', '', $response_security));
                $result_sha = new \SimpleXMLElement(str_replace('soap:', '', $response_security));
                $result_sha = json_encode($result_sha);
                $result_sha = json_decode($result_sha);
                $hash_value = $result_sha->Body->SHA2B64Response->SHA2B64Result ?? '';

            }catch (\Exception $exception){

            }

        }else{
            $logData['action'] = 'TURKPOS_HASH_CREATION_FAIL_LOG';
            $logData['security_sting'] = $security_sting;
            $logData['response'] = $response_security;
            $this->createLog($this->_getCommonLogData($logData));
        }
        return $hash_value;

    }

    private function getPurchaseRequestIp($order_id){
        $purchaseRequestObj = GlobalFunction::getBrandSession('PurchaseRequest',$order_id);
        if (!empty($purchaseRequestObj)){
            $ip = $purchaseRequestObj->ip;
        }else{
            $ip = $this->getClientIp();
        }
        return $ip;
    }


    private function requestForTurkpos3d($info){

        if ($info['currency_code'] == Currency::TRY_CODE){
            $SOAPAction = "https://turkpos.com.tr/TP_Islem_Odeme";
            $xml_post_string = '<?xml version="1.0" encoding="utf-8"?><soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/"><soap:Body><TP_Islem_Odeme xmlns="https://turkpos.com.tr/"><G><CLIENT_CODE>' . $info['client_code'] . '</CLIENT_CODE><CLIENT_USERNAME>' . $info['client_username'] . '</CLIENT_USERNAME><CLIENT_PASSWORD>' . $info['password'] . '</CLIENT_PASSWORD></G><SanalPOS_ID>' . $info['posNetId'] . '</SanalPOS_ID><GUID>' . $info['GUID'] . '</GUID><KK_Sahibi>' . $info['cardHolerName'] . '</KK_Sahibi><KK_No>' . $info['cardNo'] . '</KK_No><KK_SK_Ay>' . $info['month'] . '</KK_SK_Ay><KK_SK_Yil>' . $info['year'] . '</KK_SK_Yil><KK_CVC>' . $info['cvv'] . '</KK_CVC><KK_Sahibi_GSM></KK_Sahibi_GSM><Hata_URL>' . $info['failed_url'] . '</Hata_URL><Basarili_URL>' . $info['successUrl'] . '</Basarili_URL><Siparis_ID>' . $info['orderId'] . '</Siparis_ID><Siparis_Aciklama>' . $info['order_description'] . '</Siparis_Aciklama><Taksit>' . $info['installment'] . '</Taksit><Islem_Tutar>' . $info['totalAmount'] . '</Islem_Tutar><Toplam_Tutar>' . $info['generalAmount'] . '</Toplam_Tutar><Islem_Hash>' . $info['hash_value'] . '</Islem_Hash><Islem_ID>' . $info['Islem_ID'] . '</Islem_ID><IPAdr>'.$this->getPurchaseRequestIp($info['orderId']).'</IPAdr><Ref_URL>' . $info['pay3dGateUrl'] . '</Ref_URL><Data1>data 1</Data1><Data2>data 2</Data2><Data3>data 3</Data3><Data4>data 4</Data4><Data5>data 5</Data5></TP_Islem_Odeme></soap:Body></soap:Envelope>';

        }else{
            $SOAPAction = "https://turkpos.com.tr/TP_Islem_Odeme_WD";
            $xml_post_string ='<?xml version="1.0" encoding="utf-8"?><soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/"><soap:Body><TP_Islem_Odeme_WD xmlns="https://turkpos.com.tr/"><G><CLIENT_CODE>'.$info['client_code'].'</CLIENT_CODE><CLIENT_USERNAME>'.$info['client_username'].'</CLIENT_USERNAME><CLIENT_PASSWORD>'.$info['password'].'</CLIENT_PASSWORD></G><Doviz_Kodu>'.$info['posNetId'].'</Doviz_Kodu><GUID>'.$info['GUID'].'</GUID><KK_Sahibi>'.$info['cardHolerName'].'</KK_Sahibi><KK_No>'.$info['cardNo'].'</KK_No><KK_SK_Ay>'.$info['month'].'</KK_SK_Ay><KK_SK_Yil>'.$info['year'].'</KK_SK_Yil><KK_CVC>'.$info['cvv'].'</KK_CVC><KK_Sahibi_GSM></KK_Sahibi_GSM><Hata_URL>'.$info['failed_url'].'</Hata_URL><Basarili_URL>'.$info['successUrl'].'</Basarili_URL><Siparis_ID>'.$info['orderId'].'</Siparis_ID><Siparis_Aciklama>'.$info['order_description'].'</Siparis_Aciklama><Islem_Tutar>'.$info['totalAmount'].'</Islem_Tutar><Toplam_Tutar>'.$info['generalAmount'].'</Toplam_Tutar><Islem_Hash>'.$info['hash_value'].'</Islem_Hash><Islem_Guvenlik_Tip>3D</Islem_Guvenlik_Tip><Islem_ID>'.$info['Islem_ID'].'</Islem_ID><IPAdr>'.$this->getPurchaseRequestIp($info['orderId']).'</IPAdr><Ref_URL>'.$info['pay3dGateUrl'].'</Ref_URL><Data1>data 1</Data1><Data2>data 2</Data2><Data3>data 3</Data3><Data4>data 4</Data4><Data5>data 5</Data5></TP_Islem_Odeme_WD></soap:Body></soap:Envelope>';

        }

        $headers = array(
            "Host: dmzws.param.com.tr",
            "Content-type: text/xml;charset=\"utf-8\"",
            "Accept: text/xml",
            "SOAPAction:".$SOAPAction,
            "Content-length: " . strlen($xml_post_string),
        );

        // PHP cURL  for https connection with auth
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
        curl_setopt($ch, CURLOPT_URL, $info['pay3dGateUrl']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        //curl_setopt($ch, CURLOPT_USERPWD, $soapUser.":".$soapPassword);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
        curl_setopt($ch, CURLOPT_TIMEOUT, 90);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml_post_string); // the SOAP request
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        // converting
        $response = curl_exec($ch);
        /*
        $fp = fopen("log2.txt","w+");
        $strFileOut = "URL:".  $info['pay3dGateUrl'] . "\n";
         $strFileOut .= "body:". $xml_post_string . "\n";
         $strFileOut .= "Response:". $response . "\n";
         fwrite($fp, $strFileOut);
        fclose($fp);
        */

        curl_close($ch);
        $payment_action = '';
        $message = '';
        $bank_response_code = '';

        if (!empty($response)) {

            try {
                libxml_use_internal_errors(TRUE);
//                $result_payment = simplexml_load_string(str_replace('soap:', '', $response));
                $result_payment = new \SimpleXMLElement(str_replace('soap:', '', $response));
                $result_payment = json_encode($result_payment);
                $result_payment = json_decode($result_payment, true);
                if ($info['currency_code'] == Currency::TRY_CODE){
                    if (isset($result_payment['Body']['TP_Islem_OdemeResponse']['TP_Islem_OdemeResult']['Sonuc'])
                        && $result_payment['Body']['TP_Islem_OdemeResponse']['TP_Islem_OdemeResult']['Sonuc'] == '1') {
                        $payment_action = $result_payment['Body']['TP_Islem_OdemeResponse']['TP_Islem_OdemeResult']['UCD_URL'] ?? null;
                    }else{
                        $message = $result_payment['Body']['TP_Islem_OdemeResponse']['TP_Islem_OdemeResult']['Sonuc_Str'] ?? null;

                        $logData['action'] = 'TURKPOS_PAYMENT_LINK_CREATION_FAIL_LOG';
                        $logData['order_id'] = $info['orderId'];
                        $logData['request_xml'] = $xml_post_string;
                        $logData['response'] = $response;
                        $this->createLog($this->_getCommonLogData($logData));
                    }
                }else{
                    if (isset($result_payment['Body']['TP_Islem_Odeme_WDResponse']['TP_Islem_Odeme_WDResult']['Sonuc'])
                        && $result_payment['Body']['TP_Islem_Odeme_WDResponse']['TP_Islem_Odeme_WDResult']['Sonuc'] == '1') {
                        $payment_action = $result_payment['Body']['TP_Islem_Odeme_WDResponse']['TP_Islem_Odeme_WDResult']['UCD_URL'] ?? null;
                    }else{
                        $message = $result_payment['Body']['TP_Islem_Odeme_WDResponse']['TP_Islem_Odeme_WDResult']['Sonuc_Str'] ?? null;

                        $logData['action'] = 'TURKPOS_PAYMENT_LINK_CREATION_FAIL_LOG';
                        $logData['order_id'] = $info['orderId'];
                        $logData['request_xml'] = $xml_post_string;
                        $logData['response'] = $response;
                        $this->createLog($this->_getCommonLogData($logData));
                    }
                }
                if (isset($result_payment['Body']['TP_Islem_OdemeResponse']['TP_Islem_OdemeResult']['Banka_Sonuc_Kod'] )){
                    $bank_response_code = $result_payment['Body']['TP_Islem_OdemeResponse']['TP_Islem_OdemeResult']['Banka_Sonuc_Kod'] ;
                }elseif (isset($result_payment['Body']['TP_Islem_Odeme_WDResponse']['TP_Islem_Odeme_WDResult']['Banka_Sonuc_Kod'])){
                    $bank_response_code = $result_payment['Body']['TP_Islem_Odeme_WDResponse']['TP_Islem_Odeme_WDResult']['Banka_Sonuc_Kod'];
                }

            }catch (\Exception $exception){
                $message = $exception->getMessage();
            }


            return [$payment_action,$message, $bank_response_code];

        }

        return [$payment_action,$message, $bank_response_code];
    }




    private function redirectToEsnekPos($info, $user_name, $password)
    {
        $form = '';

        $bankObj = $info['bankObj'];

        $merchantObj = $info['merchantObj'] ?? null;

        $esnekpos_url = $bankObj->gate_3d_url;

        if (strlen($info["month"]) == 1) {
            $info["month"] = '0' . $info["month"];
        }

        $username = $user_name;
        $orderid = $info['oder_id'];
        $SubMerchantName = "";


        $name = '';
        $surname = '';
        $mail = '';
        $phone = '';
        $city = '';
        $state = '';
        $address = '';

        $purchaseRequestObj = GlobalFunction::getBrandSession('PurchaseRequest', $info["oder_id"]);

        if (!empty($purchaseRequestObj)) {
            $billAddress1 = $purchaseRequestObj->data->bill_address1 ?? '';
            $billAddress2 = $purchaseRequestObj->data->bill_address2 ?? '';
            $name = $purchaseRequestObj->name;
            $surname = $purchaseRequestObj->surname;
            $mail = $purchaseRequestObj->data->bill_email ?? '';

            $phone = $purchaseRequestObj->data->bill_phone ?? '';
            $city = $purchaseRequestObj->data->bill_city ?? '';
            $state = $purchaseRequestObj->data->bill_state ?? '';
            $address = $billAddress1 . ' ' . $billAddress2;

        }

        if (empty($phone) && !empty($merchantObj)) {

            $phone = $merchantObj->authorized_person_phone_number;

        }

        /*
                $currencyAssoc = [
                    '949' => 'TRY',
                    '840' => 'USD',
                    '978' => 'EUR',
                ];
                */

        $veri = array(
            'Config' => array(
                'MERCHANT' => $username,
                'MERCHANT_KEY' => $password,
                'BACK_URL' => route('3d.esnekSuccessFailResponse').'?brand_order_id='.$info['oder_id'],
                'PRICES_CURRENCY' => $info["currency_code"],  // $currencyAssoc[$info['currency']]
                'ORDER_REF_NUMBER' => (string)$orderid,
                'ORDER_AMOUNT' => $info['amount']
            ),
            'CreditCard' => array(
                'CC_NUMBER' => $info["card_no"],
                'EXP_MONTH' => $info["month"],
                'EXP_YEAR' => $info["year"],
                'CC_CVV' => $info["cvv"],
                'CC_OWNER' => $info["extras"]['card_holder_name'],
                'INSTALLMENT_NUMBER' => $info['installment']
            ),
            'Customer' => array(
                'FIRST_NAME' => $name,
                'LAST_NAME' => $surname,
                'MAIL' => $mail,
                'PHONE' => $phone,
                'CITY' => $city,
                'STATE' => $state,
                'ADDRESS' => $address,
                'CLIENT_IP' => $this->getPurchaseRequestIp($orderid)
            ));

        $veri = json_encode($veri);
        $ch = curl_init($esnekpos_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $veri);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        $result = curl_exec($ch);
        curl_close($ch);
        $result = json_decode($result);
        $actionUrl = '';




        $logdata['bank_request'] = $veri;
        $logdata['bank_response'] = $result;
        if ($result->STATUS == 'SUCCESS') {
            $actionUrl = $result->URL_3DS;
//            header("Location:" . $result->URL_3DS);
        } else {
//            if (isset($info['merchantObj']) && !empty($info['merchantObj'])) {
//                $merchantObj = $info['merchantObj'];
//
//                $cancel_url = $merchantObj->fail_link;
//                $cancel_url .= (parse_url($cancel_url, PHP_URL_QUERY) ? '&' : '?') . "error_code=" . $result->RETURN_CODE . "&invoice_id=" . $info["invoice_id"] . "&error=" . $result->RETURN_MESSAGE;
//
////            return Redirect::away($cancel_url);
//                header("Location:" . $cancel_url);
//                exit();
//            } else {
//                abort(404, $result->RETURN_MESSAGE);
//            }

            $form = '<input type="hidden" name="responseMsg" value="'.$result->RETURN_MESSAGE.'">';

            $actionUrl = route('3d.esnekSuccessFailResponse').'?brand_order_id='.$info['oder_id'];

        }

        return array($form, $actionUrl, $logdata);

    }

    private function redirectToMsu($info, $client_id, $user_name, $password,$payByCardToken = false)
    {
        if ($payByCardToken && (isset($info['extras']['credit_card_no']) && !empty($info['extras']['credit_card_no']))){
            $payByCardToken = false;
        }
        if(!$payByCardToken) {
            if (strlen($info["month"]) == 1) {
                $info["month"] = '0' . $info["month"];
            }
        }



        $purchaseRequestObj = GlobalFunction::getBrandSession('PurchaseRequest', $info["oder_id"]);
        $purchaseRequestData = $purchaseRequestObj->data;
        $logdata = [];
        if(!$payByCardToken) {
            $info['card_holder_name'] = $info['extras']['card_holder_name'] ?? '';
        }
//        dd($info);
        list($token, $responseMsg, $xFormRequest, $sessionOutput) = $this->generateMsuSessionToken($info,
            $client_id, $user_name, $password, $purchaseRequestObj, $payByCardToken);
        if (intval($info["installment"]) < 2) {
            $info["installment"] = 1;
        }

            if (!empty($token)) {
                if($payByCardToken){
                    $form = '
                    <input type="hidden" name="installmentCount" value="' . $info["installment"] . '">
                    <input type="hidden" name="cardToken" value="'.$info['input']['card_token'].'"  >
                   ';
                }else {
                    $form = '
                    <input type="hidden" name="cardOwner" value="' . $info["extras"]['card_holder_name'] . '">
                    <input type="hidden" name="pan" value="' . $info["card_no"] . '"  >
                    <input type="hidden" name="expiryMonth" value="' . $info["month"] . '"  >
                    <input type="hidden" name="expiryYear" value="' . $info["year"] . '"  >
                    <input type="hidden" name="cvv" value="' . $info["cvv"] . '"/>
                    <input type="hidden" name="saveCard" value=""  >
                    <input type="hidden" name="cardName"  value="" >
                    <input type="hidden" name="cardCutoffDay"  value="" >
                    <input type="hidden" name="installmentCount"  value="' . $info["installment"] . '">
                    <input type="hidden" name="points"  value="">
                    <input type="hidden" name="callbackUrl" value="' . route('3d.msuSuccessFailResponse') . '?brand_order_id=' . $info['oder_id'] . '" >
        ';
                }

                $actionUrl = $info['bankObj']->gate_3d_url . '/' . $token;

                if (GlobalFunction::isTestTransaction() && GlobalFunction::isTestMerchantKey($purchaseRequestObj->merchant_key)) {
                    $actionUrl = 'https://entegrasyon.asseco-see.com.tr/msu/api/v2/post/sale3d/' . $token;
                }


            } else {

                $form = '
                <input type="hidden" name="RETURN_MESSAGE" value="Bank refused to connect. ' . $responseMsg . '">
                 <input type="hidden" name="merchantPaymentId" value="' . $info["oder_id"] . '">
              
                ';
                $actionUrl = route('3d.msuSuccessFailResponse') . '?brand_order_id=' . $info['oder_id'];
            }

        return array($form, $actionUrl, $logdata);

    }

    public function generateMsuSessionToken($info, $client_id, $user_name, $password, $purchaseRequestObj, $payByCardToken = false)
    {
        $purchaseRequestData = $purchaseRequestObj->data;
        $bankObj = $info['bankObj'];
        $merchantObj = $info['merchantObj'] ?? null;
        $items = [];

        $extras = $info['extras'];

        if ($payByCardToken){
            $customerName = $info['input']['customer_name'] ?? '';
        }else{
            $customerName = $extras['card_holder_name'] ?? '';

            if (empty($customerName) && (!empty($purchaseRequestObj->name) || !empty($purchaseRequestObj->surname))){
                $customerName = $purchaseRequestObj->name. ' '.$purchaseRequestObj->surname;
            }
            if (empty($customerName)){
                $customerName = config('brand.name').' '.str_random(5);
            }
        }






//        foreach ($purchaseRequestData->items as $item) {
//            $quantity = 1;
//
//            if (isset($item->qty)) {
//                $quantity = $item->qty;
//            } elseif (isset($item->qnantity)) {
//                $quantity = $item->qnantity;
//            } elseif (isset($item->quantity)) {
//                $quantity = $item->quantity;
//            }
//            $items[] = [
//                "code" => $item->name ?? '' . '_' . $quantity . '_' . $item->price ?? 0,
//                "name" => $item->name ?? '',
//                "description" => $item->description ?? '',
//                "quantity" => $quantity,
//                "amount" => $item->price
//            ];
//        }

        $URL = $bankObj->token_url;
//        if (GlobalFunction::isTestTransaction() && isset($merchantObj->merchant_key)
//            && GlobalFunction::isTestMerchantKey($merchantObj->merchant_key)) {
//            $URL = 'https://entegrasyon.asseco-see.com.tr/msu/api/v2';
//            $client_id = 'sipay';//client id
//            $user_name = 'it@sipay.com.tr';//user_name
//            $password = 'Nop@ss1234';//password
//        }
        $items[] = [
            "code" => 'Sipay-product_1_' . $info['amount'],
            "name" => 'Sipay-product',
            "description" => '',
            "quantity" => 1,
            "amount" => $info['amount']
        ];
        $itemJson = json_encode($items);
        if($payByCardToken){
            $request = [
                'ACTION' => 'SESSIONTOKEN',
                'MERCHANTUSER' => $user_name,
                'MERCHANTPASSWORD' => $password,
                'MERCHANT' => $client_id,
                'CUSTOMER' => $info["input"]["customer_number"],
                'SESSIONTYPE' => 'PAYMENTSESSION',
                'MERCHANTPAYMENTID' => $info["oder_id"],
                'AMOUNT' => $info['amount'],
                'CURRENCY' => $info['currency_code'],
                'CUSTOMEREMAIL' => $purchaseRequestData->bill_email ?? '',
                'CUSTOMERNAME' => $customerName,
                'CUSTOMERPHONE' => $purchaseRequestData->bill_phone ?? '',
                'RETURNURL' => route('3d.msuSuccessFailResponse').'?brand_order_id='.$info['oder_id'],
                'EXTRA' => [
                    'IsbankBolumKodu' => $info["bolum"] ?? 1
                ],
                'ORDERITEMS' => [$itemJson]

            ];

        }else {
            $request = [
                'ACTION' => 'SESSIONTOKEN',
                'MERCHANTUSER' => $user_name,
                'MERCHANTPASSWORD' => $password,
                'MERCHANT' => $client_id,
                'CUSTOMER' => 'Customer-UCUoumJV',
                'SESSIONTYPE' => 'PAYMENTSESSION',
                'MERCHANTPAYMENTID' => $info["oder_id"],
                'AMOUNT' => $info['amount'],
                'CURRENCY' => $info['currency_code'],
                'CUSTOMEREMAIL' => $purchaseRequestData->bill_email ?? '',
                'CUSTOMERNAME' => $customerName,
                'CUSTOMERPHONE' => $purchaseRequestData->bill_phone ?? '',
                'RETURNURL' => route('3d.msuSuccessFailResponse') . '?brand_order_id=' . $info['oder_id'],
                'EXTRA' => [
                    'IsbankBolumKodu' => $info["bolum"] ?? 1
                ],
                'ORDERITEMS' => [$itemJson]

            ];
        }

//        $paymentProvider = new PaymentProvider();
        if(!$payByCardToken) {
            $globalFunction = new GlobalFunction();
            $pfArr = $globalFunction->managePFRecords($merchantObj, $bankObj,
                $info["extras"]['posObj'],
                $info["card_no"],true, $info['oder_id']);
        }

        if (!empty($pfArr)) {
            $request['EXTRA'] = $request['EXTRA'] + $pfArr;
        }
        $xFormRequest = http_build_query($request);




        $ch = curl_init($URL);
        if ($this->isNonSecureConnection()) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        }
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xFormRequest);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 90);
        $output = curl_exec($ch);
        curl_close($ch);


        $sessionOutput = json_decode($output, true);

        $token = '';
        $responseMsg = '';
        if (isset($sessionOutput['responseCode']) && $sessionOutput['responseCode'] == "00") {
            $token = $sessionOutput['sessionToken'];
        } else {
            $responseMsg = 'Response is Empty';
            if (isset($sessionOutput['responseCode'])) {
                $responseMsg = $sessionOutput['responseMsg'];
            }
        }

        return [$token, $responseMsg, $request, $sessionOutput];

    }


    public function payBy3DCreditCard($cardHolderName, $cardNo, $cardMonth, $cardYear,
                                      $cardCvv, $amount,$product_price,$currencyId, $order_id,
                                      $invoice_id, $installment = 0, $deposit_method_id = 4,
                                      $deposit_requester_ip = '', $authUserObj, $isDeposit=false)
    {

        list($minPosId, $cardInfo, $card_type_value) = $this->defineMinimumPos($cardNo, $currencyId, $amount, null, $isDeposit);

        $pos = new Pos();

        // get pos by pos_id
        $posObj = $pos->getPosByPosId($minPosId);

        $info["extras"] = [
            'amount' => $amount,
            'currencyid' => $currencyId,
            'card_holder_name' => $cardHolderName,
            'issuer_name' => $cardInfo['issuer_name'],
            'actual_issuer_name' => $cardInfo['actual_issuer_name'] ?? '',
            'card_type' => $card_type_value,
            'methodID' => $deposit_method_id,
            'pos_id' => $minPosId,
            'posObj' => $posObj,
            'deposit_fee' => session()->get('deposit_fee'),
            'deposit_requester_ip' => $deposit_requester_ip,
            'ref' => $order_id
        ];

        $purchaseObj = $this->makePuchaseRequestObjectManually($order_id, $invoice_id,
            $amount, $product_price, $authUserObj, $currencyId);

        GlobalFunction::setBrandSession('PurchaseRequest', $purchaseObj, $purchaseObj->ref);


        //get bank object by  pos
        $bank = new Bank();
        $bankObj = $bank->findBankByID($posObj->bank_id);

        $info["url"] = $bankObj->gate_3d_url;
        //bank  credentials
        $info["client_id"] = $bankObj->client_id;
        $info["store_key"] = $bankObj->store_key;

        $info["store_type"] = $bankObj->store_type;

        //car  info
        $info["card_name"] = $cardHolderName;
        $info["card_no"] = $cardNo;
        $info["month"] = $cardMonth;
        $info["year"] = $cardYear;
        $info["cvv"] = $cardCvv;

        $info["success_url_3d"] = route('3d.success');
        $info["failed_url_3d"] = route('3d.fail').'?brand_order_id='.$order_id;

        $info["card_type"] = $card_type_value;
        $info["oder_id"] = $order_id;

        $info["bolum"] = $posObj->bolum;

        //car  info
        $info["amount"] = $amount;

        $currency = new  Currency();
        $currencyObj = $currency->getCurrencyById($currencyId);
        $info["currency"] = $currencyObj->iso_code;
        $info["currency_code"] = $currencyObj->code;


        $info["installment"] = $installment;
        $info["invoice_id"] = $invoice_id;

        $info['bankObj'] = $bankObj;
        $info['payment_type'] = 'PAY_3D';

//        1=Sale, 2=Deposit
        $info['type'] = Deposit::TMP_PAYMENT_TRANS_TYPE_DEPOSIT;

        $this->redirectToBank($info,false);

    }

    public function makePuchaseRequestObjectManually($order_id, $invoice_id, $amount, $product_price, $authUserObj, $currency_id )
    {
        $currency = new Currency();
        $currencyObj = $currency->getCurrencyById($currency_id);

        $newPurchaseObject = [
            'ref' => $order_id,
            'data' => (object)[
                'items' => (object)[
                    'item' => (object)[
                        "code" => $order_id,
                        "name" => 'Sipay Deposit',
                        "description" => $invoice_id,
                        "quantity" => 1,
                        "price" => $amount
                    ]
                ],
                'total' => $product_price,
                'bill_address1' => $authUserObj->address,
                'bill_address2' => '',
                'bill_city' => $authUserObj->city,
                'bill_postcode' => '1111',
                'bill_state' => 'Istanbul',
                'bill_country' => 'TURKEY',
                'bill_phone' => $authUserObj->phone,
                'bill_email' => $authUserObj->email,
            ],
            'name' => $authUserObj->first_name,
            'surname' => $authUserObj->last_name,
            'ip' => $this->getClientIp(),
            'currency_id' => $currency_id,
            'currency_code' => $currencyObj->code,
            'merchant_key' => '',
            'invoice_id' => $invoice_id
        ];

        return (object)$newPurchaseObject;
    }

    public function defineMinimumPos($cardNo, $currencyId, $amount, $cardInfo = null, $isDeposit = false, $is2dPayment = false, $posIdList = [])
    {
        $posList = array();
        //get  Card info  by  card number

        if (empty($cardInfo)) {
            $paymentProvider = new PaymentProvider();
            $cardInfo = $paymentProvider->getCardInfoByCardNo($cardNo);
        }


        $issuerName = $cardInfo['issuer_name'];
        $cardType = $cardInfo['card_type'];
        $card_type_value = 2;


        if ($cardType == "CREDIT CARD") {
            $card_type_value = 1;
        }

        $pos = new Pos();

        if (!empty($issuerName)) {
            $posList = $pos->getPosListByIssuer($cardInfo['issuer_name'], $currencyId, $isDeposit, $is2dPayment);

            if (count($posIdList) > 0){
                $posList = $posList->whereIn('id', $posIdList);
            }
        }


        if (empty($issuerName) || count($posList) == 0) {

            $issuerName = "";

            $posList = $pos->getAll($currencyId, $isDeposit, $is2dPayment);

            if (count($posIdList) > 0){
                $posList = $posList->whereIn('id', $posIdList);
            }

            $minPosId = $this->minimumPoscalculation($posList, $amount, $card_type_value, 'not_on_us', $cardInfo);

        } else {

            $minPosId = $this->minimumPoscalculation($posList, $amount, $card_type_value, 'on_us', $cardInfo);

        }

        $posObj = $posList->where('id', $minPosId)->first();

        return [$minPosId, $cardInfo, $card_type_value, $posObj];
    }


    public function minimumPoscalculation($posList, $amount, $card_type, $type, $cardinfo)
    {

        $minPosId = 0;
        $posAssoc = array();
        if (!empty($posList)) {

            foreach ($posList as $pos) {
                list($calculateAmount) = (new GlobalFunction())->conditionalCotCalculation(
                    $pos,$cardinfo['issuer_name'] ?? '', $cardinfo['card_program'] ?? '',$card_type ,$amount);
//                list($calculateAmount) = (new GlobalFunction())->conditionalCotCalculation($pos,$cardinfo['issuer_name'], $cardinfo['card_program'],$card_type,$amount);

                $posAssoc[$pos->pos_id] = $calculateAmount;
            }
            if (!empty($posAssoc)) {
                $minPosId = array_search(min($posAssoc), $posAssoc);
            }
        }


        return $minPosId;
    }

    public function getMinRatedPos($posList, $amount, $card_type, $issuer_name)
    {

        $minPosId = 0;
        $posAssoc = array();


        if (!empty($posList)) {

            foreach ($posList as $pos) {

                if ($card_type == 1) {
                    if ($issuer_name == $pos->bank_name) {
                        $calculateAmount = (($amount * $pos->on_us_cc_cot_percentage) / 100) + $pos->on_us_cc_cot_fixed;
                    } else {
                        $calculateAmount = (($amount * $pos->not_us_cc_cot_percentage) / 100) + $pos->not_us_cc_cot_fixed;
                    }
                    //for Debit card
                } else {
                    if ($issuer_name == $pos->bank_name) {
                        $calculateAmount = (($amount / 100) * $pos->debit_cot_percentage) + $pos->debit_cot_fixed;
                    } else {
                        $calculateAmount = (($amount / 100) * $pos->not_us_debit_cot_percentage) + $pos->not_us_debit_cot_fixed;
                    }
                }

                $posAssoc[$pos->pos_id] = $calculateAmount;
            }
            if (!empty($posAssoc)) {
                $minPosId = array_search(min($posAssoc), $posAssoc);
            }
        }


        return $minPosId;
    }



    public function validatePayment($payment_type, $amount, $data)
    {
        $errorCode = '';
        $errorMessage = '';
        $payable_amount = $amount;
        $posObj = null;
        $bankObj = null;
        $merchant_com = null;
        $merchantObj = $data['merchantObj'];
        $currencyObj = null;
        $commissionData = [];

        if ($payment_type == PaymentRecOption::CREDITCARD) {
            $data['card_no'] = $data['card'];

            if ($this->isPreAuthTransaction($data) && $data['card_info']['card_type'] != 'CREDIT CARD'){
                $errorCode = 1;
                $errorMessage = "Only Credit card is allowed for PreAuth transaction";
            }

            list($response_code, $message, $posObj, $merchantPosCommissionObj) = $this->validatePos($data["pos_id"],
                $data["currency_id"], $data['merchant_id'], $data['installment']);
            if ($response_code != 100) {
                $errorCode = $response_code;
                $errorMessage = $message;
            }

            if (empty($errorCode)){
                $merchantCardBlockList = new MerchantCardBlacklist();
                $isCardBlocked = $merchantCardBlockList->isMerchantCardBlackListed($data['card'], $merchantObj->id);
                if ($isCardBlocked){
                    $errorCode = 60;
                    $errorMessage = "The merchant is not allowed to perform transaction using this card.";
                }
            }

            if (empty($errorCode)) {
                list($errorCode, $errorMessage) = $this->validateCardNumber($data['card'], $data['cvv']);
            }

            if (empty($errorCode)) {
                $mcomObj = new MerchantCommission();
                $merchant_com = $mcomObj->getMCommissionByMIdPType($data['merchant_id'], $payment_type, $data['currency_id']);

                if (empty($merchant_com)) {
                    $errorCode = 2;
                    $errorMessage = 'Merchant Commission was not set for this currency and payment method. Please try another payment method';
                }
            }

            if (empty($errorCode)){
                if (isset($data['card_info']['issuer_name']) && GlobalFunction::isForeignCard($data['card_info']['issuer_name'])) {

                    if (empty($errorCode)  && !empty($merchantObj) &&  $merchantObj->is_allow_foreign_cards != 1){

                        $errorCode = 76;
                        $errorMessage = 'Foreign Card is not allowed for this merchant';

                    }elseif(empty($errorCode) && !empty($posObj) && $posObj->allow_foreign_card != 1){

                        $errorCode = 77;
                        $errorMessage = 'Foreign Card is not allowed for this Pos';

                    } elseif(empty($errorCode) && !empty($merchant_com) && $merchant_com->is_foreign_card_commission_enable != 1){

                        $errorCode = 77;
                        $errorMessage = 'Foreign Card Commission is not set for this merchant';

                    }elseif(empty($errorCode) && !empty($merchantPosCommissionObj) && $merchantPosCommissionObj->is_allow_foreign_card != 1){

                        $errorCode = 77;
                        $errorMessage = 'Foreign Card  is not set for this Merchant pos commission';

                    }
                }
            }
        }

        if (empty($errorCode) && !empty($merchant_com)) {
            $settlementObj = new Settlement();
            $settlementData = $settlementObj->getById($merchant_com->settlement_id);
            if (empty($settlementData)) {
                $errorCode = 3;
                $errorMessage = 'Merchant settlement date was not set for this currency and payment method. Please try another payment method';
            }
        }


        if (empty($errorCode)) {
            $commission = new Commission();
            $commissionData = $commission->getCalculatedFees(
                config('constants.TRANSACTION_TYPE.SALE'),
                $amount,
                $data
            );


            $net = $commissionData['net'];
            $cost = $commissionData['cost'];

            $payable_amount = $amount + $commissionData['user_fee'];


            if ($net < 0) {

                $errorCode = 4;
                $errorMessage = 'Transaction amount can\'t be less than commission amount!';


            } elseif ($cost > ($amount + $commissionData['user_fee'])) {

                $errorCode = 5;
                $errorMessage = 'Payable amount can\'t be less than cost amount!';

            }
        }

        $is2D = isset($data['is_2d']) && !empty($data['is_2d']) ? $data['is_2d'] : 0;

        if (empty($errorCode)) {
            list($errorCode, $errorMessage) = $this->checkMerchantTransactionLimit(
                $merchantObj, $data["currency_id"], $payment_type, $payable_amount, !$is2D);

//            $card_type_value = PaymentProvider::getCardType($data['card']);

            if (isset($data['currency_code']) && isset($data['installment'])){
                $bank = new Bank();
                $bankObj = $bank->findBankByID($posObj->bank_id);


/*
               if ($payment_type == PaymentRecOption::CREDITCARD && empty($errorCode)) {
//                  if(isset($data['is_2d']) && $data['is_2d']){
//                     if(isset($bankObj->is_allow_2d_cvvless) && $bankObj->is_allow_2d_cvvless == 0){
//
//                        if(empty($data['cvv'])){
//                           $errorCode = 1;
//                           $errorMessage = 'The cvv field is required';
//                        }else if(!is_numeric($data['cvv'])){
//                           $errorCode = 1;
//                           $errorMessage = 'The cvv field must be numeric';
//                        }
//                     }
//                  }else{
//                     if(isset($bankObj->is_allow_3d_cvvless) && $bankObj->is_allow_3d_cvvless == 0 && isset($data['cvv'])){
//
//                        if(empty($data['cvv'])){
//                           $errorCode = 1;
//                           $errorMessage = 'The cvv field is required';
//                        }else if(!is_numeric($data['cvv'])){
//                           $errorCode = 1;
//                           $errorMessage = 'The cvv field must be numeric';
//                        }
//
//                     }
//                  }
               }
*/


               if ($payment_type == PaymentRecOption::CREDITCARD && empty($errorCode)) {
                  list($errorCode, $errorMessage) = $this->restrictVisaCardPaymix($bankObj, $data['card']);
               }

               if ($payment_type == PaymentRecOption::CREDITCARD && empty($errorCode) && isset($data['merchantObj'])) {
                  $merchantSettingObj = (new MerchantSettings())->getMerchantSettingByMerchantId($merchantObj->id);
                  list($errorCode, $errorMessage) = $this->restrictVisaAndMesterCardMerchant($merchantSettingObj, $data['card']);
               }




               if (empty($errorCode)) {

//                list($errorCode, $errorMessage, $data, $posObj, $bankObj, $currencyObj, $payable_amount)
                list($errorCode, $errorMessage,$data, $posObj, $bankObj, $currencyObj, $payable_amount) = $this->reassignPosId($errorCode, $errorMessage,
                    $data, $merchantObj, $posObj,$data['is_recurring'], $is2D, $bankObj, $currencyObj, $payable_amount);
               }

                if (empty($errorCode)){
                    $commission = new Commission();
                    $commissionData = $commission->getCalculatedFees(
                        config('constants.TRANSACTION_TYPE.SALE'),
                        $data['total'],
                        $data
                    );
                }
            }

//            if ($payment_type == PaymentRecOption::CREDITCARD && !empty($errorCode) && $card_type_value != 1) {
//
//                $bank = new Bank();
//                $bankObj = $bank->findBankByID($posObj->bank_id);
//                list($errorCode, $errorMessage,$data, $posObj, $bankObj) = $this->reassignPosId($errorCode, $errorMessage,
//                    $data, $merchantObj, $posObj,$data['is_recurring'], $data['is_2d'], $bankObj, $currencyObj, $payable_amount);
//            }
        }

        if (empty($errorCode)) {
            $errorCode = 100;
        }


        return [$errorCode, $errorMessage, $payable_amount, $posObj, $data, $bankObj, $currencyObj, $commissionData];

    }


   public function restrictMerchantIp($merchantSettingObj, $merchantObj, $is_dpl = false, $is_manual_pos = false){

      $status_code = '';
      $status_message = '';

      if (isset($merchantObj->id) && isset($merchantSettingObj->is_enable_ip_restriction)
        && $merchantSettingObj->is_enable_ip_restriction && !$is_dpl && !$is_manual_pos
      && $merchantObj->type != Merchant::DEPOSIT_BY_CREDIT_CARD_PF_MERCHANT) {

         $merchant_ip = $this->getMerchantServerIp();
         // $merchant_ip = $this->getClientIp();
         $merchant_ip_assaignment = new MerchantIpAssaignment();
         $merchant_valid_ip_list = $merchant_ip_assaignment->getIpListByMerchantId($merchantObj->id);

         if (!in_array($merchant_ip, $merchant_valid_ip_list->toArray())) {
            $status_code = 15;
            $status_message = 'Merchant IP ' . $merchant_ip . " is not allowed";

         }
      }

      return [$status_code, $status_message];
   }

    public function restrictVisaAndMesterCardMerchant($merchantSettingObj, $cardNo){

      $status_code = '';
      $status_message = '';

      $cardType = PaymentProvider::getCardType($cardNo);

      if ( $cardType == config('constants.CARD_TYPE.VISA') &&
        isset($merchantSettingObj->is_visa_allow) && !$merchantSettingObj->is_visa_allow) {
         $status_code = 1;
         $status_message = "Visa Card is not allowed for this merchant";
      }elseif($cardType == config('constants.CARD_TYPE.MASTER') &&
          isset($merchantSettingObj->is_master_card_allow) &&
          !$merchantSettingObj->is_master_card_allow) {
          $status_code = 1;
          $status_message = "Master Card is not allowed for this merchant";
      }

      return [$status_code, $status_message];
    }

    public function validatePos($pos_id, $request_currency_id, $merchant_id, $installment, $is_pay_by_card_token = false)
    {
        $error_code = '';
        $message = '';
        $merchantPosCommissionObj = null;

        $pos = new Pos();
        $posObj = $pos->getPosByPosId($pos_id);
        if (empty($posObj)) {
            $error_code = 36;
            $message = 'POS Not Found';
        }
        if (empty($error_code)) {
            if ($posObj->currency_id != $request_currency_id) {
                $error_code = 48;
                $message = 'POS currency does not matches with expected currency id';
            }
        }

        if (empty($error_code) && !$is_pay_by_card_token) {
            $merchantPosCommission = new MerchantPosCommission();
            $merchantPosCommissionObj = $merchantPosCommission->getMerchantPosCommissionByInstallment($merchant_id, $posObj->id, $installment);
            if (empty($merchantPosCommissionObj)) {
                $error_code = 37;
                $message = 'Merchant Pos Commission was not set. Please contact with service provider';

            }
        }
        if (empty($error_code)) {
            $error_code = 100;
        }

        return [$error_code, $message, $posObj, $merchantPosCommissionObj];


    }


    private function vakifBankAuthResultManipulate($result)
    {

        $resultDocument = new \DOMDocument();
        $resultDocument->loadXML($result);

        //Status Bilgisi okunuyor
        $statusNode = $resultDocument->getElementsByTagName("Status")->item(0);
        $status = "";
        if (!empty($statusNode)) {
            $status = $statusNode->nodeValue;
        }


        //PAReq Bilgisi okunuyor
        $PAReqNode = $resultDocument->getElementsByTagName("PaReq")->item(0);
        $PaReq = "";
        if (!empty($PAReqNode)) {
            $PaReq = $PAReqNode->nodeValue;
        }
        //ACSUrl Bilgisi okunuyor
        $ACSUrlNode = $resultDocument->getElementsByTagName("ACSUrl")->item(0);
        $ACSUrl = "";
        if (!empty($ACSUrlNode)) {
            $ACSUrl = $ACSUrlNode->nodeValue;
        }
        //Term Url Bilgisi okunuyor
        $TermUrlNode = $resultDocument->getElementsByTagName("TermUrl")->item(0);
        $TermUrl = "";
        if (!empty($TermUrlNode)) {
            $TermUrl = $TermUrlNode->nodeValue;
        }

        //MD Bilgisi okunuyor
        $MDNode = $resultDocument->getElementsByTagName("MD")->item(0);
        $MD = "";
        if (!empty($MDNode)) {
            $MD = $MDNode->nodeValue;
        }


        //MessageErrorCode Bilgisi okunuyor
        $messageErrorCodeNode = $resultDocument->getElementsByTagName("MessageErrorCode")->item(0);
        $messageErrorCode = "";
        if (!empty($messageErrorCodeNode))
            $messageErrorCode = $messageErrorCodeNode->nodeValue;

        //MessageErrorCode Bilgisi okunuyor
        $ErrorMessageNode = $resultDocument->getElementsByTagName("ErrorMessage")->item(0);
        $ErrorMessage = "";
        if (!empty($ErrorMessageNode))
            $ErrorMessage = $ErrorMessageNode->nodeValue;

        // Sonu� dizisi olu�turuluyor
        $result = array
        (
            "Status" => $status,
            "PaReq" => $PaReq,
            "ACSUrl" => $ACSUrl,
            "TermUrl" => $TermUrl,
            "MerchantData" => $MD,
            "MessageErrorCode" => $messageErrorCode,
            "ErrorMessage" => $ErrorMessage
        );
        return $result;

    }

    public function albarakaResponseValidation($request, $sessionData)
    {

        $status = false;
        $remote_order_id = $request->SecureTransactionId;

        $payable_amount = $sessionData['payable_amount'];

        $currency_code = $sessionData['currency_iso_code'];
        $installment = $sessionData['installment'];
        $amount = floatval(str_replace(',', '.', str_replace('.', '', $request->Amount)));
        $str_arr = explode('.', $amount);
        $solidAmount = $str_arr[0];
        $pointAmount = $str_arr[1] ?? '00';
        $multipliedAmount = intval($amount * 100);

        if (intval($installment) < 2) {
            $installment = 0;
        }

        $posObj = $sessionData['posObj'];

        $bank = new Bank();
        $bankObj = $bank->findBankByID($posObj->bank_id);

        $terminalNo = $this->customEncryptionDecryption($bankObj->password,
            \config('app.brand_secret_key'), 'decrypt'); //"SiPay1!!!";
//
//
//
////        testing evironment
//        $terminalNo = '67832591';
//
////        production credentials
//        $terminalNo = '67224074';

        $encryptionKey = config('constants.ALBARAKA_ENCRYPTION_KEY');
//        MerchantNo:TerminalNo:SecureTransactionId:CavvData:Eci:MdStatus
        $macParams = $request->MerchantId . $terminalNo . $request->SecureTransactionId
            . $request->CAVV . $request->ECI . $request->MdStatus . $encryptionKey;
        $mac = hash("sha256", $macParams, true);
        $mac = base64_encode($mac);

        $month = $sessionData['expiry_month'];
        if (strlen($month) == 1) {
            $month = '0' . $month;
        }

        $year = $sessionData['expiry_year'];;
        if (strlen($year) == 4) {
            $year = substr($year, -2);
        }


        $apiRequest = [
            "ApiType" => "JSON",
            "ApiVersion" => "V100",
            "MerchantNo" => $request->MerchantId,
            "TerminalNo" => $terminalNo,
            "PaymentInstrumentType" => "CARD",
            "IsEncrypted" => "N",
            "IsTDSecureMerchant" => "Y",
            "IsMailOrder" => "N",
            "ThreeDSecureData" => [
                "SecureTransactionId" => $request->SecureTransactionId,
                "CavvData" => $request->CAVV,
                "Eci" => $request->ECI,
                "MdStatus" => $request->MdStatus,
                "MD" => $request->MD,
            ],
            "CardInformationData" => [
                "CardHolderName" => $sessionData['card_holder_name'],
                "CardNo" => $sessionData["credit_card_no"],
                "Cvc2" => $sessionData['cvv'],
                "ExpireDate" => $year . $month,
            ],
            "MAC" => $mac,
            "MACParams" => 'MerchantNo:TerminalNo:SecureTransactionId:CavvData:Eci:MdStatus',
            "Amount" => $multipliedAmount,
            "CurrencyCode" => $currency_code,
            "PointAmount" => $pointAmount,
            "OrderId" => $request->OrderId,
            "InstallmentCount" => $installment,
        ];


        //production URL
//        $url = "https://epos.albarakaturk.com.tr/ALBMerchantService/MerchantJSONAPI.svc/Sale";
        $url = $bankObj->api_url . "/Sale";

//        test url
//        $url = "https://epostest.albarakaturk.com.tr/ALBMerchantService/MerchantJSONAPI.svc/Sale";


        $payload = json_encode($apiRequest);


        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json"));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 90);
        if ($this->isNonSecureConnection()) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        }
        $response = curl_exec($ch);
        curl_close($ch);
        $response = json_decode($response, true);

        $message = '';
        if (isset($response['ServiceResponseData']['ResponseCode'])
            && $response['ServiceResponseData']['ResponseCode'] == "00") {
            $status = true;
            $remote_order_id = $response['ReferenceCode'];
        } else {
            if (isset($response['ServiceResponseData'])) {
                $message = $response['ServiceResponseData']['ResponseDescription'];
            }
        }

        $logData['action'] = 'ALBARAKA_RESPONSE_VALIDATION';
        $logData['status'] = $status;
        $logData['api_response'] = $response;
        $this->createLog($this->_getCommonLogData($logData));

        return [$status, $message, $remote_order_id, $apiRequest, $response];
    }


    public function preparePaymentExtrasData($gate, $is_3d, $input, $payable_amount, $merchantObj, $currencyObj, $posObj, $bankObj, $payment_source,
                                             $cardInfo, $purchaseRequestObj, $payByCardToken = false, $subMerchantPFObj = null)
    {

        //$gate  1 = Branden, 2 = White Level API 3= Deposit


        //1=Sale, 2=Deposit
        $type = Sale::TMP_PAYMENT_TRANS_TYPE_SALE;

        $installment = 1;
        $card_holder_name = '';
        $credit_card_no = '';
        $transaction_type = 'Auth';
        $items = [];
        $saved_card = 0;
        $is_white_level = false;

        $is_pay_by_marketplace = $input['is_pay_by_marketplace'] ?? 0;
        $customer_number = $input['customer_number'] ?? '';

        $extras = [
            'payment_method' => PaymentRecOption::CREDITCARD,
            'pos_id' => $posObj->id,
            'posObj' => $posObj,
            'ref' => $purchaseRequestObj->ref,
            'check_order_status' => 1,
            'order_id' => $purchaseRequestObj->ref,
            'invoice_id' => $purchaseRequestObj->invoice_id,
            'request_ip' => $purchaseRequestObj->ip,
            'payment_source' => $payment_source,
            'bank_code' => $bankObj->code
        ];

        if(!empty($subMerchantPFObj)){
            $extras['subMerchantPFObj'] = $subMerchantPFObj;
        }

        if(isset($input['second_hand_request']) && $input['second_hand_request'] == 1 ){
            $extras['is_second_hand_request'] = 1;
            $extras['return_url'] = $input['return_url'];
        }

        if (!empty($cardInfo)){
            $extras['card_type'] = $cardInfo["card_type"];
            $extras['card_program'] = $cardInfo["card_program"];
            $extras['issuer_bank'] = $cardInfo["issuer_name"];
            $extras['actual_issuer_bank'] = $cardInfo["actual_issuer_name"];
            $extras['card_holder_bank'] = $cardInfo['issuer_name'];
            $extras['card_country_code'] = $cardInfo['card_country_code'];
            $extras['card_info'] = $cardInfo;
        }

        if (isset($input['is_tarim_payment']) && $input['is_tarim_payment']){
            $extras['is_tarim_payment'] = $input['is_tarim_payment'];
            $extras['maturity_period'] = $input['maturity_period'] ?? '';
            $extras['payment_frequency'] = $input['payment_frequency'] ?? '';
        }

        if(isset($input['is_notification_off']) && $input['is_notification_off'] == 1 ){
           $extras['is_notification_off'] = 1;
        }

        $extras["merchant_server_id"] = $purchaseRequestObj->data->merchant_server_id ?? '';
        $extras["referer_url"] = $purchaseRequestObj->data->referer_url ?? '';

        if ($gate == config('constants.PAYMENT_BRANDED_GATE') || $gate == config('constants.PAYMENT_DEPOSIT_GATE')) {//1=branded, 3=Deposit


            if (isset($purchaseRequestObj->data->items)) {
                $items = json_decode(json_encode($purchaseRequestObj->data->items), true);
            }


            $card_holder_name = $input['name'] ?? '';
            $credit_card_no = $input['card'] ?? '';
            $installment = $input['installment'];

            if ($gate == config('constants.PAYMENT_DEPOSIT_GATE')) {//deposit

                $type = Deposit::TMP_PAYMENT_TRANS_TYPE_DEPOSIT;

            }else{

                if (isset($purchaseRequestObj->data->is_pay_by_marketplace)) {
                    $is_pay_by_marketplace = $purchaseRequestObj->data->is_pay_by_marketplace;
                }

                if (isset($purchaseRequestObj->data->transaction_type)) {
                    $transaction_type = $purchaseRequestObj->data->transaction_type;
                }

                if (isset($purchaseRequestObj->data->save_card)) {
                    $saved_card = $purchaseRequestObj->data->saved_card;
                }

                if (isset($purchaseRequestObj->data->customer_number)) {
                    $customer_number = $purchaseRequestObj->data->customer_number;
                }
                
                $extras['dpl_option'] = $merchantObj->dpl_option ?? '';
                $extras["dpl_token"] = GlobalFunction::getBrandSession('dpl_token', $purchaseRequestObj->ref);
            }

            $extras["gsm_number"] = GlobalFunction::getBrandSession('mobile', $purchaseRequestObj->ref);



        } elseif ($gate == config('constants.PAYMENT_WHITE_LABEL_API_GATE')) {// API white Label

            if (isset($input['transaction_type'])) {
                $transaction_type = $input['transaction_type'];
            }
            if (isset($input['items'])) {
                $items = $input['items'];
            }

            if (isset($input['saved_card'])) {
                $saved_card = $input['saved_card'];
            }

            $card_holder_name = $input['cc_holder_name'] ?? '';
            $credit_card_no = $input['cc_no'] ?? '';

            $installment = $input['installments_number'];
            $is_white_level = true;

        }




        if ($payByCardToken) {
            $extras['is_pay_by_card_token'] = 1;
            $extras['campaign_id'] = 0;
            $extras['allocation_id'] = 0;
//            $extras['card_type'] = 'DEBIT CARD';
            $extras['card_token'] = $input['card_token'];
            $extras['card_user_key']  = $input['card_user_key'];
            if (isset($input['pay_by_card_token_provider'])){
                $extras['pay_by_card_token_provider'] = $input['pay_by_card_token_provider']; // this variable is available for craftgate api only
            }

        } else {
            $extras['campaign_id'] = $input['campaign_id'];
            $extras['allocation_id'] = $input['allocation_id'];

        }

        if (isset($input['expiry_month'])){
            $extras['expiry_month'] = $input['expiry_month'];
        }

        if (isset($input['expiry_year'])){
            $extras['expiry_year'] = $input['expiry_year'];
        }

        if (isset($input['cvv'])){
            $extras['cvv'] = $input['cvv'];
        }

        $extras['card_holder_name'] = $card_holder_name;

        if (!empty($credit_card_no)){
            $extras['credit_card_no'] = $credit_card_no;
        }


        if ($is_3d) {
            if ($bankObj->payment_provider == config('constants.PAYMENT_PROVIDER.YAPI_VE_KREDI')
                || $bankObj->payment_provider == config('constants.PAYMENT_PROVIDER.KUVEYT_TURK_KATILIM')) {
                $extras['check_order_status'] = 0;
            }
        }
//
//        if($bankObj->payment_provider == config('constants.PAYMENT_PROVIDER.YAPI_VE_KREDI')){
//            $extras['card_holder_name'] = mb_substr( $extras['card_holder_name'], 0,60 );
//        }elseif($bankObj->payment_provider == config('constants.PAYMENT_PROVIDER.KUVEYT_TURK_KATILIM')){
//            $extras['card_holder_name'] = mb_substr( $extras['card_holder_name'], 0,26 );
//        }

        if (!empty($customer_number)) {
            $extras['customer_number'] = $customer_number;
        }

        if (!empty($transaction_type)) {
            $extras['transaction_type'] = $transaction_type;
//            if ($transaction_type == 'PreAuth') {
//                $extras['transaction_state_id'] = TransactionState::PENDING;
//            }
        }


        $extras['type'] = $input['type'] ?? Sale::TMP_PAYMENT_TRANS_TYPE_SALE;

        if($extras['type'] == Deposit::TMP_PAYMENT_TRANS_TYPE_DEPOSIT){
           $extras['is_credit_card'] = true;
           $extras['merchant_parent_user_id'] = $input['merchant_parent_user_id'];
           $extras['phone'] = $input['phone'];
           $extras['user_type'] = $input['user_type'];
           $extras['userCategory'] = $input['userCategory'];
           $extras['paymentType'] = 'Credit Card';
           $extras['currencyid'] = $currencyObj->id;
           $extras['actual_issuer_name'] = $cardInfo['actual_issuer_name'];
           $extras['amount'] = $input['total'];
           $extras['methodID'] = DepositeMethod::CREDIT_CARD;
           $extras['credit_card_no'] = $input['cc_no'];
           $extras['name'] = $input['name'];

           $card_type_value = 2;

           if ($cardInfo["card_type"] == "CREDIT CARD") {
              $card_type_value = 1;
           }

           $extras['card_type'] = $card_type_value;
           $extras['deposit_fee'] = $input["deposit_fee"];
           $extras['deposit_requester_ip'] = $purchaseRequestObj->ip;
        }

        $extras['items'] = $items;
        $extras['payable_amount'] = $payable_amount;
        $extras['currency_code'] = $currencyObj->code;
        $extras['currency_iso_code'] = $currencyObj->iso_code;
        $extras['merchant_id'] = $merchantObj->id ?? 0;
        $extras['payment_provider'] = $bankObj->payment_provider;
        $extras['is_white_level'] = $is_white_level;
        $extras['installment'] = $installment;
        $extras['is_pay_by_marketplace'] = $is_pay_by_marketplace;
        $extras['saved_card'] = $saved_card;

        if (isset($input['isWix'])) {
            $extras['is_wix'] = 1;
        }

        if ($this->isOnePagePaymentDPL($input)) {
            unset($extras['is_white_level']);
            $extras['dpl_option'] = $merchantObj->dpl_option ?? '';
            $extras["dpl_token"] = GlobalFunction::getBrandSession('dpl_token', $purchaseRequestObj->ref);
        }

        return $extras;
    }


    public function updatePaymentExtrasAfterBankResponse($extras, $error_code, $message,
                                                         $auth_code, $remote_order_id, $payment_status, $extra_card_holder_name = ''){

        //$payment status 1= success, 0=failed
        $extras['response_code'] = $error_code;
        $extras['authcode'] = $auth_code;
        $extras['remote_order_id'] = $remote_order_id;
        if ($payment_status == 1){
            $extras['result'] = 'Approved('.$remote_order_id.')';
        }elseif ($payment_status == 0){
            $extras['result'] = 'Failed # ' . $message;
        }
        $extras['extra_card_holder_name'] = $extra_card_holder_name;

        return $extras;
    }

    public function isPreAuthTransaction($extras){
        $status = false;

        if (isset($extras['transaction_type']) && $extras['transaction_type'] == 'PreAuth'){
            $status = true;
        }
        return $status;
    }

    public function validatePreAuth($inputData, $gate)
    {
        $error_code = '';
        $error_message = '';

        if (isset($inputData['transaction_type'])) {

            if (!in_array($inputData['transaction_type'], array('PreAuth', 'Auth'))) {
                $error_code = 1;
                $error_message = "Transaction type must be Auth or PreAuth";
            }

            if (empty($error_code)) {
                if (isset($inputData['order_type']) && $inputData['order_type'] == 1 && $inputData['transaction_type'] == 'PreAuth') {
                    $error_code = 1;
                    $error_message = 'PreAuth transaction is not allowed for recurring payment';
                }

            }


            if (empty($error_code) && $gate == config('constants.PAYMENT_WHITE_LABEL_API_GATE')) {

                $preAuthSupportedEndPoints = [
                    'PAY_SMART_2D',
                    'PAY_SMART_3D',
                    'PAY_BY_CARD_TOKEN',
                    'PAY_BY_CARD_TOKEN_2D'
                ];
                if ($inputData['transaction_type'] == 'PreAuth' && !in_array($inputData['api_name'], $preAuthSupportedEndPoints)) {
                    $error_code = 1;
                    $error_message = "PreAuth Transaction is not allowed for this end point";
                }
            }



        }

        return [$error_code, $error_message];
    }

    private function validateCardNumber($cardNo, $cvv): array
    {
        // if card no exists
        if (isset($cardNo) && !empty($cardNo)) {
            $ccNoLength = strlen($cardNo);
            if (!($ccNoLength >= 15 && $ccNoLength <= 16)) {
                $errorCode = 108;
                $errorMessage = 'Card  number length  must  be 15 to 16';
            }

            // if also cvv is exists
            if (empty($errorCode) && isset($cvv) && !empty($cvv)) {
                if ((strlen($cardNo) + strlen($cvv)) != 19) {
                    $errorCode = 108;
                    $errorMessage = 'Total length of card number and  cvv must  be 19';
                }
            }
        }
        return [$errorCode ?? "", $errorMessage ?? ""];
    }

    private function isOnePagePaymentDPL($input) {
        return isset($input['is_one_page_payment_dpl']) && $input['is_one_page_payment_dpl'] == 1;
    }
}
