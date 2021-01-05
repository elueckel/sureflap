# Sureflap Katzenklappe
Das Modul ermöglicht das Abfragen von Daten von Sureflap Connect Haustier- und Katzenklappen. Hierbei ist es möglich Daten zu den Haustieren vom Sureflap Portal abzufragen und die Daten in Symcon anzuzeigen. Weitere funktionen wie die Steuerung der Klappe können bei Bedarf vermutlich hinzugefügt werden.

## Konfiguration 1.00
Um das Modul nutzen zu können ist ein Account bei Sureflap notwendig in dem die Klappen und Haustiere eingerichtet sind - hierfür bitte die entsprechende Dokumentation von Sureflap beachten. Das Modul kann wie jedes Modul in Symcon via Module Store oder Modules eingerichtet werden. Nach der Einrichtung geht man wie folgt vor:

1. Eingabe Benutzername & Kennwort
2. Klick auf Einrichten hierbei werden der Hub, Klappen und Haustiere von Sureflap abgefragt, danach können die Stati der Klappen und Tiere abgefragt werden.
3. Setzen des Abrufintervalls - dieser bestimmt wie oft Daten abgefragt werden sollen (Maximal 1 x pro Minute)


## Version 1.0 (05.01.2021)
* Abfragen von Katzen und Haustierklappen
* Abfragen von Haustieren
* Abfragen von Anwesenheiten
* Abgefragte Werte werden als Variablen bereitgestellt
