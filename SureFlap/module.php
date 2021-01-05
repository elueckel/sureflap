<?php

if (!defined('vtBoolean')) {
    define('vtBoolean', 0);
    define('vtInteger', 1);
    define('vtFloat', 2);
    define('vtString', 3);
    define('vtArray', 8);
    define('vtObject', 9);
}


	class SureFlap extends IPSModule
	
	{
		
		public function Create()
		{
			//Never delete this line!
			parent::Create();
			
			//Properties

			$this->RegisterPropertyString("UserName","");
			$this->RegisterPropertyString("Password","");
			$this->RegisterPropertyInteger("Timer", 0);
			$this->RegisterPropertyBoolean("Debug", 0);


			if (IPS_VariableProfileExists("SF.Weight") == false) {
				IPS_CreateVariableProfile("SF.Weight", 1);
				IPS_SetVariableProfileIcon("SF.Weight", "Cat");
				IPS_SetVariableProfileDigits("SF.Weight", 2);
				IPS_SetVariableProfileText("SF.Weight", "", " kg");
			}

			if (IPS_VariableProfileExists("SF.PetLocation") == false) {
				IPS_CreateVariableProfile("SF.PetLocation", 0);
				IPS_SetVariableProfileIcon("SF.PetLocation", "Cat");
				IPS_SetVariableProfileAssociation("SF.PetLocation", 1, $this->Translate("Inside"),"",-1);
				IPS_SetVariableProfileAssociation("SF.PetLocation", 0, $this->Translate("Outside"),"",-1);
			}
		
			//Component sets timer, but default is OFF
			$this->RegisterTimer("SureFlap Update",0,"SF_UpdateInformation(\$_IPS['TARGET']);");
					
		}
	
	public function ApplyChanges() {
			
		//Never delete this line!
		parent::ApplyChanges();
		
		$TimerMS = $this->ReadPropertyInteger("Timer") * 60000;
		$this->SetTimerInterval("SureFlap Update",$TimerMS);
					
	}
		
	public function AccessSureFlap() {

		$email_address = $this->ReadPropertyString("UserName");
		$password = $this->ReadPropertyString("Password");
		$device_id = (string)rand(100000,600000);
		
		if ($email_address != "" AND $password != "") {

			$json = json_encode(array("email_address" => $email_address, "password" => $password, "device_id" => $device_id));

			$endpoint = "https://app.api.surehub.io";
			
			$json = json_encode(array("email_address" => $email_address, "password" => $password, "device_id" => $device_id));
			$ch = curl_init($endpoint."/api/auth/login");
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
			curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json","Content-Length: ".strlen($json)));
			$result = json_decode(curl_exec($ch),true) or die("Curl Failed\n");
			if($result['data']['token']) {
				$token = $result['data']['token'];
				$this->SendDebug($this->Translate('AccessSureFlap'),$this->Translate('Token '.$token),0);
			}

			$this->SetBuffer("SureFlapToken",$token);
		}
		else {
			$this->SendDebug($this->Translate('AccessSureFlap'),$this->Translate('Login data is missing'),0);
			echo 'Login data is missing';
		}

	}

	public function Initialize() {
		
		$vpos=10;
		$this->AccessSureFlap();
		$token = $this->GetBuffer("SureFlapToken");

		//Create Household
		$this->RegisterVariableInteger('Household_ID', $this->Translate('Household ID'));
		$this->RegisterVariableString('Household_Name', $this->Translate('Household Name'));
		
		$ch = curl_init("https://app.api.surehub.io/api/household");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array("Authorization: Bearer $token"));
		$result = json_decode(curl_exec($ch),true) or die("Curl Failed\n");

		if($result['data']) {
			$this->SendDebug($this->Translate('Detecting Household'),'Household ID: '.$result['data'][0]['id'].' - Household Name: '.$result['data'][0]['name'],0);
			$Household_ID = $result['data'][0]['id'];
			$Household_Name = $result['data'][0]['name'];
			SetValue($this->GetIDForIdent('Household_ID'), $Household_ID);
			SetValue($this->GetIDForIdent('Household_Name'), $Household_Name);
		} else {
			$this->SendDebug($this->Translate('Detecting Household'),$this->Translate('No Household found - please check login data'),0);
		}

		//Create Devices

		$ch = curl_init("https://app.api.surehub.io/api/household/$Household_ID/device");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array("Authorization: Bearer $token"));
		$result = json_decode(curl_exec($ch),true) or die("Curl Failed\n");

		$i = 0;
		$vpos = 10;

		if($result['data']) {
			foreach($result['data'] as $device) {
				$i++;
				
				$this->RegisterVariableInteger($i.'Device_ID', $this->Translate('Device ').$i.$this->Translate(' ID'),'', $vpos++);				
				SetValue($this->GetIDForIdent($i.'Device_ID'), $device['id']);
				$this->SendDebug($this->Translate('Device '.$i.' ID'),$device['id'],0);

				if($device['serial_number']) {
					$this->RegisterVariableString($i.'Device_Serial', $this->Translate('Device ').$i.$this->Translate(' Serialnumber'),'', $vpos++);
					SetValue($this->GetIDForIdent($i.'Device_Serial'), $device['serial_number']);
					$this->SendDebug($this->Translate('Device '.$i.' Serialnumber'),$device['serial_number'],0);
				}

				switch($device['product_id']) {
					case 1:
						$this->RegisterVariableString($i.'Device_Name', $this->Translate('Device ').$i.$this->Translate('  Name '),'',$vpos++);
						$this->RegisterVariableString($i.'Device_Type', $this->Translate('Device ').$i.$this->Translate(' Type'),'', $vpos++);
						SetValue($this->GetIDForIdent($i.'Device_Name'), $device['name']);
						SetValue($this->GetIDForIdent($i.'Device_Type'), 'Hub');
						$this->SendDebug($this->Translate('Device '.$i.' Name'),$device['name'],0);		
						$this->SendDebug($this->Translate('Device '.$i.' Type'),'Hub',0);	
					break;
					case 2:
						$this->RegisterVariableString($i.'Device_Type', $this->Translate('Device ').$i.$this->Translate(' Type'),'', $vpos++);
						SetValue($this->GetIDForIdent($i.'Device_Type'), 'Repeater');
						$this->SendDebug($this->Translate('Device '.$i.' Type'),'Repeater',0);	
					break;
					case 3:
						$this->RegisterVariableString($i.'Device_Name', $this->Translate('Device '.$i.'  Name '),'',$vpos++);
						$this->RegisterVariableString($i.'Device_Type', $this->Translate('Device ').$i.$this->Translate(' Type'),'', $vpos++);
						SetValue($this->GetIDForIdent($i.'Device_Name'), $device['name']);	
						SetValue($this->GetIDForIdent($i.'Device_Type'), 'Pet Door Connect');
						$this->SendDebug($this->Translate('Device '.$i.' Name'),$device['name'],0);		
						$this->SendDebug($this->Translate('Device '.$i.' Type'),'Pet Door Connect',0);		
					break;
					case 4:
						$this->RegisterVariableString($i.'Device_Type', $this->Translate('Device ').$i.$this->Translate(' Type'),'', $vpos++);
						SetValue($this->GetIDForIdent($i.'Device_Type'), 'Pet Feeder Connect');
						$this->SendDebug($this->Translate('Device '.$i.' Type'),'Pet Feeder Connect',0);
					break;
					case 5:
						$this->RegisterVariableString($i.'Device_Type', $this->Translate('Device ').$i.$this->Translate(' Type'),'', $vpos++);
						SetValue($this->GetIDForIdent($i.'Device_Type'), 'Programmer');
						$this->SendDebug($this->Translate('Device '.$i.' Type'),'Programmer',0);
					break;
					case 6:
						$this->RegisterVariableString($i.'Device_Name', $this->Translate('Device '.$i.' Name '),'', $vpos++);
						$this->RegisterVariableString($i.'Device_Type', $this->Translate('Device ').$i.$this->Translate(' Type'),'', $vpos++);
						$this->RegisterVariableBoolean($i.'Device_Status', $this->Translate('Device ').$i.$this->Translate(' Status'),'', $vpos++);
						$this->RegisterVariableInteger($i.'Device_Mode', $this->Translate('Device ').$i.$this->Translate(' Mode'),'', $vpos++);
						SetValue($this->GetIDForIdent($i.'Device_Name'), $device['name']);	
						SetValue($this->GetIDForIdent($i.'Device_Type'), 'DualScan Cat Flap Connect');
						$this->SendDebug($this->Translate('Device '.$i.' Name'),$device['name'],0);		
						$this->SendDebug($this->Translate('Device '.$i.' Type'),'DualScan Cat Flap Connect',0);	
					break;
				}
				
			}
		}

		// Tiere Abfragen 

		$ch = curl_init("https://app.api.surehub.io/api/household/$Household_ID/pet");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array("Authorization: Bearer $token"));
		$result = json_decode(curl_exec($ch),true) or die("Curl Failed\n");
		$this->RegisterVariableString('Pets', $this->Translate('Pets'),'', 40);

		$i = 0;
		$vpos = 50;

		$pets = $result['data'];

		foreach ($pets as $pet) {
			$i++;
			
			$this->RegisterVariableString($i.'Pet_Species', $this->Translate('Pet ').$i.$this->Translate(' Species'),'', $vpos++);
			$this->RegisterVariableString($i.'Pet_Name', $this->Translate('Pet ').$i.$this->Translate(' Name'),'', $vpos++);
			$this->RegisterVariableString($i.'Pet_ID', $this->Translate('Pet ').$i.$this->Translate(' ID'),'', $vpos++);
			$this->RegisterVariableString($i.'Pet_Comment', $this->Translate('Pet ').$i.$this->Translate(' Comment'),'', $vpos++);
			$this->RegisterVariableBoolean($i.'Pet_Location', $this->Translate('Pet ').$i.$this->Translate(' Location'),'SF.PetLocation', $vpos++);
			//$this->RegisterVariableString($i.'Pet_DOB', $this->Translate('Pet ').$i.$this->Translate(' DOB'),'', $vpos++);
			//$this->RegisterVariableString($i.'Pet_Weight', $this->Translate('Pet ').$i.$this->Translate(' Weight'),'', $vpos++);
			//$this->RegisterVariableString($i.'Pet_Gender', $this->Translate('Pet ').$i.$this->Translate(' Gender'),'', $vpos++);

			if($pet['species_id']=="2") {
				SetValue($this->GetIDForIdent($i.'Pet_Species'), $this->Translate('Dog'));
				$this->SendDebug($this->Translate('Pet Species ').$i.$this->Translate(' Name'),'Dog',0);	
			} else {
				SetValue($this->GetIDForIdent($i.'Pet_Species'), $this->Translate('Cat'));
				$this->SendDebug($this->Translate('Pet Species ').$i.$this->Translate(' Name'),'Cat',0);
			}
			SetValue($this->GetIDForIdent($i.'Pet_Name'), $pet['name']);
			$this->SendDebug($this->Translate('Pet Name '),$pet['name'],0);
			SetValue($this->GetIDForIdent($i.'Pet_ID'), $pet['id']);
			$this->SendDebug($this->Translate('Pet ID '),$pet['id'],0);	
			SetValue($this->GetIDForIdent($i.'Pet_Comment'), $pet['comments']);
			$this->SendDebug($this->Translate('Pet Comment '),$pet['comments'],0);
			SetValue($this->GetIDForIdent('Pets'), $i);	

		}
		

	}

	//Function to trigger pet location update and curfew status - triggered by Timer

	public function UpdateInformation() {

		$this->GetPetLocation();
		$this->GetCurfewStatus();

	}


	public function GetPetLocation() {

		$this->AccessSureFlap();
		$token = $this->GetBuffer("SureFlapToken");
		$Pet_Count = GetValue($this->GetIDForIdent ('Pets'));
		
		$i=1;
		
		while ($i <= $Pet_Count) {
						
			if ($this->GetIDForIdent ($i.'Pet_ID') == false) {
				echo "Variable not found!";
				break;
			}
			else {
				$Pet_ID = GetValue($this->GetIDForIdent ($i.'Pet_ID'));
				$Name = GetValue($this->GetIDForIdent ($i.'Pet_Name'));

				$ch = curl_init("https://app.api.surehub.io/api/pet/$Pet_ID/position");
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($ch, CURLOPT_HTTPHEADER, array("Authorization: Bearer $token"));
				$result = json_decode(curl_exec($ch),true) or die("Curl Failed\n");

				if($result['data']) {
					if($result['data']['where']=="1") {
						$location = "Inside";
						SetValue($this->GetIDForIdent($i.'Pet_Location'), 1);
						$this->SendDebug($this->Translate('Pet Location '),$Name.'Inside',0);
					} else {
						SetValue($this->GetIDForIdent($i.'Pet_Location'), 0);
						$this->SendDebug($this->Translate('Pet Location '),$Name.'Outside',0);
					}				
				}

				$i++;
			}
			
		}
		
	}

	public function GetCurfewStatus() {

		$this->AccessSureFlap();

		$household = GetValue($this->GetIDForIdent ('Household_ID'));
		$token = $this->GetBuffer("SureFlapToken");
		//$flapname = GetValue($this->GetIDForIdent ('1Device_Name'));

		$ch = curl_init("https://app.api.surehub.io/api/household/$household/device?with[]=control");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array("Authorization: Bearer $token"));
		$result = json_decode(curl_exec($ch),true) or die("Curl Failed\n");
		//var_dump($result['data']);

		$i = 0; 

		if($result['data']) {
			foreach($result['data'] as $device) {
				$i++;
				if ($device["product_id"] == 6){
					$flapname = ($device["name"]);
					if($device['control']['locking']=="0") {
						SetValue($this->GetIDForIdent($i.'Device_Status'), 1);
						$this->SendDebug($this->Translate('Device Status'),'Enabled',0);
					} else {
						SetValue($this->GetIDForIdent($i.'Device_Status'), 0);
						$this->SendDebug($this->Translate('Device Status'),'Disabled',0);
					}
				}
				
			}
		}
	}

}