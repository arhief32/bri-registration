<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\ResponseCode;

class RegistrationController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
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

    public function giroRegister(Request $request)
    {
        $account_number = $request->accountNo;

        $client = new \GuzzleHttp\Client();
        $getInquiry = $client->request('GET', '10.35.65.152:9099/Service.asmx/InquiryAccount?accountNo='.$account_number)->getBody();
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
        
    }
}
