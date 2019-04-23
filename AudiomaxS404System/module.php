<?
	class AudioMaxServerS404 extends IPSModule {

        public function Create() {
            // Diese Zeile nicht löschen.
            parent::Create();

		$this->RegisterPropertyInteger("ConnectionType", 10);
		//$this->RegisterPropertyString("DataOutputType", "AUDIO");
		//$this->RegisterPropertyInteger("AudioMaxID", 1);
		$this->RegisterPropertyBoolean("SendKeepAlive", false);
		$this->RegisterPropertyInteger("SendKeepAliveInterval", 60);

		$this->RegisterPropertyBoolean("SendAcknowledge", false);
		$this->RegisterPropertyBoolean("DebugMode", false);
		$this->RegisterPropertyBoolean("RequestPower", false);

		//$this->RegisterPropertyBoolean("ReceiveKeepAlive", false);
		//$this->RegisterPropertyInteger("ReceiveKeepAliveInterval", 0);

        	$this->RegisterPropertyString("Serialport", "{6DC3D946-0D31-450F-A8C6-C42DB8D7D4F1}"); // SerialPort

		$this->RegisterVariableBoolean("Power", "Main Power", "~Switch",0);
	        $this->EnableAction("Power");

		$this->RegisterTimer("KeepAliveTimer", 0, 'AMS404_SendKeepAliveHeartbeat($_IPS[\'TARGET\']);');
		//$this->RegisterTimer("SysInfoRequestTimer", 86400 * 1000, 'AMS404_GetSysInfo($_IPS[\'TARGET\']);');

        }


       	public function ApplyChanges(){
		//Never delete this line!
		parent::ApplyChanges();

		//Set Parent
		switch($this->ReadPropertyInteger("ConnectionType")) {
			case 10:
				$this->ForceParent("{6DC3D946-0D31-450F-A8C6-C42DB8D7D4F1}"); //SerialPort
				break;

			case 20:
				$this->ForceParent("{3CFF0FD9-E306-41DB-9B5A-9D06D38576C3}"); //ClientSocket
				break;

			default:
				throw new Exception("Invalid ConnectionType for Parent");
				break;
		}

		if($this->ReadPropertyBoolean("SendKeepAlive")) {
			$this->SetTimerInterval("KeepAliveTimer",$this->ReadPropertyInteger("SendKeepAliveInterval")*1000);
		}
		else {
			$this->SetTimerInterval("KeepAliveTimer",0);
		}
                if($this->ReadPropertyBoolean("SendAcknowledge")) {
                	$this->Send("EVT;SRV;MOD;0;0");
                }
                else {
                	$this->Send("EVT;SRV;MOD;0;1");
                }

                if($this->ReadPropertyBoolean("DebugMode")) {
                	$this->Send("EVT;SRV;MOD;1;0");
                }
                else {
                	$this->Send("EVT;SRV;MOD;1;1");
                }

                if($this->ReadPropertyBoolean("RequestPower")) {
                	$this->Send("EVT;SRV;MOD;2;0");
                }
                else {
                	$this->Send("EVT;SRV;MOD;2;1");
                }


	}


	public function RequestAction($Ident, $Value) {
		switch($Ident){
			case "Power":
				//$this->SendDebug(("DBG: send: power" .$Ident), $Value,0);
				switch($Value) {
					case TRUE:
						$this->Send("SET;SVR;PWR;1");
						break;

					case FALSE:
						$this->Send("SET;SVR;PWR;0");
						break;
				}
				SetValue($this->GetIDForIdent("Power"), $Value);
				break;
		}
	}

        public function Send($Text)
        {
                $Text = $Text.chr(13);
                $this->SendDataToParent(json_encode(Array("DataID" => "{79827379-F36E-4ADA-8A95-5F8D1DC92FA9}", "Buffer" => $Text)));
        }

        public function ForwardData($JSONString)
	{
		$data = json_decode($JSONString);
		$this->SendDataToParent(json_encode(Array("DataID" => "{79827379-F36E-4ADA-8A95-5F8D1DC92FA9}", "Buffer" => $data->Buffer)));
	}


	public function ReceiveData($JSONString)
	{
		$data = json_decode($JSONString);

		//Kontrollieren ob Buffer leer ist.
		$bufferData = $this->GetBuffer("DataBuffer");
		$bufferData .= $data->Buffer;
		$bufferParts = explode("\r\n", $bufferData);
		//Letzten Eintrag nicht auswerten, da dieser nicht vollständig ist.
		if(sizeof($bufferParts) > 1) {
			for($i=0; $i<sizeof($bufferParts)-1; $i++) {
				$this->AnalyseData($bufferParts[$i]);
			}
		}
		$bufferData = $bufferParts[sizeof($bufferParts)-1];
		//Übriggebliebene Daten auf den Buffer schreiben
		$this->SetBuffer("DataBuffer", $bufferData);

	}


	private function AnalyseData($DataString) {

		$dataArray = explode(";", $DataString);
		$head = $dataArray[0];

		switch ($head) {
			case "EVT":
				$command = $dataArray[2];

				switch($command) {
					case "KAL":
						$this->RegisterVariableInteger("KAL","KAL","",1);
						SetValue($this->GetIDForIdent("KAL"),$dataArray[3]);
						break;
					case "HAR":
                                                $this->RegisterVariableString("HAR","HAR","",2);
                                                SetValue($this->GetIDForIdent("HAR"),$dataArray[3]);
                                                break;
					case "VER":
                                                $this->RegisterVariableString("VER","VER","",3);
                                                SetValue($this->GetIDForIdent("VER"),$dataArray[3]);
                                                break;
					case "AUD":
						$Zone = $dataArray[3];
						$AudioType = $dataArray[4];
						$AudioValue = $dataArray[5];
						$this->SendDataToChildren(json_encode(Array("DataID" => "{169B085F-A7E8-A66D-70B7-EF9217217362}", "Zone" => $Zone, "AudioType" => $AudioType, "AudioValue" => $AudioValue)));

						break;
					default:
						break;
				}


				break;

			case "SYS":
				break;

			default:
				break;
		}
	}

	public function ConfigureDevice(){

		//SendKeepAlive ein-/ausschalten
//		if ($this->ReadPropertyBoolean("SendKeepAlive")) {
//			$KeepAliveInterval = $this->ReadPropertyInteger("SendKeepAliveInterval");
			//Checken ob der Intervallwert zwischen 60-240 liegt
//			if ($KeepAliveInterval < 60) {
//				$KeepAliveInterval = 60;
//			} else if ($KeepAliveInterval > 240) {
//				$KeepAliveInterval = 240;
//			}
//			$this->ForwardData("SET;SVR;MOD;0;1");

//		}
//		else {
//			$this->Send("SET;SVR;MOD;0;0");
//		}

	}

	public function SendKeepAliveHeartbeat() {
		$this->Send("SET;SVR;KAL;0");
	}

	public function GetSysInfo() {
		// to Do
	}


	public function GetConfigurationForParent() {
		//Vordefiniertes Setup der seriellen Schnittstelle
		if ($this->ReadPropertyInteger("ConnectionType") == 10) {
			return "{\"BaudRate\": \"19200\", \"StopBits\": \"1\", \"DataBits\": \"8\", \"Parity\": \"None\"}";
		}
		else if ($this->ReadPropertyInteger("ConnectionType") == 20) {
			return "{\"Port\": \"5000\"}";
		}
		else {
			return "";
		}
	}



    }
?>
