<?
	class AudioMaxServerS406 extends IPSModule {

        public function Create() {
            // Diese Zeile nicht löschen.
            parent::Create();

			$this->RegisterPropertyInteger("ConnectionType", 10);
			$this->RegisterPropertyBoolean("SendKeepAlive", true);
			$this->RegisterPropertyInteger("SendKeepAliveInterval", 60);

			$this->RegisterPropertyBoolean("SendAcknowledge", false);
			$this->RegisterPropertyBoolean("DebugMode", false);
			$this->RegisterPropertyBoolean("RequestPower", false);

			$this->RegisterVariableBoolean("Power", "Main Power", "~Switch",0);
			$this->EnableAction("Power");

			$this->RegisterTimer("KeepAliveTimer", 0, 'AMS406_SendKeepAliveHeartbeat($_IPS[\'TARGET\']);');
			$this->RegisterTimer("SysInfoRequestTimer", 86400 * 1000, 'AMS406_GetSysInfo($_IPS[\'TARGET\']);');
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

		if($this->HasActiveParent()) {

			if($this->ReadPropertyBoolean("SendAcknowledge")) {
				$this->Send("SET,SYS,ECHO,1");
			}
			else {
				$this->Send("SET,SYS,ECHO,0");
			}

			if($this->ReadPropertyBoolean("DebugMode")) {
				$this->Send("SET,SYS,DEBUG,1");
			}
			else {
				$this->Send("SET,SYS,DEBUG,0");
			}

			if($this->ReadPropertyBoolean("RequestPower")) {
				$this->Send("SET,SYS,PUSHBUTTON,0");
			}
			else {
				$this->Send("SET,SYS,PUSHBUTTON,1");
			}
		}

	}


	public function RequestAction($Ident, $Value) {
		switch($Ident){
			case "Power":
				switch($Value) {
					case TRUE:
						$this->Send("SET,SYS,PWR,1");
						break;

					case FALSE:
						$this->Send("SET,SYS,PWR,0");
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

		$dataArray = explode(",", $DataString);
		$head = $dataArray[0];

		switch ($head) {
			case "EVT":
				$command = $dataArray[1];

				switch($command) {
					case "KAL":
						$this->RegisterVariableInteger("KAL","KAL","",1);
						SetValue($this->GetIDForIdent("KAL"),$dataArray[2]);
						break;
					default:
						break;
				}
				break;

			case "SYS":
				$command = $dataArray[1];

				switch($command) {
					case "HW":
						$this->RegisterVariableString("HW","HW","",1);
						SetValue($this->GetIDForIdent("HW"),$dataArray[2]);
						break;
					case "FW":
						$this->RegisterVariableString("FW","FW","",1);
						SetValue($this->GetIDForIdent("FW"),$dataArray[2]);
						break;

					default:
						break;
				}
				break;

			case "AUDIO":
						$Zone = $dataArray[1];
						$AudioType = $dataArray[2];
						$AudioValue = $dataArray[3];
						$this->SendDataToChildren(json_encode(Array("DataID" => "{14802A3D-6B6D-F54E-47D6-EFC5700D7558}", "Zone" => $Zone, "AudioType" => $AudioType, "AudioValue" => $AudioValue)));

						break;

			default:
				break;
		}
	}

	public function SendKeepAliveHeartbeat() {
		$this->Send("SET,SVR,KAL,1");
	}

	public function GetSysInfo() {
		$this->Send("GET,SYS,HW");
		$this->Send("GET,SYS,FW");
	}

	public function GetConfigurationForParent() {
		//Vordefiniertes Setup der seriellen Schnittstelle
		if ($this->ReadPropertyInteger("ConnectionType") == 10) {
			return "{\"BaudRate\": \"19200\", \"StopBits\": \"1\", \"DataBits\": \"8\", \"Parity\": \"None\"}";
		}
		else if ($this->ReadPropertyInteger("ConnectionType") == 20) {
			return "";
		}
		else {
			return "";
		}
	}


	}

?>