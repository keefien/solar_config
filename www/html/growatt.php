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
//  Es dient dem Auslesen des Wechselrichters Growatt über eine RS485
//  Schnittstelle mit USB Adapter. Protokoll Version 1  V3.05  und  2   V1.05
//  Version 1 + 2 ohne Batterieanschluss
//  Version 3 mit Batterieanschluss
//  Das Auslesen wird hier mit einer Schleife durchgeführt. Wie oft die Daten
//  ausgelesen und gespeichert werden steht in der user.config.php
//
//
*****************************************************************************/
$path_parts = pathinfo( $argv[0] );
$Pfad = $path_parts['dirname'];
if (!is_file( $Pfad."/1.user.config.php" )) {
  // Handelt es sich um ein Multi Regler System?
  require ($Pfad."/user.config.php");
}
require_once ($Pfad."/phpinc/funktionen.inc.php");
if (!isset($funktionen)) {
  $funktionen = new funktionen( );
}
// Im Fall, dass man die Device manuell eingeben muss
if (isset($USBDevice) and !empty($USBDevice)) {
  $USBRegler = $USBDevice;
}
$Tracelevel = 7; //  1 bis 10  10 = Debug
$Timer = 300000; // Größer wählen, wenn Lesefehler auftreten.
$RemoteDaten = true;
$Start = time( ); // Timestamp festhalten
$funktionen->log_schreiben( "----------------------   Start  growatt.php   --------------------- ", "|--", 6 );
$funktionen->log_schreiben( "Zentraler Timestamp: ".$zentralerTimestamp, "   ", 8 );
$aktuelleDaten = array();
$aktuelleDaten["zentralerTimestamp"] = $zentralerTimestamp;
setlocale( LC_TIME, "de_DE.utf8" );
//  Hardware Version ermitteln.
$Teile = explode( " ", $Platine );
if ($Teile[1] == "Pi") {
  $Version = trim( $Teile[2] );
  if ($Teile[3] == "Model") {
    $Version .= trim( $Teile[4] );
    if ($Teile[5] == "Plus") {
      $Version .= trim( $Teile[5] );
    }
  }
}
$funktionen->log_schreiben( "Hardware Version: ".$Version, "o  ", 8 );
switch ($Version) {

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
elseif (strlen( $WR_Adresse ) == 1) {
  $WR_ID = str_pad( dechex( $WR_Adresse ), 2, "0", STR_PAD_LEFT );
}
else {
  $WR_ID = str_pad( dechex( substr( $WR_Adresse, - 2 )), 2, "0", STR_PAD_LEFT );
}
$funktionen->log_schreiben( "WR_ID: ".$WR_ID, "+  ", 8 );
if ($HF2211) {
  // HF2211 WLAN Gateway wird benutzt (Noch nicht getestet 8.4.2021)
  $USB1 = fsockopen( $WR_IP, $WR_Port, $errno, $errstr, 5 ); // 5 Sekunden Timeout
  if ($USB1 === false) {
    $funktionen->log_schreiben( "Kein Kontakt zum Wechselrichter ".$WR_IP."  Port: ".$WR_Port, "XX ", 3 );
    $funktionen->log_schreiben( "Exit.... ", "XX ", 3 );
    goto Ausgang;
  }
}
else {
  $USB1 = $funktionen->openUSB( $USBRegler );
  if (!is_resource( $USB1 )) {
    $funktionen->log_schreiben( "USB Port kann nicht geöffnet werden. [1]", "XX ", 7 );
    $funktionen->log_schreiben( "Exit.... ", "XX ", 7 );
    goto Ausgang;
  }
}


/************************************************************************************
//  Sollen Befehle an den Wechselrichter gesendet werden?
//  Start "Befehl senden"
************************************************************************************/
if (file_exists( $Pfad."/../pipe/".$GeraeteNummer.".befehl.steuerung" )) {
  $funktionen->log_schreiben( "Steuerdatei '".$GeraeteNummer.".befehl.steuerung' vorhanden----", "|- ", 5 );
  $Inhalt = file_get_contents( $Pfad."/../pipe/".$GeraeteNummer.".befehl.steuerung" );
  $Befehle = explode( "\n", trim( $Inhalt ));
  $funktionen->log_schreiben( "Befehle: ".print_r( $Befehle, 1 ), "|- ", 9 );
  for ($i = 0; $i < count( $Befehle ); $i++) {
    if ($i >= 4) {
      //  Es werden nur maximal 5 Befehle pro Datei verarbeitet!
      break;
    }
    /**************************************************************************
    //  In der Datei "befehle.ini.php" müssen alle gültigen Befehle aufgelistet
    //  werden, die man benutzen möchte.
    //  Achtung! Genau darauf achten, dass der Befehl richtig geschrieben wird,
    //  damit das Gerät keinen Schaden nimmt.
    //  QPI ist nur zum Testen ...
    //  Siehe Dokument:  Befehle_senden.pdf
    **************************************************************************/
    if (file_exists( $Pfad."/befehle.ini.php" )) {
      $funktionen->log_schreiben( "Die Befehlsliste 'befehle.ini.php' ist vorhanden----", "|- ", 9 );
      $INI_File = parse_ini_file( $Pfad.'/befehle.ini.php', true );
      $Regler48 = $INI_File["Regler48"];
      $funktionen->log_schreiben( "Befehlsliste: ".print_r( $Regler48, 1 ), "|- ", 10 );
      foreach ($Regler48 as $Template) {
        $Subst = $Befehle[$i];
        $l = strlen( $Template );
        for ($p = 1; $p < $l;++$p) {
          if ($Template[$p] == "#") {
            $Subst[$p] = "#";
          }
        }
        if ($Template == $Subst) {
          break;
        }
      }
      if ($Template != $Subst) {
        $funktionen->log_schreiben( "Dieser Befehl ist nicht zugelassen. ".$Befehle[$i], "|o ", 3 );
        $funktionen->log_schreiben( "Die Verarbeitung der Befehle wird abgebrochen.", "|o ", 3 );
        break;
      }
    }
    else {
      $funktionen->log_schreiben( "Die Befehlsliste 'befehle.ini.php' ist nicht vorhanden----", "|- ", 3 );
      break;
    }
    /*************************************************************************
    //  Gültiger Befehl ist z.B. 2_1
    //  Damit wird das Register 0002 mit dem Wert 0001 beschrieben.
    *************************************************************************/
    $Wert = false;
    $Antwort = "";
    if (strlen( $Befehle[$i] ) > 2) {
      $Teile = explode( "_", $Befehle[$i] );
      $RegWert = str_pad( dechex( $Teile[1] ), 4, "0", STR_PAD_LEFT );
      $Befehl["DeviceID"] = $WR_ID;
      $Befehl["BefehlFunctionCode"] = "10";
      $Befehl["RegisterAddress"] = str_pad( dechex( $Teile[0] ), 4, "0", STR_PAD_LEFT );
      $Befehl["RegisterCount"] = "0001";
      $Befehl["Befehl"] = $RegWert;
      $funktionen->log_schreiben( "Befehl: ".print_r( $Befehl, 1 ), "    ", 8 );
      $rc = $funktionen->phocos_pv18_auslesen( $USB1, $Befehl );
      if ($rc["ok"] == true) {
        $wert = true;
        $funktionen->log_schreiben( "Befehlsausführung war erfolgreich.", "   ", 7 );
        $funktionen->log_schreiben( "Register ".hexdec( $Befehl["RegisterAddress"] )." Wert: ".$Befehl["Befehl"], "   ", 2 );
      }
      else {
        $Wert = false;
        $funktionen->log_schreiben( "Befehlsausführung war nicht erfolgreich! ", "XX ", 2 );
        $funktionen->log_schreiben( "Register ".hexdec( $Befehl["RegisterAddress"] )." Wert: ".$Befehl["Befehl"], "XX ", 2 );
      }
    }
    else {
      $funktionen->log_schreiben( "Befehl ungültig: ".$Befehle[$i], "    ", 2 );
    }
    sleep(1);   // Nach dem ein Register beschrieben wurde, muss eine Pause eingelegt werden.
  }
  $rc = unlink( $Pfad."/../pipe/".$GeraeteNummer.".befehl.steuerung" );
  if ($rc) {
    $funktionen->log_schreiben( "Datei  /pipe/".$GeraeteNummer.".befehl.steuerung  gelöscht.", "    ", 8 );
  }
}
else {
  $funktionen->log_schreiben( "Steuerdatei '".$GeraeteNummer.".befehl.steuerung' nicht vorhanden----", "|- ", 9 );
}

/*****************************************************************************
//  Ende "Befehl senden"
*****************************************************************************/

$i = 1;
do {
  $funktionen->log_schreiben( "Die Daten werden ausgelesen...", "+  ", 9 );

  /****************************************************************************
  //  Ab hier wird der Regler ausgelesen.
  //
  //  MODBUS RTU Protokoll
  //
  ****************************************************************************/
  // Holding Register  Befehl 03
  // Holding Register  Befehl 03
  $Befehl["DeviceID"] = $WR_ID;
  $Befehl["BefehlFunctionCode"] = "03";
  $Befehl["RegisterAddress"] = "0009"; // Dezimal 9
  $Befehl["RegisterCount"] = "0003";
  $rc = $funktionen->phocos_pv18_auslesen( $USB1, $Befehl );
  $aktuelleDaten["Firmware"] = trim( $funktionen->Hex2String( $rc["data"] ), "\0" );
  $funktionen->log_schreiben( "Firmware: ".print_r( $rc, 1 ), "   ", 9 );
  if ($rc["ok"] == false) {
    $funktionen->log_schreiben( "Keine Antwort vom Wechselrichter. Zu dunkel?", "   ", 7 );
    goto Ausgang;
  }
  if (strtoupper( substr( $aktuelleDaten["Firmware"], 0, 2 )) == "AL") {
    $aktuelleDaten["Protokollversion"] = 2;
  }
  elseif (strtoupper( substr( $aktuelleDaten["Firmware"], 0, 2 )) == "G.") {
    $aktuelleDaten["Protokollversion"] = 1;
  }
  elseif (strtoupper( substr( $aktuelleDaten["Firmware"], 0, 2 )) == "GH") {
    $aktuelleDaten["Protokollversion"] = 2;
  }
  elseif (strtoupper( substr( $aktuelleDaten["Firmware"], 0, 2 )) == "DH") {
    $aktuelleDaten["Protokollversion"] = 2;
  }
  elseif (strtoupper( substr( $aktuelleDaten["Firmware"], 0, 2 )) == "DL") {
    $aktuelleDaten["Protokollversion"] = 2;
  }
  elseif (strtoupper( substr( $aktuelleDaten["Firmware"], 0, 2 )) == "DK") {
    $aktuelleDaten["Protokollversion"] = 1;
  }
  elseif (strtoupper( substr( $aktuelleDaten["Firmware"], 0, 2 )) == "DN") {
    $aktuelleDaten["Protokollversion"] = 2;
  }
  elseif (strtoupper( substr( $aktuelleDaten["Firmware"], 0, 2 )) == "RA") {
    $aktuelleDaten["Protokollversion"] = 3;
  }
  elseif (strtoupper( substr( $aktuelleDaten["Firmware"], 0, 2 )) == "TJ") {
    $aktuelleDaten["Protokollversion"] = 3;
  }
  elseif (strtoupper( substr( $aktuelleDaten["Firmware"], 0, 2 )) == "YA") {
    $aktuelleDaten["Protokollversion"] = 3;
  }
  elseif (strtoupper( substr( $aktuelleDaten["Firmware"], 0, 3 )) == "040") {
    $aktuelleDaten["Protokollversion"] = 4;
  }
  elseif (strtoupper( substr( $aktuelleDaten["Firmware"], 0, 3 )) == "067") {
    $aktuelleDaten["Protokollversion"] = 4;
  }
  elseif (strtoupper( substr( $aktuelleDaten["Firmware"], 0, 3 )) == "001") {
    $aktuelleDaten["Protokollversion"] = 4;
  }
  elseif (strtoupper( substr( $aktuelleDaten["Firmware"], 0, 3 )) == "095") {
    $aktuelleDaten["Protokollversion"] = 4;
  }
  else {
    $funktionen->log_schreiben( "Modell: ".$aktuelleDaten["Firmware"], "   ", 2 );
    $funktionen->log_schreiben( "Diese Firmware Version ist noch nicht bekannt. Bitte melden: hilfe@solaranzeige.de", "   ", 2 );
    goto Ausgang;
  }
  $funktionen->log_schreiben( "Protokoll Version: ".$aktuelleDaten["Protokollversion"], "   ", 7 );
  if ($aktuelleDaten["Protokollversion"] != 4) {
    $Befehl["RegisterAddress"] = "002C"; // Dezimal 44
    $Befehl["RegisterCount"] = "0001";
    $rc = $funktionen->phocos_pv18_auslesen( $USB1, $Befehl );
    $aktuelleDaten["Anz.MPPT"] = substr( ($rc["data"]), 0, 2 );
    $aktuelleDaten["Anz.Phasen"] = substr( ($rc["data"]), 2, 2 );
    $funktionen->log_schreiben( "Debug: ".print_r( $rc, 1 ), "   ", 10 );
    // Input Register  Befehl 04      Input Register        Input Register
    // Input Register  Befehl 04      Input Register        Input Register
    // -----------------
    $Befehl["BefehlFunctionCode"] = "04";
    $Befehl["RegisterAddress"] = "0000"; // Dezimal 0
    $Befehl["RegisterCount"] = "0001";
    $rc = $funktionen->phocos_pv18_auslesen( $USB1, $Befehl );
    $aktuelleDaten["Status"] = $rc["data"] + 0;
    if ($aktuelleDaten["Status"] == 3) {
      $funktionen->log_schreiben( "Fehlermeldung. Zu dunkel?", "   ", 5 );
      goto Ausgang;
    }
    elseif ($aktuelleDaten["Status"] == 0) {
      $funktionen->log_schreiben( "Es ist zu dunkel. Wechselrichter im Schlafmodus.", "   ", 5 );
      goto Ausgang;
    }
    elseif ($rc["ok"] == false) {
      $funktionen->log_schreiben( "Keine Antwort vom Wechselrichter. Zu dunkel?", "   ", 5 );
      goto Ausgang;
    }
    $Befehl["RegisterAddress"] = "0001"; // Dezimal 1
    $Befehl["RegisterCount"] = "0002";
    $rc = $funktionen->phocos_pv18_auslesen( $USB1, $Befehl );
    $aktuelleDaten["PV_Leistung"] = hexdec( $rc["data"] ) / 10;
    $Befehl["RegisterAddress"] = "0003"; // Dezimal 3
    $Befehl["RegisterCount"] = "0001";
    $rc = $funktionen->phocos_pv18_auslesen( $USB1, $Befehl );
    $aktuelleDaten["PV_Spannung1"] = hexdec( $rc["data"] ) / 10;
    $Befehl["RegisterAddress"] = "0004"; // Dezimal 4
    $Befehl["RegisterCount"] = "0001";
    $rc = $funktionen->phocos_pv18_auslesen( $USB1, $Befehl );
    $aktuelleDaten["PV_Strom1"] = hexdec( $rc["data"] ) / 10;
    $Befehl["RegisterAddress"] = "0005"; // Dezimal 5
    $Befehl["RegisterCount"] = "0002";
    $rc = $funktionen->phocos_pv18_auslesen( $USB1, $Befehl );
    $aktuelleDaten["PV_Leistung1"] = hexdec( $rc["data"] ) / 10;
    // -----------------
    $Befehl["RegisterAddress"] = "0007"; // Dezimal 7
    $Befehl["RegisterCount"] = "0001";
    $rc = $funktionen->phocos_pv18_auslesen( $USB1, $Befehl );
    $aktuelleDaten["PV_Spannung2"] = hexdec( $rc["data"] ) / 10;
    $Befehl["RegisterAddress"] = "0008"; // Dezimal 8
    $Befehl["RegisterCount"] = "0001";
    $rc = $funktionen->phocos_pv18_auslesen( $USB1, $Befehl );
    $aktuelleDaten["PV_Strom2"] = hexdec( $rc["data"] ) / 10;
    $Befehl["RegisterAddress"] = "0009"; // Dezimal 9
    $Befehl["RegisterCount"] = "0002";
    $rc = $funktionen->phocos_pv18_auslesen( $USB1, $Befehl );
    $aktuelleDaten["PV_Leistung2"] = hexdec( $rc["data"] ) / 10;
    $Befehl["RegisterAddress"] = "0076"; // Dezimal 118
    $Befehl["RegisterCount"] = "0004";
    $rc = $funktionen->phocos_pv18_auslesen( $USB1, $Befehl );
    $aktuelleDaten["InverterModell"] = $rc["data"];
    if ($aktuelleDaten["Protokollversion"] == 1) {
      // Growatt PV Inverter Modbus RS485 RTU Protocol V3.05 (25.4.2013)
      $Befehl["RegisterAddress"] = "000B"; // Dezimal 11
      $Befehl["RegisterCount"] = "0002";
      $rc = $funktionen->phocos_pv18_auslesen( $USB1, $Befehl );
      $aktuelleDaten["AC_Leistung"] = $funktionen->hexdecs( $rc["data"] ) / 10;
      $funktionen->log_schreiben( "AC_Leistung: ".print_r( $rc, 1 ), "   ", 8 );
      if ($aktuelleDaten["AC_Leistung"] < 0) {
        $aktuelleDaten["AC_Leistung"] = 0;
      }
      $Befehl["RegisterAddress"] = "000D"; // Dezimal 13
      $Befehl["RegisterCount"] = "0001";
      $rc = $funktionen->phocos_pv18_auslesen( $USB1, $Befehl );
      $aktuelleDaten["AC_Frequenz"] = hexdec( $rc["data"] ) / 100;
      $Befehl["RegisterAddress"] = "000E"; // Dezimal 14
      $Befehl["RegisterCount"] = "0001";
      $rc = $funktionen->phocos_pv18_auslesen( $USB1, $Befehl );
      $aktuelleDaten["AC_Spannung_R"] = hexdec( $rc["data"] ) / 10;
      $Befehl["RegisterAddress"] = "000F"; // Dezimal 15
      $Befehl["RegisterCount"] = "0001";
      $rc = $funktionen->phocos_pv18_auslesen( $USB1, $Befehl );
      $aktuelleDaten["AC_Strom_R"] = hexdec( $rc["data"] ) / 10;
      $Befehl["RegisterAddress"] = "0010"; // Dezimal 16
      $Befehl["RegisterCount"] = "0002";
      $rc = $funktionen->phocos_pv18_auslesen( $USB1, $Befehl );
      $aktuelleDaten["AC_Leistung_R"] = hexdec( $rc["data"] ) / 10;
      $Befehl["RegisterAddress"] = "0012"; // Dezimal 18
      $Befehl["RegisterCount"] = "0001";
      $rc = $funktionen->phocos_pv18_auslesen( $USB1, $Befehl );
      $aktuelleDaten["AC_Spannung_S"] = hexdec( $rc["data"] ) / 10;
      $Befehl["RegisterAddress"] = "0013"; // Dezimal 19
      $Befehl["RegisterCount"] = "0001";
      $rc = $funktionen->phocos_pv18_auslesen( $USB1, $Befehl );
      $aktuelleDaten["AC_Strom_S"] = hexdec( $rc["data"] ) / 10;
      $Befehl["RegisterAddress"] = "0014"; // Dezimal 20
      $Befehl["RegisterCount"] = "0002";
      $rc = $funktionen->phocos_pv18_auslesen( $USB1, $Befehl );
      $aktuelleDaten["AC_Leistung_S"] = hexdec( $rc["data"] ) / 10;
      $Befehl["RegisterAddress"] = "0016"; // Dezimal 22
      $Befehl["RegisterCount"] = "0001";
      $rc = $funktionen->phocos_pv18_auslesen( $USB1, $Befehl );
      $aktuelleDaten["AC_Spannung_T"] = hexdec( $rc["data"] ) / 10;
      $Befehl["RegisterAddress"] = "0017"; // Dezimal 23
      $Befehl["RegisterCount"] = "0001";
      $rc = $funktionen->phocos_pv18_auslesen( $USB1, $Befehl );
      $aktuelleDaten["AC_Strom_T"] = hexdec( $rc["data"] ) / 10;
      $Befehl["RegisterAddress"] = "0018"; // Dezimal 24
      $Befehl["RegisterCount"] = "0002";
      $rc = $funktionen->phocos_pv18_auslesen( $USB1, $Befehl );
      $aktuelleDaten["AC_Leistung_T"] = hexdec( $rc["data"] ) / 10;
      $Befehl["RegisterAddress"] = "001A"; // Dezimal 26
      $Befehl["RegisterCount"] = "0002";
      $rc = $funktionen->phocos_pv18_auslesen( $USB1, $Befehl );
      $aktuelleDaten["WattstundenGesamtHeute"] = hexdec( $rc["data"] ) * 100;
      $Befehl["RegisterAddress"] = "001C"; // Dezimal 28
      $Befehl["RegisterCount"] = "0002";
      $rc = $funktionen->phocos_pv18_auslesen( $USB1, $Befehl );
      $aktuelleDaten["WattstundenGesamt"] = hexdec( $rc["data"] ) * 100;
      $Befehl["RegisterAddress"] = "0020"; // Dezimal 32
      $Befehl["RegisterCount"] = "0001";
      $rc = $funktionen->phocos_pv18_auslesen( $USB1, $Befehl );
      $aktuelleDaten["Temperatur"] = hexdec( $rc["data"] ) / 10;
      $Befehl["RegisterAddress"] = "0028"; // Dezimal 40
      $Befehl["RegisterCount"] = "0001";
      $rc = $funktionen->phocos_pv18_auslesen( $USB1, $Befehl );
      $aktuelleDaten["FehlerCode"] = $rc["data"];
      $Befehl["RegisterAddress"] = "0040"; // Dezimal 64
      $Befehl["RegisterCount"] = "0001";
      $rc = $funktionen->phocos_pv18_auslesen( $USB1, $Befehl );
      $aktuelleDaten["Warnungen"] = $rc["data"];
    }
    if ($aktuelleDaten["Protokollversion"] == 2 or $aktuelleDaten["Protokollversion"] == 3) {
      // Growatt Inverter Modbus RTU Protocol_II  V1.05  (9.4.2018)
      $Befehl["RegisterAddress"] = "0023"; // Dezimal 35
      $Befehl["RegisterCount"] = "0002";
      $rc = $funktionen->phocos_pv18_auslesen( $USB1, $Befehl );
      $aktuelleDaten["AC_Leistung"] = $funktionen->hexdecs( $rc["data"] ) / 10;
      $funktionen->log_schreiben( "AC_Leistung: ".print_r( $rc, 1 ), "   ", 8 );
      $funktionen->log_schreiben( "AC_Leistung: ".$aktuelleDaten["AC_Leistung"], "   ", 8 );
      if ($aktuelleDaten["AC_Leistung"] < 0) {
        $aktuelleDaten["AC_Leistung"] = 0;
      }
      $Befehl["RegisterAddress"] = "0025"; // Dezimal 37
      $Befehl["RegisterCount"] = "0001";
      $rc = $funktionen->phocos_pv18_auslesen( $USB1, $Befehl );
      $aktuelleDaten["AC_Frequenz"] = hexdec( $rc["data"] ) / 100;
      $Befehl["RegisterAddress"] = "0026"; // Dezimal 38
      $Befehl["RegisterCount"] = "0001";
      $rc = $funktionen->phocos_pv18_auslesen( $USB1, $Befehl );
      $aktuelleDaten["AC_Spannung_R"] = hexdec( $rc["data"] ) / 10;
      $Befehl["RegisterAddress"] = "0027"; // Dezimal 39
      $Befehl["RegisterCount"] = "0001";
      $rc = $funktionen->phocos_pv18_auslesen( $USB1, $Befehl );
      $aktuelleDaten["AC_Strom_R"] = hexdec( $rc["data"] ) / 10;
      $Befehl["RegisterAddress"] = "0028"; // Dezimal 40
      $Befehl["RegisterCount"] = "0002";
      $rc = $funktionen->phocos_pv18_auslesen( $USB1, $Befehl );
      $aktuelleDaten["AC_Leistung_R"] = hexdec( $rc["data"] ) / 10;
      $Befehl["RegisterAddress"] = "002A"; // Dezimal 42
      $Befehl["RegisterCount"] = "0001";
      $rc = $funktionen->phocos_pv18_auslesen( $USB1, $Befehl );
      $aktuelleDaten["AC_Spannung_S"] = hexdec( $rc["data"] ) / 10;
      $Befehl["RegisterAddress"] = "002B"; // Dezimal 43
      $Befehl["RegisterCount"] = "0001";
      $rc = $funktionen->phocos_pv18_auslesen( $USB1, $Befehl );
      $aktuelleDaten["AC_Strom_S"] = hexdec( $rc["data"] ) / 10;
      $Befehl["RegisterAddress"] = "002C"; // Dezimal 44
      $Befehl["RegisterCount"] = "0002";
      $rc = $funktionen->phocos_pv18_auslesen( $USB1, $Befehl );
      $aktuelleDaten["AC_Leistung_S"] = hexdec( $rc["data"] ) / 10;
      $Befehl["RegisterAddress"] = "002E"; // Dezimal 46
      $Befehl["RegisterCount"] = "0001";
      $rc = $funktionen->phocos_pv18_auslesen( $USB1, $Befehl );
      $aktuelleDaten["AC_Spannung_T"] = hexdec( $rc["data"] ) / 10;
      $Befehl["RegisterAddress"] = "002F"; // Dezimal 47
      $Befehl["RegisterCount"] = "0001";
      $rc = $funktionen->phocos_pv18_auslesen( $USB1, $Befehl );
      $aktuelleDaten["AC_Strom_T"] = hexdec( $rc["data"] ) / 10;
      $Befehl["RegisterAddress"] = "0030"; // Dezimal 48
      $Befehl["RegisterCount"] = "0002";
      $rc = $funktionen->phocos_pv18_auslesen( $USB1, $Befehl );
      $aktuelleDaten["AC_Leistung_T"] = hexdec( $rc["data"] ) / 10;
      $Befehl["RegisterAddress"] = "0035"; // Dezimal 53
      $Befehl["RegisterCount"] = "0002";
      $rc = $funktionen->phocos_pv18_auslesen( $USB1, $Befehl );
      $aktuelleDaten["WattstundenGesamtHeute"] = hexdec( $rc["data"] ) * 100;
      $Befehl["RegisterAddress"] = "0037"; // Dezimal 55
      $Befehl["RegisterCount"] = "0002";
      $rc = $funktionen->phocos_pv18_auslesen( $USB1, $Befehl );
      $aktuelleDaten["WattstundenGesamt"] = hexdec( $rc["data"] ) * 100;
      $Befehl["RegisterAddress"] = "005D"; // Dezimal 93
      $Befehl["RegisterCount"] = "0001";
      $rc = $funktionen->phocos_pv18_auslesen( $USB1, $Befehl );
      if (strtoupper( substr( $aktuelleDaten["Firmware"], 0, 2 )) == "DK") {
        $aktuelleDaten["Temperatur"] = hexdec( $rc["data"] ) / 100;
      }
      else {
        $aktuelleDaten["Temperatur"] = hexdec( $rc["data"] ) / 10;
      }
      $Befehl["RegisterAddress"] = "0068"; // Dezimal 104
      $Befehl["RegisterCount"] = "0001";
      $rc = $funktionen->phocos_pv18_auslesen( $USB1, $Befehl );
      $aktuelleDaten["DeratingMode"] = hexdec( $rc["data"] );
      $Befehl["RegisterAddress"] = "0069"; // Dezimal 105
      $Befehl["RegisterCount"] = "0001";
      $rc = $funktionen->phocos_pv18_auslesen( $USB1, $Befehl );
      $aktuelleDaten["FehlerCode"] = $rc["data"];
      $Befehl["RegisterAddress"] = "006E"; // Dezimal 110
      $Befehl["RegisterCount"] = "0002";
      $rc = $funktionen->phocos_pv18_auslesen( $USB1, $Befehl );
      $aktuelleDaten["Warnungen"] = $rc["data"];
      $Befehl["RegisterAddress"] = "0420"; // Dezimal   1056
      $Befehl["RegisterCount"] = "0002";
      $rc = $funktionen->phocos_pv18_auslesen( $USB1, $Befehl );
      $aktuelleDaten["GeladenHeute"] = hexdec( $rc["data"] ) * 100;
      $Befehl["RegisterAddress"] = "041C"; // Dezimal   1052
      $Befehl["RegisterCount"] = "0002";
      $rc = $funktionen->phocos_pv18_auslesen( $USB1, $Befehl );
      $aktuelleDaten["EntladenHeute"] = hexdec( $rc["data"] ) * 100;
    }
    if ($aktuelleDaten["Protokollversion"] == 3) {
      // Growatt Inverter Modbus RTU Protocol  V1.20  (28.4.2020)
      $Befehl["RegisterAddress"] = "003B"; // Dezimal   59 PV1 Energie Heute
      $Befehl["RegisterCount"] = "0002";
      $rc = $funktionen->phocos_pv18_auslesen( $USB1, $Befehl );
      $aktuelleDaten["PV1_Leistung_Heute"] = hexdec( $rc["data"] ) * 100;
      $Befehl["RegisterAddress"] = "003D"; // Dezimal   61 PV1 Energie Total
      $Befehl["RegisterCount"] = "0002";
      $rc = $funktionen->phocos_pv18_auslesen( $USB1, $Befehl );
      $aktuelleDaten["PV1_Leistung_Gesamt"] = hexdec( $rc["data"] ) * 100;
      $Befehl["RegisterAddress"] = "003F"; // Dezimal   63 PV2 Energie Heute
      $Befehl["RegisterCount"] = "0002";
      $rc = $funktionen->phocos_pv18_auslesen( $USB1, $Befehl );
      $aktuelleDaten["PV2_Leistung_Heute"] = hexdec( $rc["data"] ) * 100;
      $Befehl["RegisterAddress"] = "0041"; // Dezimal   65 PV2 Energie Total
      $Befehl["RegisterCount"] = "0002";
      $rc = $funktionen->phocos_pv18_auslesen( $USB1, $Befehl );
      $aktuelleDaten["PV2_Leistung_Gesamt"] = hexdec( $rc["data"] ) * 100;
      $Befehl["RegisterAddress"] = "03E8"; // Dezimal   1000 System Work Mode
      $Befehl["RegisterCount"] = "0001";
      $rc = $funktionen->phocos_pv18_auslesen( $USB1, $Befehl );
      $aktuelleDaten["SystemWorkMode"] = $rc["data"];
      $Befehl["RegisterAddress"] = "03F1"; // Dezimal   1009
      $Befehl["RegisterCount"] = "0002";
      $rc = $funktionen->phocos_pv18_auslesen( $USB1, $Befehl );
      $aktuelleDaten["EntladeLeistung"] = hexdec( $rc["data"] ) / 10;
      $Befehl["RegisterAddress"] = "03F3"; // Dezimal   1011
      $Befehl["RegisterCount"] = "0002";
      $rc = $funktionen->phocos_pv18_auslesen( $USB1, $Befehl );
      $aktuelleDaten["LadeLeistung"] = hexdec( $rc["data"] ) / 10;
      $Befehl["RegisterAddress"] = "03F5"; // Dezimal   1013 Battery voltage
      $Befehl["RegisterCount"] = "0001";
      $rc = $funktionen->phocos_pv18_auslesen( $USB1, $Befehl );
      $aktuelleDaten["Batteriespannung"] = hexdec( $rc["data"] ) / 10;
      $Befehl["RegisterAddress"] = "03F6"; // Dezimal   1014 SoC
      $Befehl["RegisterCount"] = "0001";
      $rc = $funktionen->phocos_pv18_auslesen( $USB1, $Befehl );
      $aktuelleDaten["SOC"] = hexdec( $rc["data"] );
      $Befehl["RegisterAddress"] = "03FD"; // Dezimal   1021
      $Befehl["RegisterCount"] = "0002";
      $rc = $funktionen->phocos_pv18_auslesen( $USB1, $Befehl );
      $aktuelleDaten["Gesamtverbrauch"] = hexdec( $rc["data"] ) / 10;
      $Befehl["RegisterAddress"] = "0405"; // Dezimal   1029
      $Befehl["RegisterCount"] = "0002";
      $rc = $funktionen->phocos_pv18_auslesen( $USB1, $Befehl );
      $aktuelleDaten["Gesamteinspeisung"] = hexdec( $rc["data"] ) / 10;
      $Befehl["RegisterAddress"] = "040D"; // Dezimal   1037
      $Befehl["RegisterCount"] = "0002";
      $rc = $funktionen->phocos_pv18_auslesen( $USB1, $Befehl );
      $aktuelleDaten["LoadLeistungGesamt"] = hexdec( $rc["data"] ) / 10;
      $Befehl["RegisterAddress"] = "0414"; // Dezimal   1044
      $Befehl["RegisterCount"] = "0002";
      $rc = $funktionen->phocos_pv18_auslesen( $USB1, $Befehl );
      $aktuelleDaten["Energieerzeugung_Heute"] = hexdec( $rc["data"] ) * 100;
      $Befehl["RegisterAddress"] = "0416"; // Dezimal   1046
      $Befehl["RegisterCount"] = "0002";
      $rc = $funktionen->phocos_pv18_auslesen( $USB1, $Befehl );
      $aktuelleDaten["EnergieerzeugungGesamt"] = hexdec( $rc["data"] ) * 100;
      $Befehl["RegisterAddress"] = "0418"; // Dezimal   1048
      $Befehl["RegisterCount"] = "0002";
      $rc = $funktionen->phocos_pv18_auslesen( $USB1, $Befehl );
      $aktuelleDaten["Energieeinspeisung_Heute"] = hexdec( $rc["data"] ) * 100;
      $Befehl["RegisterAddress"] = "041A"; // Dezimal   1050
      $Befehl["RegisterCount"] = "0002";
      $rc = $funktionen->phocos_pv18_auslesen( $USB1, $Befehl );
      $aktuelleDaten["EnergieeinspeisungGesamt"] = hexdec( $rc["data"] ) * 100;
      $Befehl["RegisterAddress"] = "0424"; // Dezimal   1060
      $Befehl["RegisterCount"] = "0002";
      $rc = $funktionen->phocos_pv18_auslesen( $USB1, $Befehl );
      $aktuelleDaten["Local_load_energy_today"] = hexdec( $rc["data"] ) * 100;
      $Befehl["RegisterAddress"] = "0426"; // Dezimal   1062
      $Befehl["RegisterCount"] = "0002";
      $rc = $funktionen->phocos_pv18_auslesen( $USB1, $Befehl );
      $aktuelleDaten["Local_load_energy_total"] = hexdec( $rc["data"] ) * 100;
      $Befehl["RegisterAddress"] = "043B"; // Dezimal   1083
      $Befehl["RegisterCount"] = "0001";
      $rc = $funktionen->phocos_pv18_auslesen( $USB1, $Befehl );
      $aktuelleDaten["BMS_Status"] = hexdec( $rc["data"] );
      $Befehl["RegisterAddress"] = "043D"; // Dezimal   1085
      $Befehl["RegisterCount"] = "0001";
      $rc = $funktionen->phocos_pv18_auslesen( $USB1, $Befehl );
      $aktuelleDaten["BMS_ErrorCode"] = hexdec( $rc["data"] );
      $Befehl["RegisterAddress"] = "043E"; // Dezimal   1086
      $Befehl["RegisterCount"] = "0001";
      $rc = $funktionen->phocos_pv18_auslesen( $USB1, $Befehl );
      $aktuelleDaten["BMS_SOC"] = hexdec( $rc["data"] );
      $Befehl["RegisterAddress"] = "0448"; // Dezimal   1096
      $Befehl["RegisterCount"] = "0001";
      $rc = $funktionen->phocos_pv18_auslesen( $USB1, $Befehl );
      $aktuelleDaten["BMS_SOH"] = hexdec( $rc["data"] );
      $Befehl["RegisterAddress"] = "0454"; // Dezimal   1108
      $Befehl["RegisterCount"] = "0001";
      $rc = $funktionen->phocos_pv18_auslesen( $USB1, $Befehl );
      $aktuelleDaten["Zellenspannung1"] = hexdec( $rc["data"] ) / 1000;
      $Befehl["RegisterAddress"] = "0455"; // Dezimal   1109
      $Befehl["RegisterCount"] = "0001";
      $rc = $funktionen->phocos_pv18_auslesen( $USB1, $Befehl );
      $aktuelleDaten["Zellenspannung2"] = hexdec( $rc["data"] ) / 1000;
      $Befehl["RegisterAddress"] = "0456"; // Dezimal   1110
      $Befehl["RegisterCount"] = "0001";
      $rc = $funktionen->phocos_pv18_auslesen( $USB1, $Befehl );
      $aktuelleDaten["Zellenspannung3"] = hexdec( $rc["data"] ) / 1000;
      $Befehl["RegisterAddress"] = "0457"; // Dezimal   1111
      $Befehl["RegisterCount"] = "0001";
      $rc = $funktionen->phocos_pv18_auslesen( $USB1, $Befehl );
      $aktuelleDaten["Zellenspannung4"] = hexdec( $rc["data"] ) / 1000;
      $Befehl["RegisterAddress"] = "0458"; // Dezimal   1112
      $Befehl["RegisterCount"] = "0001";
      $rc = $funktionen->phocos_pv18_auslesen( $USB1, $Befehl );
      $aktuelleDaten["Zellenspannung5"] = hexdec( $rc["data"] ) / 1000;
      $Befehl["RegisterAddress"] = "0459"; // Dezimal   1113
      $Befehl["RegisterCount"] = "0001";
      $rc = $funktionen->phocos_pv18_auslesen( $USB1, $Befehl );
      $aktuelleDaten["Zellenspannung6"] = hexdec( $rc["data"] ) / 1000;
      $Befehl["RegisterAddress"] = "045A"; // Dezimal   1114
      $Befehl["RegisterCount"] = "0001";
      $rc = $funktionen->phocos_pv18_auslesen( $USB1, $Befehl );
      $aktuelleDaten["Zellenspannung7"] = hexdec( $rc["data"] ) / 1000;
      $Befehl["RegisterAddress"] = "045B"; // Dezimal   1115
      $Befehl["RegisterCount"] = "0001";
      $rc = $funktionen->phocos_pv18_auslesen( $USB1, $Befehl );
      $aktuelleDaten["Zellenspannung8"] = hexdec( $rc["data"] ) / 1000;
      $Befehl["RegisterAddress"] = "045C"; // Dezimal   1116
      $Befehl["RegisterCount"] = "0001";
      $rc = $funktionen->phocos_pv18_auslesen( $USB1, $Befehl );
      $aktuelleDaten["Zellenspannung9"] = hexdec( $rc["data"] ) / 1000;
      $Befehl["RegisterAddress"] = "045D"; // Dezimal   1117
      $Befehl["RegisterCount"] = "0001";
      $rc = $funktionen->phocos_pv18_auslesen( $USB1, $Befehl );
      $aktuelleDaten["Zellenspannung10"] = hexdec( $rc["data"] ) / 1000;
      $Befehl["RegisterAddress"] = "045E"; // Dezimal   1118
      $Befehl["RegisterCount"] = "0001";
      $rc = $funktionen->phocos_pv18_auslesen( $USB1, $Befehl );
      $aktuelleDaten["Zellenspannung11"] = hexdec( $rc["data"] ) / 1000;
      $Befehl["RegisterAddress"] = "045F"; // Dezimal   1119
      $Befehl["RegisterCount"] = "0001";
      $rc = $funktionen->phocos_pv18_auslesen( $USB1, $Befehl );
      $aktuelleDaten["Zellenspannung12"] = hexdec( $rc["data"] ) / 1000;
      $Befehl["RegisterAddress"] = "0460"; // Dezimal   1120
      $Befehl["RegisterCount"] = "0001";
      $rc = $funktionen->phocos_pv18_auslesen( $USB1, $Befehl );
      $aktuelleDaten["Zellenspannung13"] = hexdec( $rc["data"] ) / 1000;
      $Befehl["RegisterAddress"] = "0461"; // Dezimal   1121
      $Befehl["RegisterCount"] = "0001";
      $rc = $funktionen->phocos_pv18_auslesen( $USB1, $Befehl );
      $aktuelleDaten["Zellenspannung14"] = hexdec( $rc["data"] ) / 1000;
      $Befehl["RegisterAddress"] = "0462"; // Dezimal   1122
      $Befehl["RegisterCount"] = "0001";
      $rc = $funktionen->phocos_pv18_auslesen( $USB1, $Befehl );
      $aktuelleDaten["Zellenspannung15"] = hexdec( $rc["data"] ) / 1000;
      $Befehl["RegisterAddress"] = "0463"; // Dezimal   1123
      $Befehl["RegisterCount"] = "0001";
      $rc = $funktionen->phocos_pv18_auslesen( $USB1, $Befehl );
      $aktuelleDaten["Zellenspannung16"] = hexdec( $rc["data"] ) / 1000;
    }
  }
  if ($aktuelleDaten["Protokollversion"] == 4) {
    //  Holding Register
    $Befehl["BefehlFunctionCode"] = "03";
    $Befehl["RegisterAddress"] = "0000"; // Dezimal 0
    $Befehl["RegisterCount"] = "0028";
    $rc = $funktionen->phocos_pv18_auslesen( $USB1, $Befehl, $Timer );
    $funktionen->log_schreiben( print_r( $rc, 1 ), "   ", 9 );
    $aktuelleDaten["Standby"] = substr( $rc["data"], 0, 2 );
    $aktuelleDaten["AC_output"] = substr( $rc["data"], 2, 2 );
    $aktuelleDaten["OutputConfig"] = hexdec( substr( $rc["data"], 4, 4 ));
    $aktuelleDaten["ChargeConfig"] = hexdec( substr( $rc["data"], 8, 4 ));

    $aktuelleDaten["MaxChargeCurrent"] = hexdec( substr( $rc["data"], 136, 4 ));
    $aktuelleDaten["BulkChargeVolt"] = hexdec( substr( $rc["data"], 140, 4 ))/10;
    $aktuelleDaten["FloatChargeVolt"] = hexdec( substr( $rc["data"], 144, 4 ))/10;
    $aktuelleDaten["BatLowUtiVolt"] = hexdec( substr( $rc["data"], 148, 4 ))/10;
    $aktuelleDaten["FloatChargCurrent"] = hexdec( substr( $rc["data"], 152, 4 ))/10;
    $aktuelleDaten["BatteryType"] = hexdec( substr( $rc["data"], 156, 4 ));




    //  Input Register
    $Befehl["BefehlFunctionCode"] = "04";
    $Befehl["RegisterAddress"] = "0000"; // Dezimal 0
    $Befehl["RegisterCount"] = "0053";
    $rc = $funktionen->phocos_pv18_auslesen( $USB1, $Befehl, $Timer );
    $funktionen->log_schreiben( $rc["data"], "   ", 9 );
    $aktuelleDaten["Status"] = hexdec( substr( $rc["data"], 0, 4 ));
    $aktuelleDaten["PV_Spannung1"] = hexdec( substr( $rc["data"], 4, 4 )) / 10;
    $aktuelleDaten["PV_Spannung2"] = hexdec( substr( $rc["data"], 8, 4 )) / 10;
    $aktuelleDaten["PV_Leistung1"] = hexdec( substr( $rc["data"], 12, 8 )) / 10;
    $aktuelleDaten["PV_Leistung2"] = hexdec( substr( $rc["data"], 20, 8 )) / 10;
    $aktuelleDaten["Buck1_Strom"] = hexdec( substr( $rc["data"], 28, 4 )) / 10;
    $aktuelleDaten["Buck2_Strom"] = hexdec( substr( $rc["data"], 32, 4 )) / 10;
    if ($aktuelleDaten["PV_Leistung1"] > 0) {
      $aktuelleDaten["PV_Strom1"] = round(($aktuelleDaten["PV_Leistung1"] / $aktuelleDaten["PV_Spannung1"]),2);
    }
    else {
      $aktuelleDaten["PV_Strom1"] = 0;
    }
    if ($aktuelleDaten["PV_Leistung2"] > 0) {
      $aktuelleDaten["PV_Strom2"] = round(($aktuelleDaten["PV_Leistung2"] / $aktuelleDaten["PV_Spannung2"]),2);
    }
    else {
      $aktuelleDaten["PV_Strom2"] = 0;
    }
    $aktuelleDaten["AC_Leistung_R"] = hexdec( substr( $rc["data"], 36, 8 )) / 10;
    $aktuelleDaten["AC_Scheinleistung_R"] = hexdec( substr( $rc["data"], 44, 8 )) / 10;
    $aktuelleDaten["AC_Charge"] = hexdec( substr( $rc["data"], 52, 8 )) / 10;
    $aktuelleDaten["Batteriespannung"] = hexdec( substr( $rc["data"], 68, 4 )) / 100;
    $aktuelleDaten["BatterieSOC"] = hexdec( substr( $rc["data"], 72, 4 ));
    $aktuelleDaten["AC_Spannung_R"] = hexdec( substr( $rc["data"], 80, 4 )) / 10;
    $aktuelleDaten["AC_Frequenz"] = hexdec( substr( $rc["data"], 84, 4 )) / 100;
    $aktuelleDaten["AC_Ausgangsspannung"] = hexdec( substr( $rc["data"], 88, 4 )) / 10;
    $aktuelleDaten["AC_Ausgangsfrequenz"] = hexdec( substr( $rc["data"], 92, 4 )) / 100;
    $aktuelleDaten["DC_Spannung"] = $funktionen->hexdecs( substr( $rc["data"], 96, 4 )) / 10;
    $aktuelleDaten["Temperatur"] = hexdec( substr( $rc["data"], 100, 4 )) / 10;
    $aktuelleDaten["DC-DC-Temperatur"] = hexdec( substr( $rc["data"], 104, 4 )) / 10;
    $aktuelleDaten["Ausgangslast"] = hexdec( substr( $rc["data"], 108, 4 )) / 10;
    $aktuelleDaten["Batt_DSP_Port"] = hexdec( substr( $rc["data"], 112, 4 )) / 100;
    $aktuelleDaten["Batt_DSP_Bus"] = hexdec( substr( $rc["data"], 116, 4 )) / 100;
    $aktuelleDaten["AC_Strom_R"] = hexdec( substr( $rc["data"], 136, 4 )) / 10;
    $aktuelleDaten["Inverterstrom"] = hexdec( substr( $rc["data"], 140, 4 )) / 10;
    $aktuelleDaten["AC_Eingangsleistung"] = hexdec( substr( $rc["data"], 144, 8 ))/10;
    $aktuelleDaten["AC_Eingangsscheinleistung"] = hexdec( substr( $rc["data"], 152, 8 ))/10;
    $aktuelleDaten["FehlerCodeBit"] = $funktionen->d2b( substr( $rc["data"], 160, 4 ));
    $aktuelleDaten["WarnungenBit"] = $funktionen->d2b( substr( $rc["data"], 164, 4 ));
    $aktuelleDaten["FehlerCode"] = hexdec(substr( $rc["data"], 168, 4 ));
    $aktuelleDaten["Warnungen"] = hexdec(substr( $rc["data"], 172, 4 ));
    $aktuelleDaten["Mode"] = hexdec( substr( $rc["data"], 184, 4 ));
    $aktuelleDaten["PV1_Leistung_Heute"] = hexdec( substr( $rc["data"], 192, 8 )) * 100;
    $aktuelleDaten["PV1_Leistung_Gesamt"] = hexdec( substr( $rc["data"], 200, 8 )) * 100;
    $aktuelleDaten["PV2_Leistung_Heute"] = hexdec( substr( $rc["data"], 208, 8 )) * 100;
    $aktuelleDaten["PV2_Leistung_Gesamt"] = hexdec( substr( $rc["data"], 216, 8 )) * 100;
    $aktuelleDaten["AC_LadeenergieHeute"] = hexdec( substr( $rc["data"], 224, 8 )) * 100;
    $aktuelleDaten["AC_LadeenergieGesamt"] = hexdec( substr( $rc["data"], 232, 8 )) * 100;

    $aktuelleDaten["Batt_EntladeenergieHeute"] = hexdec( substr( $rc["data"], 240, 8 )) * 100;
    $aktuelleDaten["Batt_EntladeenergieGesamt"] = hexdec( substr( $rc["data"], 248, 8 )) * 100;

    $aktuelleDaten["AC_EntladeenergieHeute"] = hexdec( substr( $rc["data"], 256, 8 )) * 100;
    $aktuelleDaten["AC_EntladeenergieGesamt"] = hexdec( substr( $rc["data"], 264, 8 )) * 100;

    $aktuelleDaten["AC_Ladestrom"] = hexdec( substr( $rc["data"], 272, 4 )) / 10;
    $aktuelleDaten["AC_Entladeleistung"] = hexdec( substr( $rc["data"], 276, 8 )) / 10;
    $aktuelleDaten["Batt_Entladeleistung"] = hexdec( substr( $rc["data"], 292, 8 )) / 10;
    $aktuelleDaten["Batterie_Leistung"] = $funktionen->hexdecs( substr( $rc["data"], 308, 8 )) / 10;
    $aktuelleDaten["MPPT_Fan_Speed"] = hexdec( substr( $rc["data"], 324, 4 ));
    $aktuelleDaten["Inv_Fan_Speed"] = hexdec( substr( $rc["data"], 328, 4 ));

    $aktuelleDaten["AC_Leistung"] =  $aktuelleDaten["AC_Leistung_R"];
    $aktuelleDaten["PV_Leistung"] = ($aktuelleDaten["PV_Leistung1"] + $aktuelleDaten["PV_Leistung2"]);
    $aktuelleDaten["WattstundenGesamtHeute"] = ($aktuelleDaten["PV1_Leistung_Heute"] + $aktuelleDaten["PV2_Leistung_Heute"]);
    $aktuelleDaten["WattstundenGesamt"] = ($aktuelleDaten["PV1_Leistung_Gesamt"] + $aktuelleDaten["PV2_Leistung_Gesamt"]);

    if ($aktuelleDaten["Batterie_Leistung"] > 0) {
      $aktuelleDaten["Batterie_Entladung"] =  $aktuelleDaten["Batterie_Leistung"];
      $aktuelleDaten["Batterie_Ladung"] =  0;

    }
    else {
      $aktuelleDaten["Batterie_Ladung"] =  abs($aktuelleDaten["Batterie_Leistung"]);
      $aktuelleDaten["Batterie_Entladung"] =  0;
    }

    $aktuelleDaten["Netz_Bezug_Heute"]  = ($aktuelleDaten["AC_LadeenergieHeute"] +  $aktuelleDaten["AC_EntladeenergieHeute"]); 
    $aktuelleDaten["Netz_Bezug_Gesamt"] = ($aktuelleDaten["AC_LadeenergieGesamt"] +  $aktuelleDaten["AC_EntladeenergieGesamt"]); 

  }






  $funktionen->log_schreiben( "Firmware: ".$aktuelleDaten["Firmware"]."  Warnungen: ".$aktuelleDaten["Warnungen"], "   ", 6 );
  $funktionen->log_schreiben( "Auslesen des Gerätes beendet.", "   ", 7 );




  /****************************************************************************
  //  ENDE REGLER AUSLESEN      ENDE REGLER AUSLESEN      ENDE REGLER AUSLESEN
  ****************************************************************************/

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
  $aktuelleDaten["Modell"] = "Growatt";
  $aktuelleDaten["zentralerTimestamp"] = ($aktuelleDaten["zentralerTimestamp"] + 10);

  /****************************************************************************
  //  Standard Daten für die HomeMatic Übertragung.
  ****************************************************************************/
  $aktuelleDaten["HM_Solarleistung"] = $aktuelleDaten["PV_Leistung"];
  $aktuelleDaten["HM_AC_Leistung"] = $aktuelleDaten["AC_Leistung"];
  // $aktuelleDaten["WattstundenGesamtHeute"] = 0;
  $aktuelleDaten["HM_Temperatur"] = $aktuelleDaten["Temperatur"];

  /****************************************************************************
  //  User PHP Script, falls gewünscht oder nötig
  ****************************************************************************/
  if (file_exists( "/var/www/html/growatt_math.php" )) {
    include 'growatt_math.php'; // Falls etwas neu berechnet werden muss.
  }
  $funktionen->log_schreiben( print_r( $aktuelleDaten, 1 ), "   ", 8 );

  /**************************************************************************
  //  Alle ausgelesenen Daten werden hier bei Bedarf als mqtt Messages
  //  an den mqtt-Broker Mosquitto gesendet.
  //  Achtung! Die Übertragung dauert ca. 30 Sekunden!
  **************************************************************************/
  if ($MQTT and strtoupper( $MQTTAuswahl ) != "OPENWB") {
    $funktionen->log_schreiben( "MQTT Daten zum [ $MQTTBroker ] senden.", "   ", 1 );
    require ($Pfad."/mqtt_senden.php");
  }

  /****************************************************************************
  //  Zeit und Datum
  ****************************************************************************/
  //  Der Regler hat keine interne Uhr! Deshalb werden die Daten vom Raspberry benutzt.
  $aktuelleDaten["Timestamp"] = time( );
  $aktuelleDaten["Monat"] = date( "n" );
  $aktuelleDaten["Woche"] = date( "W" );
  $aktuelleDaten["Wochentag"] = strftime( "%A", time( ));
  $aktuelleDaten["Datum"] = date( "d.m.Y" );
  $aktuelleDaten["Uhrzeit"] = date( "H:i:s" );

  /****************************************************************************
  //  InfluxDB  Zugangsdaten ...stehen in der user.config.php
  //  falls nicht, sind das hier die default Werte.
  ****************************************************************************/
  $aktuelleDaten["InfluxAdresse"] = $InfluxAdresse;
  $aktuelleDaten["InfluxPort"] = $InfluxPort;
  $aktuelleDaten["InfluxUser"] = $InfluxUser;
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
      $rc = $funktionen->influx_remote_test( );
      if ($rc) {
        $rc = $funktionen->influx_remote( $aktuelleDaten );
        if ($rc) {
          $RemoteDaten = false;
        }
      }
      else {
        $RemoteDaten = false;
      }
    }
    if ($InfluxDB_local) {
      $rc = $funktionen->influx_local( $aktuelleDaten );
    }
  }
  else {
    $rc = $funktionen->influx_local( $aktuelleDaten );
  }
  if (is_file( $Pfad."/1.user.config.php" )) {
    // Ausgang Multi-Regler-Version
    $Zeitspanne = (9 - (time( ) - $Start));
    $funktionen->log_schreiben( "Multi-Regler-Ausgang. ".$Zeitspanne, "   ", 2 );
    if ($Zeitspanne > 0) {
      sleep( $Zeitspanne );
    }
    break;
  }
  else {
    $funktionen->log_schreiben( "Schleife: ".($i)." Zeitspanne: ".(floor( (56 - (time( ) - $Start)) / ($Wiederholungen - $i + 1))), "   ", 9 );
    sleep( floor( (56 - (time( ) - $Start)) / ($Wiederholungen - $i + 1)));
  }
  if ($Wiederholungen <= $i or $i >= 6) {
    $funktionen->log_schreiben( "Schleife ".$i." Ausgang...", "   ", 8 );
    break;
  }
  $i++;
} while (($Start + 54) > time( ));

