<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Rest;
use Illuminate\Support\Facades\DB;

class RestAPIController extends Controller
{
    function getOTP(Request $req){

        // $rest = new Rest;
        // $rest->mobile = $req->mobile;
        // $rest->code = $req->code;
        $token = $req->bearerToken();

        $results = DB::select('select * from users where rowid = ?', [1]);
        // print_r($results);
        return ["success"=>"OTP sent successfully","results"=>$results,"token"=>$token];
    }
}
