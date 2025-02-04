#!/usr/bin/php
<?php
/*****************************************************************************
//  Solaranzeige Projekt             Copyright (C) [2015-2016]  [Ulrich Kunz]
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
//  Es dient dem Auslesen des Pylontech US 2000B BMS über die USB Schnittstelle
//  Das Auslesen wird hier mit einer Schleife durchgeführt. Wie oft die Daten
//  ausgelesen und gespeichert werden steht in der user.config.php
//
//  Achtung! Der Regler sendet zwischendurch immer wieder asynchrone Daten!
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
$Device = "BMS"; // BMS = Batteriemanagementsystem
$Version = ""; 
$Start = time();  // Timestamp festhalten
$funktionen->log_schreiben("------------   Start  us2000_bms.php   ----------------- ","|--",6);

$funktionen->log_schreiben("Zentraler Timestamp: ".$zentralerTimestamp,"   ",8);
$aktuelleDaten = array();
$aktuelleDaten["zentralerTimestamp"] = $zentralerTimestamp;
$aktuelleDaten["PylonTech"] = "2000"; 

setlocale(LC_TIME,"de_DE.utf8");
$RemoteDaten = true;


//  Hardware Version ermitteln.
$Teile =  explode(" ",$Platine);
if ($Teile[1] == "Pi") {
  $Version = trim($Teile[2]);
  if ($Teile[3] == "Model") {
    $Version .= trim($Teile[4]);
    if ($Teile[5] == "Plus") {
      $Version .= trim($Teile[5]);
    }
  }
}
$funktionen->log_schreiben("Hardware Version: ".$Version,"o  ",8);

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


//  Nach em Öffnen des Port muss sofort der Regler ausgelesen werden, sonst
//  sendet er asynchrone Daten!
$USB1 = $funktionen->openUSB($USBRegler);
if (!is_resource($USB1)) {
  $funktionen->log_schreiben("USB Port kann nicht geöffnet werden. [1]","XX ",7);
  $funktionen->log_schreiben("Exit.... ","XX ",7);
  goto Ausgang;
}



/************************************************************************************
//  Sollen Befehle an das Gerät gesendet werden?
//  Achtung! Diese Funktion ist noch nicht fertig und noch nicht geprüft.
************************************************************************************/

if (file_exists($Pfad."/../pipe/".$GeraeteNummer.".befehl.steuerung")) {

  $funktionen->log_schreiben("Steuerdatei '".$GeraeteNummer.".befehl.steuerung' vorhanden----","|- ",5);
  $Inhalt = file_get_contents($Pfad."/../pipe/".$GeraeteNummer.".befehl.steuerung");
  $Befehle = explode("\n",trim($Inhalt));
  $funktionen->log_schreiben("Befehle: ".print_r($Befehle,1),"|- ",9);

  for ($i = 0; $i < count($Befehle); $i++) {

    if ($i > 10) {
      //  Es werden nur maximal 10 Befehle pro Datei verarbeitet!
      break;
    }
    /***************************************************************************
    //  In der Datei "befehle.ini.php" müssen alle gültigen Befehle aufgelistet
    //  werden, die man benutzen möchte.
    //  Achtung! Genau darauf achten, dass der Befehl richtig geschrieben wird,
    //  damit das Gerät keinen Schaden nimmt.
    //  Siehe Dokument:  Befehle_senden.pdf
    ***************************************************************************/
    if (file_exists($Pfad."/befehle.ini.php")) {

      $funktionen->log_schreiben("Die Befehlsliste 'befehle.ini.php' ist vorhanden----","|- ",9);
      $INI_File =  parse_ini_file($Pfad.'/befehle.ini.php', true);
      $Regler13 = $INI_File["Regler13"];
      $funktionen->log_schreiben("Befehlsliste: ".print_r($Regler13,1),"|- ",10);
      if (!in_array(strtoupper($Befehle[$i]), $Regler13)) {
        $funktionen->log_schreiben("Dieser Befehl ist nicht zugelassen. ".$Befehle[$i],"|o ",3);
        $funktionen->log_schreiben("Die Verarbeitung der Befehle wird abgebrochen.","|o ",3);
        break;
      }
 
    }
    else {
      $funktionen->log_schreiben("Die Befehlsliste 'befehle.ini.php' ist nicht vorhanden----","|- ",3);
      break;
    }
    $Wert = false;
    $Antwort = "";
    /************************************************************************
    //  Ab hier wird der Befehl gesendet.
    //  Diese Funktion ist noch nicht fertig programmiert.
    ************************************************************************/
    $funktionen->log_schreiben("Befehl zur Ausführung: ".strtoupper($Befehle[$i]),"|- ",3);
  }
  $rc = unlink($Pfad."/../pipe/".$GeraeteNummer.".befehl.steuerung");
  if ($rc) {
    $funktionen->log_schreiben("Datei  /pipe/".$GeraeteNummer.".befehl.steuerung  gelöscht.","    ",8);
  }
}
else {
  $funktionen->log_schreiben("Steuerdatei '".$GeraeteNummer.".befehl.steuerung' nicht vorhanden----","|- ",8);
}

