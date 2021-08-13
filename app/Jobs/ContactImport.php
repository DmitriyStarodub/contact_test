<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\ContactService;

class ContactImport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    protected $contactService;
    protected $contact_data;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(ContactService $contactService, array $contact_data)
    {
      $this->contactService = $contactService;
        $this->contact_data = $contact_data;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $contact_data = $this->contactService->validateData($this->contact_data);
        $path = 'www.example.org';

        if($contact_data){
            $contact = $this->contactService->store($contact_data);
            $this->contactService->generateImage($contact);
            $this->contactService->sendContact($contact, $path);
        }
    }
}
