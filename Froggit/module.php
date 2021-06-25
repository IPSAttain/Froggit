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
		$this->RegisterPropertyString("HookPrefix","/hook/");
		$this->RegisterPropertyString("Hook","froggit");
		$this->RegisterPropertyBoolean("SaveAllValues",false);
		$this->RegisterPropertyBoolean("IgnoreImprobableValues",false);
		$this->RegisterPropertyBoolean("DewPoint",false);
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
			
			$this->RegisterHook($this->ReadPropertyString('HookPrefix') . $this->ReadPropertyString('Hook'));
		}
	}

	public function MessageSink($TimeStamp, $SenderID, $Message, $Data)
	{

		//Never delete this line!
		parent::MessageSink($TimeStamp, $SenderID, $Message, $Data);

		if ($Message == IPS_KERNELMESSAGE && $Data[0] == KR_READY) {
			$this->RegisterHook($this->ReadPropertyString('HookPrefix') . $this->ReadPropertyString('Hook'));
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
		$this->SendDebug(__FUNCTION__, 'Array POST: ' . print_r($_POST, true), 0);

		foreach ($_POST as $key => $value) {
			$SaveAllValues = $this->ReadPropertyBoolean("SaveAllValues");
			$IgnoreImprobableValues = $this->ReadPropertyBoolean("IgnoreImprobableValues");
			if ($key == 'stationtype')
			{
				$this->RegisterVariableString($key, $this->Translate('Station Type'),'');
				if($this->GetValue($key) != $value || $SaveAllValues) $this->SetValue($key, $value);
			}
			elseif ($key == 'model')
			{
				$this->RegisterVariableString($key, $this->Translate('Model'),'');
				if($this->GetValue($key) != $value || $SaveAllValues) $this->SetValue($key, $value);
			}
			elseif ($key == 'winddir')
			{
				$this->RegisterVariableInteger($key."_int", $this->Translate('Wind Direction'),'~WindDirection');
				if($this->GetValue($key."_int") != $value || $SaveAllValues) $this->SetValue($key."_int", intval($value));
				$this->RegisterVariableFloat($key."_txt", $this->Translate('Wind Direction'),'~WindDirection.Text');
				if($this->GetValue($key."_txt") != $value || $SaveAllValues) $this->SetValue($key."_txt", floatval($value));
			}
			elseif ($key == 'winddir_avg10m')
			{
				$this->RegisterVariableInteger($key."_int", $this->Translate('Wind Direction (10min Average)'),'~WindDirection');
				if($this->GetValue($key."_int") != $value || $SaveAllValues) $this->SetValue($key."_int", intval($value));
				$this->RegisterVariableFloat($key."_txt", $this->Translate('Wind Direction (10min Average)'),'~WindDirection.Text');
				if($this->GetValue($key."_txt") != $value || $SaveAllValues) $this->SetValue($key."_txt", floatval($value));
			}
			elseif ($key == 'windspeedmph' || $key == 'windspdmph_avg10m')
			{
				$wind = $this->ConvertWindSpeed(floatval($value));
				if (substr($key,-6) == 'avg10m') $this->RegisterVariableFloat($key, $this->Translate('Wind Speed (10min Average)'),$wind->profile);
				else $this->RegisterVariableFloat($key, $this->Translate('Wind Speed'),$wind->profile);
				if($this->GetValue($key) != $wind->windspeed || $SaveAllValues) $this->SetValue($key, $wind->windspeed);
			}
			elseif ($key == 'maxdailygust')
			{
				$wind = $this->ConvertWindSpeed(floatval($value));
				$this->RegisterVariableFloat($key, $this->Translate('Day Wind Max'),$wind->profile);
				if($this->GetValue($key) != $wind->windspeed || $SaveAllValues) $this->SetValue($key, $wind->windspeed);
			}
			elseif ($key == 'windgustmph')
			{
				$wind = $this->ConvertWindSpeed(floatval($value));
				$this->RegisterVariableFloat($key, $this->Translate('Wind Gust'),$wind->profile);
				if($this->GetValue($key) != $wind->windspeed || $SaveAllValues) $this->SetValue($key, $wind->windspeed);
			}
			elseif (substr($key,0,4) == 'temp' )
			{
				if($IgnoreImprobableValues && $value <= -1000)
				{
					$this->SendDebug("Ignored Improbable Value","Key: " . $key . " | Value: " . $value , 0);
				}
				else
				{
					if($this->ReadPropertyInteger("Temperature") == 0) { // °C
						$temp = round(($value - 32) / 1.8 ,2);
						$profile = '~Temperature';
					} else { // °F
						$profile = '~Temperature.Fahrenheit';
						$temp = $value;
					}
					$sensor = $key;
					if(is_numeric(substr($key,4,1))) $sensor = $this->Translate('Channel') . ' ' . substr($key,4,1);
					elseif($key == 'tempf')   $sensor = $this->Translate('Outdoor sensor');
					elseif($key == 'tempinf') $sensor = $this->Translate('Indoor sensor');
					$this->RegisterVariableFloat($key, $this->Translate('Temperature') . " (" . $sensor . ")",$profile);
					if($this->GetValue($key) != $temp || $SaveAllValues) $this->SetValue($key, $temp);
				}
			}
			elseif (substr($key,0,8) == 'humidity' )
			{
				if($IgnoreImprobableValues && $value < -1000)
				{
					$this->SendDebug("Ignored Improbable Value","Key: " . $key . " | Value: " . $value , 0);
				}
				else
				{
					$this->RegisterVariableInteger($key, $this->Translate('Humidity') . " (" . $key . ")",'~Humidity');
					if($this->GetValue($key) != $value || $SaveAllValues) $this->SetValue($key, intval($value));
				}
			}
			elseif (substr($key,0,12) == 'soilmoisture' )
			{
				$this->RegisterVariableInteger($key, $this->Translate('Soilmoisture') . " (" . substr($key,-1) . ")",'~Humidity');
				if($this->GetValue($key) != $value || $SaveAllValues) $this->SetValue($key, intval($value));
			}
			elseif (substr($key,0,5) == 'barom' )
			{
				if($this->ReadPropertyInteger("Pressure") == 0) { // hPa
					$pressure = round($value / 0.02952998751 , 1);
					$profile = '~AirPressure.F';
				} elseif ($this->ReadPropertyInteger("Pressure") == 1) { // inHg
					$pressure = round($value, 2);
					$this->CreateVarProfileFloat('Froggit.AirPressure.inHg','Gauge',' inHG', 30, 50);
					$profile = 'Froggit.AirPressure.inHg';
				} else { // mmHg
					$pressure = round($value * 25.4 , 2);
					$this->CreateVarProfileFloat('Froggit.AirPressure.mmHg','Gauge',' mmHG', 900 , 1100);
					$profile = 'Froggit.AirPressure.mmHg';
				}
				$this->RegisterVariableFloat($key, $this->Translate('Air Pressure') . " (" . $key . ")",$profile);
				if($this->GetValue($key) != $pressure || $SaveAllValues) $this->SetValue($key, $pressure);
			}
			elseif (strpos($key, 'rain') !== false)
			{
				if($this->ReadPropertyInteger("Rain") == 0) { // mm
					$rain = round($value * 25.4,2);
					$profile = '~Rainfall';
				} else { // inch
					$this->CreateVarProfileFloat('Froggit.Rain.Inch', 'Rainfall',' in', 0, 10);
					$rain = $value;
					$profile = 'Froggit.Rain.Inch';
				}
				$this->RegisterVariableFloat($key, $this->Translate($key),$profile);
				if($this->GetValue($key) != $rain || $SaveAllValues) $this->SetValue($key,$rain);
			}
			elseif ($key == 'solarradiation' )
			{
				if($this->ReadPropertyInteger("Light") == 0) { // w/m²
					$solarradiation = intval($value );
					$this->CreateVarProfileInteger('Froggit.Light.wm2','Sun',' w/m²');
					$profile = 'Froggit.Light.wm2';
				} elseif ($this->ReadPropertyInteger("Light") == 1) { // lux
					$solarradiation = intval($value * 126.7 );
					$profile = '~Illumination';
				} else { //fc
					$solarradiation = intval($value * 126.7 / 10.76);
					$this->CreateVarProfileInteger('Froggit.Light.fc','Sun',' fc');
					$profile = 'Froggit.Light.fc';
				}
				$this->RegisterVariableInteger($key, $this->Translate('Solar Radiation'),$profile);
				if($this->GetValue($key) != $value || $SaveAllValues) $this->SetValue($key, $solarradiation);
			}
			elseif ($key == 'uv' )
			{
				$this->RegisterVariableInteger($key, $this->Translate('UV Index'),'~UVIndex');
				if($this->GetValue($key) != $value || $SaveAllValues) $this->SetValue($key, intval($value));
			}
			// Lightning Detection Sensor (WH57)
			elseif ($key == 'lightning' )
			{
				$this->CreateVarProfileFloat('Froggit.Distance.km','Distance',' km');
				$this->RegisterVariableFloat($key, $this->Translate('lightning dist'),'Froggit.Distance.km');
				$value = round($value, 2);
				if($this->GetValue($key) != $value || $SaveAllValues) $this->SetValue($key, $value);
			}
			elseif ($key == 'lightning_num' )
			{
				$this->CreateVarProfileInteger('Froggit.Lightning.Count','Lightning',' ');
				$this->RegisterVariableInteger($key, $this->Translate('lightning count'),'Froggit.Lightning.Count');
				if($this->GetValue($key) != $value || $SaveAllValues) $this->SetValue($key, intval($value));
			}
			elseif ($key == 'lightning_time' )
			{
				// $value = $this->ConvertUTCtoLocal(intval($value)); 24.04.21 comes already as a Unix Timestamp
				$this->RegisterVariableInteger($key, $this->Translate('lightning time'),'~UnixTimestamp');
				if($this->GetValue($key) != $value || $SaveAllValues) $this->SetValue($key, intval($value));
			}
			elseif ($key == 'dateutc' )
			{
				$time = str_replace("+"," ",$value);
				$time = $this->ConvertUTCtoLocal(strtotime($time));
				$this->RegisterVariableInteger($key, $this->Translate('Time'),'~UnixTimestamp');
				$this->SetValue($key, $time);
			}
			elseif (substr($key,0,5) == "pm25_")
			{
				$this->CreateVarProfileInteger('Froggit.PM25_ch','Fog',' µg/m³');
				if (substr($key,-11,7) == 'avg_24h')
				{
					$this->RegisterVariableInteger($key, $this->Translate('PM2.5 particle') . " 24h_avg (" . substr($key,-1) . ")",'Froggit.PM25_ch');
				}
				else
				{
					$this->RegisterVariableInteger($key, $this->Translate('PM2.5 particle') . " (" . substr($key,-1) . ")",'Froggit.PM25_ch');
				}
				if($this->GetValue($key) != $value || $SaveAllValues) $this->SetValue($key, intval($value));
			}
			elseif (substr($key,0,7) == 'leak_ch')
			{
				$batt = boolval($value);
				$this->RegisterVariableBoolean($key, $this->Translate('Water Leak Sensor') . ' (' . substr($key,-1) . ')','~Alert');
				if($this->GetValue($key) != $batt || $SaveAllValues) $this->SetValue($key, $batt);
			}
			// >>>>>>>>>>>>>>>>> Battery <<<<<<<<<<<<<<<<<<<
			elseif (substr($key,0,8) == 'soilbatt' ) // soil moisture
			{
				$batt = $value * 200 - 220;  // from 1.1 == empty to 1.6 == full
				$this->RegisterVariableInteger($key, $this->Translate('SoilMoistureBattery') . " (" . substr($key,-1) . ")",'~Battery.100');
				if($this->GetValue($key) != $batt || $SaveAllValues) $this->SetValue($key, $batt);
			}
			elseif (substr($key,0,8) == 'leakbatt') // water leak
			{
				$batt = intval($value)*20; // from 0 == empty to 5 == full
				$this->RegisterVariableInteger($key, $this->Translate('Battery') . ' ' . $this->Translate('Water Leak Sensor') . ' (' . substr($key,-1) . ')','~Battery.100');
				if($this->GetValue($key) != $batt || $SaveAllValues) $this->SetValue($key, $batt);
			}
			elseif (substr($key,0,8) == 'wh57batt') // lightning
			{
				$batt = intval($value)*20; // from 0 == empty to 5 == full
				$this->RegisterVariableInteger($key, $this->Translate('Battery Lightning Sensor') ,'~Battery.100');
				if($this->GetValue($key) != $batt || $SaveAllValues) $this->SetValue($key, $batt);
			}
			elseif (substr($key,0,8) == 'pm25batt')
			{
				$batt = intval($value)*20; // from 0 == empty to 5 == full
				$this->RegisterVariableInteger($key, $this->Translate('Battery') . " PM2.5 (" . substr($key,-1) . ")",'~Battery.100');
				if($this->GetValue($key) != $batt || $SaveAllValues) $this->SetValue($key, $batt);
			}
			elseif (substr($key,4,4) == 'batt')
			{
				$batt = boolval($value); // 0 == OK
				$this->RegisterVariableBoolean($key, $this->Translate('Battery') . " (" . substr($key,0,4) . ")",'~Battery');
				if($this->GetValue($key) != $batt || $SaveAllValues) $this->SetValue($key, $batt);
			}
			elseif (substr($key,0,4) == 'batt')
			{
				$batt = boolval($value); // 0 == OK
				$this->RegisterVariableBoolean($key, $this->Translate('Battery Temperature Sensor Channel ') . ' ('  . substr($key,4,1) . ')','~Battery');
				if($this->GetValue($key) != $batt || $SaveAllValues) $this->SetValue($key, $batt);
			}
			// >>>>>>>>>>>>>>>>> all other <<<<<<<<<<<<<<<<<<
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

		//dew Point calculation
		if ($this->ReadPropertyBoolean("DewPoint") && array_key_exists('tempf',$_POST) && array_key_exists('humidity',$_POST))
		{
			$key='dewpoint';
			if($this->ReadPropertyInteger("Temperature") == 0) { // °C
				$temp = ($_POST['tempf'] - 32) / 1.8;
				$profile = '~Temperature';
			} else { // °F
				$profile = '~Temperature.Fahrenheit';
				$temp = $_POST['tempf'];
			}
			if ($temp >= 0) $dewpoint=243.12*((17.62*$temp)/(243.12+$temp)+log($_POST['humidity']/100))/((17.62*243.12)/(243.12+$temp)-log($_POST['humidity']/100));
			else $dewpoint=272.62*((22.46*$temp)/(272.62+$temp)+log($_POST['humidity']/100))/((22.46*272.62)/(272.62+$temp)-log($_POST['humidity']/100));
			$this->RegisterVariableFloat($key, $this->Translate('Dew Point'),$profile);
			if($this->GetValue($key) != $dewpoint || $SaveAllValues) $this->SetValue($key, $dewpoint);
		}
		else $this->SendDebug("Dew Point Calculation","inactive or missing data" , 0);
		
		// windchill calculation
		if ($this->ReadPropertyBoolean("DewPoint") && array_key_exists('tempf',$_POST) && array_key_exists('windspeedmph',$_POST))
		{
			$key = 'windchill';
			$wind = $this->ConvertWindSpeed(floatval($_POST['windspeedmph']));
			if($this->ReadPropertyInteger("Temperature") == 0) { // °C
				$temp = ($_POST['tempf'] - 32) / 1.8;
				$profile = '~Temperature';
			} else { // °F
				$profile = '~Temperature.Fahrenheit';
				$temp = $_POST['tempf'];
			}
			if ($temp <= 10) $windchill = 13.12 + 0.6215 * $temp - 11.37 * pow($wind->windspeed,0.16) + 0.3965 * $temp * pow($wind->windspeed,0.16);
			else $windchill = $temp;
			$this->RegisterVariableFloat($key, $this->Translate('Windchill'),$profile);
			if($this->GetValue($key) != $windchill || $SaveAllValues) $this->SetValue($key, $windchill);
		}
		else $this->SendDebug("Windchill Calculation","inactive or missing data" , 0);
	}

	private function CreateVarProfileFloat(string $ProfilName, string $ProfilIcon, string $ProfileText, float $Min = 0 , float $Max = 100)
	{
		if (!IPS_VariableProfileExists($ProfilName)) {
			IPS_CreateVariableProfile($ProfilName, 2);
			IPS_SetVariableProfileIcon($ProfilName, $ProfilIcon);
			IPS_SetVariableProfileText($ProfilName, '', $ProfileText);
			IPS_SetVariableProfileDigits($ProfilName, 2);
			IPS_SetVariableProfileValues($ProfilName,$Min,$Max);
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
	private function ConvertUTCtoLocal(int $timestamp)
	{
		$df = "G:i:s";  // Use a simple time format to find the difference
		$ts1 = strtotime(date($df));   // Timestamp of current local time
		$ts2 = strtotime(gmdate($df)); // Timestamp of current UTC time
		$ts3 = $ts1-$ts2;              // Their difference
		$timestamp += $ts3;  			// Add the difference
		return $timestamp;
	}
	private function ConvertWindSpeed(float $value)
	{
		$return = new StdClass;
		if($this->ReadPropertyInteger("Wind") == 0) { // km/h
			$return->windspeed = round($value * 1.609344 , 2);
			$return->profile = '~WindSpeed.kmh';
		} elseif ($this->ReadPropertyInteger("Wind") == 1) { // m/s
			$return->windspeed = round($value * 1.609344 / 3.6 , 2);
			$return->profile = '~WindSpeed.ms';
		} else { //mph
			$return->windspeed = round($value,2);
			$this->CreateVarProfileFloat('Froggit.Wind.mph','WindSpeed',' mph', 0, 100);
			$return->profile = 'Froggit.Wind.mph';
		}
		return $return;
	}
}