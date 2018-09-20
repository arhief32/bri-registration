<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\ResponseCode;
use Carbon\Carbon;

class RegistrationController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    
    public function selectCorporate($corporate_code)
    {
        config(['database.default' => 'paygate']);
        $result = DB::table('mappings')->select('*')
        ->where('corp_code', $corporate_code)
        ->first();

        return $result;
    }

    public function generateRegistrationNumber()
    {
        $str = "";
        $chars = "01234567890123456789012345678901234567890123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";	

        $length = 12;
        $size = strlen($chars);
        for($i=0; $i<$length; $i++) 
        {
	    	$str .= $chars[rand(0, $size-1)];
	    }

	    return $str;
    }

    function removeNamespaceFromXML($xml)
    {
        $toRemove = ['rap', 'turss', 'crim', 'cred', 'j', 'rap-code', 'evic'];
        $nameSpaceDefRegEx = '(\S+)=["\']?((?:.(?!["\']?\s+(?:\S+)=|[>"\']))+.)["\']?';

        foreach( $toRemove as $remove ) {
            $xml = str_replace('<' . $remove . ':', '<', $xml);
            $xml = str_replace('</' . $remove . ':', '</', $xml);
            $xml = str_replace($remove . ':commentText', 'commentText', $xml);
            $pattern = "/xmlns:{$remove}{$nameSpaceDefRegEx}/";
            $xml = preg_replace($pattern, '', $xml, 1);
        }

        return $xml;
    }

    public function giroCheck(Request $request)
    {
        $this->validate($request, [
            'account_number' => 'required',
        ]);
    
        $account_number = $request->account_number;

        $client = new \GuzzleHttp\Client();
        $getInquiry = $client->request('GET', env('BRIVA_SERVICE_URL').'/InquiryAccount?accountNo='.$account_number)->getBody();
        $inquiry = json_decode(json_encode(simplexml_load_string($this->removeNamespaceFromXML($getInquiry))), true);

        if($inquiry['accountStatus'] !== '0001')
        {
            return ResponseCode::giroNotExist();
        }
        else
        {
            return ResponseCode::giroExist($inquiry);
        }
    }

    public function accountRegister(Request $request)
    {
        $this->validate($request, [
            'corporate_code' => 'required',
            'nama_company' => 'required',
            'nama' => 'required',
            'telepon' => 'required',
            'email' => 'required',
            'nomor_rekening' => 'required',
            'nama_rekening' => 'required',

        ]);

        $nomor_registrasi = $this->generateRegistrationNumber();
        $corporate_code = $request->corporate_code;
        $nama_company = $request->nama_company;
        $nama = $request->nama;
        $telepon = $request->telepon;
        $email = $request->email;
        $nomor_rekening = $request->nomor_rekening;
        $nama_rekening = $request->nama_rekening;
        $tanggal_registrasi = Carbon::now('Asia/jakarta')->toDateTimeString();

        $insert_data = DB::table('registrasi')->insert([
            'nomor_registrasi' => $nomor_registrasi,
            'corporate_code' => $corporate_code,
            'nama_company' => $nama_company,
            'nama' => $nama,
            'telepon' => $telepon,
            'email' => $email,
            'nomor_rekening' => $nomor_rekening,
            'nama_rekening' => $nama_rekening,
            'tgl_registrasi' => $tanggal_registrasi
        ]);

        if($insert_data == true)
        {
            return ResponseCode::successInsertData();
        }
        else
        {
            return ResponseCode::failedInsertData();
        }
    }

    public function platformRegister(Request $request)
    {
        $corporate_code = $request->corporate_code;
        $nama_company = $request->nama_company;
        $nama = $request->nama;
        $telepon = $request->telepon;
        $email = $request->email;
        $nomor_rekening = $request->nomor_rekening;
        $nama_rekening = $request->nama_rekening;

        $platform_environment = $this->selectCorporate($corporate_code);

        $client = new \GuzzleHttp\Client;
        $postRegistration = $client->post($platform_environment->corp_url.'registration-company', [
            \GuzzleHttp\RequestOptions::JSON => [
                'nomor_rekening' => (string) $request->nomor_rekening,
                'nama_rekening' => (string) $request->nama_rekening,
                'nama' => (string) $request->nama,
                'telepon' => (string) $request->telepon,
                'email' => (string) $request->email,
                'nama_company' => (string) $request->nama_company,
                'corporate_code' => (string) $request->corporate_code,
            ]
        ])->getBody();
        $registration = json_decode($postRegistration);

        $postInsertBrivaCo = $client->post(env('BRIVA_SERVICE_URL').'/InsertBrivaCo', [
            'headers' => [
                'Content-Type' => 'application/x-www-form-urlencoded'
            ],
            'form_params' => [
                'corpCode' => (string)$corporate_code,
                'uniqueCode' => (string)$registration->message->corporate_code,
                'accountNo' => (string)$nomor_rekening,
                'description' => (string)$nama_company,
            ]
        ])->getBody();
        $insertBrivaCo = json_decode(json_encode(simplexml_load_string($this->removeNamespaceFromXML($postInsertBrivaCo))), true);;
        
        return response()->json($insertBrivaCo);
    }
}
