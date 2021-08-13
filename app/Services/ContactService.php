<?php

namespace App\Services;

use Illuminate\Http\Request;
use App\Models\Contact;
use Carbon\Carbon;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Http;

class ContactService
{
    public function get(Request $request)
    {
        $contacts = Contact::query();

        if($request->has('timezone')){
            Redis::set('timezone',$request->timezone);
        }

        return $contacts;
    }

    public function getTimezones(Request $request)
    {
        $timezones = \DB::table('contacts')->select('tz', \DB::raw('count(id) as total_contacts'));

        if($request->has('timezone')){
            if(is_array($request->timezone)){
                $timezones = $timezones->whereIn('tz', $request->timezone);
            }else{
                $timezones = $timezones->where('tz', $request->timezone);
            }
        }

        $timezones = $timezones->groupBy('tz')->get()->toArray();

        if(count($timezones) > 0){
            foreach($timezones as $key=>$timezone){
                $contacts = Contact::where('tz', $timezone->tz)->get();
                $timezone->contacts = $contacts;
                $timezones[$key] = $timezone;
            }
        }

        return $timezones;
    }

    public function store($data)
    {
        $contact = Contact::create($data);

        return $contact;
    }

    public function validateData(array $contact_data)
    {
        if(isset($contact_data['date'])){
            try{
                $date = Carbon::parse($contact_data['date']);
                $contact_data['date'] = $date->toDateString();
            }catch (\Exception $e){
                return false;
            }
        }

        if(isset($item['email'])){
            $host = explode('@',$contact_data['email']);
            $ip = isset($host[1])? gethostbyname('www.'.$host[1]):null;

            if($ip && $ip != 'www.'.$host[1]){
                $contact_data['ip'] = $ip;
            }else{
                return false;
            }
        }

        return $contact_data;
    }

    public function csvToAr($filename = '', $delimiter = ',')
    {
        if (!file_exists($filename) || !is_readable($filename))
            return false;

        $key = 'id,title,first_name,last_name,email,tz,date,time,note';
        $key = iconv("UTF-8", "UTF-8", $key);
        $header = explode($delimiter, $key);

        $data = array();
        if (($handle = fopen($filename, 'r')) !== false)
        {
            while (($row = $this->customfgetcsv($handle, 1000, $delimiter)) !== false)
            {
                if (!$header){
                    $header = $row;
                }
                else{
                    if (count($header) != count($row)) {
                        continue;
                    }
                    $item = array_combine($header,   $row);

                    $item['note'] = $item['note']?str_replace("\n", '', $item['note']):'';

                    $item['note'] = $item['note'] == 'NULL' || $item['note'] == 'null'? null: $item['note'];

                    $data[] = $item;
                }
            }
            fclose($handle);
        }

        return $data;
    }

    private function customfgetcsv(&$handle, $length, $separator = ';')
    {
        if (($buffer = fgets($handle, $length)) !== false) {
            $code = mb_detect_encoding($buffer);
            $code = $code !== FALSE? $code: "UTF-8";

            return explode($separator, iconv($code, "UTF-8", $buffer));
        }
        return false;
    }

    public function generateImage(Contact $contact, $type = 'image/jpeg')
    {
        $im = imagecreatetruecolor(200, 70);
        $bg = imagecolorallocate($im, 255, 255, 255);
        $text_color = imagecolorallocate($im, 255, 255, 255);
        imagestring($im, 5, 5, 5,  $contact->first_name.' '.$contact->last_name, $text_color);
        imagestring($im, 5, 5, 30,  $contact->email, $text_color);
        $uploadPath = public_path('/contacts');
        if (! file_exists($uploadPath)) {
            mkdir($uploadPath, 0777);
        }

        imagejpeg($im,  public_path('/contacts/'.$contact->id.'.jpeg'));
        imagedestroy($im);
    }

    public function sendContact(Contact $contact, $path)
    {
        $headers = [
            'Content-Type' => 'application/json',
        ];

        try{
            Http::withHeaders($headers)->post($path, $contact->toArray());
        }catch (\Exception $e){
            return false;
        }
    }
}