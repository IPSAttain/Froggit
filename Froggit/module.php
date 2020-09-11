<?php

declare(strict_types=1);

//Constants will be defined with IP-Symcon 5.0 and newer
if (!defined('IPS_KERNELMESSAGE')) {
    define('IPS_KERNELMESSAGE', 10100);
}
if (!defined('KR_READY')) {
    define('KR_READY', 10103);
}

	class Froggit extends IPSModule {


		public function Create()
		{
			//Never delete this line!
			parent::Create();

			//We need to call the RegisterHook function on Kernel READY
			$this->RegisterMessage(0, IPS_KERNELMESSAGE);
			//$this->ConnectParent("{8062CF2B-600E-41D6-AD4B-1BA66C32D6ED}");
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

			//Only call this in READY state. On startup the WebHook instance might not be available yet
			if (IPS_GetKernelRunlevel() == KR_READY) {
				$this->RegisterHook('/hook/froggit');
			}
		}

		public function MessageSink($TimeStamp, $SenderID, $Message, $Data)
		{
	
			//Never delete this line!
			parent::MessageSink($TimeStamp, $SenderID, $Message, $Data);
	
			if ($Message == IPS_KERNELMESSAGE && $Data[0] == KR_READY) {
				$this->RegisterHook('/hook/froggit');
			}
		}

		private function RegisterHook($WebHook)
		{
			$ids = IPS_GetInstanceListByModuleID('{015A6EB8-D6E5-4B93-B496-0D3F77AE9FE1}');
			if (count($ids) > 0) {
				$hooks = json_decode(IPS_GetProperty($ids[0], 'Hooks'), true);
				$found = false;
				foreach ($hooks as $index => $hook) {
					if ($hook['Hook'] == $WebHook) {
						if ($hook['TargetID'] == $this->InstanceID) {
							return;
						}
						$hooks[$index]['TargetID'] = $this->InstanceID;
						$found = true;
					}
				}
				if (!$found) {
					$hooks[] = ['Hook' => $WebHook, 'TargetID' => $this->InstanceID];
				}
				IPS_SetProperty($ids[0], 'Hooks', json_encode($hooks));
				IPS_ApplyChanges($ids[0]);
			}
		}
	
		/**
		 * This function will be called by the hook control. Visibility should be protected!
		 */
		protected function ProcessHookData()
		{
			$this->SendDebug('WebHook', 'Array POST: ' . print_r($_POST, true), 0);
			IPS_LogMessage("WebHook POST", print_r($_POST, true));
			//$datasets = $_POST;
			// alle nicht durch ; terminierten Datensätze ausgeben
			foreach $_POST as $array {
				// ($i = 1; $i < count($_POST) - 1; $i++) {
				//$this->SendDebug("Received", $datasets[$i] , 0);
				//$array[0] = $_POST[0];
				//$array[1] = $_POST[1];
				$this->SendDebug($array[0], $array[1] , 0);
				if ($array[0] == 'stationtype')
				{
					$this->RegisterVariableString($array[0], $this->Translate('Station Type'),'');
					if($this->GetValue($array[0]) != $array[1]) $this->SetValue($array[0], $array[1]);
				}
				elseif ($array[0] == 'winddir')
				{
					$this->RegisterVariableInteger($array[0]."_int", $this->Translate('Wind Direction'),'~WindDirection');
					if($this->GetValue($array[0]."_int") != $array[1]) $this->SetValue($array[0]."_int", intval($array[1]));
					$this->RegisterVariableFloat($array[0]."_txt", $this->Translate('Wind Direction'),'~WindDirection.Text');
					if($this->GetValue($array[0]."_txt") != $array[1]) $this->SetValue($array[0]."_txt", floatval($array[1]));
				}
				elseif ($array[0] == 'windspeedmph')
				{
					$windspeed = round($array[1] * 1.609344 , 2);
					$this->RegisterVariableFloat($array[0], $this->Translate('Wind Speed'),'~WindSpeed.kmh');
					if($this->GetValue($array[0]) != $windspeed) $this->SetValue($array[0], $windspeed);
				}
				elseif ($array[0] == 'maxdailygust')
				{
					$windspeed = round($array[1] * 1.609344 , 2);
					$this->RegisterVariableFloat($array[0], $this->Translate('Day Wind Max'),'~WindSpeed.kmh');
					if($this->GetValue($array[0]) != $windspeed) $this->SetValue($array[0], $windspeed);
				}
				elseif ($array[0] == 'windgustmph')
				{
					$windspeed = round($array[1] * 1.609344 , 2);
					$this->RegisterVariableFloat($array[0], $this->Translate('Wind Gust'),'~WindSpeed.kmh');
					if($this->GetValue($array[0]) != $windspeed) $this->SetValue($array[0], $windspeed);
				}
				elseif (substr($array[0],0,4) == 'temp' )
				{
					$temp = round(($array[1] - 32) / 1.8 ,2);
					$this->RegisterVariableFloat($array[0], $this->Translate('Temperature') . "_(" . $array[0] . ")",'~Temperature');
					if($this->GetValue($array[0]) != $temp) $this->SetValue($array[0], $temp);
				}
				elseif (substr($array[0],0,8) == 'humidity' )
				{
					$this->RegisterVariableInteger($array[0], $this->Translate('Humidity') . "_(" . $array[0] . ")",'~Humidity');
					if($this->GetValue($array[0]) != $array[1]) $this->SetValue($array[0], intval($array[1]));
				}
				elseif (substr($array[0],0,5) == 'barom' )
				{
					$pressure = round($array[1] / 0.02952998751 , 2);
					$this->RegisterVariableFloat($array[0], $this->Translate('Air Pressure') . "_(" . $array[0] . ")",'~AirPressure.F');
					if($this->GetValue($array[0]) != $pressure) $this->SetValue($array[0], $pressure);
				}
				elseif (substr($array[0],-6) == 'rainin')
				{
					$rain = round($array[1] * 25.4,2);
					$this->RegisterVariableFloat($array[0], $this->Translate($array[0]),'~Rainfall');
					if($this->GetValue($array[0]) != $rain) $this->SetValue($array[0],$rain);
				}
				elseif ($array[0] == 'rainratein' )
				{
					$rain = round($array[1] * 25.4,2);
					$this->RegisterVariableFloat($array[0], $this->Translate('Rain Rate'),'~Rainfall');
					if($this->GetValue($array[0]) != $rain) $this->SetValue($array[0],$rain);
				}
				elseif ($array[0] == 'solarradiation' )
				{
					$this->RegisterVariableInteger($array[0], $this->Translate('Solar Radiation'),'~Illumination');
					if($this->GetValue($array[0]) != $array[1]) $this->SetValue($array[0], intval($array[1] * 10,7639));
				}
				elseif ($array[0] == 'uv' )
				{
					$this->RegisterVariableInteger($array[0], $this->Translate('UV Index'),'~UVIndex');
					if($this->GetValue($array[0]) != $array[1]) $this->SetValue($array[0], intval($array[1]));
				}
				elseif ($array[0] == 'dateutc' )
				{
					$time = str_replace("+"," ",$array[1]);
					$this->RegisterVariableInteger($array[0], $this->Translate('Time'),'~UnixTimestamp');
					$this->SetValue($array[0], strtotime($time));
				}
				//else
				{
					if (isset($array[0]) && isset($array[1]))
					{
						$this->RegisterVariableString($array[0], $array[0],'');
						if($this->GetValue($array[0]) != $array[1]) $this->SetValue($array[0], $array[1]);
					}
				}
			}
		}
	}