if (isset($aktuelleDaten["Temperatur"]) and isset($aktuelleDaten["Regler"])) {

  /*********************************************************************
  //  Jede Minute werden bei Bedarf einige Werte zur Homematic Zentrale
  //  übertragen.
  *********************************************************************/
  if (isset($Homematic) and $Homematic == true) {
    $funktionen->log_schreiben( "Daten werden zur HomeMatic übertragen...", "   ", 8 );
    require ($Pfad."/homematic.php");
  }

  $funktionen->log_schreiben( "OK. Datenübertragung erfolgreich.", "   ", 7 );
}
else {
  $funktionen->log_schreiben( "Keine gültigen Daten empfangen.", "!! ", 6 );
}

/**********/
Ausgang:
/*********************************************************************
//  Sollen Nachrichten an einen Messenger gesendet werden?
//  Bei einer Multi-Regler-Version sollte diese Funktion nur bei einem
//  Gerät aktiviert sein.
*********************************************************************/
if (isset($Messenger) and $Messenger == true) {
  $funktionen->log_schreiben( "Nachrichten versenden...", "   ", 8 );
  require ($Pfad."/meldungen_senden.php");
}

$funktionen->log_schreiben( "----------------------   Stop   growatt.php   --------------------- ", "|--", 6 );
return;
?>
