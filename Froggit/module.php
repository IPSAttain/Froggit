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
		$this->RegisterPropertyBoolean("CreateVariables",true);
		$this->RegisterPropertyBoolean("DewPoint",false);
		$this->RegisterPropertyInteger("Debug",0);
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
		$Debug = $this->ReadPropertyInteger("Debug");
		$SaveAllValues = $this->ReadPropertyBoolean("SaveAllValues");
		$IgnoreImprobableValues = $this->ReadPropertyBoolean("IgnoreImprobableValues");

		if ($Debug) $this->SendDebug(__FUNCTION__, 'Array POST: ' . print_r($_POST, true), 0);

		foreach ($_POST as $key => $value) {
			if ($Debug > 1) $this->SendDebug(__FUNCTION__,"Key: " . $key . " Value: " . $value, 0);
			switch ($key)
			{
				case 'stationtype' :
					$ID = $this->VariableCreate('string', $key, 'Station Type', '',900);
					if ($ID && ($this->GetValue($key) != $value || $SaveAllValues)) $this->SetValue($key, $value);
				break;

				case  'model' :
					$ID = $this->VariableCreate('string', $key, 'Model','', 901);
					if($ID && ($this->GetValue($key) != $value || $SaveAllValues)) $this->SetValue($key, $value);
				break;
				
				case 'runtime' :
					$uptime = time() - $value;
					$ID = $this->VariableCreate('integer', $key, $this->Translate('Start Time Gateway') ,'~UnixTimestamp', 903);
					if($ID && ($this->GetValue($key) != $uptime || $SaveAllValues)) $this->SetValue($key, intval($uptime));
				break;

				case 'winddir' :
					$ID = $this->VariableCreate('integer', $key."_int", 'Wind Direction','~WindDirection', 500);
					if($ID && ($this->GetValue($key."_int") != $value || $SaveAllValues)) $this->SetValue($key."_int", intval($value));
					$ID = $this->VariableCreate('float', $key."_txt", 'Wind Direction','~WindDirection.Text', 501);
					if($ID && ($this->GetValue($key."_txt") != $value || $SaveAllValues)) $this->SetValue($key."_txt", floatval($value));
				break;

				case 'winddir_avg10m' :
					$ID = $this->VariableCreate('integer', $key."_int", 'Wind Direction (10min Average)','~WindDirection', 510);
					if($ID && ($this->GetValue($key."_int") != $value || $SaveAllValues)) $this->SetValue($key."_int", intval($value));
					$ID = $this->VariableCreate('float', $key."_txt", 'Wind Direction (10min Average)','~WindDirection.Text', 511);
					if($ID && ($this->GetValue($key."_txt") != $value || $SaveAllValues)) $this->SetValue($key."_txt", floatval($value));
				break;

				case 'windspdmph_avg10m' :
					$wind = $this->ConvertWindSpeed(floatval($value));
					$ID = $this->VariableCreate('float', $key, 'Wind Speed (10min Average)',$wind->profile, 512);
					if($ID && ($this->GetValue($key) != $wind->windspeed || $SaveAllValues)) $this->SetValue($key, $wind->windspeed);
				break;

				case'windspeedmph' :
					$wind = $this->ConvertWindSpeed(floatval($value));
					$ID = $this->VariableCreate('float', $key, 'Wind Speed',$wind->profile, 502);
					if($ID && ($this->GetValue($key) != $wind->windspeed || $SaveAllValues)) $this->SetValue($key, $wind->windspeed);
				break;

				case 'maxdailygust' :
					$wind = $this->ConvertWindSpeed(floatval($value));
					$ID = $this->VariableCreate('float', $key, 'Day Wind Max',$wind->profile, 523);
					if($ID && ($this->GetValue($key) != $wind->windspeed || $SaveAllValues)) $this->SetValue($key, $wind->windspeed);
				break;

				case 'windgustmph' :
					$wind = $this->ConvertWindSpeed(floatval($value));
					$ID = $this->VariableCreate('float', $key, 'Wind Gust',$wind->profile, 522);
					if($ID && ($this->GetValue($key) != $wind->windspeed || $SaveAllValues)) $this->SetValue($key, $wind->windspeed);
				break;

				case 'solarradiation' :
					switch ($this->ReadPropertyInteger("Light"))
					{
						case 0:
							$solarradiation = intval($value );
							$this->CreateVarProfileInteger('Froggit.Light.wm2','Sun',' w/m²');
							$profile = 'Froggit.Light.wm2';
							break;
						
						case 1:
							$solarradiation = intval($value * 126.7 );
							$profile = '~Illumination';
							break;

						case 2:
							$solarradiation = intval($value * 126.7 / 10.76);
							$this->CreateVarProfileInteger('Froggit.Light.fc','Sun',' fc');
							$profile = 'Froggit.Light.fc';
							break;

						case 3:
							$solarradiation = $value;
							$this->CreateVarProfileFloat('Froggit.Light.wm2.float','Sun',' w/m²',0,1300);
							$profile = 'Froggit.Light.wm2.float';
							$ID = $this->VariableCreate('float', $key, 'Solar Radiation',$profile, 550);
							break;
/*
					}
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
					*/
					if(!isset($ID)) $ID = $this->VariableCreate('integer', $key, 'Solar Radiation',$profile, 550);
					if($ID && ($this->GetValue($key) != $value || $SaveAllValues)) $this->SetValue($key, $solarradiation);
				break;

				case 'uv' :
					$ID = $this->VariableCreate('integer', $key, 'UV Index','~UVIndex', 551);
					if($ID && ($this->GetValue($key) != $value || $SaveAllValues)) $this->SetValue($key, intval($value));
				break;

				case 'lightning' : // Lightning Detection Sensor (WH57)
					$this->CreateVarProfileFloat('Froggit.Distance.km','Distance',' km');
					$ID = $this->VariableCreate('float', $key, $this->Translate('lightning dist'),'Froggit.Distance.km', 802);
					$value = round($value, 2);
					if($ID && ($this->GetValue($key) != $value || $SaveAllValues)) $this->SetValue($key, $value);
				break;

				case 'lightning_num' :
					$this->CreateVarProfileInteger('Froggit.Lightning.Count','Lightning',' ');
					$ID = $this->VariableCreate('integer', $key, 'lightning count','Froggit.Lightning.Count' , 801);
					if($ID && ($this->GetValue($key) != $value || $SaveAllValues)) $this->SetValue($key, intval($value));
				break;

				case 'lightning_time' :
					$ID = $this->VariableCreate('integer', $key, $this->Translate('lightning time'),'~UnixTimestamp' , 803);
					if($ID && ($this->GetValue($key) != $value || $SaveAllValues)) $this->SetValue($key, intval($value));
				break;

				case 'dateutc' :
					$time = str_replace("+"," ",$value);
					$time = $this->ConvertUTCtoLocal(strtotime($time));
					$ID = $this->VariableCreate('integer', $key, $this->Translate('Time'),'~UnixTimestamp', 902);
					if($ID) $this->SetValue($key, $time);
				break;

				case (substr($key,0,5) == "pm25_") :
					$this->CreateVarProfileInteger('Froggit.PM25_ch','Fog',' µg/m³');
					if (substr($key,-11,7) == 'avg_24h')
					{
						$ID = $this->VariableCreate('integer', $key, $this->Translate('PM2.5 particle') . " 24h_avg (" . substr($key,-1) . ")",'Froggit.PM25_ch', 700 + intval(substr($key,-1)));
					}
					else
					{
						$ID = $this->VariableCreate('integer', $key, $this->Translate('PM2.5 particle') . " (" . substr($key,-1) . ")",'Froggit.PM25_ch', 710 + intval(substr($key,-1)));
					}
					if($ID && ($this->GetValue($key) != $value || $SaveAllValues)) $this->SetValue($key, intval($value));
				break;

				case (substr($key,0,7) == 'leak_ch') :
					$batt = boolval($value);
					$ID = $this->VariableCreate('bool', $key, $this->Translate('Water Leak Sensor') . ' (' . substr($key,-1) . ')','~Alert', 750 + intval(substr($key,-1)));
					if($ID && ($this->GetValue($key) != $batt || $SaveAllValues)) $this->SetValue($key, $batt);
				break;

				// >>>>>>>>>>>>>>>>> Battery <<<<<<<<<<<<<<<<<<<
				case (substr($key,0,8) == 'soilbatt' ) :
					$batt = $value * 200 - 220;  // from 1.1 == empty to 1.6 == full
					$ID = $this->VariableCreate('integer', $key, $this->Translate('SoilMoistureBattery') . " (" . substr($key,-1) . ")",'~Battery.100', 410 + intval(substr($key,-1)));
					if($ID && ($this->GetValue($key) != $batt || $SaveAllValues)) $this->SetValue($key, $batt);
				break;

				case (substr($key,0,9) == 'leaf_batt' ) :
					$batt = $value * 200 - 220;  // from 1.1 == empty to 1.6 == full
					$ID = $this->VariableCreate('integer', $key, $this->Translate('Leaf Sensor Battery') . " (" . substr($key,-1) . ")",'~Battery.100', 520 + intval(substr($key,-1)));
					if($ID && ($this->GetValue($key) != $batt || $SaveAllValues)) $this->SetValue($key, $batt);
				break;

				case (substr($key,0,7) == 'tf_batt' ) :
					$batt = $value * 200 - 220;  // from 1.1 == empty to 1.6 == full
					$ID = $this->VariableCreate('integer', $key, $this->Translate('Floor Temperature Battery') . " (" . substr($key,-1) . ")",'~Battery.100', 510 + intval(substr($key,-1)));
					if($ID && ($this->GetValue($key) != $batt || $SaveAllValues)) $this->SetValue($key, $batt);
				break;

				case 'wh40batt' :  // digital rain gauge sensor
				case 'wh68batt' :  // wireless solar powered anemometer
					$batt = $value * 200 - 220;  // from 1.1 == empty to 1.6 == full
					$ID = $this->VariableCreate('integer', $key, $this->Translate('Battery') . " (" . $key . ")",'~Battery.100', 520);
					if($ID && ($this->GetValue($key) != $batt || $SaveAllValues)) $this->SetValue($key, $batt);
				break;

				case 'wh90batt' : // DP2000
				case 'wh80batt' : // ultrasonic anemometer
				case 'console_batt' :
					$batt = $value * 75 - 150;  // from 2 == empty to 3.3 == full
					$ID = $this->VariableCreate('integer', $key, $this->Translate('Battery Weather Station') ,'~Battery.100', 810);
					if($ID && ($this->GetValue($key) != $batt || $SaveAllValues)) $this->SetValue($key, $batt);
				break;

				case (substr($key,0,8) == 'leakbatt') : // water leak
					$batt = intval($value)*20; // from 0 == empty to 5 == full
					$ID = $this->VariableCreate('integer', $key, $this->Translate('Battery') . ' ' . $this->Translate('Water Leak Sensor') . ' (' . substr($key,-1) . ')','~Battery.100', 810 + intval(substr($key,-1)));
					if($ID && ($this->GetValue($key) != $batt || $SaveAllValues)) $this->SetValue($key, $batt);
				break;

				case (substr($key,0,8) == 'wh57batt') : // lightning
					$batt = intval($value)*20; // from 0 == empty to 5 == full
					$ID = $this->VariableCreate('integer', $key, $this->Translate('Battery Lightning Sensor'),'~Battery.100' , 820);
					if($ID && ($this->GetValue($key) != $batt || $SaveAllValues)) $this->SetValue($key, $batt);
				break;

				case (substr($key,0,8) == 'pm25batt') :
					$batt = intval($value)*20; // from 0 == empty to 5 == full
					$ID = $this->VariableCreate('integer', $key, $this->Translate('Battery') . " PM2.5 (" . substr($key,-1) . ")",'~Battery.100', 830 + intval(substr($key,-1)));
					if($ID && ($this->GetValue($key) != $batt || $SaveAllValues)) $this->SetValue($key, $batt);
				break;

				case (substr($key,4,4) == 'batt') :
					$batt = boolval($value); // 0 == OK
					$ID = $this->VariableCreate('bool', $key, $this->Translate('Battery') . " (" . substr($key,0,4) . ")",'~Battery' , 850 + intval(substr($key,0,4)));
					if($ID && ($this->GetValue($key) != $batt || $SaveAllValues)) $this->SetValue($key, $batt);
				break;

				case (substr($key,0,4) == 'batt') :
					$batt = boolval($value); // 0 == OK
					$ID = $this->VariableCreate('bool', $key, $this->Translate('Battery Temperature Sensor Channel ') . ' ('  . substr($key,4,1) . ')','~Battery', 860 + intval(substr($key,4,1)));
					if($ID && ($this->GetValue($key) != $batt || $SaveAllValues)) $this->SetValue($key, $batt);
				break;

				// >>>>>>>>>>>>>>>>>>>>>>> Temperature Sensors <<<<<<<<<<<<<<<<<<<<
				case (substr($key,0,4) == 'temp' ) :
				case (substr($key,0,5) == 'tf_ch' ) :
					$pos = 0;
					if($IgnoreImprobableValues && $value <= -1000)
					{
						if ($Debug) $this->SendDebug("Ignored Improbable Value","Key: " . $key . " | Value: " . $value , 0);
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
						$name = $this->Translate('Temperature');
						if(is_numeric(substr($key,-1))) 
						{
							$sensor = $this->Translate('Channel') . ' ' . substr($key,-1);
							$pos = 10 * substr($key,-1) + 1;
							if (substr($key,0,5) == 'tf_ch' ) {
								$pos =+ 400;
								$name = $this->Translate('Floor Temperature');
							}
						}
						elseif($key == 'tempf')   $sensor = $this->Translate('Outdoor sensor');
						elseif($key == 'tempinf') $sensor = $this->Translate('Indoor sensor');
						$ID = $this->VariableCreate('float', $key, $name . ' (' . $sensor . ')', $profile , 100 + $pos);
						if($ID && ($this->GetValue($key) != $temp || $SaveAllValues)) $this->SetValue($key, $temp);
					}
				break;

				case (substr($key,0,8) == 'humidity' ) :
					if($IgnoreImprobableValues && $value < -1000)
					{
						if ($Debug) $this->SendDebug("Ignored Improbable Value","Key: " . $key . " | Value: " . $value , 0);
					}
					else
					{
						$ID = $this->VariableCreate('integer', $key, $this->Translate('Humidity') . " (" . $key . ")",'~Humidity' , 200);
						if($ID && ($this->GetValue($key) != $value || $SaveAllValues)) $this->SetValue($key, intval($value));
					}
				break;

				case (substr($key,0,12) == 'soilmoisture' ) :
					$ID = $this->VariableCreate('integer', $key, $this->Translate('Soilmoisture') . " (" . substr($key,-1) . ")",'~Humidity', 400 + intval(substr($key,-1)));
					if($ID && ($this->GetValue($key) != $value || $SaveAllValues)) $this->SetValue($key, intval($value));
				break;

				case (substr($key,0,11) == 'leafwetness' ) :
					$ID = $this->VariableCreate('integer', $key, $this->Translate('Leafwetness') . " (" . substr($key,-1) . ")",'~Humidity', 410 + intval(substr($key,-1)));
					if($ID && ($this->GetValue($key) != $value || $SaveAllValues)) $this->SetValue($key, intval($value));
				break;

				case 'baromabsin' :
				case 'baromrelin' :
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
					$ID = $this->VariableCreate('float', $key, $this->Translate('Air Pressure') . " (" . $key . ")",$profile, 250);
					if($ID && ($this->GetValue($key) != $pressure || $SaveAllValues)) $this->SetValue($key, $pressure);
				break;

				case (strpos($key, 'rain') !== false) :
					if($this->ReadPropertyInteger("Rain") == 0) { // mm
						$rain = round($value * 25.4,2);
						$profile = '~Rainfall';
					} else { // inch
						$this->CreateVarProfileFloat('Froggit.Rain.Inch', 'Rainfall',' in', 0, 10);
						$rain = $value;
						$profile = 'Froggit.Rain.Inch';
					}
					$ID = $this->VariableCreate('float', $key, $key,$profile, 600);
					if($ID && ($this->GetValue($key) != $rain || $SaveAllValues)) $this->SetValue($key,$rain);
				break;

				case 'ws90cap_volt' :
					$ID = $this->VariableCreate('float', $key, $this->Translate('Voltage Outdoor Sensor') ,'~Volt', 903);
					if($ID && ($this->GetValue($key) != $value || $SaveAllValues)) $this->SetValue($key, $value);
				break;

				case 'ws90_ver' :
					$ID = $this->VariableCreate('integer', $key, $this->Translate('Version') ,'', 904);
					if($ID && ($this->GetValue($key) != $value || $SaveAllValues)) $this->SetValue($key, intval($value));
				break;

				case 'interval' :
					$this->CreateVarProfileInteger('Froggit.interval.sec', '',' sek');
					$ID = $this->VariableCreate('integer', $key, $this->Translate('Interval'),'Froggit.interval.sec', 906);
					if($ID && ($this->GetValue($key) != $value || $SaveAllValues)) $this->SetValue($key, $value);
				break;

					// >>>>>>>>>>>>>>>>> all other <<<<<<<<<<<<<<<<<<
				default : // all other keys that needs to be seperated
					if (isset($key) && isset($value))
					{
						if ($Debug) $this->SendDebug("Unsupportet Feature","Key: " . $key . " | Value: " . $value , 0);
						//$ID = $this->VariableCreate('string', $key, $key,'',1000);
						//if($ID && ($this->GetValue($key) != $value)) $this->SetValue($key, $value);
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
			$ID = $this->VariableCreate('float', $key, 'Dew Point', $profile, 110);
			if($ID && ($this->GetValue($key) != $dewpoint || $SaveAllValues)) $this->SetValue($key, $dewpoint);
		}
		else if ($Debug) $this->SendDebug("Dew Point Calculation","inactive or missing data" , 0);
		
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
			//Windchill temperature is defined only for temperatures at or below 10 °C (50 °F) and wind speeds above 4.8 kilometres per hour (3.0 mph).
			if ($temp <= 10 && $wind->windspeed > 5) $windchill = 13.12 + 0.6215 * $temp - 11.37 * pow($wind->windspeed,0.16) + 0.3965 * $temp * pow($wind->windspeed,0.16);
			else $windchill = $temp;
			$ID = $this->VariableCreate('float', $key, 'Windchill', $profile, 111);
			if($ID && ($this->GetValue($key) != $windchill || $SaveAllValues)) $this->SetValue($key, $windchill);
		}
		else if ($Debug) $this->SendDebug("Windchill Calculation","inactive or missing data" , 0);
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
	private function VariableCreate(string $Type, string $Ident, string $Name, string $Profil = '', int $Position = 100)
	{
		$Debug = $this->ReadPropertyInteger("Debug");
		if ($this->ReadPropertyBoolean("CreateVariables"))
		{
			switch ($Type) 
			{
				case 'string':
					$return = $this->RegisterVariableString($Ident, $this->Translate($Name), $Profil, $Position);
					break;
				case 'float' :
					$return = $this->RegisterVariableFloat($Ident, $this->Translate($Name), $Profil, $Position);
					break;
				case 'bool'  :
				case 'boolean' :
					$return = $this->RegisterVariableBoolean($Ident, $this->Translate($Name), $Profil, $Position);
					break;
				case 'integer':
					$return = $this->RegisterVariableInteger($Ident, $this->Translate($Name), $Profil, $Position);
					break;
				default:
				$return = false;
				if ($Debug) $this->SendDebug(__FUNCTION__,"The Variable typ:  " . $type . " don't exist", 0);
			}
		}
		else 
		{
			$return = $this->GetIDForIdent($Ident);
			if (!$return) 
			{
				if ($Debug) $this->SendDebug(__FUNCTION__,"The Variable " . $this->Translate($Name) . " with Key: " . $Ident . " were not created", 0);
			}
		}
		if ($Debug > 1) $this->SendDebug(__FUNCTION__,"Variable ID: " . $return . " for Name: " . $Name, 0);
		return $return;
	}
}