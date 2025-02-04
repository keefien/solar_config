#!/usr/bin/php
<?php
/******************************************************************************
//  Mit diesem Script wird der richtige USB Port initialisiert.
//  Bitte nur Änderungen vornehmen, wenn man sich auskennt.
//  Version 16.8.2018
******************************************************************************/
$path_parts = pathinfo($argv[0]);
$Pfad = $path_parts['dirname'];
require($Pfad."/phpinc/funktionen.inc.php");
$Tracelevel = 8; //  1 bis 10  10 = Debug
$funktionen = new funktionen();
setlocale(LC_TIME,"de_DE.utf8");

if (is_file($Pfad."/1.user.config.php")) {
  require($Pfad."/1.user.config.php");
}
else {
  $funktionen->log_schreiben("Es ist keine '1.user.config.php' vorhanden.","!! ",6);
  $funktionen->log_schreiben("Fehler! Ende...","!! ",6);
  exit;
}

$funktionen->log_schreiben("Die seriellen Schnittstellen werden initialisiert.","   ",6);

//  Auf welcher Platine läuft die Software?
$Platine = file_get_contents("/sys/firmware/devicetree/base/model");

for ($i = 1; $i < 7; $i++) {

  $funktionen->log_schreiben("Schleife: ".$i,"   ",9);

  if (is_file($Pfad."/".$i.".user.config.php")) {
    require($Pfad."/".$i.".user.config.php");


    // Im Fall, dass keine Device nötig ist (Ethernetverbindung)
    if (!isset($USBDevice) or empty($USBDevice)) {
      $USBDevice = "Ethernet";
    }



    switch ($Regler) {
      case 1:
        // ivt-Hirschau Regler SCPlus und SCDPlus
        $rc = exec("stty -F  ".$USBDevice."  raw speed 9600 cs8 -iexten -echo -echoe -echok -onlcr -hupcl ignbrk time 5");
        $funktionen->log_schreiben("Device: ".$USBDevice." Geschwindigkeit: ".$rc,"   ",6);
      break;
      case 2:
        // Steca Regler Tarom 4545 und Tarom 6000-S
        $rc = exec("stty -F  ".$USBDevice."  38400 cs8 raw -iexten -echo -echoe -echok -onlcr -hupcl ignbrk time 5");
        $funktionen->log_schreiben("Device: ".$USBDevice." Geschwindigkeit: ".$rc,"   ",6);
      break;
      case 3:
        // Tracer Serie
        if (isset($SerielleGeschwindigkeit)) {
          $rc = exec("stty -F  ".$USBDevice."  raw speed ".$SerielleGeschwindigkeit." cs8 -iexten -echo -echoe -echok -onlcr -hupcl ignbrk time 5");
        }
        else {
          $rc = exec("stty -F  ".$USBDevice."  raw speed 115200 cs8  -iexten -echo -echoe -echok -onlcr -hupcl ignbrk time 5");
        }
        $funktionen->log_schreiben("Device: ".$USBDevice." Geschwindigkeit: ".$rc,"   ",6);
      break;
      case 4:
        // Victron-energy Regler der Serie BlueSolar
        $rc = exec("stty -F  ".$USBDevice."  raw speed 19200 cs8  -iexten -echo -echoe -echok -onlcr -hupcl ignbrk time 5");
        $funktionen->log_schreiben("Device: ".$USBDevice." Geschwindigkeit: ".$rc,"   ",6);
      break;
      case 5:
        // Micro-Wechselrichter von AEconversion
        if ($HF2211 === false) {
          if (isset($SerielleGeschwindigkeit)) {
            $rc = exec("stty -F  ".$USBDevice."  raw speed ".$SerielleGeschwindigkeit." cs8 -iexten -echo -echoe -echok -onlcr -hupcl ignbrk time 5");
          }
          else {
            $rc = exec("stty -F  ".$USBDevice."  raw speed 9600 cs8  -iexten -echo -echoe -echok -onlcr -hupcl ignbrk time 5");
          }
        }
        else {
          $funktionen->log_schreiben("Device: ".$USBDevice." Keine USB / Serielle Schnittstelle.","   ",6);
        }
      break;
      case 6:
        // Victron-energy Batterie Wächter BMV 700
        $rc = exec("stty -F  ".$USBDevice."  raw speed 19200 cs8  -iexten -echo -echoe -echok -onlcr -hupcl ignbrk time 5");
        $funktionen->log_schreiben("Device: ".$USBDevice." Geschwindigkeit: ".$rc,"   ",6);
      break;
      case 7:
        // Steca Solarix PLI 5000-48 und Baugleiche
        $funktionen->log_schreiben("Device: ".$USBDevice." > Hidraw Schnittstelle.","   ",6);
        //  Für baugleiche Geräte mit seriellem Anschluss
        if (substr($USBDevice, 0, 11)   == "/dev/ttyUSB") {
          if (isset($SerielleGeschwindigkeit)) {
            $rc = exec("stty -F  ".$USBDevice."  raw speed ".$SerielleGeschwindigkeit." cs8 -iexten -echo -echoe -echok -onlcr -hupcl ignbrk time 5");
          }
          else {
            $rc = exec("stty -F  ".$USBDevice."  raw speed 9600 cs8 -iexten -echo -echoe -echok -onlcr -hupcl ignbrk time 5");
          }
        }
      break;
      case 8:
        // InfiniSolar V Serie
        $funktionen->log_schreiben("Device: ".$USBDevice." Keine USB / Serielle Schnittstelle.","   ",6);
      break;
      case 9:
        // MPPSolar MPI Hybrid 3 Phasen
        $funktionen->log_schreiben("Device: ".$USBDevice." Keine USB / Serielle Schnittstelle.","   ",6);
      break;
      case 10:
        // SolarMax Ethernet Anschluss
        $funktionen->log_schreiben("Device: ".$USBDevice." Keine USB / Serielle Schnittstelle.","   ",6);
      break;
      case 11:
        // Victron-energy Phoenix Wechselrichter
        $rc = exec("stty -F  ".$USBDevice."  raw speed 19200 cs8  -iexten -echo -echoe -echok -onlcr -hupcl ignbrk time 5");
        $funktionen->log_schreiben("Device: ".$USBDevice." Geschwindigkeit: ".$rc,"   ",6);
      break;
      case 12:
        // Fronius Symo Serie - Ethernet Anschluss
        $funktionen->log_schreiben("Device: ".$USBDevice." Keine USB / Serielle Schnittstelle.","   ",6);
      break;
      case 13:
        // Autarctech BMS
        $rc = exec("stty -F  ".$USBDevice."  raw speed 115200 cs8 -iexten -echo -echoe -echok -onlcr -hupcl ignbrk time 5");
        $funktionen->log_schreiben("Device: ".$USBDevice." Geschwindigkeit: ".$rc,"   ",6);
      break;
      case 14:
        // Rover von Renogy
        $rc = exec("stty -F  ".$USBDevice."  raw speed 9600 cs8  -iexten -echo -echoe -echok -onlcr -hupcl ignbrk time 5");
        $funktionen->log_schreiben("Device: ".$USBDevice." Geschwindigkeit: ".$rc,"   ",6);
      break;
      case 15:
        // Pylontech US20000B
        $rc = exec("stty -F  ".$USBDevice."  raw speed 1200 cs8  -iexten -echo -echoe -echok -onlcr -hupcl ignbrk time 5");
        $funktionen->log_schreiben("Device: ".$USBDevice." Geschwindigkeit: ".$rc,"   ",6);
      break;
      case 16:
        // SolarEdge Ethernet LAN Anschluss
        $funktionen->log_schreiben("Device: ".$USBDevice." Keine USB / Serielle Schnittstelle.","   ",6);
      break;
      case 17:
        // KOSTAL Plenticore Ethernet LAN Anschluss
        $funktionen->log_schreiben("Device: ".$USBDevice." Keine USB / Serielle Schnittstelle.","   ",6);
      break;
      case 18:
        // S10 Wechselrichter von E3/DC mit Ethernet LAN Anschluss
        $funktionen->log_schreiben("Device: ".$USBDevice." Keine USB / Serielle Schnittstelle.","   ",6);
      break;
      case 19:
        // eSmart3 Laderegler
        if ($HF2211 === false) {
          $rc = exec("stty -F  ".$USBDevice."  raw speed 9600 cs8  -iexten -echo -echoe -echok -onlcr -hupcl ignbrk time 5");
          $funktionen->log_schreiben("Device: ".$USBDevice." Geschwindigkeit: ".$rc,"   ",6);
        }
        else {
          $funktionen->log_schreiben("Device: ".$USBDevice." Keine USB / Serielle Schnittstelle.","   ",6);
        }
      break;
      case 20:
        // SolarEdge Ethernet LAN Anschluss
        $funktionen->log_schreiben("Device: ".$USBDevice." Keine USB / Serielle Schnittstelle.","   ",6);
      break;
      case 21:
        // Kostal Pico mit RS485
        if ($HF2211 === false) {
          if (isset($SerielleGeschwindigkeit))  {
            $funktionen->log_schreiben("Device: ".$USBDevice." Geschwindigkeit ".$SerielleGeschwindigkeit." wird eingestellt.","   ",6);
            $rc = exec("stty -F  ".$USBDevice."  raw speed ".$SerielleGeschwindigkeit." cs8  -iexten -echo -echoe -echok -onlcr -hupcl ignbrk time 5");
          }
          else {
            $funktionen->log_schreiben("Device: ".$USBDevice." Geschwindigkeit 19200 wird eingestellt.","   ",6);
            $rc = exec("stty -F  ".$USBDevice."  raw speed 19200 cs8  -iexten -echo -echoe -echok -onlcr -hupcl ignbrk time 5");
          }
        }
        $funktionen->log_schreiben("Device: ".$USBDevice." Geschwindigkeit: ".exec('stty -F  '.$USBDevice.' speed'),"   ",6);
      break;
      case 22:
        // Kostal Smart Energy Meter
        $funktionen->log_schreiben("Device: ".$USBDevice." Keine USB / Serielle Schnittstelle.","   ",6);
      break;
      case 23:
        // SONOFF POW R2
        $funktionen->log_schreiben("Sonoff hat keine USB / Serielle Schnittstelle.","   ",6);
      break;
      case 24:
        // Infini 3KW Hybrid Wechselrichter
        $funktionen->log_schreiben("Device: ".$USBDevice." Keine USB / Serielle Schnittstelle.","   ",6);
      break;
      case 25:
        // Sonnen Batterie
        $funktionen->log_schreiben("Die Sonnen Batterie hat keine USB / Serielle Schnittstelle.","   ",6);
      break;
      case 26:
        // MPPSolar 5048MK und 5048GK
        $funktionen->log_schreiben("Device: ".$USBDevice." > HIDRAW Schnittstelle.","   ",6);
        //  Für baugleiche Geräte mit seriellem Anschluss
        if (substr($USBDevice, 0, 11)   == "/dev/ttyUSB") {
          if (isset($SerielleGeschwindigkeit)) {
            $rc = exec("stty -F  ".$USBDevice."  raw speed ".$SerielleGeschwindigkeit." cs8 -iexten -echo -echoe -echok -onlcr -hupcl ignbrk time 5");
          }
          else {
            $rc = exec("stty -F  ".$USBDevice."  raw speed 9600 cs8 -iexten -echo -echoe -echok -onlcr -hupcl ignbrk time 5");
          }
        }
      break;
      case 27:
        $USBRegler = "Ethernet";     // Dummy
        $USBDevice = "Ethernet";
        // SMA  Sunny Tripower mit LAN Anschluss
      break;
      case 28:
        // HRDi marlec Laderegler
        $rc = exec("stty -F  ".$USBDevice."  raw speed 115200 cs8 -iexten -echo -echoe -echok -onlcr -hupcl ignbrk time 5");
        $funktionen->log_schreiben("Device: ".$USBDevice." Geschwindigkeit: ".$rc,"   ",6);
      break;
      case 29:
        $USBRegler = "Ethernet";     // Dummy
        $USBDevice = "Ethernet";
        // go-e Charger Wallbox
      break;
      case 30:
        $USBRegler = "Ethernet";     // Dummy
        $USBDevice = "Ethernet";
        // Keba Wallbox
      break;
      case 31:
        $USBRegler = "Ethernet";     // Dummy
        $USBDevice = "Ethernet";
        // Shelly 3EM
      break;
      case 32:
        $USBRegler = "Ethernet";     // Dummy
        $USBDevice = "Ethernet";
        // KACO Wechselrichter
      break;
      case 33:
        if (isset($USB_Regler)) {
          $USBDevice = $USB_Regler;
          $funktionen->log_schreiben("Regler erkannt: ".$USB_Regler,"   ",6);
        }
        elseif (!isset($USBDevice) or empty($USBDevice) ) {
          $USBDevice = "/dev/ttyUSB0";
        }
        // Labornetzteil
          $rc = exec("stty -F  ".$USBDevice."  raw speed 38400 cs8 -iexten -echo -echoe -echok -onlcr -hupcl ignbrk time 5");
      break;
      case 34:
        if (isset($USB_Regler)) {
          $USBDevice = $USB_Regler;
          $funktionen->log_schreiben("Regler erkannt: ".$USB_Regler,"   ",6);
        }
        elseif (!isset($USBDevice) or empty($USBDevice) ) {
          $USBDevice = "/dev/ttyUSB0";
        }
        // SDM630 Smart Meter
        $rc = exec("stty -F  ".$USBDevice."  raw speed 19200 cs8 -iexten -echo -echoe -echok -onlcr -hupcl ignbrk time 5");
      break;
      case 35:
        $USBRegler = "Ethernet";     // Dummy
        $USBDevice = "Ethernet";
        // Wallbe Wallbox
      break;
      case 36:
        if (isset($USB_Regler)) {
          $USBDevice = $USB_Regler;
          $funktionen->log_schreiben("Regler erkannt: ".$USB_Regler,"   ",6);
        }
        elseif (!isset($USBDevice) or empty($USBDevice) ) {
          $USBDevice = "/dev/ttyUSB0";
        }
        // Delta Wechselrichter  RS485
        $rc = exec("stty -F  ".$USBDevice."  raw speed 19200 cs8 -iexten -echo -echoe -echok -onlcr -hupcl ignbrk time 5");
      break;
      case 37:
        $USBRegler = "Ethernet";     // Dummy
        $USBDevice = "Ethernet";
        // Simple EVSE WiFi Wallbox
      break;
      case 38:
        if (isset($USB_Regler)) {
          $USBDevice = $USB_Regler;
          $funktionen->log_schreiben("Regler erkannt: ".$USB_Regler,"   ",6);
        }
        elseif (!isset($USBDevice) or empty($USBDevice) ) {
          $USBDevice = "/dev/ttyUSB0";
        }
        // Alpha ESS Wechselrichter  RS485
        $rc = exec("stty -F  ".$USBDevice."  raw speed 9600 cs8 -iexten -echo -echoe -echok -onlcr -hupcl ignbrk time 5");
      break;
      case 39:
        $USBRegler = "Ethernet";     // Dummy
        $USBDevice = "Ethernet";
        // openWB Wallbox
      break;
      case 40:
        if (isset($USB_Regler)) {
          $USBDevice = $USB_Regler;
          $funktionen->log_schreiben("Regler erkannt: ".$USB_Regler,"   ",6);
        }
        elseif (!isset($USBDevice) or empty($USBDevice) ) {
          $USBDevice = "/dev/ttyUSB0";
        }
        // Phocos Wechselrichter  RS485
        $rc = exec("stty -F  ".$USBDevice."  raw speed 19200 cs8 -iexten -echo -echoe -echok -onlcr -hupcl ignbrk time 5");
      break;
      case 41:
        // Pylontech US30000A
        $rc = exec("stty -F  ".$USBDevice."  raw speed 115200 cs8  -iexten -echo -echoe -echok -onlcr -hupcl ignbrk time 5");
        $funktionen->log_schreiben("Device: ".$USBDevice." Geschwindigkeit: ".$rc,"   ",6);
      break;
      case 42:
        if (isset($USB_Regler)) {
          $USBDevice = $USB_Regler;
          $funktionen->log_schreiben("Regler erkannt: ".$USB_Regler,"   ",6);
        }
        elseif (!isset($USBDevice) or empty($USBDevice) ) {
          $USBDevice = "/dev/ttyUSB0";
        }
        // Pv18 VHM Serie mit RS485 Schnittstelle
        $rc = exec("stty -F  ".$USBDevice."  raw speed 19200 cs8 -iexten -echo -echoe -echok -onlcr -hupcl ignbrk time 5");
      break;

      case 43:
        $USBRegler = "Ethernet";     // Dummy
        $USBDevice = "Ethernet";
        // Senec Stromspeicher
      break;

      case 44:
        $USBRegler = "Ethernet";     // Dummy
        $USBDevice = "Ethernet";
        // Webasto Wallbox
      break;

      case 45:
        if (isset($USB_Regler)) {
          $USBDevice = $USB_Regler;
          $funktionen->log_schreiben("Regler erkannt: ".$USB_Regler,"   ",6);
        }
        elseif (!isset($USBDevice) or empty($USBDevice) ) {
          $USBDevice = "/dev/ttyUSB0";
        }
        // Phocos Any-Grid
        $rc = exec("stty -F  ".$USBDevice."  raw speed 2400 cs8 -iexten -echo -echoe -echok -onlcr -hupcl ignbrk time 5");
      break;

      case 46:
        if (isset($USB_Regler)) {
          $USBDevice = $USB_Regler;
          $funktionen->log_schreiben("Regler erkannt: ".$USB_Regler,"   ",6);
        }
        elseif (!isset($USBDevice) or empty($USBDevice) ) {
          $USBDevice = "/dev/ttyUSB0";
        }
        // Huawei Wechselrichter
        $rc = exec("stty -F  ".$USBDevice." raw speed 9600 cs8 -iexten -echo -echoe -echok -onlcr -hupcl ignbrk time 5");
      break;

      case 47:
        $USBRegler = "Ethernet";     // Dummy
        $USBDevice = "Ethernet";
        // Phoenix Contact Wallbox
      break;

      case 48:
        if (isset($USB_Regler)) {
          $USBDevice = $USB_Regler;
          $funktionen->log_schreiben("Regler erkannt: ".$USB_Regler,"   ",6);
        }
        elseif (!isset($USBDevice) or empty($USBDevice) ) {
          $USBDevice = "/dev/ttyUSB0";
        }
        // Growatt Wechselrichter
        $rc = exec("stty -F  ".$USBDevice." raw speed 9600 cs8  -iexten -echo -echoe -echok -onlcr -hupcl ignbrk time 5");
      break;

      case 49:
        $USBRegler = "Ethernet";     // Dummy
        $USBDevice = "Ethernet";
        // Huawei SmartLogger 3000
      break;

      case 50:
        if (isset($USB_Regler)) {
          $USBDevice = $USB_Regler;
          $funktionen->log_schreiben("Regler erkannt: ".$USB_Regler,"   ",6);
        }
        elseif (!isset($USBDevice) or empty($USBDevice) ) {
          $USBDevice = "/dev/ttyUSB0";
        }
        // SDM230 Smart Meter
        $rc = exec("stty -F  ".$USBDevice."  raw speed 9600 cs8 -iexten -echo -echoe -echok -onlcr -hupcl ignbrk time 5");
      break;

      case 51:
        $USBRegler = "Ethernet";     // Dummy
        $USBDevice = "Ethernet";
        // Siemens PAC2200 3 Phasen Zähler
      break;

      case 52:
        if (isset($USB_Regler)) {
          $USBDevice = $USB_Regler;
          $funktionen->log_schreiben("Regler erkannt: ".$USB_Regler,"   ",6);
        }
        elseif (!isset($USBDevice) or empty($USBDevice) ) {
          $USBDevice = "/dev/ttyUSB0";
        }
        // Goodwe Wechselrichter
        $rc = exec("stty -F  ".$USBDevice."  raw speed 9600 cs8 -iexten -echo -echoe -echok -onlcr -hupcl ignbrk time 5");
      break;

      case 53:
        if (isset($USB_Regler)) {
          $USBDevice = $USB_Regler;
          $funktionen->log_schreiben("Regler erkannt: ".$USB_Regler,"   ",6);
        }
        elseif (!isset($USBDevice) or empty($USBDevice) ) {
          $USBDevice = "/dev/ttyUSB0";
        }
        //Solarlog Pro 380 -Mod
        $rc = exec("stty -F  ".$USBDevice."  raw speed 9600 cs8 -iexten -echo -echoe -echok -onlcr -hupcl ignbrk time 5");
      break;

      case 54:
        $USBRegler = "Ethernet";     // Dummy
        $USBDevice = "Ethernet";
        // SMA Energy Meter 2.0
      break;

      case 55:
        if (isset($USB_Regler)) {
          $USBDevice = $USB_Regler;
          $funktionen->log_schreiben("Regler erkannt: ".$USB_Regler,"   ",6);
        }
        elseif (!isset($USBDevice) or empty($USBDevice) ) {
          $USBDevice = "/dev/ttyUSB0";
        }
        // Studer xtender Wechselrichter
        $rc = exec("stty -F  ".$USBDevice."  raw speed 9600 cs8 parenb -parodd -iexten -echo -echoe -echok -onlcr -hupcl ignbrk time 5");
      break;

      case 56:
        if (isset($USB_Regler)) {
          $USBDevice = $USB_Regler;
          $funktionen->log_schreiben("Regler erkannt: ".$USB_Regler,"   ",6);
        }
        elseif (!isset($USBDevice) or empty($USBDevice) ) {
          $USBDevice = "/dev/ttyUSB0";
        }
        // Huawei M1 Modelle
        $rc = exec("stty -F  ".$USBDevice." raw speed 9600 cs8 -iexten -echo -echoe -echok -onlcr -hupcl ignbrk time 5");
      break;

      case 57:
        if (isset($USB_Regler)) {
          $USBDevice = $USB_Regler;
          $funktionen->log_schreiben("Regler erkannt: ".$USB_Regler,"   ",6);
        }
        elseif (!isset($USBDevice) or empty($USBDevice) ) {
          $USBDevice = "/dev/ttyUSB0";
        }
        //Daly BMS China
        $rc = exec("stty -F  ".$USBDevice."  raw speed 9600 cs8 -iexten -echo -echoe -echok -onlcr -hupcl ignbrk time 5");
      break;

      case 58: // Solaredge WND-3Y-400-MB
        if (isset($USB_Regler)) {
          $USBDevice = $USB_Regler;
          $funktionen->log_schreiben("Regler erkannt: ".$USB_Regler,"   ",6);
        }
        elseif (!isset($USBDevice) or empty($USBDevice) ) {
          $USBDevice = "/dev/ttyUSB0";
        }
        //Solaredge WND
        $rc = exec("stty -F  ".$USBDevice."  raw speed 19200 cs8 -iexten -echo -echoe -echok -onlcr -hupcl ignbrk time 5");
      break;

      case 59:
        if (isset($USB_Regler)) {
          $USBDevice = $USB_Regler;
          $funktionen->log_schreiben("Regler erkannt: ".$USB_Regler,"   ",6);
        }
        elseif (!isset($USBDevice) or empty($USBDevice) ) {
          $USBDevice = "/dev/ttyUSB0";
        }
        // EASUN POWER Wechselrichter
        $rc = exec("stty -F  ".$USBDevice."  raw speed 2400 cs8  -iexten -echo -echoe -echok -onlcr -hupcl ignbrk time 5");
        $funktionen->log_schreiben("Device: ".$USBDevice." Geschwindigkeit: ".$rc,"   ",6);
      break;

      case 60:
        $USBRegler = "Ethernet";     // Dummy
        $USBDevice = "Ethernet";
        // Hardy Barth Wallbox
      break;

      case 61:
        $USBRegler = "Ethernet";     // Dummy
        $USBDevice = "Ethernet";
        // SMARTPI  Zähler
      break;

      case 62:
        $USBRegler = "Ethernet";     // Dummy
        $USBDevice = "Ethernet";
        // Huawei Wechselrichter mit SDongle
      break;

      case 63:
        $USBRegler = "Ethernet";     // Dummy
        $USBDevice = "Ethernet";
        // cFos Wallbox
      break;

      case 64:
        if (isset($USB_Regler)) {
          $USBDevice = $USB_Regler;
          $funktionen->log_schreiben("Regler erkannt: ".$USB_Regler,"   ",6);
        }
        elseif (!isset($USBDevice) or empty($USBDevice) ) {
          $USBDevice = "/dev/ttyUSB0";
        }
        // Goodwe Wechselrichter ET Serie
        $rc = exec("stty -F  ".$USBDevice."  raw speed 9600 cs8 -iexten -echo -echoe -echok -onlcr -hupcl ignbrk time 5");
      break;

      case 65:
        $USBRegler = "Ethernet";     // Dummy
        $USBDevice = "Ethernet";
        // RCT Wechselrichter
      break;

      case 66:
        $USBRegler = "Ethernet";     // Dummy
        $USBDevice = "Ethernet";
        // Kostal Piko CI
      break;

      case 67:
        if (isset($USB_Regler)) {
          $USBDevice = $USB_Regler;
          $funktionen->log_schreiben("Regler erkannt: ".$USB_Regler,"   ",6);
        }
        elseif (!isset($USBDevice) or empty($USBDevice) ) {
          $USBDevice = "/dev/ttyUSB0";
        }
        // Goodwe Wechselrichter
        $rc = exec("stty -F  ".$USBDevice."  raw speed 9600 cs8 -iexten -echo -echoe -echok -onlcr -hupcl ignbrk time 5");
      break;

      case 68:
        $USBRegler = "Ethernet";     // Dummy
        $USBDevice = "Ethernet";
        // VARTA Pulse Neo Speichersystem
      break;

      case 69:
        $USBRegler = "Ethernet";     // Dummy
        $USBDevice = "Ethernet";
        // Vestel Wallbox
      break;

      case 70:
        $USBRegler = "Ethernet";     // Dummy
        $USBDevice = "Ethernet";
        // Sungrow Wechselrichter
      break;

      case 71:
        if (isset($USB_Regler)) {
          $USBDevice = $USB_Regler;
          $funktionen->log_schreiben("Regler erkannt: ".$USB_Regler,"   ",6);
        }
        elseif (!isset($USBDevice) or empty($USBDevice) ) {
          $USBDevice = "/dev/ttyUSB0";
        }
        // EASUN SMG II Wechselrichter
        $rc = exec("stty -F  ".$USBDevice."  raw speed 9600 cs8 -iexten -echo -echoe -echok -onlcr -hupcl ignbrk time 5");
      break;

      case 72:
        $USBRegler = "Ethernet";     // Dummy
        $USBDevice = "Ethernet";
        // HomeMatic Gaszähler
      break;

      case 73:
        if (isset($USB_Regler)) {
          $USBDevice = $USB_Regler;
          $funktionen->log_schreiben("Regler erkannt: ".$USB_Regler,"   ",6);
        }
        elseif (!isset($USBDevice) or empty($USBDevice) ) {
          $USBDevice = "/dev/ttyUSB0";
        }
        // SofarSolar  Wechselrichter
        $rc = exec("stty -F  ".$USBDevice."  raw speed 9600 cs8 -iexten -echo -echoe -echok -onlcr -hupcl ignbrk time 5");
      break;

      case 74:
        $USBRegler = "Ethernet";     // Dummy
        $USBDevice = "Ethernet";
        // Carlo Gavazzi EM24 Meter
      break;

      case 75:
        if (isset($USB_Regler)) {
          $USBDevice = $USB_Regler;
          $funktionen->log_schreiben("Regler erkannt: ".$USB_Regler,"   ",6);
        }
        elseif (!isset($USBDevice) or empty($USBDevice) ) {
          $USBDevice = "/dev/ttyUSB0";
        }
        // Hager Meter mit RS485 Schnittstelle
        $rc = exec("stty -F  ".$USBDevice."  raw speed 19200 cs8 -iexten -echo -echoe -echok -onlcr -hupcl ignbrk time 5");
      break;

      case 76:
        $USBRegler = "Ethernet";     // Dummy
        $USBDevice = "Ethernet";
        // Hoymiles Microwechselrichter
      break;

      case 77:
        if (isset($USB_Regler)) {
          $USBDevice = $USB_Regler;
          $funktionen->log_schreiben("Regler erkannt: ".$USB_Regler,"   ",6);
        }
        elseif (!isset($USBDevice) or empty($USBDevice) ) {
          $USBDevice = "/dev/ttyUSB0";
        }
        // AX Licom-Box von Effekta
        $rc = exec("stty -F  ".$USBDevice."  raw speed 19200 cs8 -iexten -echo -echoe -echok -onlcr -hupcl ignbrk time 5");
      break;

      case 78:
        $USBRegler = "Ethernet";     // Dummy
        $USBDevice = "Ethernet";
        // Innogy Wallbox
      break;

      case 79:
        $USBRegler = "Ethernet";     // Dummy
        $USBDevice = "Ethernet";
        // WEM3080t
      break;

      case 80:
        if (isset($USB_Regler)) {
          $USBDevice = $USB_Regler;
          $funktionen->log_schreiben("Regler erkannt: ".$USB_Regler,"   ",6);
        }
        elseif (!isset($USBDevice) or empty($USBDevice) ) {
          $USBDevice = "/dev/ttyUSB0";
        }
        // Solax X3 POWER Wechselrichter
        if (isset($SerielleGeschwindigkeit)) {
          $rc = exec("stty -F  ".$USBDevice."  raw speed ".$SerielleGeschwindigkeit." cs8 -iexten -echo -echoe -echok -onlcr -hupcl ignbrk time 5");
        }
        else {
          $rc = exec("stty -F  ".$USBDevice."  raw speed 19200 cs8 -iexten -echo -echoe -echok -onlcr -hupcl ignbrk time 5");
        }
      break;

      case 81:
        $USBRegler = "Ethernet";     // Dummy
        $USBDevice = "Ethernet";
        // my-PV THOR
      break;

      case 82:
        if (isset($USB_Regler)) {
          $USBDevice = $USB_Regler;
          $funktionen->log_schreiben("Regler erkannt: ".$USB_Regler,"   ",6);
        }
        elseif (!isset($USBDevice) or empty($USBDevice) ) {
          $USBDevice = "/dev/ttyUSB0";
        }
        // Solis Wechselrichter  RS485
        $rc = exec("stty -F  ".$USBDevice."  raw speed 9600 cs8 -iexten -echo -echoe -echok -onlcr -hupcl ignbrk time 5");
      break;

      case 83:
        if (isset($USB_Regler)) {
          $USBDevice = $USB_Regler;
          $funktionen->log_schreiben("Regler erkannt: ".$USB_Regler,"   ",6);
        }
        elseif (!isset($USBDevice) or empty($USBDevice) ) {
          $USBDevice = "/dev/ttyUSB0";
        }
        // JK-BMS  RS485
        $rc = exec("stty -F  ".$USBDevice."  raw speed 115200 cs8 -iexten -echo -echoe -echok -onlcr -hupcl ignbrk time 5");
      break;

      default:
        /***********************************************************************
        //  User PHP Script, falls gewünscht oder nötig
        ***********************************************************************/
        if ( file_exists ("/var/www/html/user_init.php")) {
          $funktionen->log_schreiben("Datei 'user_init.php' gefunden.","   ",8);
          include 'user_init.php';  // Vom Benutzer selber geschriebene Datei.
        }
        else {
          $funktionen->log_schreiben("Angegebener Regler ungültig. ".$Regler,"   ",2);
        }
      break;
    }

    $INI = file($Pfad."/".$i.".user.config.php");
    foreach($INI as $index => $item){
      if(strpos($item,'$Platine')!== false){
        $funktionen->log_schreiben("Zeile gefunden. Raspberry Modell kann eingetragen werden. Index: ".$index."   ".$INI[$index],"   ",5);
        $Zeile3 = $index;
      }
      if(strpos($item,'$GeraeteNummer')!== false){
        $funktionen->log_schreiben("Zeile gefunden. Gerätenummer kann ausgetauscht werden. Index: ".$index."   ".$INI[$index],"   ",5);
        $Zeile1 = $index;
      }
    }
    $INI[$Zeile1] = "\$GeraeteNummer = \"".$i."\";\n";
    $INI[$Zeile3] = "\$Platine = \"".trim($Platine)."\";\n";
    $rc = file_put_contents ($Pfad."/".$i.".user.config.php",$INI ) ;
  }
}

if(PHP_INT_SIZE>4)
  $funktionen->log_schreiben("Es handelt sich um ein 64 Bit System.","   ",5);
else
  $funktionen->log_schreiben("Es handelt sich um ein 32 Bit System.","   ",5);


exit;



?>
