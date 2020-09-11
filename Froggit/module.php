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
			foreach ($_POST as $key => $value) {
				//$this->SendDebug($key, $value , 0);
				if ($key == 'stationtype')
				{
					$this->RegisterVariableString($key, $this->Translate('Station Type'),'');
					if($this->GetValue($key) != $value) $this->SetValue($key, $value);
				}
				elseif ($key == 'model')
				{
					$this->RegisterVariableString($key, $this->Translate('Model'),'');
					if($this->GetValue($key) != $value) $this->SetValue($key, $value);
				}
				elseif ($key == 'winddir')
				{
					$this->RegisterVariableInteger($key."_int", $this->Translate('Wind Direction'),'~WindDirection');
					if($this->GetValue($key."_int") != $value) $this->SetValue($key."_int", intval($value));
					$this->RegisterVariableFloat($key."_txt", $this->Translate('Wind Direction'),'~WindDirection.Text');
					if($this->GetValue($key."_txt") != $value) $this->SetValue($key."_txt", floatval($value));
				}
				elseif ($key == 'windspeedmph')
				{
					$windspeed = round($value * 1.609344 , 2);
					$this->RegisterVariableFloat($key, $this->Translate('Wind Speed'),'~WindSpeed.kmh');
					if($this->GetValue($key) != $windspeed) $this->SetValue($key, $windspeed);
				}
				elseif ($key == 'maxdailygust')
				{
					$windspeed = round($value * 1.609344 , 2);
					$this->RegisterVariableFloat($key, $this->Translate('Day Wind Max'),'~WindSpeed.kmh');
					if($this->GetValue($key) != $windspeed) $this->SetValue($key, $windspeed);
				}
				elseif ($key == 'windgustmph')
				{
					$windspeed = round($value * 1.609344 , 2);
					$this->RegisterVariableFloat($key, $this->Translate('Wind Gust'),'~WindSpeed.kmh');
					if($this->GetValue($key) != $windspeed) $this->SetValue($key, $windspeed);
				}
				elseif (substr($key,0,4) == 'temp' )
				{
					$temp = round(($value - 32) / 1.8 ,2);
					$this->RegisterVariableFloat($key, $this->Translate('Temperature') . "_(" . $key . ")",'~Temperature');
					if($this->GetValue($key) != $temp) $this->SetValue($key, $temp);
				}
				elseif (substr($key,0,8) == 'humidity' )
				{
					$this->RegisterVariableInteger($key, $this->Translate('Humidity') . "_(" . $key . ")",'~Humidity');
					if($this->GetValue($key) != $value) $this->SetValue($key, intval($value));
				}
				elseif (substr($key,0,5) == 'barom' )
				{
					$pressure = round($value / 0.02952998751 , 2);
					$this->RegisterVariableFloat($key, $this->Translate('Air Pressure') . "_(" . $key . ")",'~AirPressure.F');
					if($this->GetValue($key) != $pressure) $this->SetValue($key, $pressure);
				}
				elseif (substr($key,-6) == 'rainin')
				{
					$rain = round($value * 25.4,2);
					$this->RegisterVariableFloat($key, $this->Translate($key),'~Rainfall');
					if($this->GetValue($key) != $rain) $this->SetValue($key,$rain);
				}
				elseif ($key == 'rainratein' )
				{
					$rain = round($value * 25.4,2);
					$this->RegisterVariableFloat($key, $this->Translate('Rain Rate'),'~Rainfall');
					if($this->GetValue($key) != $rain) $this->SetValue($key,$rain);
				}
				elseif ($key == 'solarradiation' )
				{
					$this->RegisterVariableInteger($key, $this->Translate('Solar Radiation'),'~Illumination');
					if($this->GetValue($key) != $value) $this->SetValue($key, intval($value * 126.7));
				}
				elseif ($key == 'uv' )
				{
					$this->RegisterVariableInteger($key, $this->Translate('UV Index'),'~UVIndex');
					if($this->GetValue($key) != $value) $this->SetValue($key, intval($value));
				}
				elseif ($key == 'dateutc' )
				{
					$time = str_replace("+"," ",$value);
					$this->RegisterVariableInteger($key, $this->Translate('Time'),'~UnixTimestamp');
					$this->SetValue($key, strtotime($time));
				}
				elseif (strpos($key, 'batt'))
				{
					$batt = boolval($value);
					$this->RegisterVariableBoolean($key, $this->Translate('Battery') . "_(" . $key . ")",'~Battery');
					if($this->GetValue($key) != $value) $this->SetValue($key, $value);
				}
				else
				{
					if (isset($key) && isset($value))
					{
						$this->SendDebug("Unsupportet Feature","Key: " . $key . "  Value: " . $value , 0);
						//$this->RegisterVariableString($key, $key,'');
						//if($this->GetValue($key) != $value) $this->SetValue($key, $value);
						}
				}
			}
		}
	}