/*******************************************************************************
//
//  Befehle senden Ende
//
//  Hier beginnt das Auslesen der Daten
//
*******************************************************************************/

$i = 1;
do {
  $funktionen->log_schreiben("Die Daten werden ausgelesen...","+  ",9);

  /****************************************************************************
  //  Ab hier wird der Regler ausgelesen.
  //
  //  Ergebniswerte:
  //  $aktuelleDaten["Firmware"]
  //  $aktuelleDaten["Produkt"]
  //  $aktuelleDaten["Batteriespannung"]
  //  $aktuelleDaten["Batteriestrom"]
  //  $aktuelleDaten["KilowattstundenGesamt"]
  //  $aktuelleDaten["AmperestundenGesamt"]
  //  $aktuelleDaten["Temperatur"]
  //  $aktuelleDaten["SOC"]
  //  $aktuelleDaten["TTG"]
  //  $aktuelleDaten["Leistung"]
  //
  ****************************************************************************/

  $Befehl = "200146900000";
  $CRC = $funktionen->crc16_us2000($Befehl);
  $rc =  $funktionen->us2000_auslesen($USB1,"~".$Befehl.$CRC."\r");
  if ($rc) {
    $Daten = $funktionen->us2000_daten_entschluesseln($rc);
    $aktuelleDaten["Packs"] = hexdec($Daten["INFO"]);
  }

  $funktionen->log_schreiben(print_r($rc,1),"** ",10);


  for ($n = 1; $n <= $aktuelleDaten["Packs"]; $n++) {

    $Befehl = "20014642E0020".$n;
    $CRC = $funktionen->crc16_us2000($Befehl);
    $rc =  $funktionen->us2000_auslesen($USB1,"~".$Befehl.$CRC."\r");
    if ($rc) {
      $Daten = $funktionen->us2000_daten_entschluesseln($rc);
      $aktuelleDaten["Pack".$n."_Zellen"] = hexdec(substr($Daten["INFO"],4,2));
      $aktuelleDaten["Pack".$n."_Zelle1"] = (hexdec(substr($Daten["INFO"],6,4))/1000);
      $aktuelleDaten["Pack".$n."_Zelle2"] = (hexdec(substr($Daten["INFO"],10,4))/1000);
      $aktuelleDaten["Pack".$n."_Zelle3"] = (hexdec(substr($Daten["INFO"],14,4))/1000);
      $aktuelleDaten["Pack".$n."_Zelle4"] = (hexdec(substr($Daten["INFO"],18,4))/1000);
      $aktuelleDaten["Pack".$n."_Zelle5"] = (hexdec(substr($Daten["INFO"],22,4))/1000);
      $aktuelleDaten["Pack".$n."_Zelle6"] = (hexdec(substr($Daten["INFO"],26,4))/1000);
      $aktuelleDaten["Pack".$n."_Zelle7"] = (hexdec(substr($Daten["INFO"],30,4))/1000);
      $aktuelleDaten["Pack".$n."_Zelle8"] = (hexdec(substr($Daten["INFO"],34,4))/1000);
      $aktuelleDaten["Pack".$n."_Zelle9"] = (hexdec(substr($Daten["INFO"],38,4))/1000);
      $aktuelleDaten["Pack".$n."_Zelle10"] = (hexdec(substr($Daten["INFO"],42,4))/1000);
      $aktuelleDaten["Pack".$n."_Zelle11"] = (hexdec(substr($Daten["INFO"],46,4))/1000);
      $aktuelleDaten["Pack".$n."_Zelle12"] = (hexdec(substr($Daten["INFO"],50,4))/1000);
      $aktuelleDaten["Pack".$n."_Zelle13"] = (hexdec(substr($Daten["INFO"],54,4))/1000);
      $aktuelleDaten["Pack".$n."_Zelle14"] = (hexdec(substr($Daten["INFO"],58,4))/1000);
      $aktuelleDaten["Pack".$n."_Zelle15"] = (hexdec(substr($Daten["INFO"],62,4))/1000);
      $aktuelleDaten["Pack".$n."_Temp_Anz"]  = substr($Daten["INFO"],66,2);
      $aktuelleDaten["Pack".$n."_Temp1"] = ((hexdec(substr($Daten["INFO"],68,4))-2731)/10);
      $aktuelleDaten["Pack".$n."_Temp2"] = ((hexdec(substr($Daten["INFO"],72,4))-2731)/10);
      $aktuelleDaten["Pack".$n."_Temp3"] = ((hexdec(substr($Daten["INFO"],76,4))-2731)/10);
      $aktuelleDaten["Pack".$n."_Temp4"] = ((hexdec(substr($Daten["INFO"],80,4))-2731)/10);
      $aktuelleDaten["Pack".$n."_Temp5"] = ((hexdec(substr($Daten["INFO"],84,4))-2731)/10);
      $aktuelleDaten["Pack".$n."_Strom"] = ($funktionen->hexdecs(substr($Daten["INFO"],88,4))/10);
      $aktuelleDaten["Pack".$n."_Spannung"] = (hexdec(substr($Daten["INFO"],92,4))/1000);
      $aktuelleDaten["Pack".$n."_Ah_left"] = (hexdec(substr($Daten["INFO"],96,4))/1000);
      $aktuelleDaten["Pack".$n."_Ah_total"] = (hexdec(substr($Daten["INFO"],102,4))/1000);
      $aktuelleDaten["Pack".$n."_cycle"] = hexdec(substr($Daten["INFO"],106,4));
    }

    $Befehl = "20014692E0020".$n;
    $CRC = $funktionen->crc16_us2000($Befehl);
    $rc =  $funktionen->us2000_auslesen($USB1,"~".$Befehl.$CRC."\r");
    if ($rc) {
      $Daten = $funktionen->us2000_daten_entschluesseln($rc);
      // $aktuelleDaten["INFO".$n] = $Daten["INFO"];
      $aktuelleDaten["Pack".$n."_Status"] = hexdec(substr($Daten["INFO"],18,2));
    }

    $Befehl = "20014644E0020".$n;
    $CRC = $funktionen->crc16_us2000($Befehl);
    $rc = $funktionen->us2000_auslesen($USB1,"~".$Befehl.$CRC."\r");
    if ($rc) {
      $Daten = $funktionen->us2000_daten_entschluesseln($rc);
      // $aktuelleDaten["INFO".$n] = $Daten["INFO"];
      $aktuelleDaten["Pack".$n."_Warn_Zelle1"] = hexdec(substr($Daten["INFO"],6,2));
      $aktuelleDaten["Pack".$n."_Warn_Zelle2"] = hexdec(substr($Daten["INFO"],8,2));
      $aktuelleDaten["Pack".$n."_Warn_Zelle3"] = hexdec(substr($Daten["INFO"],10,2));
      $aktuelleDaten["Pack".$n."_Warn_Zelle4"] = hexdec(substr($Daten["INFO"],12,2));
      $aktuelleDaten["Pack".$n."_Warn_Zelle5"] = hexdec(substr($Daten["INFO"],14,2));
      $aktuelleDaten["Pack".$n."_Warn_Zelle6"] = hexdec(substr($Daten["INFO"],16,2));
      $aktuelleDaten["Pack".$n."_Warn_Zelle7"] = hexdec(substr($Daten["INFO"],18,2));
      $aktuelleDaten["Pack".$n."_Warn_Zelle8"] = hexdec(substr($Daten["INFO"],20,2));
      $aktuelleDaten["Pack".$n."_Warn_Zelle9"] = hexdec(substr($Daten["INFO"],22,2));
      $aktuelleDaten["Pack".$n."_Warn_Zelle10"] = hexdec(substr($Daten["INFO"],24,2));
      $aktuelleDaten["Pack".$n."_Warn_Zelle11"] = hexdec(substr($Daten["INFO"],26,2));
      $aktuelleDaten["Pack".$n."_Warn_Zelle12"] = hexdec(substr($Daten["INFO"],28,2));
      $aktuelleDaten["Pack".$n."_Warn_Zelle13"] = hexdec(substr($Daten["INFO"],30,2));
      $aktuelleDaten["Pack".$n."_Warn_Zelle14"] = hexdec(substr($Daten["INFO"],32,2));
      $aktuelleDaten["Pack".$n."_Warn_Zelle15"] = hexdec(substr($Daten["INFO"],34,2));
      $aktuelleDaten["Pack".$n."_Warn_Temp1"] = hexdec(substr($Daten["INFO"],38,2));
      $aktuelleDaten["Pack".$n."_Warn_Temp2"] = hexdec(substr($Daten["INFO"],40,2));
      $aktuelleDaten["Pack".$n."_Warn_Temp3"] = hexdec(substr($Daten["INFO"],42,2));
      $aktuelleDaten["Pack".$n."_Warn_Temp4"] = hexdec(substr($Daten["INFO"],44,2));
      $aktuelleDaten["Pack".$n."_Warn_Temp5"] = hexdec(substr($Daten["INFO"],46,2));
      $aktuelleDaten["Pack".$n."_Warn_LadeStrom"] = hexdec(substr($Daten["INFO"],48,2));
      $aktuelleDaten["Pack".$n."_Warn_Spannung"] = hexdec(substr($Daten["INFO"],50,2));
      $aktuelleDaten["Pack".$n."_Warn_Entladestrom"] = hexdec(substr($Daten["INFO"],52,2));
      $aktuelleDaten["Pack".$n."_Warn_Status1"] = hexdec(substr($Daten["INFO"],54,2));
      $aktuelleDaten["Pack".$n."_Warn_Status2"] = hexdec(substr($Daten["INFO"],56,2));
      $aktuelleDaten["Pack".$n."_Warn_Status3"] = hexdec(substr($Daten["INFO"],58,2));
      $aktuelleDaten["Pack".$n."_Warn_Status4"] = hexdec(substr($Daten["INFO"],60,2));
      $aktuelleDaten["Pack".$n."_Warn_Status5"] = hexdec(substr($Daten["INFO"],62,2));
    }




  }

  $Befehl = "200146510000";
  $CRC = $funktionen->crc16_us2000($Befehl);
  $rc = $funktionen->us2000_auslesen($USB1,"~".$Befehl.$CRC."\r");
  if ($rc) {
    $Daten = $funktionen->us2000_daten_entschluesseln($rc);
    $aktuelleDaten["Firmware"] = hexdec(substr($Daten["INFO"],20,4));
    $aktuelleDaten["Produkt"] = trim($funktionen->Hex2String(substr($Daten["INFO"],0,20)));
    $PylonTech = "2000";
  }

  if ($aktuelleDaten["Packs"] == "")  {
    $funktionen->log_schreiben("Keine Verbindung zum Pylontech Gerät vorhanden.","   ",3);
    goto Ausgang;
  }



  /**************************************************************************
  //  Falls ein ErrorCode vorliegt, wird er hier in einen lesbaren
  //  Text umgewandelt, sodass er als Fehlermeldung gesendet werden kann.
  //  Die Funktion ist noch nicht überall implementiert.
  **************************************************************************/
  $FehlermeldungText = "";
  //  Dummy Wert.
  $aktuelleDaten["WattstundenGesamtHeute"] = 0;


  /****************************************************************************
  //  Die Daten werden für die Speicherung vorbereitet.
  ****************************************************************************/
  $aktuelleDaten["Regler"] = $Regler;
  $aktuelleDaten["Objekt"] = $Objekt;
  $aktuelleDaten["zentralerTimestamp"] = ($aktuelleDaten["zentralerTimestamp"]+10);
  $aktuelleDaten["PylonTech"] = $PylonTech;


  if ($i == 1) 
    $funktionen->log_schreiben(var_export($aktuelleDaten,1),"   ",8);


  /****************************************************************************
  //  User PHP Script, falls gewünscht oder nötig
  ****************************************************************************/
  if ( file_exists ("/var/www/html/us2000_bms_math.php")) {
    include 'us_2000_bms_math.php';  // Falls etwas neu berechnet werden muss.
  }



  /**************************************************************************
  //  Alle ausgelesenen Daten werden hier bei Bedarf als mqtt Messages
  //  an den mqtt-Broker Mosquitto gesendet.
  //  Achtung! Die Übertragung dauert ca. 30 Sekunden!
  **************************************************************************/
  if ($MQTT and $i == 1) {
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

  $funktionen->log_schreiben(print_r($aktuelleDaten,1),"** ",9);



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
    $Zeitspanne = (7 - (time() - $Start));
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
    $funktionen->log_schreiben("OK. Daten gelesen.","   ",9);
    $funktionen->log_schreiben("Schleife ".$i." Ausgang...","   ",8);
    break;
  }


  $i++;
} while (($Start + 55) > time());


if (isset($aktuelleDaten["Packs"]) and isset($aktuelleDaten["Regler"])) {


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

$funktionen->log_schreiben("------------   Stop   us2000_bms.php   ----------------- ","|--",6);

return;




?>