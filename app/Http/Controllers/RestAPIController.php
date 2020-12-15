<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Rest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class RestAPIController extends Controller
{

    function deviceActivation(Request $req){
        $response = array();
        $responseCode = 500;
        $rules = array(
            "device_code"=>"required",
            "app_version"=>"required",
        );
        $validator = Validator::make($req->all(),$rules);

        if($validator->fails()){
            $response=array('status'=>'error','message'=>'Invalid parameters',"errors"=>$validator->errors());
            $responseCode = 400;
        }
        else
        {
            $device_code=$req->device_code;
            $app_version=$req->app_version;
            $device_uuid=$req->device_uuid;
            $device_model=$req->device_model;
            $battery_status=$req->battery_status;
            $signal_strength=$req->signal_strength;
            $latitude=$req->latitude;
            $longitude=$req->longitude;
            $imei_number=$req->imei_number;
            $serial_number=$req->serial_number;
    
            $query = "SELECT rowid,bus_id,business_id FROM `devices` where activation_code='$device_code' and status=0 and deleted_date is null";
            $results = DB::select($query);
            $total  = count($results);
            
            if ($total > 0) {
                $last_id = $results[0]->rowid;
                $bus_id = $results[0]->bus_id;
                $business_id = $results[0]->business_id;
    
                $query_business = "SELECT created_date FROM account where rowid='$business_id' and status=1";
                $result_business = DB::select($query_business);
                $total_business  = count($result_business);
                $business_created_date=$result_business[0]->created_date;
    
                // DB::statement(DB::raw("UPDATE devices SET sync_time=?,signal_strength=?,app_version=?,activation_code=null,battery_status=?,device_model=?,status=1,device_uuid=?,lat=?,lon=?,imei=?,sn=? where activation_code=?"),
                // array($business_created_date, $signal_strength, $app_version, $battery_status, $device_model, $device_uuid, $latitude, $longitude, $imei_number, $serial_number, $device_code)
                // );
                try {
                    $updated = DB::table('devices')
                            ->where('activation_code', $device_code)
                            ->update([
                                'sync_time' => $business_created_date,
                                'signal_strength' => $signal_strength,
                                'app_version' => $app_version,
                                'activation_code' => null,
                                'battery_status' => $battery_status,
                                'device_model' => $device_model,
                                'status' => 1,
                                'device_uuid' => $device_uuid,
                                'lat' => $latitude,
                                'lon' => $longitude,
                                'imei' => $imei_number,
                                'sn' => $serial_number
                            ]);

                    if($updated) {
                        $response=array('status'=>'success','message'=>'Device activated successfully','token'=>base64_encode($last_id),'temp'=>'98.6','bus_id'=>$bus_id,'business_id'=>$business_id,'school_id'=>2,'timout'=>'5000');
                        $responseCode = 200;
                    }
                    else {
                        $response=array('status'=>'error', "message"=>"Error on activating device");
                        $responseCode = 400;
                    }
                } catch (\Illuminate\Database\QueryException $e) {
                    $response=array('status'=>'error', "message"=>"Error on activating device");
                    $responseCode = 400;
                }
            } else {
                $response=array('status'=>'error','message'=>'Invalid activation code');
                $responseCode = 400;
            }
        }
        return response()->json($response,$responseCode);
    }

    function getOTP(Request $req){

        // $token = $req->bearerToken();

        $response = array();
        $responseCode = 500;
        $rules = array(
            "country_code"=>"required",
            "mobile"=>"required",
        );
        $validator = Validator::make($req->all(),$rules);

        if($validator->fails()){
            $response=array('status'=>'error','message'=>'Invalid parameters',"errors"=>$validator->errors());
            $responseCode = 400;
        }
        else
        {
            $country_code=$req->country_code;
            $mobile=$req->mobile;
            $cc_mobile = $country_code.''.$mobile;
            $mobile_no = preg_replace('/[^0-9]/','',$mobile);

            $query = "SELECT * FROM users WHERE phoneno = '$mobile_no' AND status='1'";
            $results = DB::select($query);
            $count  = count($results);

            if($count>0) {
                // generate OTP
                $otp = rand(1000,9999);
                // $textlocal = new Textlocal(TEXTLOCAL_USERNAME, TEXTLOCAL_PASSWORD);
      
                // $numbers = array($mobile_no);
                // $sender = 'TXTLCL';
                // $message = 'This is a your OTP '.$otp;
      
                // try {
                //     $result = $textlocal->sendSms($numbers, $message, $sender);
                // } catch (Exception $e) {
                //     echo json_encode(["status"=>"error","status_code"=>"402", "message"=>$e->getMessage()]);
                // }
                try {
                    $updated = DB::table('users')
                            ->where('phoneno', $mobile_no)
                            ->update([
                                'otp' => $otp,
                                'is_expired' => 0 
                            ]);

                    if($updated) {
                        $response=array("status"=>'success', "message"=>'OTP Sent successfully '.$otp);
                        $responseCode = 200;
                    }
                    else {
                        $response=array('status'=>'error', "message"=>"Error on getting OTP");
                        $responseCode = 400;
                    }
                } catch (\Illuminate\Database\QueryException $e) {
                    $response=array('status'=>'error', "message"=>"Error on getting OTP");
                    $responseCode = 400;
                }
              } else {
                $response=array('status'=>'error', "message"=>"Invalid Mobile Number");
                $responseCode = 400;
              }
        }
        return response()->json($response,$responseCode);
    }
}
