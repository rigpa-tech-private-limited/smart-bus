<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class RestAPIController extends Controller
{

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
                            'sn' => $serial_number,
                        ]);

                    if ($updated) {
                        $response = array('status' => 'success', 'message' => 'Device activated successfully', 'token' => base64_encode($last_id), 'temp' => '98.6', 'bus_id' => $bus_id, 'business_id' => $business_id, 'school_id' => 2, 'timout' => '5000');
                        $responseCode = 200;
                    } else {
                        $response = array('status' => 'error', "message" => "Error on activating device");
                        $responseCode = 200;
                    }
                } catch (\Illuminate\Database\QueryException $e) {
                    $response = array('status' => 'error', "message" => "Error on activating device");
                    $responseCode = 200;
                }
            } else {
                $response = array('status' => 'error', 'message' => 'Invalid activation code');
                $responseCode = 200;
            }
        }
        return response()->json($response, $responseCode);
    }

    public function getOTP(Request $req)
    {

        // $token = $req->bearerToken();

        $response = array();
        $responseCode = 500;
        $rules = array(
            "country_code" => "required",
            "mobile" => "required",
        );
        $validator = Validator::make($req->all(), $rules);

        if ($validator->fails()) {
            $response = array('status' => 'error', 'message' => 'Invalid parameters', "errors" => $validator->errors());
            $responseCode = 400;
        } else {
            $country_code = $req->country_code;
            $mobile = $req->mobile;
            $cc_mobile = $country_code . '' . $mobile;
            $mobile_no = preg_replace('/[^0-9]/', '', $mobile);

            $query = "SELECT * FROM users WHERE phoneno = '$mobile_no' AND status='1' AND usertype='2'";
            $results = DB::select($query);
            $count = count($results);

            if ($count > 0) {
                // generate OTP
                $otp = rand(1000, 9999);

                // $curl = curl_init();
                // $app_code = "zPTTeR09Bf7";
                // $auth_key = "346743AvSmQp2ZmE5fa938afP1";
                // $template_id = "5fd759970b278d5dcf7aa8e3";
                // $sms_url = "https://api.msg91.com/api/v5/otp?extra_param=%7B%22VAR1%22%3A%22" . $app_code . "%22%7D&authkey=" . $auth_key . "&template_id=" . $template_id . "&mobile=" . $cc_mobile . "&otp=" . $otp . "&otp_length=4";
                // curl_setopt_array($curl, array(
                //     CURLOPT_URL => $sms_url,
                //     CURLOPT_RETURNTRANSFER => true,
                //     CURLOPT_ENCODING => "",
                //     CURLOPT_MAXREDIRS => 10,
                //     CURLOPT_TIMEOUT => 30,
                //     CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                //     CURLOPT_CUSTOMREQUEST => "GET",
                //     CURLOPT_SSL_VERIFYHOST => 0,
                //     CURLOPT_SSL_VERIFYPEER => 0,
                //     CURLOPT_HTTPHEADER => array(
                //         "content-type: application/json",
                //     ),
                // ));
                // // print_r($curl);
                // $mresponse = curl_exec($curl);
                // $merr = curl_error($curl);

                // curl_close($curl);
                // if ($merr) {
                //     // echo "cURL Error #:".$merr;
                //     $merror = json_decode($merr);
                //     $response = array('status' => 'error', "message" => $merror);
                //     $responseCode = 200;
                // } else {
                //     // echo $mresponse;
                //     $mres = json_decode($mresponse);
                //     if ($mres->type == "success") {
                //         try {
                //             $updated = DB::table('users')
                //                 ->where('phoneno', $mobile_no)
                //                 ->update([
                //                     'otp' => $otp,
                //                     'is_expired' => 0,
                //                 ]);

                //             if ($updated) {
                //                 $response = array("status" => 'success', "request_id" => $mres->request_id, "message" => 'OTP Sent successfully');
                //                 $responseCode = 200;
                //             } else {
                //                 $response = array('status' => 'error', "message" => "Error on getting OTP");
                //                 $responseCode = 200;
                //             }
                //         } catch (\Illuminate\Database\QueryException $e) {
                //             $response = array('status' => 'error', "message" => "Error on getting OTP");
                //             $responseCode = 200;
                //         }
                //     }
                // }

                try {
                    $updated = DB::table('users')
                        ->where('phoneno', $mobile_no)
                        ->update([
                            'otp' => $otp,
                            'is_expired' => 0,
                        ]);

                    if ($updated) {
                        $response = array("status" => 'success', "message" => 'OTP Sent successfully ' . $otp);
                        $responseCode = 200;
                    } else {
                        $response = array('status' => 'error', "message" => "Error on getting OTP");
                        $responseCode = 200;
                    }
                } catch (\Illuminate\Database\QueryException $e) {
                    $response = array('status' => 'error', "message" => "Error on getting OTP");
                    $responseCode = 200;
                }

            } else {
                $response = array('status' => 'error', "message" => "Invalid Mobile Number");
                $responseCode = 200;
            }
        }
        return response()->json($response, $responseCode);
    }

    public function verifyOTP(Request $req)
    {

        // $token = $req->bearerToken();
        $logo_base_url = "https://smartbus.vaango.co/photo/logo/";
        $profile_base_url = "https://smartbus.vaango.co/photo/profile/";

        $response = array();
        $responseCode = 500;
        $rules = array(
            "country_code" => "required",
            "mobile" => "required",
            "otp" => "required",
        );
        $validator = Validator::make($req->all(), $rules);

        if ($validator->fails()) {
            $response = array('status' => 'error', 'message' => 'Invalid parameters', "errors" => $validator->errors());
            $responseCode = 400;
        } else {
            $country_code = $req->country_code;
            $mobile = $req->mobile;
            $cc_mobile = $country_code . '' . $mobile;
            $mobile_no = preg_replace('/[^0-9]/', '', $mobile);
            $otp = $req->otp;

            $query = "SELECT u.rowid as uid,u.name,u.staff_id,u.nfcid,u.image_url as profile,u.phoneno as mobile,u.business_id,u.school_id,u.routeid,ac.name as school_name,ac.location as school_location,ac.address as school_address,ac.logo as school_logo FROM `users` u
            INNER JOIN account ac ON ac.rowid = u.business_id  WHERE u.phoneno='" . $mobile_no . "' AND u.otp='" . $otp . "' AND u.is_expired!=1 AND u.status='1'";
            $results = DB::select($query);
            $count = count($results);

            if ($count > 0) {
                try {
                    // $hashed_token = password_hash($results[0]->uid, PASSWORD_BCRYPT, array('cost' => 5));
                    $hashed_token = base64_encode($results[0]->uid . "##" . $results[0]->name);
                    $updated = DB::table('users')
                        ->where([
                            'phoneno' => $mobile_no,
                            'otp' => $otp,
                        ])
                        ->update([
                            'api_token' => $hashed_token,
                            'is_expired' => 1,
                        ]);

                    if ($updated) {
                        $user = array();
                        $user['uid'] = $results[0]->uid;
                        $user['name'] = $results[0]->name;
                        $user['staff_id'] = $results[0]->staff_id;
                        $user['nfcid'] = $results[0]->nfcid;
                        $profile_url = "";
                        if ($results[0]->profile != "") {
                            $profile_url = $profile_base_url . $results[0]->profile;
                        }
                        $user['profile'] = $profile_url;
                        $user['mobile'] = $results[0]->mobile;
                        $user['business_id'] = $results[0]->business_id;
                        $user['school_id'] = $results[0]->school_id;
                        $user['routeid'] = $results[0]->routeid;
                        $user['school_name'] = $results[0]->school_name;
                        $user['school_location'] = $results[0]->school_location;
                        $user['school_address'] = $results[0]->school_address;
                        $school_logo = "";
                        if ($results[0]->school_logo != "") {
                            $school_logo = $logo_base_url . $results[0]->school_logo;
                        }
                        $user['school_logo'] = $school_logo;
                        $user['token'] = $hashed_token;
                        $response = array("status" => 'success', 'user' => $user);
                        $responseCode = 200;
                    } else {
                        $response = array('status' => 'error', "message" => "Error on verify OTP");
                        $responseCode = 200;
                    }
                } catch (Exception $e) {
                    $response = array('status' => 'error', "message" => "Error on verify OTP");
                    $responseCode = 200;
                }
            } else {
                $response = array('status' => 'error', "message" => "Invalid OTP");
                $responseCode = 200;
            }
        }
        return response()->json($response, $responseCode);
    }

    public function getTrips(Request $req)
    {

        $response = array();
        $responseCode = 500;

        if ($req->bearerToken()) {
            $token = $req->bearerToken();
            $decodedStr = base64_decode($token);
            $explodedStr = explode("##", $decodedStr);
            // print_r($explodedStr);
            $driver_id = $explodedStr[0];

            $curr_date = date('Y-m-d');
            $tripRes = array();
            $nQuery = "SELECT td.rowid tripid,td.routeid,td.bus_id,td.business_id,IF(td.running_from_date LIKE '0000-00-00 00:00:00%', DATE_FORMAT(NOW(), '%W, %e %M, %Y'), DATE_FORMAT(td.running_from_date, '%W, %e %M, %Y')) trip_date,td.name trip_name,td.running_time trip_time,r.waypoints trip_waypoints,r.locations trip_locations,r.tot_distance trip_distance, r.tot_time trip_duration,td.route_type,b.reg_no bus_number,b.name bus_name FROM trip_details td INNER JOIN `bus_details` b ON b.rowid=td.bus_id INNER JOIN route r on r.rowid=td.routeid WHERE recurring='n' AND ((DATE(running_from_date)  <= '$curr_date' AND DATE(running_to_date) >= '$curr_date') OR DATE(running_from_date)='$curr_date') AND td.driver_id='$driver_id' ORDER BY running_time ASC";
            $nResults = DB::select($nQuery);
            $nCount = count($nResults);

            if ($nCount > 0) {
                $tripRes = $nResults;
            } else {
                $chkday = date("D", strtotime($curr_date));
                $rQuery = "SELECT td.rowid tripid,td.routeid,td.bus_id,td.business_id,IF(td.running_from_date LIKE '0000-00-00 00:00:00%', DATE_FORMAT(NOW(), '%W, %e %M, %Y'), DATE_FORMAT(td.running_from_date, '%W, %e %M, %Y')) trip_date,td.name trip_name,td.running_time trip_time,r.waypoints trip_waypoints,r.locations trip_locations,r.tot_distance trip_distance, r.tot_time trip_duration,td.route_type,b.reg_no bus_number,b.name bus_name FROM trip_details td INNER JOIN `bus_details` b ON b.rowid=td.bus_id INNER JOIN route r on r.rowid=td.routeid WHERE recurring='y' AND (days LIKE '%,$chkday' or days LIKE '%,$chkday,%' or days LIKE '$chkday,%' or days LIKE '$chkday') and td.driver_id='$driver_id' ORDER BY running_time ASC";
                $rResults = DB::select($rQuery);
                $rCount = count($rResults);
                $tripRes = $rResults;
            }

            $response = array('status' => 'success', 'trips' => $tripRes);
            $responseCode = 200;
        } else {
            $response = array('status' => 'error', 'message' => 'unauthorized');
            $responseCode = 401;
        }
        return response()->json($response, $responseCode);
    }
}
