# Froggit Wetterstation

### Inhaltsverzeichnis

1. [Function](#1-function)
2. [Voraussetzungen / Requirements](#2-voraussetzungen)
3. [Software-Installation](#3-software-installation)
4. [Einrichten der Instanzen in IP-Symcon](#4-einrichten-der-instanzen-in-ip-symcon)
5. [Statusvariablen und Profile](#5-statusvariablen-und-profile)
6. [WebFront](#6-webfront)

### 1. Function

* Empfangen von Daten einer Froggit/Ecowitt Wetterstation und ablegen in Variablen.
* Die Kommunikation läuft über das WebHook Control. Die nötigen Einstellungen werde automatisch vom Modul vorgenommen.

* Receive data from a Froggit / Ecowitt weather station and store it in variables.
* Communication takes place via the WebHook Control. The necessary settings are made automatically by the module. 

### 2. Vorraussetzungen / Requirements 

- IP-Symcon ab Version 5.3
- DP1500 Wi-Fi Wetterserver USB-Dongle, Displayeinheit HP1000SE PRO, Sainlogic WS 3500 oder ähnliche

- IP-Symcon from version 5.3
- Froggit DP1500, Froggit HP1000SE PRO, Sainlogic WS 3500 or similar 

### 3. Software-Installation

* Im Module Store das 'Froggit Wetterstation'-Modul suchen und installieren.
* Search for the 'Froggit Weather Station' module in the Module Store and install it. 

![ModulStore](../docs/ModulStore.png)

* Konfiguriert wird das WiFi Gateeway mit der App "WS View". Erhältlich für Android und IOS.
* Im Bereich "Weather Services" muss der "Customized Upload" aktiviert und konfiguriert werden. 
* Protokoll Typ: | Ecowitt
* Server IP: | IP-Adresse des IPS Servers. 
* Path: | /hook/froggit eintragen
* Port: | 3777 (Standard-Zugangsport IPS)
* Wenn mehrere Stationen eingebunden werden sollen, ist jeweils ein Pfad zu definieren. Dieser muss immer mit "/hook/..." beginnen.

* The WiFi gateway must configured with the "WS View" app. Available for Android and IOS.
* "Customized Upload" must be activated and configured in the "Weather Services" area.
* Protocol type: | Ecowitt
* Server IP: | IP address of the IPS server.
* Path: | /hook/froggit
* Port: | 3777 (standard access port IPS)
* If several stations are to be integrated, a path must be defined for each. This must always begin with "/hook/...". 


 ![Config_App](../docs/Config_App.png)

* Auch ist es möglich, Wetterstationen von außerhalb des eigenen Netzwerkes, über Symcon Connect einzubinden.
* Dazu deine Connect Adresse als Hostname eintragen und Port 80 wählen.

* It is also possible to integrate weather stations from outside your own network via Symcon Connect.
* To do this, enter your Connect address as the host name and select port 80. 

 ![Config_Connect](../docs/Config_Connect.png)
 

### 4. Einrichten der Instanzen in IP-Symcon

 Unter 'Instanz hinzufügen' kann das 'Froggit'-Modul mithilfe des Schnellfilters gefunden werden.  
- Weitere Informationen zum Hinzufügen von Instanzen in der [Dokumentation der Instanzen](https://www.symcon.de/service/dokumentation/konzepte/instanzen/#Instanz_hinzufügen)

The 'Froggit' module can be found under 'Add instance' using the quick filter.
- Further information on adding instances in the [documentation of the instances](https://www.symcon.de/en/service/documentation/concepts/instances/)

__Configuration__:

* In der Instanz können die bevorzugten Einheiten ausgewählt werden.
* Der Pfad zum hook kann eingestellt werden. Damit ist es möglich, mehrere Wetterstationen mit mehreren Instanzen einzubinden. Der Pfad muss immer mit "/hook/..." beginnen!

* The preferred units can be selected in the instance.
* The path to the hook can be set. This makes it possible to integrate several weather stations with several instances. The path must always begin with "/hook/..."! 

 ![Config_Instanz](../docs/Config_Instanz.PNG)

### 5. Status Variables und Profiles
#### Status Variables

* Die Statusvariablen werden automatisch angelegt. 
* Werden diese gelöscht, werden sie wieder angelegt. 
* Die Variablen können umbenannt werden.

* Status variables are created automatically.
* If these are deleted, they will be created again.
* The variables can be renamed. 

#### Profile

Name   | Typ
------ | -------
Froggit.Rain.Inch  |  float
Froggit.Light.wm2  |  integer
Froggit.Light.fc   |  integer
Froggit.AirPressure.inHg  |  float
Froggit.AirPressure.mmHg..|  float
Froggit.Wind.mph   |  float

### 6. WebFront

* Die Werte werden nur angezeigt, eine Bedienung ist nicht vorgesehen.
* The values are only displayed, operation is not intended. 
