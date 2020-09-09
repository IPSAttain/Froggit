<?php
	class Froggit extends IPSModule {

		public function Create()
		{
			//Never delete this line!
			parent::Create();

			$this->ConnectParent("{8062CF2B-600E-41D6-AD4B-1BA66C32D6ED}");
		}

		public function Destroy()
		{
			//Never delete this line!
			parent::Destroy();
		}

		public function ApplyChanges()
		{
			//Never delete this line!
			parent::ApplyChanges();
		}

		public function Send(string $Text, string $ClientIP, int $ClientPort)
		{
			$this->SendDataToParent(json_encode(Array("DataID" => "{C8792760-65CF-4C53-B5C7-A30FCC84FEFE}", "ClientIP" => $ClientIP, "ClientPort" => $ClientPort, "Buffer" => $Text)));
		}

		public function ReceiveData($JSONString)
		{
			$data = json_decode($JSONString);
			$incomming = utf8_decode($data->Buffer);
			IPS_LogMessage("Device RECV",$data->ClientIP ." +  Port ". $data->ClientPort);
			if (strpos($incomming, '&')) {
				// $data in durch & separierte Datensätze zerlegen
				$datasets = explode('&', $incomming);
				// alle nicht durch ; terminierten Datensätze ausgeben
				for ($i = 0; $i < count($datasets) - 1; $i++) {
					//$this->SendDebug("Received", $datasets[$i] , 0);
					$array = explode('=', $datasets[$i]);
					$this->SendDebug($array[0], $array[1] , 0);
					if ($array[0] == 'stationtype')
					{
						$this->RegisterVariableString($array[0], $this->Translate('Station Type'),'');
						if($this->GetValue($array[0]) != $array[1]) $this->SetValue($array[0], $array[1]);
					}
					if ($array[0] == 'winddir')
					{
						$this->RegisterVariableInteger($array[0]."_int", $this->Translate('Wind Direction'),'~WindDirection');
						if($this->GetValue($array[0]."_int") != $array[1]) $this->SetValue($array[0]."_int", intval($array[1]));
						$this->RegisterVariableFloat($array[0]."_txt", $this->Translate('Wind Direction'),'~WindDirection.Text');
						if($this->GetValue($array[0]."_txt") != $array[1]) $this->SetValue($array[0]."_txt", floatval($array[1]));
					}
				}
			}
		}

	}