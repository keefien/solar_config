#!/usr/bin/php
<?php
/*****************************************************************************
//  Solaranzeige Projekt             Copyright (C) [2015-2020]  [Ulrich Kunz]
//
//  Dieses Programm ist freie Software. Sie können es unter den Bedingungen
//  der GNU General Public License, wie von der Free Software Foundation
//  veröffentlicht, weitergeben und/oder modifizieren, entweder gemäß
//  Version 3 der Lizenz oder (nach Ihrer Option) jeder späteren Version.
//
//  Die Veröffentlichung dieses Programms erfolgt in der Hoffnung, daß es
//  Ihnen von Nutzen sein wird, aber OHNE IRGENDEINE GARANTIE, sogar ohne
//  die implizite Garantie der MARKTREIFE oder der VERWENDBARKEIT FÜR EINEN
//  BESTIMMTEN ZWECK. Details finden Sie in der GNU General Public License.
//
//  Ein original Exemplar der GNU General Public License finden Sie hier:
//  http://www.gnu.org/licenses/
//
//  Dies ist ein Programmteil des Programms "Solaranzeige"
//
//  Es dient dem Auslesen des Wechselrichters Solax X3 über eine RS485
//  Schnittstelle mit USB Adapter. 
//  Das Auslesen wird hier mit einer Schleife durchgeführt. Wie oft die Daten
//  ausgelesen und gespeichert werden steht in der user.config.php
//
//
*****************************************************************************/
$path_parts = pathinfo($argv[0]);
$Pfad = $path_parts['dirname'];
if (!is_file($Pfad."/1.user.config.php")) {
  // Handelt es sich um ein Multi Regler System?
  require($Pfad."/user.config.php");
}

require_once($Pfad."/phpinc/funktionen.inc.php");
if (!isset($funktionen)) {
  $funktionen = new funktionen();
}

// Im Fall, dass man die Device manuell eingeben muss
if (isset($USBDevice) and !empty($USBDevice)) {
  $USBRegler = $USBDevice;
}

$Tracelevel = 7;  //  1 bis 10  10 = Debug
$RemoteDaten = true;
$Start = time();  // Timestamp festhalten
$funktionen->log_schreiben("----------------------   Start  solax_wr.php   --------------------- ","|--",6);

$funktionen->log_schreiben("Zentraler Timestamp: ".$zentralerTimestamp,"   ",8);
$aktuelleDaten = array();
$aktuelleDaten["zentralerTimestamp"] = $zentralerTimestamp;
$aktuelleDaten["KeineSonne"] = false;


setlocale(LC_TIME,"de_DE.utf8");


//  Hardware Version ermitteln.
//  $Platine = "Raspberry Pi Model B Plus Rev 1.2";
$Teile =  explode(" ",$Platine);
if ($Teile[1] == "Pi") {
  $funktionen->log_schreiben("Hardware Version: ".$Platine,"o  ",8);
  $Version = trim($Teile[2]);
  if ($Teile[3] == "Model") {
    $Version .= trim($Teile[4]);
    if ($Teile[5] == "Plus") {
      $Version .= trim($Teile[5]);
    }
  }
}

switch($Version) {
  case "2B":
  break;
  case "3B":
  break;
  case "3BPlus":
  break;
  case "4B":
  break;
  default:
  break;
}

if (empty($WR_Adresse)) {
  $WR_ID = "01";
}
elseif(strlen($WR_Adresse) == 1)  {
  $WR_ID = str_pad(dechex($WR_Adresse),2,"0",STR_PAD_LEFT);
}
elseif(strlen($WR_Adresse) == 2)  {
  $WR_ID = str_pad(dechex(substr($WR_Adresse,-2)),2,"0",STR_PAD_LEFT);
}
else {
  $WR_ID = dechex($WR_Adresse);
}


$funktionen->log_schreiben("WR_ID: ".$WR_ID,"+  ",8);


