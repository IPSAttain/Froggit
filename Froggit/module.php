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
			IPS_LogMessage("Device RECV", utf8_decode($data->Buffer . " - " + $data->ClientIP + " - " . $data->ClientPort));
			if (strpos($data, '&')) {
				// $data in durch & separierte Datensätze zerlegen
				$datasets = explode('&', $data);
				$wsvar = array();
		 
				// alle nicht durch ; terminierten Datensätze ausgeben
				for ($i = 0; $i < count($datasets) - 1; $i++) {
					$this->SendDebug("Received", $datasets[$i] , 0);
					//$wsvar = explode('=', $datasets[$i]);
					$array = explode('=', $datasets[$i]);
					$wsvar[$array[0]] = $array[1];
				}
			}
		}

	}