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
			$this->RegisterPropertyInteger("Temperature", 0);
			$this->RegisterPropertyInteger("Rain", 0);
			$this->RegisterPropertyInteger("Wind", 0);
			$this->RegisterPropertyInteger("Light", 0);
			$this->RegisterPropertyInteger("Pressure", 0);
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
					if($this->ReadPropertyInteger("Wind") == 0) { // km/h
						$windspeed = round($value * 1.609344 , 2);
						$profile = '~WindSpeed.kmh';
					} elseif ($this->ReadPropertyInteger("Wind") == 1) { // m/s
						$windspeed = round($value * 1.609344 / 3.6 , 2);
						$profile = '~WindSpeed.ms';
					} else { //mph
						$windspeed = round($value,2);
						$this->CreateVarProfileFloat('Froggit.Wind.mph','WindSpeed',' mph');
						$profile = 'Froggit.Wind.mph';
					}
					$this->RegisterVariableFloat($key, $this->Translate('Wind Speed'),$profile);
					if($this->GetValue($key) != $windspeed) $this->SetValue($key, $windspeed);
				}
				elseif ($key == 'maxdailygust')
				{
					if($this->ReadPropertyInteger("Wind") == 0) { // km/h
						$windspeed = round($value * 1.609344 , 2);
						$profile = '~WindSpeed.kmh';
					} elseif ($this->ReadPropertyInteger("Wind") == 1) { // m/s
						$windspeed = round($value * 1.609344 / 3.6 , 2);
						$profile = '~WindSpeed.ms';
					} else { //mph
						$windspeed = round($value,2);
						$profile = 'Wind.Froggit.mph';
						$this->CreateVarProfileFloat('Froggit.Wind.mph','WindSpeed',' mph');
						$profile = 'Froggit.Wind.mph';
					}
					$this->RegisterVariableFloat($key, $this->Translate('Day Wind Max'),$profile);
					if($this->GetValue($key) != $windspeed) $this->SetValue($key, $windspeed);
				}
				elseif ($key == 'windgustmph')
				{
					if($this->ReadPropertyInteger("Wind") == 0) { // km/h
						$windspeed = round($value * 1.609344 , 2);
						$profile = '~WindSpeed.kmh';
					} elseif ($this->ReadPropertyInteger("Wind") == 1) { // m/s
						$windspeed = round($value * 1.609344 / 3.6 , 2);
						$profile = '~WindSpeed.ms';
					} else { //mph
						$windspeed = round($value,2);
						$this->CreateVarProfileFloat('Froggit.Wind.mph','WindSpeed',' mph');
						$profile = 'Froggit.Wind.mph';
					}
					$this->RegisterVariableFloat($key, $this->Translate('Wind Gust'),$profile);
					if($this->GetValue($key) != $windspeed) $this->SetValue($key, $windspeed);
				}
				elseif (substr($key,0,4) == 'temp' )
				{
					if($this->ReadPropertyInteger("Temperature") == 0) { // °C
						$temp = round(($value - 32) / 1.8 ,2);
						$profile = '~Temperature';
					} else { // °F
						$profile = '~Temperature.Fahrenheit';
						$temp = $value;
					}
					$this->RegisterVariableFloat($key, $this->Translate('Temperature') . "_(" . $key . ")",$profile);
					if($this->GetValue($key) != $temp) $this->SetValue($key, $temp);
				}
				elseif (substr($key,0,8) == 'humidity' )
				{
					$this->RegisterVariableInteger($key, $this->Translate('Humidity') . "_(" . $key . ")",'~Humidity');
					if($this->GetValue($key) != $value) $this->SetValue($key, intval($value));
				}
				elseif (substr($key,0,5) == 'barom' )
				{
					if($this->ReadPropertyInteger("Pressure") == 0) { // hPa
						$pressure = round($value / 0.02952998751 , 1);
						$profile = '~AirPressure.F';
					} elseif ($this->ReadPropertyInteger("Pressure") == 1) { // inHg
						$pressure = round($value, 2);
						$this->CreateVarProfileFloat('Froggit.AirPressure.inHg','Gauge',' inHG');
						$profile = 'Froggit.AirPressure.inHg';
					} else { // mmHg
						$pressure = round($value * 25.4 , 2);
						$this->CreateVarProfileFloat('Froggit.AirPressure.mmHg','Gauge',' mmHG');
						$profile = 'Froggit.AirPressure.mmHg';
					}
					$this->RegisterVariableFloat($key, $this->Translate('Air Pressure') . "_(" . $key . ")",$profile);
					if($this->GetValue($key) != $pressure) $this->SetValue($key, $pressure);
				}
				elseif (strpos($key, 'rain'))
				{
					if($this->ReadPropertyInteger("Rain") == 0) { // mm
						$rain = round($value * 25.4,2);
						$profile = '~Rainfall';
					} else { // inch
						$this->CreateVarProfileFloat('Froggit.Rain.Inch', 'Rainfall',' in');
						$rain = $value;
					}
					$this->RegisterVariableFloat($key, $this->Translate($key),$profile);
					if($this->GetValue($key) != $rain) $this->SetValue($key,$rain);
				}
				elseif ($key == 'solarradiation' )
				{
					if($this->ReadPropertyInteger("Light") == 0) { // w/m²
						$solarradiation = intval($value );
						$this->CreateVarProfileInteger('Froggit.Light.w_m','Sun',' w/m²');
						$profile = 'Froggit.Light.w_m';
					} elseif ($this->ReadPropertyInteger("Light") == 1) { // lux
						$solarradiation = intval($value * 126.7 );
						$profile = '~Illumination';
					} else { //fc
						$solarradiation = intval($value * 126.7 / 10.76);
						$this->CreateVarProfileInteger('Froggit.Light.fc','Sun',' fc');
						$profile = 'Froggit.Light.fc';
					}
					$this->RegisterVariableInteger($key, $this->Translate('Solar Radiation'),$profile);
					if($this->GetValue($key) != $value) $this->SetValue($key, $solarradiation);
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
						$this->SendDebug("Unsupportet Feature","Key: " . $key . " | Value: " . $value , 0);
						//$this->RegisterVariableString($key, $key,'');
						//if($this->GetValue($key) != $value) $this->SetValue($key, $value);
					}
				}
			}
		}

		private function CreateVarProfileFloat(string $ProfilName, string $ProfilIcon, string $ProfileText)
		{
			if (!IPS_VariableProfileExists($ProfilName)) {
				IPS_CreateVariableProfile($ProfilName, 2);
				IPS_SetVariableProfileIcon($ProfilName, $ProfilIcon);
				IPS_SetVariableProfileText($ProfilName, '', $ProfileText);
				IPS_SetVariableProfileDigits($ProfilName, 2);
			}
		}
		private function CreateVarProfileInteger(string $ProfilName, string $ProfilIcon, string $ProfileText)
		{
			if (!IPS_VariableProfileExists($ProfilName)) {
				IPS_CreateVariableProfile($ProfilName, 1);
				IPS_SetVariableProfileIcon($ProfilName, $ProfilIcon);
				IPS_SetVariableProfileText($ProfilName, '', $ProfileText);
			}
		}
	}
