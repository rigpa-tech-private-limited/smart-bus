<?php

namespace App\Http\Controllers;

use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class RestAPIController extends Controller
{

    private function validateToken($token)
    {
        $query = "SELECT rowid FROM `users` where api_token='$token' and status=1 LIMIT 1";
        $results = DB::select($query);
        return $results;
    }

    public function deviceActivation(Request $req)
    {
        $response = array();
        $responseCode = 500;
        $rules = array(
            "device_code" => "required",
            "app_version" => "required",
        );
        $validator = Validator::make($req->all(), $rules);

        if ($validator->fails()) {
            $response = array('status' => 'error', 'message' => 'Invalid parameters', "errors" => $validator->errors());
            $responseCode = 400;
        } else {
            $device_code = $req->device_code;
            $app_version = $req->app_version;
            $device_uuid = $req->device_uuid;
            $device_model = $req->device_model;
            $battery_status = $req->battery_status;
            $signal_strength = $req->signal_strength;
            $latitude = $req->latitude;
            $longitude = $req->longitude;
            $imei_number = $req->imei_number;
            $serial_number = $req->serial_number;

            $query = "SELECT rowid,bus_id,business_id FROM `devices` where activation_code='$device_code' and status=0 and deleted_date is null";
            $results = DB::select($query);
            $total = count($results);

            if ($total > 0) {
                $last_id = $results[0]->rowid;
                $bus_id = $results[0]->bus_id;
                $business_id = $results[0]->business_id;

                $query_business = "SELECT created_date FROM account where rowid='$business_id' and status=1";
                $result_business = DB::select($query_business);
                $total_business = count($result_business);
                $business_created_date = $result_business[0]->created_date;

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
                            'sn' => $serial_number,
                        ]);

                    if ($updated) {
                        $response = array('status' => 'success', 'message' => 'Device activated successfully', 'token' => base64_encode($last_id), 'temp' => '98.6', 'bus_id' => $bus_id, 'business_id' => $business_id, 'school_id' => 2, 'timout' => '5000');
                        $responseCode = 200;
                    } else {
                        $response = array('status' => 'error', "message" => "Error on activating device");
                        $responseCode = 200;
                    }
                } catch (QueryException | \Exception $e) {
                    $response = array('status' => 'error', "message" => "Error on activating device", "errors" => $e->getMessage());
                    $responseCode = 200;
                }
            } else {
                $response = array('status' => 'error', 'message' => 'Invalid activation code');
                $responseCode = 200;
            }
        }
        return response()->json($response, $responseCode);
    }

}