$USB1 = $funktionen->openUSB($USBRegler);
if (!is_resource($USB1)) {
  $funktionen->log_schreiben("USB Port kann nicht geöffnet werden. [1]","XX ",7);
  $funktionen->log_schreiben("Exit.... ","XX ",7);
  goto Ausgang;
}

$i = 1;
do {
  $funktionen->log_schreiben("Die Daten werden ausgelesen...","+  ",9);

  /****************************************************************************
  //  Ab hier wird der Regler ausgelesen.
  //
  //
  ****************************************************************************/

  $aktuelleDaten["Fehler1"] = 0;
  $aktuelleDaten["Modell"] = "";
  $aktuelleDaten["Laufzeit_Heute"] = 0;


  $Befehl["DeviceID"] = $WR_ID;   
  $Befehl["BefehlFunctionCode"] = "03";
  $Befehl["RegisterAddress"] = "0000";  // in HEX
  $Befehl["RegisterCount"] = "0007";
  $rc = $funktionen->phocos_pv18_auslesen($USB1,$Befehl);
  $aktuelleDaten["Seriennummer"] = $funktionen->hex2string($rc["data"]);
  if ($rc["ok"] == 0 or $rc["data"] == 0) {
    $funktionen->log_schreiben("Der Wechselrichter sendet keine Daten.","   ",5);
    goto Ausgang;
  }


  $Befehl["RegisterAddress"] = "0007";  // in HEX
  $Befehl["RegisterCount"] = "0007";
  $rc = $funktionen->phocos_pv18_auslesen($USB1,$Befehl);
  $aktuelleDaten["Firmenname"] = $funktionen->hex2string($rc["data"]);
  if ($rc["ok"] == 0) {
    $funktionen->log_schreiben("Der Wechselrichter sendet keine Daten.","   ",5);
    goto Ausgang;
  }

  $Befehl["RegisterAddress"] = "000E";  // in HEX
  $Befehl["RegisterCount"] = "0007";
  $rc = $funktionen->phocos_pv18_auslesen($USB1,$Befehl);
  $aktuelleDaten["Modell"] = $funktionen->hex2string($rc["data"]);
  if ($rc["ok"] == 0) {
    $funktionen->log_schreiben("Der Wechselrichter sendet keine Daten.","   ",5);
    goto Ausgang;
  }

  $Befehl["RegisterAddress"] = "0105";  // in HEX
  $Befehl["RegisterCount"] = "0001";
  $rc = $funktionen->phocos_pv18_auslesen($USB1,$Befehl);
  $aktuelleDaten["Type"] = hexdec($rc["data"]);
  if ($rc["ok"] == 0) {
    $funktionen->log_schreiben("Der Wechselrichter sendet keine Daten.","   ",5);
    goto Ausgang;
  }

  $funktionen->log_schreiben("Es handelt sich um ein ".$aktuelleDaten["Firmenname"]." X".$aktuelleDaten["Type"]." Modell.","   ",5);

  $Befehl["RegisterAddress"] = "0107";  // in HEX
  $Befehl["RegisterCount"] = "0001";
  $rc = $funktionen->phocos_pv18_auslesen($USB1,$Befehl);
  $aktuelleDaten["Hybrid"] = hexdec($rc["data"]);
  // Hybrid = 0 = true
  if ($rc["ok"] == 0) {
    $funktionen->log_schreiben("Der Wechselrichter sendet keine Daten.","   ",5);
    goto Ausgang;
  }
  
  $Befehl["RegisterAddress"] = "0108";  // in HEX
  $Befehl["RegisterCount"] = "0001";
  $rc = $funktionen->phocos_pv18_auslesen($USB1,$Befehl);
  $aktuelleDaten["Meter"] = hexdec($rc["data"]);
  // Meter = 1 = true
  if ($rc["ok"] == 0) {
    $funktionen->log_schreiben("Der Wechselrichter sendet keine Daten.","   ",5);
    goto Ausgang;
  }

  if ($aktuelleDaten["Type"] == 1) {
    // X1 Modelle

    $Befehl["BefehlFunctionCode"] = "04";
    $Befehl["RegisterAddress"] = "0000";  // in HEX
    $Befehl["RegisterCount"] = "0003";
    $rc = $funktionen->phocos_pv18_auslesen($USB1,$Befehl);
    $aktuelleDaten["AC_Spannung"] = (hexdec(substr($rc["data"],0,4))/10);
    $aktuelleDaten["AC_Strom"] = (hexdec(substr($rc["data"],4,4))/10);
    $aktuelleDaten["AC_Leistung"] = hexdec(substr($rc["data"],8,4));
    if ($rc["ok"] == 0) {
      $funktionen->log_schreiben("Fehler beim Auslesen.","   ",5);
      $aktuelleDaten["AC_Spannung"] = 0;
      $aktuelleDaten["AC_Strom"] = 0;
      $aktuelleDaten["AC_Leistung"] = 0;
    }




    $Befehl["RegisterAddress"] = "0007";  // in HEX
    $Befehl["RegisterCount"] = "0001";
    $rc = $funktionen->phocos_pv18_auslesen($USB1,$Befehl);
    $aktuelleDaten["AC_Frequenz"] = (hexdec(substr($rc["data"],0,2))/100);
    if ($rc["ok"] == 0) {
      $funktionen->log_schreiben("Fehler beim Auslesen.","   ",5);
      $aktuelleDaten["AC_Frequenz"] = 0;
    }


  }
  elseif ($aktuelleDaten["Type"] == 3) {
    // X3 Modelle
    $Befehl["BefehlFunctionCode"] = "04";
    $Befehl["RegisterAddress"] = "006A";  // in HEX
    $Befehl["RegisterCount"] = "0004";
    $rc = $funktionen->phocos_pv18_auslesen($USB1,$Befehl);
    $aktuelleDaten["AC_Spannung_R"] = (hexdec(substr($rc["data"],0,4))/10);
    $aktuelleDaten["AC_Strom_R"] = (hexdec(substr($rc["data"],4,4))/10);
    $aktuelleDaten["AC_Leistung_R"] = hexdec(substr($rc["data"],8,4));
    $aktuelleDaten["AC_Frequenz"] = (hexdec(substr($rc["data"],12,4))/100);
    if ($rc["ok"] == 0) {
      $funktionen->log_schreiben("Fehler beim Auslesen.","   ",5);
      $aktuelleDaten["AC_Spannung_R"] = 0;
      $aktuelleDaten["AC_Strom_R"] = 0;
      $aktuelleDaten["AC_Leistung_R"] = 0;
      $aktuelleDaten["AC_Frequenz"] = 0;
    }

    $Befehl["RegisterAddress"] = "006E";  // in HEX
    $Befehl["RegisterCount"] = "0003";
    $rc = $funktionen->phocos_pv18_auslesen($USB1,$Befehl);
    $aktuelleDaten["AC_Spannung_S"] = (hexdec(substr($rc["data"],0,4))/10);
    $aktuelleDaten["AC_Strom_S"] = (hexdec(substr($rc["data"],4,4))/10);
    $aktuelleDaten["AC_Leistung_S"] = hexdec(substr($rc["data"],8,4));
    if ($rc["ok"] == 0) {
      $funktionen->log_schreiben("Fehler beim Auslesen.","   ",5);
      $aktuelleDaten["AC_Spannung_S"] = 0;
      $aktuelleDaten["AC_Strom_S"] = 0;
      $aktuelleDaten["AC_Leistung_S"] = 0;
    }

    $Befehl["RegisterAddress"] = "0072";  // in HEX
    $Befehl["RegisterCount"] = "0003";
    $rc = $funktionen->phocos_pv18_auslesen($USB1,$Befehl);
    $aktuelleDaten["AC_Spannung_T"] = (hexdec(substr($rc["data"],0,4))/10);
    $aktuelleDaten["AC_Strom_T"] = (hexdec(substr($rc["data"],4,4))/10);
    $aktuelleDaten["AC_Leistung_T"] = hexdec(substr($rc["data"],8,4));
    if ($rc["ok"] == 0) {
      $funktionen->log_schreiben("Fehler beim Auslesen.","   ",5);
      $aktuelleDaten["AC_Spannung_T"] = 0;
      $aktuelleDaten["AC_Strom_T"] = 0;
      $aktuelleDaten["AC_Leistung_T"] = 0;
    }

    $Befehl["RegisterAddress"] = "0091";  // in HEX
    $Befehl["RegisterCount"] = "0003";
    $rc = $funktionen->phocos_pv18_auslesen($USB1,$Befehl);
    $aktuelleDaten["LadeleistungHeute"] = (hexdec(substr($rc["data"],0,4))*100);
    $aktuelleDaten["LadeleistungTotal"] = (hexdec(substr($rc["data"],8,4).substr($rc["data"],4,4))*100);
    if ($rc["ok"] == 0) {
      $funktionen->log_schreiben("Fehler beim Auslesen.","   ",5);
      $aktuelleDaten["LadeleistungHeute"] = 0;
      $aktuelleDaten["LadeleistungTotal"] = 0;
    }

    $Befehl["RegisterAddress"] = "0094";  // in HEX
    $Befehl["RegisterCount"] = "0003";
    $rc = $funktionen->phocos_pv18_auslesen($USB1,$Befehl);
    $aktuelleDaten["Energie_Total"] = (hexdec(substr($rc["data"],4,4).substr($rc["data"],0,4))*100);
    $aktuelleDaten["WattstundenGesamtHeute"] = (hexdec(substr($rc["data"],8,4))*100);
    if ($rc["ok"] == 0) {
      $funktionen->log_schreiben("Fehler beim Auslesen.","   ",5);
      $aktuelleDaten["WattstundenGesamtHeute"] = 0;
      $aktuelleDaten["Energie_Total"] = 0;
    }

    $Befehl["RegisterAddress"] = "0098";  // in HEX
    $Befehl["RegisterCount"] = "0002";
    $rc = $funktionen->phocos_pv18_auslesen($USB1,$Befehl);
    $aktuelleDaten["BezugHeute"] = (hexdec(substr($rc["data"],4,4).substr($rc["data"],0,4))*10);
    if ($rc["ok"] == 0) {
      $funktionen->log_schreiben("Fehler beim Auslesen.","   ",5);
      $aktuelleDaten["BezugHeute"] = 0;
    }

    $Befehl["RegisterAddress"] = "009A";  // in HEX
    $Befehl["RegisterCount"] = "0002";
    $rc = $funktionen->phocos_pv18_auslesen($USB1,$Befehl);
    $aktuelleDaten["VerbrauchHeute"] = (hexdec(substr($rc["data"],4,4).substr($rc["data"],0,4))*10);
    if ($rc["ok"] == 0) {
      $funktionen->log_schreiben("Fehler beim Auslesen.","   ",5);
      $aktuelleDaten["VerbrauchHeute"] = 0;
    }

    $Befehl["RegisterAddress"] = "00BA";  // in HEX
    $Befehl["RegisterCount"] = "0002";
    $rc = $funktionen->phocos_pv18_auslesen($USB1,$Befehl);
    $aktuelleDaten["BatterieTemperaturMax"] = (hexdec(substr($rc["data"],0,4))/10);
    $aktuelleDaten["BatterieTemperaturMin"] = (hexdec(substr($rc["data"],4,4))/10);
    if ($rc["ok"] == 0) {
      $funktionen->log_schreiben("Fehler beim Auslesen.","   ",5);
      $aktuelleDaten["BatterieTemperaturMax"] = 0;
      $aktuelleDaten["BatterieTemperaturMin"] = 0;
    }

    $Befehl["RegisterAddress"] = "011B";  // in HEX
    $Befehl["RegisterCount"] = "0001";
    $rc = $funktionen->phocos_pv18_auslesen($USB1,$Befehl);
    $aktuelleDaten["SOC"] = hexdec($rc["data"]);
    if ($rc["ok"] == 0) {
      $funktionen->log_schreiben("Fehler beim Auslesen.","   ",5);
      $aktuelleDaten["SOC"] = 0;
    }


    $aktuelleDaten["AC_Leistung"] = $aktuelleDaten["AC_Leistung_R"] + $aktuelleDaten["AC_Leistung_S"] +  $aktuelleDaten["AC_Leistung_T"];

  }
  else {
    $funktionen->log_schreiben("Unbekanntes Modell. Bitte melden: support@solaranzeige.de","   ",5);
  }



  $Befehl["RegisterAddress"] = "0003";  // in HEX
  $Befehl["RegisterCount"] = "0004";
  $rc = $funktionen->phocos_pv18_auslesen($USB1,$Befehl);
  $aktuelleDaten["PV1_Spannung"] = (hexdec(substr($rc["data"],0,4))/10);
  $aktuelleDaten["PV2_Spannung"] = (hexdec(substr($rc["data"],4,4))/10);
  $aktuelleDaten["PV1_Strom"] = (hexdec(substr($rc["data"],8,4))/10);
  $aktuelleDaten["PV2_Strom"] = (hexdec(substr($rc["data"],12,4))/10);
  if ($rc["ok"] == 0) {
    $funktionen->log_schreiben("Fehler beim Auslesen.","   ",5);
    $aktuelleDaten["PV1_Spannung"] = 0;
    $aktuelleDaten["PV1_Strom"] = 0;
    $aktuelleDaten["PV2_Spannung"] = 0;
    $aktuelleDaten["PV2_Strom"] = 0;
  }

  $Befehl["RegisterAddress"] = "0008";  // in HEX
  $Befehl["RegisterCount"] = "0002";
  $rc = $funktionen->phocos_pv18_auslesen($USB1,$Befehl);
  $aktuelleDaten["Temperatur"] = hexdec(substr($rc["data"],0,4));
  $aktuelleDaten["Mode"] = hexdec(substr($rc["data"],4,4));
  if ($rc["ok"] == 0) {
    $funktionen->log_schreiben("Fehler beim Auslesen.","   ",5);
    $aktuelleDaten["Temperatur"] = 0;
    $aktuelleDaten["Mode"] = 0;
  }


  $Befehl["RegisterAddress"] = "0009";  // in HEX
  $Befehl["RegisterCount"] = "0001";
  $rc = $funktionen->phocos_pv18_auslesen($USB1,$Befehl);
  $aktuelleDaten["Status"] = hexdec($rc["data"]);
  if ($rc["ok"] == 0) {
    $funktionen->log_schreiben("Fehler beim Auslesen.","   ",5);
    $aktuelleDaten["Status"] = 0;
  }


  $Befehl["RegisterAddress"] = "000A";  // in HEX
  $Befehl["RegisterCount"] = "0002";
  $rc = $funktionen->phocos_pv18_auslesen($USB1,$Befehl);
  $aktuelleDaten["PV1_Leistung"] = hexdec(substr($rc["data"],0,4));
  $aktuelleDaten["PV2_Leistung"] = hexdec(substr($rc["data"],4,4));
  if ($rc["ok"] == 0) {
    $funktionen->log_schreiben("Fehler beim Auslesen.","   ",5);
    $aktuelleDaten["PV1_Leistung"] = 0;
    $aktuelleDaten["PV2_Leistung"] = 0;
  }

  $Befehl["RegisterAddress"] = "0040";  // in HEX
  $Befehl["RegisterCount"] = "0002";
  $rc = $funktionen->phocos_pv18_auslesen($USB1,$Befehl);
  $aktuelleDaten["Fehler1"] = hexdec(substr($rc["data"],4,4).substr($rc["data"],0,4));
  if ($rc["ok"] == 0) {
    $funktionen->log_schreiben("Fehler beim Auslesen.","   ",5);
    $aktuelleDaten["Fehler1"] = 0;
  }

  $Befehl["RegisterAddress"] = "0046";  // in HEX
  $Befehl["RegisterCount"] = "0002";
  $rc = $funktionen->phocos_pv18_auslesen($USB1,$Befehl);
  $aktuelleDaten["Bezug_Einspeisung"] = $funktionen->hexdecs(substr($rc["data"],4,4).substr($rc["data"],0,4));
  if ($rc["ok"] == 0) {
    $funktionen->log_schreiben("Fehler beim Auslesen.","   ",5);
    $aktuelleDaten["Bezug_Einspeisung"] = 0;
  }


  $Befehl["RegisterAddress"] = "0050";  // in HEX
  $Befehl["RegisterCount"] = "0004";
  $rc = $funktionen->phocos_pv18_auslesen($USB1,$Befehl);
  $aktuelleDaten["EinspeisungHeute"] = (hexdec(substr($rc["data"],0,4))*100);
  $aktuelleDaten["EinspeisungGesamt"] = (hexdec(substr($rc["data"],12,4).substr($rc["data"],8,4))*100);
  if ($rc["ok"] == 0) {
    $funktionen->log_schreiben("Fehler beim Auslesen.","   ",5);
    $aktuelleDaten["EinspeisungHeute"] = 0;
    $aktuelleDaten["EinspeisungGesamt"] = 0;
  }

  $Befehl["RegisterAddress"] = "0088";  // in HEX
  $Befehl["RegisterCount"] = "0002";
  $rc = $funktionen->phocos_pv18_auslesen($USB1,$Befehl);
  $aktuelleDaten["Laufzeit_Gesamt"] = (hexdec(substr($rc["data"],4,4).substr($rc["data"],0,4))/10);
  if ($rc["ok"] == 0) {
    $funktionen->log_schreiben("Fehler beim Auslesen.","   ",5);
    $aktuelleDaten["Laufzeit_Gesamt"] = 0;
  }



  /****************************************************************************
  //  ENDE REGLER AUSLESEN      ENDE REGLER AUSLESEN      ENDE REGLER AUSLESEN
  ****************************************************************************/

  $aktuelleDaten["PV_Leistung"] = $aktuelleDaten["PV1_Leistung"] + $aktuelleDaten["PV2_Leistung"];

  /**************************************************************************
  //  Falls ein ErrorCode vorliegt, wird er hier in einen lesbaren
  //  Text umgewandelt, sodass er als Fehlermeldung gesendet werden kann.
  //  Die Funktion ist noch nicht überall implementiert.
  **************************************************************************/
  $FehlermeldungText = "";



  /****************************************************************************
  //  Die Daten werden für die Speicherung vorbereitet.
  ****************************************************************************/
  $aktuelleDaten["Regler"] = $Regler;
  $aktuelleDaten["Objekt"] = $Objekt;
  $aktuelleDaten["Produkt"] = $aktuelleDaten["Firmenname"];
  $aktuelleDaten["zentralerTimestamp"] = ($aktuelleDaten["zentralerTimestamp"]+10);


  $funktionen->log_schreiben(print_r($aktuelleDaten,1),"   ",8);



  /****************************************************************************
  //  User PHP Script, falls gewünscht oder nötig
  ****************************************************************************/
  if ( file_exists ("/var/www/html/solax_wr_math.php")) {
    include 'solax_wr_math.php';  // Falls etwas neu berechnet werden muss.
  }


  /**************************************************************************
  //  Alle ausgelesenen Daten werden hier bei Bedarf als mqtt Messages
  //  an den mqtt-Broker Mosquitto gesendet.
  //  Achtung! Die Übertragung dauert ca. 30 Sekunden!
  **************************************************************************/
  if ($MQTT and strtoupper($MQTTAuswahl) != "OPENWB") {
    $funktionen->log_schreiben("MQTT Daten zum [ $MQTTBroker ] senden.","   ",1);
    require($Pfad."/mqtt_senden.php");
  }


  /****************************************************************************
  //  Zeit und Datum
  ****************************************************************************/
  //  Der Regler hat keine interne Uhr! Deshalb werden die Daten vom Raspberry benutzt.
  $aktuelleDaten["Timestamp"] = time();
  $aktuelleDaten["Monat"]     = date("n");
  $aktuelleDaten["Woche"]     = date("W");
  $aktuelleDaten["Wochentag"] = strftime("%A",time());
  $aktuelleDaten["Datum"]     = date("d.m.Y");
  $aktuelleDaten["Uhrzeit"]      = date("H:i:s");




  /****************************************************************************
  //  InfluxDB  Zugangsdaten ...stehen in der user.config.php
  //  falls nicht, sind das hier die default Werte.
  ****************************************************************************/
  $aktuelleDaten["InfluxAdresse"] = $InfluxAdresse;
  $aktuelleDaten["InfluxPort"] = $InfluxPort;
  $aktuelleDaten["InfluxUser"] =  $InfluxUser;
  $aktuelleDaten["InfluxPassword"] = $InfluxPassword;
  $aktuelleDaten["InfluxDBName"] = $InfluxDBName;
  $aktuelleDaten["InfluxDaylight"] = $InfluxDaylight;
  $aktuelleDaten["InfluxDBLokal"] = $InfluxDBLokal;
  $aktuelleDaten["InfluxSSL"] = $InfluxSSL;
  $aktuelleDaten["Demodaten"] = false;


  /*********************************************************************
  //  Daten werden in die Influx Datenbank gespeichert.
  //  Lokal und Remote bei Bedarf.
  *********************************************************************/
  if ($InfluxDB_remote) {
    // Test ob die Remote Verbindung zur Verfügung steht.
    if ($RemoteDaten) {
      $rc = $funktionen->influx_remote_test();
      if ($rc) {
        $rc = $funktionen->influx_remote($aktuelleDaten);
        if ($rc) {
          $RemoteDaten = false;
        }
      }
      else {
        $RemoteDaten = false;
      }
    }
    if ($InfluxDB_local) {
      $rc = $funktionen->influx_local($aktuelleDaten);
    }
  }
  else {
    $rc = $funktionen->influx_local($aktuelleDaten);
  }




  if (is_file($Pfad."/1.user.config.php")) {
    // Ausgang Multi-Regler-Version
    $Zeitspanne = (9 - (time() - $Start));
    $funktionen->log_schreiben("Multi-Regler-Ausgang. ".$Zeitspanne,"   ",2);
    if ($Zeitspanne > 0) {
      sleep($Zeitspanne);
    }
    break;
  }
  else {
    $funktionen->log_schreiben("Schleife: ".($i)." Zeitspanne: ".(floor((56 - (time() - $Start))/($Wiederholungen-$i+1))),"   ",9);
    sleep(floor((56 - (time() - $Start))/($Wiederholungen-$i+1)));
  }
  if ($Wiederholungen <= $i or $i >= 6) {
      $funktionen->log_schreiben("Schleife ".$i." Ausgang...","   ",8);
      break;
  }
  $i++;
} while (($Start + 54) > time());


if (isset($aktuelleDaten["Seriennummer"]) and $aktuelleDaten["KeineSonne"] == false) {


  /*********************************************************************
  //  Jede Minute werden bei Bedarf einige Werte zur Homematic Zentrale
  //  übertragen.
  *********************************************************************/
  if (isset($Homematic) and $Homematic == true) {
    $funktionen->log_schreiben("Daten werden zur HomeMatic übertragen...","   ",8);
    require($Pfad."/homematic.php");
  }

  /*********************************************************************
  //  Sollen Nachrichten an einen Messenger gesendet werden?
  //  Bei einer Multi-Regler-Version sollte diese Funktion nur bei einem
  //  Gerät aktiviert sein.
  *********************************************************************/
  if (isset($Messenger) and $Messenger == true) {
    $funktionen->log_schreiben("Nachrichten versenden...","   ",8);
    require($Pfad."/meldungen_senden.php");
  }

  $funktionen->log_schreiben("OK. Datenübertragung erfolgreich.","   ",7);
}
else {
  $funktionen->log_schreiben("Keine gültigen Daten empfangen.","!! ",6);
}




Ausgang:

$funktionen->log_schreiben("----------------------   Stop   solax_wr.php   --------------------- ","|--",6);

return;



?>