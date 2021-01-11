<?php

namespace App\Http\Controllers;

use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class RestAPIController extends Controller
{

    private function validateDeviceToken($token)
    {
        $query = "SELECT rowid,sync_time FROM `devices` where api_token='$token' and status=1 LIMIT 1";
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
            "device_uuid" => "required",
            "device_model" => "required",
            "battery_status" => "required",
            "signal_strength" => "required",
            "latitude" => "required",
            "longitude" => "required",
            "imei_number" => "required",
            "serial_number" => "required",
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
                    $hashed_token = password_hash($last_id, PASSWORD_BCRYPT, array('cost' => 5));
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
                            'api_token' => $hashed_token,
                        ]);

                    if ($updated) {
                        $response = array('status' => 'success', 'message' => 'Device activated successfully', 'token' => $hashed_token, 'temp' => '98.6', 'bus_id' => $bus_id, 'business_id' => $business_id, 'school_id' => 2, 'time_out' => '5000');
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

    public function getUsers(Request $req)
    {

        $response = array();
        $responseCode = 500;
        $rules = array(
            "bus_id" => "required",
            "business_id" => "required",
        );
        $validator = Validator::make($req->all(), $rules);

        if ($validator->fails()) {
            $response = array('status' => 'error', 'message' => 'Invalid parameters', "errors" => $validator->errors());
            $responseCode = 400;
        } else {
            if ($req->bearerToken()) {
                $token = $req->bearerToken();
                $deviceRes = $this->validateDeviceToken($token);
                $dCount = count($deviceRes);
                if ($dCount > 0) {

                    $bus_id = $req->bus_id;
                    $business_id = $req->business_id;
                    $device_id = $deviceRes[0]->rowid;
                    $devices_sync_time = $deviceRes[0]->sync_time;
                    $query = "SELECT u.rowid,nfcid,u.name,update_flag,t.title usertype FROM users u INNER JOIN usertype t ON t.rowid= u.usertype WHERE u.status=1 AND (u.usertype=2 OR (trip_id!='' OR ret_trip_id!='')) AND business_id=$business_id AND u.updated_date >='$devices_sync_time'";
                    $userRes = DB::select($query);
                    $uCount = count($userRes);

                    if ($uCount > 0) {
                        try {
                            $sync_time = date('Y-m-d H:i:s');
                            $updated = DB::table('devices')
                                ->where('rowid', $device_id)
                                ->update([
                                    'sync_time' => $sync_time,
                                ]);

                            if ($updated) {
                                $response = array('status' => 'success', 'users' => $userRes);
                                $responseCode = 200;
                            } else {
                                $response = array('status' => 'error', "message" => "Error on getting users data");
                                $responseCode = 200;
                            }
                        } catch (QueryException | \Exception $e) {
                            $response = array('status' => 'error', "message" => "Error on getting users data", "errors" => $e->getMessage());
                            $responseCode = 200;
                        }
                    } else {
                        $response = array('status' => 'success', 'users' => []);
                        $responseCode = 200;
                    }
                } else {
                    $response = array('status' => 'error', 'message' => 'Invalid token');
                    $responseCode = 200;
                }
            } else {
                $response = array('status' => 'error', 'message' => 'unauthorized');
                $responseCode = 401;
            }
        }
        return response()->json($response, $responseCode);
    }

    public function getTripDetailsByDeviceID($deviceid, $driver_id = '', $entry_date = '', $driverorder = '')
    {

        $orderby = '';
        $chktime = "";

        if ($entry_date == '') {
            $curr_date = date('Y-m-d');
        } else {
            $curr_date = date('Y-m-d', strtotime($entry_date));
            if ($driverorder == 'asc') {
                $orderby = 'ORDER BY running_time ASC limit 1';
                $chktime = " AND  running_time>='" . date("H:i:s", strtotime($entry_date)) . "'";
            } else {
                $orderby = 'ORDER BY running_time DESC limit 1';
                $chktime = " AND  running_time<='" . date("H:i:s", strtotime($entry_date)) . "'";
            }
        }
        if ($driver_id != '') {
            $qu = ' AND (td.driver_id=' . $driver_id . ' OR td.attender_user_id=' . $driver_id . ')';
        } else {
            $qu = '';
        }

        $nrQuery = "SELECT td.rowid tripid,driver_id,routeid,td.bus_id,td.business_id,running_time,tot_time,attender_user_id,td.name,td.route_type,r.tot_distance FROM trip_details td INNER JOIN `devices` d ON td.bus_id=d.bus_id INNER JOIN route r ON r.rowid=td.routeid WHERE d.rowid='$deviceid' AND recurring='n' AND ((DATE(running_from_date)  <= '$curr_date' AND DATE(running_to_date) >= '$curr_date') OR DATE(running_from_date)='$curr_date') $qu $chktime $orderby";

        $chkday = date("D", strtotime($curr_date));

        $rQuery = "SELECT td.rowid tripid,driver_id,routeid,td.bus_id,td.business_id,running_time,tot_time,attender_user_id  FROM trip_details td INNER JOIN `devices` d ON td.bus_id=d.bus_id INNER JOIN route r ON r.rowid=td.routeid WHERE d.rowid='$deviceid' AND recurring='y' AND (days LIKE '%,$chkday' OR days LIKE '%,$chkday,%' OR days LIKE '$chkday,%' OR days LIKE '$chkday') $qu $chktime $orderby";

        $nrTripRes = DB::select($nrQuery);
        $nrCount = count($nrTripRes);

        if ($nrCount > 0) {
            return $nrTripRes;
        } else {
            $rTripRes = DB::select($rQuery);
            $rCount = count($rTripRes);
            if ($rCount > 0) {
                return $rTripRes;
            } else {
                return [];
            }
        }

    }

    private function getTripDetails($device_id, $entry_date = '')
    {

        if ($entry_date == '') {
            $curr_date = date('Y-m-d');
        } else {
            $curr_date = date('Y-m-d', strtotime($entry_date));
        }

        $tripDetailsRes = $this->getTripDetailsByDeviceID($device_id, '', $entry_date);
        $tdCount = count($tripDetailsRes);

        if ($tdCount > 0) {
            $checkLiveTripQuery = "SELECT tripid,t.bus_id,driver_id,routeid,d.business_id,t.attender_user_id FROM `trip_status` t INNER JOIN `devices` d ON t.bus_id=d.bus_id WHERE d.rowid=$device_id AND DATE(t.tripdate)='$curr_date' AND t.trip_status='Live'";

            $liveTripRes = DB::select($checkLiveTripQuery);
            $ltCount = count($liveTripRes);
            if ($ltCount > 0) {
                $runningTripID = $liveTripRes[0]->tripid;
                $runningBusID = $liveTripRes[0]->bus_id;
                $runningRouteID = $liveTripRes[0]->routeid;
                for ($i = 0; $i < count($tripDetailsRes); $i++) {
                    if ($runningTripID == $tripDetailsRes[$i]->tripid && $runningBusID == $tripDetailsRes[$i]->bus_id && $runningRouteID == $tripDetailsRes[$i]->routeid) {
                        return $liveTripRes;
                    } else {

                        $updated = DB::table('trip_status')
                            ->where([
                                'bus_id', $runningBusID,
                                'tripid' => $runningTripID,
                                'routeid' => $runningRouteID,
                                'DATE(tripdate)' => $curr_date,
                                'trip_status' => 'Live',
                            ])
                            ->update([
                                'trip_status' => 'Closed',
                                'updated_date' => $curr_date,
                            ]);

                        if ($updated) {
                            return $tripDetailsRes;
                        } else {
                            return [];
                        }
                    }
                }

            } else {
                return $tripDetailsRes;
            }
        } else {
            return [];
        }

    }

    public function markAttendance(Request $req)
    {

        $response = array();
        $responseCode = 500;
        $rules = array(
            "user_id" => "required",
            "temperature" => "required",
            "latitude" => "required",
            "longitude" => "required",
            "date_time" => "required",
        );
        $validator = Validator::make($req->all(), $rules);

        if ($validator->fails()) {
            $response = array('status' => 'error', 'message' => 'Invalid parameters', "errors" => $validator->errors());
            $responseCode = 400;
        } else {
            if ($req->bearerToken()) {
                $token = $req->bearerToken();
                $deviceRes = $this->validateToken($token);
                $dCount = count($deviceRes);
                if ($dCount > 0) {
                    $user_id = $req->user_id;
                    $temperature = $req->temperature;
                    $latitude = $req->latitude;
                    $longitude = $req->longitude;
                    $created_date = $req->date_time;

                    $device_id = $deviceRes[0]->rowid;
                    $trips = $this->getTripDetails($device_id);
                    if (count($trips) > 0) {
                        $tripid = $trips[0]->tripid;
                        $driver_id = $trips[0]->driver_id;
                        $routeid = $trips[0]->routeid;
                        $bus_id = $trips[0]->bus_id;
                        $attender_user_id = $trips[0]->attender_user_id;
                        $business_id = $trips[0]->business_id;
                    } else {
                        $tripid = '';
                        $driver_id = '';
                        $routeid = '';
                        $bus_id = '';
                        $attender_user_id = '';
                        $business_id = '';
                    }

                    $today = date('Y-m-d');
                    // try {
                    //     $insertID = DB::table('trip_status')->insertGetId(
                    //         [
                    //             'tripid' => $trip_id,
                    //             'driver_id' => $driver_id,
                    //             'routeid' => $route_id,
                    //             'bus_id' => $bus_id,
                    //             'starttime' => $starttime,
                    //             'endtime' => $endtime,
                    //             'tripdate' => $trip_date,
                    //             'business_id' => $business_id,
                    //             'topspeed' => 0,
                    //             'completed_route' => '',
                    //             'attender_user_id' => 0,
                    //             'act_distance' => 0,
                    //         ]
                    //     );
                    //     if ($insertID > 0) {
                    //         $response = array('status' => 'success', 'message' => "Trip started", "trip_status_id" => $insertID);
                    //         $responseCode = 200;
                    //     } else {
                    //         $response = array('status' => 'error', "message" => "Error on Start Trip");
                    //         $responseCode = 200;
                    //     }
                    // } catch (QueryException | \Exception $e) {
                    //     $response = array('status' => 'error', "message" => "Error on Start Trip", "errors" => $e->getMessage());
                    //     $responseCode = 200;
                    // }
                } else {
                    $response = array('status' => 'error', 'message' => 'Invalid token');
                    $responseCode = 200;
                }
            } else {
                $response = array('status' => 'error', 'message' => 'unauthorized');
                $responseCode = 401;
            }
        }
        return response()->json($response, $responseCode);
    }

}
