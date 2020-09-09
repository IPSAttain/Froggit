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
					if ($array[0] == 'windspeedmph')
					{
						$this->RegisterVariableFloat($array[0], $this->Translate('Wind Speed'),'~WindSpeed.kmh');
						if($this->GetValue($array[0]) != $array[1]) $this->SetValue($array[0], $this->MilesToKilometer(floatval($array[1])));
					}
					if (substr($array[0],0,4) == 'temp' )
					{
						$this->RegisterVariableFloat($array[0], $this->Translate('Temperature') . "_(" . $array[0] . ")",'~Temperature');
						if($this->GetValue($array[0]) != $array[1]) $this->SetValue($array[0], $this->FahrenheitToCelsius(floatval($array[1])));
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
	
		private function CelsiusToFahrenheit(float $celsius)
		{
			return $celsius * 1.8 + 32;
		}
	
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
	
		private function Rain(float $inch)
		{
			$mm = $inch * 25.4;
			return $mm;
		}
	
		private function RainToInch(float $mm)
		{
			$inch = $mm * 0.03937007874;
			return $inch;
		}
	
		private function Pressure_absolute(float $pressure)
		{
			$pascal = $pressure / 0.02952998751;
			return $pascal;
		}
	
		private function Pressure(float $pressure, float $temperature)
		{
			$pascal   = $pressure / 0.02952998751;
			$altitude = $this->ReadPropertyInteger('altitude_above_sea_level');
	
			$g0 = 9.80665;                                         // Normwert der Fallbeschleunigung
			$R  = 287.05;                                          // Gaskonstante trockener Luft
			$T  = 273.15;                                          // 0°C in Kelvin
			$Ch = 0.12;                                            // Beiwert zu E
			if ($temperature < 9.1) {
				$E = 5.6402 * (-0.0916 + exp(0.06 * $temperature));        // Dampfdruck des Wasserdampfanteils bei t < 9.1°C
			} else {
				$E = 18.2194 * (1.0463 - exp(-0.0666 * $temperature));    // Dampfdruck des Wasserdampfanteils bei t >= 9.1°C
			}
			$a  = 0.0065;                                          // vertikaler Temperaturgradient
			$xp = $altitude * $g0 / ($R * ($T + $temperature + $Ch * $E + $a * $altitude / 2)); // Exponent für Formel
			$p0 = $pascal * exp($xp);                             // Formel für den NN-bezogenen Luftdruck laut Wikipedia
			return $p0;
		}
	
		private function PressurehPaToBar($pressure)
		{
			$bar = $pressure * 0.02952998751;
			return $bar;
		}
	}