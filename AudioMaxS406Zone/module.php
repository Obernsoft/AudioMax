<?
    class AudioMaxServerS406Zone extends IPSModule {


 	public function Create(){
	        //Never delete this line!
	        parent::Create();

		$this->RegisterPropertyInteger("Zone",1);

		$this->CreateVariableProfile("S406.AudioMaxVolume",1,"%",0,40,1,0,"Intensity");
		$this->CreateVariableProfile("S406.AudioMaxGain",1,"%",0,15,1,0,"Intensity");
		$this->CreateVariableProfile("S406.AudioMaxTone",1,"%",0,15,1,0,"Intensity");
		$this->CreateVariableProfile("S406.AudioMaxBalance",1,"%",0,15,1,0,"Intensity");
		$this->CreateVariableProfile("S406.AudioMaxInput",1,"",0,5,1,0,"");
		$this->CreateVariableProfile("S406.AudioMaxMute",0,"",0,1,1,0,"Power");

		$this->CreateVariableAssociation("S406.AudioMaxInput", 0, "Input 1", "Light" , 0x00FF00);
		$this->CreateVariableAssociation("S406.AudioMaxInput", 1, "Input 2", "Light" , 0x00FF00);
		$this->CreateVariableAssociation("S406.AudioMaxInput", 2, "Input 3", "Light" , 0x00FF00);
		$this->CreateVariableAssociation("S406.AudioMaxInput", 3, "Input 4", "Light" , 0x00FF00);
		$this->CreateVariableAssociation("S406.AudioMaxInput", 4, "Input 5", "Light" , 0x00FF00);
		$this->CreateVariableAssociation("S406.AudioMaxInput", 5, "Input 6", "Light" , 0x00FF00);

		$this->RegisterVariableBoolean("Power", "Room Power", "~Switch",1);
	        $this->EnableAction("Power");

		$this->RegisterVariableInteger("Volume","Volume","S406.AudioMaxVolume",2);
		$this->EnableAction("Volume");

                $this->RegisterVariableBoolean("Mute", "Mute", "~Switch",3);
                $this->EnableAction("Mute");

		$this->RegisterVariableInteger("Gain","Gain","S406.AudioMaxGain",5);
		$this->EnableAction("Gain");

		$this->RegisterVariableInteger("Balance","Balance","S406.AudioMaxBalance",6);
		$this->EnableAction("Balance");

		$this->RegisterVariableInteger("Bass","Bass","S406.AudioMaxTone",7);
		$this->EnableAction("Bass");

		$this->RegisterVariableInteger("Middle","Middle","S406.AudioMaxTone",8);
		$this->EnableAction("Middle");

		$this->RegisterVariableInteger("Treble","Treble","S406.AudioMaxTone",9);
		$this->EnableAction("Treble");

		$this->RegisterVariableInteger("Input","Input","S406.AudioMaxInput",4);
		$this->EnableAction("Input");

	}

	public function ApplyChanges()
	{
		//Never delete this line!
		parent::ApplyChanges();

		//Connect to available splitter or create a new one
		$this->ConnectParent("{DBA37017-532B-8269-B46F-6AF063589869}");

	}

        public function RequestAction($Ident, $Value) {

		$Zone = $this->ReadPropertyInteger("Zone");

                switch($Ident){
                        case "Power":
								$this->SetZonePower($Zone,$Value);
                                break;
                        case "Volume";
                                $this->SetZoneVolume($Zone,$Value);
                                break;
						case "Mute":
							$this->SetZoneMute($Zone,$Value);
							break;
						case "Input";
							$this->SetZoneInput($Zone,$Value);
							break;
                        case "Gain";
                                $this->SetZoneGain($Zone,$Value);
                                break;
                        case "Balance";
                                $this->SetZoneBalance($Zone,$Value);
                                break;
                        case "Bass";
                                $this->SetZoneBass($Zone,$Value);
                                break;
                        case "Middle";
                                $this->SetZoneMiddle($Zone,$Value);
                                break;
                        case "Treble";
                                $this->SetZoneTreble($Zone,$Value);
                                break;
			default:
				break;
                }
		SetValue($this->GetIDForIdent($Ident), $Value);
        }


	public function SetZonePower($Zone,$State) {

        	switch($State) {
                	case TRUE:
        	        	$this->Send("SET,AUDIO,".$Zone.",AMP,1");
                          	break;

			case FALSE:
                        	$this->Send("SET,AUDIO,".$Zone.",AMP,0");
	                	break;
                 }
	}

	public function SetZoneVolume($Zone,$Value) {

		$this->Send("SET,AUDIO,".$Zone.",VOL,".round(40-$Value));
	}


	public function SetZoneMute($Zone,$State) {

			switch($State) {
					case TRUE:
							$this->Send("SET,AUDIO,".$Zone.",MUT,1");
							break;

					case FALSE:
							$this->Send("SET,AUDIO,".$Zone.",MUT,0");
							break;
			 }
	}

	public function SetZoneInput($Zone,$Value) {

		$this->Send("SET,AUDIO,".$Zone.",INP,".$Value);
	}

	public function SetZoneGain($Zone,$Value) {

		$this->Send("SET,AUDIO,".$Zone.",GAI,".$Value);
	}

	public function SetZoneBalance($Zone,$Value) {

		$this->Send("SET,AUDIO,".$Zone.",BAL,".$Value);
	}

	public function SetZoneBass($Zone,$Value) {

		$this->Send("SET,AUDIO,".$Zone.",BAS,".$Value);
	}

	public function SetZoneMiddle($Zone,$Value) {

		$this->Send("SET,AUDIO,".$Zone.",MID,".$Value);
	}

	public function SetZoneTreble($Zone,$Value) {

		$this->Send("SET,AUDIO,".$Zone.",TRE,".$Value);
	}




	public function Send($Text)
	{
		$this->SendDataToParent(json_encode(Array("DataID" => "{2261C602-62C5-43DC-467F-7699B75E182E}", "Buffer" => $Text.chr(13))));
	}

	public function ReceiveData($JSONString)
	{
		$data = json_decode($JSONString);

		$Zone = intval($data->Zone, 10);
		$Type = $data->AudioType;
		$Value = intval($data->AudioValue,10);

		if($this->ReadPropertyInteger("Zone")==$Zone) {

			switch($Type){
				case "VOL":
					SetValue($this->GetIDForIdent("Volume"), $Value);
					break;

				case "INP":
					SetValue($this->GetIDForIdent("Input"), $Value);
					break;

				case "MUT":
					SetValue($this->GetIDForIdent("Mute"), $Value);
					break;

				case "GAI":
					SetValue($this->GetIDForIdent("Gain"), $Value);
					break;

				case "BAS":
					SetValue($this->GetIDForIdent("Bass"), $Value);
					break;

				case "MID":
					SetValue($this->GetIDForIdent("Middle"), $Value);
					break;

				case "TRE":
					SetValue($this->GetIDForIdent("Treble"), $Value);
					break;

				case "BAL":
					SetValue($this->GetIDForIdent("Balance"), $Value);
					break;

				case "AMP":
					break;

				default:
					IPS_LogMessage("AMS406Zone","Unknown SET Audiotype: ".$Type);
					break;
			}
		}

	}

	private function CreateVariableProfile($ProfileName, $ProfileType, $Suffix, $MinValue, $MaxValue, $StepSize, $Digits, $Icon) {
		    if (!IPS_VariableProfileExists($ProfileName)) {
			       IPS_CreateVariableProfile($ProfileName, $ProfileType);
			       IPS_SetVariableProfileText($ProfileName, "", $Suffix);
			       IPS_SetVariableProfileValues($ProfileName, $MinValue, $MaxValue, $StepSize);
			       IPS_SetVariableProfileDigits($ProfileName, $Digits);
			       IPS_SetVariableProfileIcon($ProfileName, $Icon);
		    }
	}


	private function CreateVariableAssociation($ProfileName, $Wert, $Name, $Icon , $color) {
				IPS_SetVariableProfileAssociation($ProfileName, $Wert, $Name, $Icon , $color);
	}


    }
?>