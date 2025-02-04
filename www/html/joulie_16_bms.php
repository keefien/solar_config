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
//  Es dient dem Auslesen des Joulie-16 BMS über die USB Schnittstelle
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

$Tracelevel = 7;  //  1 bis 10  10 = Debug
$Device = "BMS"; // BMS = Batteriemanagementsystem
$Version = ""; 
$Start = time();  // Timestamp festhalten
$funktionen->log_schreiben("---------   Start  joulie_16_bms.php   ----------------- ","|--",6);

$funktionen->log_schreiben("Zentraler Timestamp: ".$zentralerTimestamp,"   ",8);
$aktuelleDaten = array();
$aktuelleDaten["zentralerTimestamp"] = $zentralerTimestamp;

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
$funktionen->log_schreiben("Hardware Version: ".$Version,"o  ",9);

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
//  Sollen Befehle an den Wechselrichter gesendet werden?
//
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
      /*********************************************************************************
      //  In der Datei "befehle.ini.php" müssen alle gültigen Befehle aufgelistet
      //  werden, die man benutzen möchte.
      //  Achtung! Genau darauf achten, dass der Befehl richtig geschrieben wird,
      //  damit das Gerät keinen Schaden nimmt.
      //  Siehe Dokument:  Befehle_senden.pdf
      *********************************************************************************/
      if (file_exists($Pfad."/befehle.ini.php")) {

        $funktionen->log_schreiben("Die Befehlsliste 'befehle.ini.php' ist vorhanden----","|- ",9);
        $INI_File =  parse_ini_file($Pfad.'/befehle.ini.php', true);
        $Regler13 = $INI_File["Regler13"];
        $funktionen->log_schreiben("Befehlsliste: ".print_r($Regler13,1),"|- ",10);
        $Subst = $Befehle[$i];

        foreach ($Regler13 as $Template) {
          $Subst = $Befehle[$i];
          $l = strlen($Template);
          for ($p =1; $p < $l; ++$p) {
            if ($Template[$p] == "#") {
              $Subst[$p] = "#";
            }
          }
          if ($Template == $Subst) {
            break;
          }
        }
        if ($Template != $Subst) {
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
      ************************************************************************/
      $funktionen->log_schreiben("Befehl zur Ausführung: ".strtoupper($Befehle[$i]),"|- ",3);

      if (strtoupper($Befehle[$i]) == "RELAY_ON") {
        $rc = $funktionen->joulie_auslesen($USB1,"login LORY27BA");
        if (!empty($rc[0])) {
          $funktionen->log_schreiben($rc[0],"|- ",3);
        }
        $rc = $funktionen->joulie_auslesen($USB1,"stopb");
        $funktionen->log_schreiben($rc[0],"|- ",3);
        $rc = $funktionen->joulie_auslesen($USB1,"unlock MAS184CU");
        $funktionen->log_schreiben($rc[0],"|- ",3);
        $rc = $funktionen->joulie_auslesen($USB1,"relay on");
        $funktionen->log_schreiben($rc[0],"|- ",3);
        $rc = $funktionen->joulie_auslesen($USB1,"startb");
        $funktionen->log_schreiben($rc[0],"|- ",3);
        sleep(1);
      }


      if (strtoupper($Befehle[$i]) == "RELAY_OFF") {
        $rc = $funktionen->joulie_auslesen($USB1,"login LORY27BA");
        if (!empty($rc[0])) {
          $funktionen->log_schreiben($rc[0],"|- ",3);
        }
        $rc = $funktionen->joulie_auslesen($USB1,"stopb");
        $funktionen->log_schreiben($rc[0],"|- ",3);
        $rc = $funktionen->joulie_auslesen($USB1,"unlock MAS184CU");
        $funktionen->log_schreiben($rc[0],"|- ",3);
        $rc = $funktionen->joulie_auslesen($USB1,"relay off");
        $funktionen->log_schreiben($rc[0],"|- ",3);
        // $rc = $funktionen->joulie_auslesen($USB1,"startb");
        // $funktionen->log_schreiben($rc[0],"|- ",3);
        sleep(1);
      }

      if (strtoupper($Befehle[$i]) == "START_BALANCING") {
        $rc = $funktionen->joulie_auslesen($USB1,"login LORY27BA");
        if (!empty($rc[0])) {
          $funktionen->log_schreiben($rc[0],"|- ",3);
        }
        $rc = $funktionen->joulie_auslesen($USB1,"startb");
        $funktionen->log_schreiben($rc[0],"|- ",3);
        sleep(1);
      }

      if (strtoupper($Befehle[$i]) == "STOP_BALANCING") {
        $rc = $funktionen->joulie_auslesen($USB1,"login LORY27BA");
        if (!empty($rc[0])) {
          $funktionen->log_schreiben($rc[0],"|- ",3);
        }
        $rc = $funktionen->joulie_auslesen($USB1,"stopb");
        $funktionen->log_schreiben($rc[0],"|- ",3);
        sleep(1);
      }

    }
    $rc = unlink($Pfad."/../pipe/".$GeraeteNummer.".befehl.steuerung");
    if ($rc) {
      $funktionen->log_schreiben("Datei  /pipe/".$GeraeteNummer.".befehl.steuerung  gelöscht.","    ",8);
    }
}
else {
  $funktionen->log_schreiben("Steuerdatei '".$GeraeteNummer.".befehl.steuerung' nicht vorhanden----","|- ",9);
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
  $aktuelleDaten["Balancing"] = 1;
  $rc =  $funktionen->joulie_auslesen($USB1,"trace");
  if (empty($rc)) {
    $aktuelleDaten["Balancing"] = 0;
    $rc =  $funktionen->joulie_auslesen($USB1,"login LORY27BA");
    if (isset($rc[99])) {
      $funktionen->log_schreiben($rc[3],"+  ",3);
    }
    $rc =  $funktionen->joulie_auslesen($USB1,"fwtrace on");
    if (isset($rc[99])) {
      $funktionen->log_schreiben($rc[3],"+  ",3);
    }
    $funktionen->log_schreiben($rc[0],"+  ",3);
    $rc =  $funktionen->joulie_auslesen($USB1,"outb");
    if (isset($rc[99])) {
      $funktionen->log_schreiben($rc[3],"+  ",3);
    }
    $funktionen->log_schreiben($rc[0],"+  ",3);

    $rc = $funktionen->joulie_outb($rc[1]);

    // print_r($rc);

  }
  elseif (count($rc) <> 38)  {
    //  Der Rest wird nicht gespeichert.
    break;

  }

  // Es kommen normale Trace Daten...  In die Datenbank abspeichern..
  $funktionen->log_schreiben(print_r($rc,1),"   ",9);

  if (isset($rc[99])) {
    $funktionen->log_schreiben($rc[3],"+  ",3);
  }


  // print_r($rc);


  $aktuelleDaten["Zelle1Volt"] = round(($rc[1]/1000),2);
  $aktuelleDaten["Zelle1Status"] = $funktionen->joulie_zahl($rc[2]);
  $aktuelleDaten["Zelle2Volt"] = round(($rc[3]/1000),2);
  $aktuelleDaten["Zelle2Status"] = $funktionen->joulie_zahl($rc[4]);
  $aktuelleDaten["Zelle3Volt"] = round(($rc[5]/1000),2);
  $aktuelleDaten["Zelle3Status"] = $funktionen->joulie_zahl($rc[6]);
  $aktuelleDaten["Zelle4Volt"] = round(($rc[7]/1000),2);
  $aktuelleDaten["Zelle4Status"] = $funktionen->joulie_zahl($rc[8]);
  $aktuelleDaten["Zelle5Volt"] = round(($rc[9]/1000),2);
  $aktuelleDaten["Zelle5Status"] = $funktionen->joulie_zahl($rc[10]);
  $aktuelleDaten["Zelle6Volt"] = round(($rc[11]/1000),2);
  $aktuelleDaten["Zelle6Status"] = $funktionen->joulie_zahl($rc[12]);
  $aktuelleDaten["Zelle7Volt"] = round(($rc[13]/1000),2);
  $aktuelleDaten["Zelle7Status"] = $funktionen->joulie_zahl($rc[14]);
  $aktuelleDaten["Zelle8Volt"] = round(($rc[15]/1000),2);
  $aktuelleDaten["Zelle8Status"] = $funktionen->joulie_zahl($rc[16]);
  $aktuelleDaten["Zelle9Volt"] = round(($rc[17]/1000),2);
  $aktuelleDaten["Zelle9Status"] = $funktionen->joulie_zahl($rc[18]);
  $aktuelleDaten["Zelle10Volt"] = round(($rc[19]/1000),2);
  $aktuelleDaten["Zelle10Status"] = $funktionen->joulie_zahl($rc[20]);
  $aktuelleDaten["Zelle11Volt"] = round(($rc[21]/1000),2);
  $aktuelleDaten["Zelle11Status"] = $funktionen->joulie_zahl($rc[22]);
  $aktuelleDaten["Zelle12Volt"] = round(($rc[23]/1000),2);
  $aktuelleDaten["Zelle12Status"] = $funktionen->joulie_zahl($rc[24]);
  $aktuelleDaten["Zelle13Volt"] = round(($rc[25]/1000),2);
  $aktuelleDaten["Zelle13Status"] = $funktionen->joulie_zahl($rc[26]);
  $aktuelleDaten["Zelle14Volt"] = round(($rc[27]/1000),2);
  $aktuelleDaten["Zelle14Status"] = $funktionen->joulie_zahl($rc[28]);
  $aktuelleDaten["Zelle15Volt"] = round(($rc[29]/1000),2);
  $aktuelleDaten["Zelle15Status"] = $funktionen->joulie_zahl($rc[30]);
  $aktuelleDaten["Zelle16Volt"] = round(($rc[31]/1000),2);
  $aktuelleDaten["Zelle16Status"] = $funktionen->joulie_zahl($rc[32]);
  $aktuelleDaten["Strom"] = round(($rc[33]/1000),2);
  $aktuelleDaten["Kapazitaet"] = round($rc[34],2);
  $aktuelleDaten["SOC"] = round(($rc[35]/1000),1);
  $aktuelleDaten["Spannung"] = round(($rc[36]/1000),2);
  $aktuelleDaten["Fehlercode"] = $rc[37];





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
  $aktuelleDaten["Firmware"] = "1.14";
  $aktuelleDaten["zentralerTimestamp"] = ($aktuelleDaten["zentralerTimestamp"]+10);



  $funktionen->log_schreiben(var_export($aktuelleDaten,1),"   ",8);


  /****************************************************************************
  //  User PHP Script, falls gewünscht oder nötig
  ****************************************************************************/
  if ( file_exists ("/var/www/html/joulie_16_bms_math.php")) {
    include 'joulie_16_bms_math.php';  // Falls etwas neu berechnet werden muss.
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

  $funktionen->log_schreiben(print_r($aktuelleDaten,1),"** ",8);



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
    sleep(floor((55 - (time() - $Start))/($Wiederholungen-$i+1)));
  }
  if ($Wiederholungen <= $i or $i >= 6) {
    $funktionen->log_schreiben("OK. Daten gelesen.","   ",9);
    $funktionen->log_schreiben("Schleife ".$i." Ausgang...","   ",8);
    break;
  }
  
  $i++;
} while (($Start + 55) > time());




if (isset($aktuelleDaten["Zelle1Status"]) and isset($aktuelleDaten["Regler"])) {


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

$funktionen->log_schreiben("---------   Stop   joulie_16_bms.php   ----------------- ","|--",6);

return;


?>