<?php namespace Code8\EgoSystem\Components;

use Cms\Classes\ComponentBase;
use App;
use Mail;
use Url;
use Input;
use Request;
use Response;
use ApplicationException;
use Validator;
use ValidationException;


class FormContact extends ComponentBase
{
    // connexion api
    public $username;
	public $password;
	public $url;
	public $session_id;
	
    public function componentDetails()
    {
        return [
            'name'        => 'FormSuitecrm Component',
            'description' => 'Generate Lead for suiteCRM and send email'
        ];
    }
    
    public function initSettingsData()
    {
        $config = App::make('config');
        $this->username = $config->get('services.suitecrm.username', 'workflow');
        $this->password = $config->get('services.suitecrm.password', 'yq8FKw');
        $this->url = $config->get('services.suitecrm.url', 'http://circosphere.ccproduction.ch/ego/service/v4_1/rest.php');
    }

    public function defineProperties()
    {
        return [];
    }
    
    
    //function to make cURL request
    public function call($method, $parameters, $url)
    {
        ob_start();
        $curl_request = curl_init();

        curl_setopt($curl_request, CURLOPT_URL, $url);
        curl_setopt($curl_request, CURLOPT_POST, 1);
        curl_setopt($curl_request, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
        curl_setopt($curl_request, CURLOPT_HEADER, 1);
        curl_setopt($curl_request, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($curl_request, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl_request, CURLOPT_FOLLOWLOCATION, 0);

        $jsonEncodedData = json_encode($parameters);

        $post = array(
             "method" => $method,
             "input_type" => "JSON",
             "response_type" => "JSON",
             "rest_data" => $jsonEncodedData
        );

        curl_setopt($curl_request, CURLOPT_POSTFIELDS, $post);
        $result = curl_exec($curl_request);
        curl_close($curl_request);

        $result = explode("\r\n\r\n", $result, 2);
        $response = json_decode($result[1]);
        ob_end_flush();

        return $response;
    } // call

	/*
	 * Get session id for api suitecrm
	 */
	 public function getSuitecrm_SessionId($username, $password, $url)
	 {
	 	//login --------------------------------------------
		$login_parameters = array(
			 "user_auth" => array(
				  "user_name" => $username,
				  "password" => md5($password),
				  "version" => "1"
			 ),
			 "application_name" => "RestTest",
			 "name_value_list" => array(),
		);

		$login_result = $this->call("login", $login_parameters, $url);
		$session_id = $login_result->id;

		return $session_id;
	 }
    
    //~ public function onSend()
    //~ {
        //~ // Collect input
        //~ $first_name = post('first_name');
        //~ $email1 = post('email1');
        //~ $message = post('message');
		//~ echo "Enviando email a " . $first_name;
        //~ // Submit form
        //~ //$to = System\Models\MailSettings::get('sender_email');
        //~ //$params = compact('name','email');
        //~ //Mail::sendTo($to, 'temp.website::mail.newrequest', $params);
        //~ return true;
    //~ }
    
    
    public function onSend()
    {
        if (Request::ajax()) {

            try {
				$this->initSettingsData();				

                $data = post();
                
                // Quick Validation rules for E-mail, Name & Message
                if (!array_key_exists('first_name', $data)) {
                    $data['first_name'] = post('first_name');
                }
                if (!array_key_exists('last_name', $data)) {
                    $data['last_name'] = post('last_name');
                }
                if (!array_key_exists('email', $data)) {
                    $data['email'] = post('email');
                }
                if (!array_key_exists('lead_source', $data)) {
                    $data['lead_source'] = post('lead_source');
                }
                if (!array_key_exists('lead_description', $data)) {
                    $data['lead_description'] = post('lead_description');
                }
                
                // rules-------------------------
                /*
                $rules = [
                    'email' => 'required|email|between:4,255',
                ];

                $validation = Validator::make($data, $rules);
                if ($validation->fails()) {
                    throw new ValidationException($validation);
                }
				*/
				
 
				// SuiteCRM
				// check if email exist in suitecrm
				// login session
				 $this->session_id = $this->getSuitecrm_SessionId($this->username, $this->password, $this->url);
				 if (!$this->isContact($data['email'])){
					//create leads ------------------------------------- 
					$set_entry_parameters = array(
						 //session id
						 "session" => $this->session_id,

						 //The name of the module from which to retrieve records.
						 "module_name" => "Leads",

						 //Record attributes
						 "name_value_list" => array(
							  //to update a record, you will nee to pass in a record id as commented below
							  //array("name" => "id", "value" => "9b170af9-3080-e22b-fbc1-4fea74def88f"),
							  array("name" => "first_name", "value" => $data['first_name']),
							  array("name" => "last_name", "value" => $data['last_name']),
							  //~ array("name" => "phone_work", "value" => $data['email']),
							  array("name" => "email1", "value" => $data['email']),
							  array("name" => "lead_source", "value" => $data['lead_source']),
							  array("name" => "lead_description", "value" => $data['lead_description']),
						 ),
					);
					$set_entry_result = $this->call("set_entry", $set_entry_parameters, $this->url);
					$data['lead_url'] = $domain = strstr($this->url, '/service/', true) . '/index.php?module=Leads&action=DetailView&record='.$set_entry_result->id;
				 }
				
                 // Send Email
				//~ $to = '8temps@gmail.com';
				//~ $params = compact($data['first_name'],$data['last_name'],$data['email'],$data['lead_source'], $data['lead_description']);
				//~ Mail::sendTo($to, 'largo.suitecrm::emails.message', $params);
				
				Mail::send('code8.egosystem::emails.contact', $data, function($message) {
														$message->from('circosphere@ccproduction.ch', 'EGOSystem - Circosphère');
														$message->to('info@circosphere.ch', 'circosphère - ego system');
														$message->subject('[contact circosphere]: Message depuis le site internet');
													});
							
				//Mail::sendTo('8temps@gmail.com', $data['lead_description'], $params);
                    //~ Mail::send("8temps@gmail.com", $data, function ($message)  {
                        //~ $message->from('noreply@circosphere.ch', 'Circosphère');
                        //~ $message->to($data['email'], $data['first_name'] . ' '  . $data['last_name']);
                        //~ $message->subject($data['lead_source']);

                    //~ });

                    // Handle Erros
                    if (count(Mail::failures()) > 0) {
                   		$this->page["confirmation_text"] = 'Oops... a server error occurred and your email was not sent';
                        return ['error' => true];
                    } else {
                        // Mail sent
                        $this->page["confirmation_text"] = 'Votre message a été envoyé avec succès';
                        return ['error' => false];
                    }

					return ['error' => false];

            } catch (Exception $ex) {

                throw $ex;
            }
        }
    }
    
    /*
     * call to api for suitecrm and search the contact
     * If the contact exists then we return True
     */
    public function isContact($email){
		$status = false;
		//retrieve records ------------------------------------- 
		$get_entry_list_parameters = array(

			 //session id
			 'session' => $this->session_id,
			 //The name of the module from which to retrieve records
			 'module_name' => "Contacts",
			 //The SQL WHERE clause without the word "where".
			 'query' => "contacts.id in (SELECT eabr.bean_id 
					FROM email_addr_bean_rel eabr JOIN email_addresses ea ON (ea.id = eabr.email_address_id) 
					WHERE eabr.deleted=0 and ea.email_address LIKE '". $email ."%')",

			 //The SQL ORDER BY clause without the phrase "order by".
			 'order_by' => "",

			 //The record offset from which to start.
			 'offset' => "0",

			 //Optional. The list of fields to be returned in the results
			 'select_fields' => array(
				  'id',
				  'first_name',
				  'last_name',
			 ),

			 //The maximum number of results to return.
			 'max_results' => '1',

			 //To exclude deleted records
			 'deleted' => 0,

			 //If only records marked as favorites should be returned.
			 'Favorites' => false,

		);

		$get_entry_list_result = $this->call("get_entry_list", $get_entry_list_parameters, $this->url);
		if ($get_entry_list_result->total_count > 0 ) {
			$status =  true;
		} 
		
		return $status;
	} // function
	
	/*
	 * call to api for suitecrm and create Lead
	 * Lead source: Web site
	 * TODO: If email exist then DO NOT create Lead and add email to exist lead
	 */
    public function createLead(){
		
	} // function

	/*
	 * Attach email a module for suitecrm
	 * TODO: Add email to Contact or Leads
	 */
	 public function attachEmail($module, $id, $data ){
		 // Let's add a new Email record
		$set_entry_parameters = array(
				'session' => $sessionId,
				'module' => 'Emails',
				'name_value_list' => array(
					array('name' => 'name', 'value' => 'email body'),
					array('name' => 'from_addr', 'value' => 'tboland+from@gmail.com'),
					array('name' => 'to_addrs', 'value' => 'tboland+from@gmail.com'),
					array('name' => 'date_sent', 'value' => gmdate("Y-m-d H:i:s")),
					array('name' => 'description', 'value' => 'This is an email created from a REST web services call'),
					array('name' => 'description_html', 'value' => '<p>This is an email created from a REST web services call</p>'),
					array('name' => 'parent_type', 'value' => '<<RelatedModuleName>>'),
					array('name' => 'parent_id', 'value' => '<<RelatedModuleRecordId>>'),
					),
				);
		$set_entry_result = $this->call("set_entry", $set_entry_parameters, $this->url);
		
	 } // function
} // class