/*
	public function ReceiveData($JSONString)
		{
			$data = json_decode($JSONString);
			$incomming = utf8_decode($data->Buffer);
			//IPS_LogMessage("Device RECV",$data->ClientIP ." +  Port ". $data->ClientPort);
			if (strpos($incomming, '&')) {
				// $data in durch & separierte Datensätze zerlegen
				$datasets = explode('&', $incomming);
				// alle nicht durch ; terminierten Datensätze ausgeben
				for ($i = 1; $i < count($datasets) - 1; $i++) {
					//$this->SendDebug("Received", $datasets[$i] , 0);
					$array = explode('=', $datasets[$i]);
					$this->SendDebug($array[0], $array[1] , 0);
					if ($array[0] == 'stationtype')
					{
						$this->RegisterVariableString($array[0], $this->Translate('Station Type'),'');
						if($this->GetValue($array[0]) != $array[1]) $this->SetValue($array[0], $array[1]);
					}
					elseif ($array[0] == 'winddir')
					{
						$this->RegisterVariableInteger($array[0]."_int", $this->Translate('Wind Direction'),'~WindDirection');
						if($this->GetValue($array[0]."_int") != $array[1]) $this->SetValue($array[0]."_int", intval($array[1]));
						$this->RegisterVariableFloat($array[0]."_txt", $this->Translate('Wind Direction'),'~WindDirection.Text');
						if($this->GetValue($array[0]."_txt") != $array[1]) $this->SetValue($array[0]."_txt", floatval($array[1]));
					}
					elseif ($array[0] == 'windspeedmph')
					{
						$windspeed = round($array[1] * 1.609344 , 2);
						$this->RegisterVariableFloat($array[0], $this->Translate('Wind Speed'),'~WindSpeed.kmh');
						if($this->GetValue($array[0]) != $windspeed) $this->SetValue($array[0], $windspeed);
					}
					elseif ($array[0] == 'maxdailygust')
					{
						$windspeed = round($array[1] * 1.609344 , 2);
						$this->RegisterVariableFloat($array[0], $this->Translate('Day Wind Max'),'~WindSpeed.kmh');
						if($this->GetValue($array[0]) != $windspeed) $this->SetValue($array[0], $windspeed);
					}
					elseif ($array[0] == 'windgustmph')
					{
						$windspeed = round($array[1] * 1.609344 , 2);
						$this->RegisterVariableFloat($array[0], $this->Translate('Wind Gust'),'~WindSpeed.kmh');
						if($this->GetValue($array[0]) != $windspeed) $this->SetValue($array[0], $windspeed);
					}
					elseif (substr($array[0],0,4) == 'temp' )
					{
						$temp = round(($array[1] - 32) / 1.8 ,2);
						$this->RegisterVariableFloat($array[0], $this->Translate('Temperature') . "_(" . $array[0] . ")",'~Temperature');
						if($this->GetValue($array[0]) != $temp) $this->SetValue($array[0], $temp);
					}
					elseif (substr($array[0],0,8) == 'humidity' )
					{
						$this->RegisterVariableInteger($array[0], $this->Translate('Humidity') . "_(" . $array[0] . ")",'~Humidity');
						if($this->GetValue($array[0]) != $array[1]) $this->SetValue($array[0], intval($array[1]));
					}
					elseif (substr($array[0],0,5) == 'barom' )
					{
						$pressure = round($array[1] / 0.02952998751 , 2);
						$this->RegisterVariableFloat($array[0], $this->Translate('Air Pressure') . "_(" . $array[0] . ")",'~AirPressure.F');
						if($this->GetValue($array[0]) != $pressure) $this->SetValue($array[0], $pressure);
					}
					elseif (substr($array[0],-6) == 'rainin')
					{
						$rain = round($array[1] * 25.4,2);
						$this->RegisterVariableFloat($array[0], $this->Translate($array[0]),'~Rainfall');
						if($this->GetValue($array[0]) != $rain) $this->SetValue($array[0],$rain);
					}
					elseif ($array[0] == 'rainratein' )
					{
						$rain = round($array[1] * 25.4,2);
						$this->RegisterVariableFloat($array[0], $this->Translate('Rain Rate'),'~Rainfall');
						if($this->GetValue($array[0]) != $rain) $this->SetValue($array[0],$rain);
					}
					elseif ($array[0] == 'solarradiation' )
					{
						$this->RegisterVariableInteger($array[0], $this->Translate('Solar Radiation'),'~Illumination');
						if($this->GetValue($array[0]) != $array[1]) $this->SetValue($array[0], intval($array[1] * 10,7639));
					}
					elseif ($array[0] == 'uv' )
					{
						$this->RegisterVariableInteger($array[0], $this->Translate('UV Index'),'~UVIndex');
						if($this->GetValue($array[0]) != $array[1]) $this->SetValue($array[0], intval($array[1]));
					}
					elseif ($array[0] == 'dateutc' )
					{
						$time = str_replace("+"," ",$array[1]);
						$this->RegisterVariableInteger($array[0], $this->Translate('Time'),'~UnixTimestamp');
						$this->SetValue($array[0], strtotime($time));
					}
					//else
					{
						if (isset($array[0]) && isset($array[1]))
						{
							$this->RegisterVariableString($array[0], $array[0],'');
							if($this->GetValue($array[0]) != $array[1]) $this->SetValue($array[0], $array[1]);
						}
					}
					
				}
			}
		}
	} //ende
	*/ 
