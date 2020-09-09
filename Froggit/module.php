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
						$this->RegisterVariableFloat($array[0], $this->Translate('Wind Speed'),'~WindSpeed.kmh');
						if($this->GetValue($array[0]) != $array[1]) $this->SetValue($array[0], $this->MilesToKilometer(floatval($array[1])));
					}
					elseif ($array[0] == 'maxdailygust')
					{
						$this->RegisterVariableFloat($array[0], $this->Translate('Day Wind Max'),'~WindSpeed.kmh');
						if($this->GetValue($array[0]) != $array[1]) $this->SetValue($array[0], $this->MilesToKilometer(floatval($array[1])));
					}
					elseif ($array[0] == 'windgustmph')
					{
						$this->RegisterVariableFloat($array[0], $this->Translate('Wind Gust'),'~WindSpeed.kmh');
						if($this->GetValue($array[0]) != $array[1]) $this->SetValue($array[0], $this->MilesToKilometer(floatval($array[1])));
					}
					elseif (substr($array[0],0,4) == 'temp' )
					{
						$$array[1] = round($this->FahrenheitToCelsius($array[1]) ,2);
						$this->RegisterVariableFloat($array[0], $this->Translate('Temperature') . "_(" . $array[0] . ")",'~Temperature');
						if($this->GetValue($array[0]) != $array[1]) $this->SetValue($array[0], $array[1]);
					}
					elseif (substr($array[0],0,8) == 'humidity' )
					{
						$this->RegisterVariableInteger($array[0], $this->Translate('Humidity') . "_(" . $array[0] . ")",'~Humidity');
						if($this->GetValue($array[0]) != $array[1]) $this->SetValue($array[0], intval($array[1]));
					}
					elseif (substr($array[0],0,5) == 'barom' )
					{
						$this->RegisterVariableFloat($array[0], $this->Translate('Air Pressure') . "_(" . $array[0] . ")",'~AirPressure.F');
						if($this->GetValue($array[0]) != $array[1]) $this->SetValue($array[0], floatval($array[1] / 0.02952998751));
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
						//$this->SendDebug($array[0], $time , 0);
						$this->RegisterVariableInteger($array[0], $this->Translate('Time'),'~UnixTimestamp');
						$this->SetValue($array[0], strtotime($time));
					}
					else
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


		// Windchill Berechnung
		private function windchill(float $temperatur, float $windspeed) {
			if ($windspeed > 4.8) {
				$windchill = 13.12 + 0.6215 * $temperatur - 11.37 * pow($windspeed, 0.16) + 0.3965 * $temperatur * pow($windspeed, 0.16);
				return $windchill;
			} else {
				$windchill = $temperatur;
				return $windchill;
			}
		}
	
		// Hitze-Index-Berechnung
		private function heatindex($t, $r) {
		// Relative Luftfeuchtigkeit limitieren
		if ($r < 0) { $r = 0; }
		if ($r > 100) { $r = 100; }
	
		// Hitzeindex
		$hi = -8.784695 + 1.61139411*$t + 2.338549*$r - 0.14611605*$t*$r - 0.012308094*$t*$t - 0.016424828*$r*$r + 0.002211732*$t*$t*$r + 0.00072546*$t*$r*$r - 0.000003582*$t*$t*$r*$r;
		return $hi;
		} // end heatindex
	
		private function FahrenheitToCelsius(float $fahrenheit)
		{
			return ($fahrenheit - 32) / 1.8;
		}
	
		private function MilesToKilometer(float $mph)
		{
			$kmh = $mph * 1.609344;
			return $kmh;
		}
	
		private function MilesToKN(float $mph)
		{
			$kn = $mph * 0.86897624190065;
			return $kn;
		}
	
		private function KilometerToKN(float $kmh)
		{
			$kn = $kmh / 1.852;
			return $kn;
		}
	
		private function MPHToMS(float $mph)
		{
			$ms = $mph * 0.44704;
			return $ms;
		}
	
		private function MSToMPH(float $ms)
		{
			$mph = $ms * 2.23694;
			return $mph;
		}
	
		private function InchToMM(float $inch)
		{
			$mm = $inch * 25.4;
			return $mm;
		}
	
		private function RainToInch(float $mm)
		{
			$inch = $mm * 0.03937007874;
			return $inch;
		}
	
		private function PressureinHGToBar($pressure)
		{
			$bar = $pressure * 0.02997;
			return $bar;
		}
	}