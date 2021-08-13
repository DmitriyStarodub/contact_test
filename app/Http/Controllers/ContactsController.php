<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ContactService;
use App\Jobs\ContactImport;
use App\Http\Traits\ApiResponser;
/**
 * @group Contacts
 *
 */
class ContactsController extends Controller
{
    use ApiResponser;
    protected $contactService;

    public function __construct(ContactService $contactService)
    {
        $this->contactService = $contactService;
    }

    /**
     * index Contacts
     *
     * @bodyParam timezone string. Exapmle Europe/Paris
     *
     */
    public function index(Request $request)
    {
        $contacts = $this->contactService->get($request);

        $contacts = $contacts->paginate();

        return $this->success(compact('contacts'),'');
    }

    /**
     * timezones Contacts
     *
     * @bodyParam timezone string. Exapmle Europe/Paris
     *
     */
    public function timezones(Request $request)
    {
        $timezones = $this->contactService->getTimezones($request);


        return $this->success(compact('timezones'),'');
    }

    /**
     * import Contacts
     *
     *
     */
    public function importData()
    {
        set_time_limit('5000');

        $file_name = public_path('MOCK_DATA.csv');

        $data_array = $this->contactService->csvToAr($file_name, ',');

        $data_array = $this->contactService->validateData($data_array);

        if(count($data_array) > 0){
            foreach($data_array as $data_contact){
                $this->dispatch(new ContactImport($this->contactService, $data_contact));
            }
        }

        return $this->success([],'The contacts has been successfully import');
    }
}
