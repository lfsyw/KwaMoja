INSERT INTO `accountsection` (`sectionid`, `language`, `sectionname`) VALUES (10,'de_DE.utf8','Aktiven');
INSERT INTO `accountsection` (`sectionid`, `language`, `sectionname`) VALUES (20,'de_DE.utf8','Passiven');
INSERT INTO `accountsection` (`sectionid`, `language`, `sectionname`) VALUES (30,'de_DE.utf8','Erfolg');
INSERT INTO `accountsection` (`sectionid`, `language`, `sectionname`) VALUES (40,'de_DE.utf8','Aufwand');

INSERT INTO `accountgroups` (`groupname`, `groupcode`, `language`, `sectioninaccounts`, `pandl`, `sequenceintb`, `parentgroupname`, `parentgroupcode`) VALUES ('ANLAGEVERM','10','de_DE.utf8',10,0,1000,'','');
INSERT INTO `accountgroups` (`groupname`, `groupcode`, `language`, `sectioninaccounts`, `pandl`, `sequenceintb`, `parentgroupname`, `parentgroupcode`) VALUES ('NEUTRALE AUFWENDUNGEN','100','de_DE.utf8',40,1,15000,'','');
INSERT INTO `accountgroups` (`groupname`, `groupcode`, `language`, `sectioninaccounts`, `pandl`, `sequenceintb`, `parentgroupname`, `parentgroupcode`) VALUES ('NEUTRALE ERTR','110','de_DE.utf8',30,1,16000,'','');
INSERT INTO `accountgroups` (`groupname`, `groupcode`, `language`, `sectioninaccounts`, `pandl`, `sequenceintb`, `parentgroupname`, `parentgroupcode`) VALUES ('PERSONALKOSTEN','120','de_DE.utf8',40,1,11000,'','');
INSERT INTO `accountgroups` (`groupname`, `groupcode`, `language`, `sectioninaccounts`, `pandl`, `sequenceintb`, `parentgroupname`, `parentgroupcode`) VALUES ('R','130','de_DE.utf8',20,0,6000,'','');
INSERT INTO `accountgroups` (`groupname`, `groupcode`, `language`, `sectioninaccounts`, `pandl`, `sequenceintb`, `parentgroupname`, `parentgroupcode`) VALUES ('RAUMKOSTEN','140','de_DE.utf8',40,1,12000,'','');
INSERT INTO `accountgroups` (`groupname`, `groupcode`, `language`, `sectioninaccounts`, `pandl`, `sequenceintb`, `parentgroupname`, `parentgroupcode`) VALUES ('SONSTIGE KOSTEN','150','de_DE.utf8',40,1,13000,'','');
INSERT INTO `accountgroups` (`groupname`, `groupcode`, `language`, `sectioninaccounts`, `pandl`, `sequenceintb`, `parentgroupname`, `parentgroupcode`) VALUES ('WARENBESTAND','160','de_DE.utf8',10,0,2000,'','');
INSERT INTO `accountgroups` (`groupname`, `groupcode`, `language`, `sectioninaccounts`, `pandl`, `sequenceintb`, `parentgroupname`, `parentgroupcode`) VALUES ('WARENEINGANG','170','de_DE.utf8',40,1,9000,'','');
INSERT INTO `accountgroups` (`groupname`, `groupcode`, `language`, `sectioninaccounts`, `pandl`, `sequenceintb`, `parentgroupname`, `parentgroupcode`) VALUES ('Ausserordentl.Ertr','20','de_DE.utf8',30,1,18000,'','');
INSERT INTO `accountgroups` (`groupname`, `groupcode`, `language`, `sectioninaccounts`, `pandl`, `sequenceintb`, `parentgroupname`, `parentgroupcode`) VALUES ('EIGENKAPITAL','30','de_DE.utf8',20,0,5000,'','');
INSERT INTO `accountgroups` (`groupname`, `groupcode`, `language`, `sectioninaccounts`, `pandl`, `sequenceintb`, `parentgroupname`, `parentgroupcode`) VALUES ('ERTR','40','de_DE.utf8',30,1,14000,'','');
INSERT INTO `accountgroups` (`groupname`, `groupcode`, `language`, `sectioninaccounts`, `pandl`, `sequenceintb`, `parentgroupname`, `parentgroupcode`) VALUES ('FORDERUNGEN','50','de_DE.utf8',10,0,3000,'','');
INSERT INTO `accountgroups` (`groupname`, `groupcode`, `language`, `sectioninaccounts`, `pandl`, `sequenceintb`, `parentgroupname`, `parentgroupcode`) VALUES ('FREMDKAPITAL KURZFRISTIG','60','de_DE.utf8',20,0,8000,'','');
INSERT INTO `accountgroups` (`groupname`, `groupcode`, `language`, `sectioninaccounts`, `pandl`, `sequenceintb`, `parentgroupname`, `parentgroupcode`) VALUES ('FREMDKAPITAL LANGFRISTIG','70','de_DE.utf8',20,0,7000,'','');
INSERT INTO `accountgroups` (`groupname`, `groupcode`, `language`, `sectioninaccounts`, `pandl`, `sequenceintb`, `parentgroupname`, `parentgroupcode`) VALUES ('Guthabenzinsen','80','de_DE.utf8',30,1,17000,'','');
INSERT INTO `accountgroups` (`groupname`, `groupcode`, `language`, `sectioninaccounts`, `pandl`, `sequenceintb`, `parentgroupname`, `parentgroupcode`) VALUES ('LIQUIDE MITTEL','90','de_DE.utf8',10,0,4000,'','');

INSERT INTO `chartmaster` (`accountcode`, `language`, `accountname`, `group_`, `groupcode`) VALUES ('0100','de_DE.utf8','Konzessionen & Lizenzen','ANLAGEVERM','');
INSERT INTO `chartmaster` (`accountcode`, `language`, `accountname`, `group_`, `groupcode`) VALUES ('0135','de_DE.utf8','EDV-Programme','ANLAGEVERM','');
INSERT INTO `chartmaster` (`accountcode`, `language`, `accountname`, `group_`, `groupcode`) VALUES ('0440','de_DE.utf8','Maschinen','ANLAGEVERM','');
INSERT INTO `chartmaster` (`accountcode`, `language`, `accountname`, `group_`, `groupcode`) VALUES ('0500','de_DE.utf8','Betriebsausstattung','ANLAGEVERM','');
INSERT INTO `chartmaster` (`accountcode`, `language`, `accountname`, `group_`, `groupcode`) VALUES ('0520','de_DE.utf8','PKW','ANLAGEVERM','');
INSERT INTO `chartmaster` (`accountcode`, `language`, `accountname`, `group_`, `groupcode`) VALUES ('0650','de_DE.utf8','B','ANLAGEVERM','');
INSERT INTO `chartmaster` (`accountcode`, `language`, `accountname`, `group_`, `groupcode`) VALUES ('0670','de_DE.utf8','GWG','ANLAGEVERM','');
INSERT INTO `chartmaster` (`accountcode`, `language`, `accountname`, `group_`, `groupcode`) VALUES ('1140','de_DE.utf8','Warenbestand','WARENBESTAND','');
INSERT INTO `chartmaster` (`accountcode`, `language`, `accountname`, `group_`, `groupcode`) VALUES ('1201','de_DE.utf8','Geleistete Anzahlungen','FORDERUNGEN','');
INSERT INTO `chartmaster` (`accountcode`, `language`, `accountname`, `group_`, `groupcode`) VALUES ('1210','de_DE.utf8','Forderungen ohne Kontokorrent','FORDERUNGEN','');
INSERT INTO `chartmaster` (`accountcode`, `language`, `accountname`, `group_`, `groupcode`) VALUES ('1300','de_DE.utf8','Sonstige Forderungen','FORDERUNGEN','');
INSERT INTO `chartmaster` (`accountcode`, `language`, `accountname`, `group_`, `groupcode`) VALUES ('1370','de_DE.utf8','Ungekl','FORDERUNGEN','');
INSERT INTO `chartmaster` (`accountcode`, `language`, `accountname`, `group_`, `groupcode`) VALUES ('1400','de_DE.utf8','Anrechenbare Vorsteuer','FORDERUNGEN','');
INSERT INTO `chartmaster` (`accountcode`, `language`, `accountname`, `group_`, `groupcode`) VALUES ('1401','de_DE.utf8','Anrechenbare Vorsteuer 7%','FORDERUNGEN','');
INSERT INTO `chartmaster` (`accountcode`, `language`, `accountname`, `group_`, `groupcode`) VALUES ('1402','de_DE.utf8','Vorsteuer ig Erwerb','FORDERUNGEN','');
INSERT INTO `chartmaster` (`accountcode`, `language`, `accountname`, `group_`, `groupcode`) VALUES ('1403','de_DE.utf8','Vorsteuer ig Erwerb 16%','FORDERUNGEN','');
INSERT INTO `chartmaster` (`accountcode`, `language`, `accountname`, `group_`, `groupcode`) VALUES ('1405','de_DE.utf8','Anrechenbare Vorsteuer 16%','FORDERUNGEN','');
INSERT INTO `chartmaster` (`accountcode`, `language`, `accountname`, `group_`, `groupcode`) VALUES ('1406','de_DE.utf8','Anrechenbare Vorsteuer 15%','FORDERUNGEN','');
INSERT INTO `chartmaster` (`accountcode`, `language`, `accountname`, `group_`, `groupcode`) VALUES ('1433','de_DE.utf8','bezahlte Einfuhrumsatzsteuer','FORDERUNGEN','');
INSERT INTO `chartmaster` (`accountcode`, `language`, `accountname`, `group_`, `groupcode`) VALUES ('1601','de_DE.utf8','Kasse','LIQUIDE MITTEL','');
INSERT INTO `chartmaster` (`accountcode`, `language`, `accountname`, `group_`, `groupcode`) VALUES ('1700','de_DE.utf8','Postgiro','LIQUIDE MITTEL','');
INSERT INTO `chartmaster` (`accountcode`, `language`, `accountname`, `group_`, `groupcode`) VALUES ('1800','de_DE.utf8','Bank','LIQUIDE MITTEL','');
INSERT INTO `chartmaster` (`accountcode`, `language`, `accountname`, `group_`, `groupcode`) VALUES ('1810','de_DE.utf8','Bank USD','LIQUIDE MITTEL','');
INSERT INTO `chartmaster` (`accountcode`, `language`, `accountname`, `group_`, `groupcode`) VALUES ('1820','de_DE.utf8','Kreditkarten','LIQUIDE MITTEL','');
INSERT INTO `chartmaster` (`accountcode`, `language`, `accountname`, `group_`, `groupcode`) VALUES ('1890','de_DE.utf8','Geldtransit','LIQUIDE MITTEL','');
INSERT INTO `chartmaster` (`accountcode`, `language`, `accountname`, `group_`, `groupcode`) VALUES ('1900','de_DE.utf8','Aktive Rechnungsabgrenzung','LIQUIDE MITTEL','');
INSERT INTO `chartmaster` (`accountcode`, `language`, `accountname`, `group_`, `groupcode`) VALUES ('2001','de_DE.utf8','Eigenkapital','EIGENKAPITAL','');
INSERT INTO `chartmaster` (`accountcode`, `language`, `accountname`, `group_`, `groupcode`) VALUES ('2100','de_DE.utf8','Privatentnahmen','EIGENKAPITAL','');
INSERT INTO `chartmaster` (`accountcode`, `language`, `accountname`, `group_`, `groupcode`) VALUES ('2150','de_DE.utf8','Privatsteuern','EIGENKAPITAL','');
INSERT INTO `chartmaster` (`accountcode`, `language`, `accountname`, `group_`, `groupcode`) VALUES ('2180','de_DE.utf8','Privateinlagen','EIGENKAPITAL','');
INSERT INTO `chartmaster` (`accountcode`, `language`, `accountname`, `group_`, `groupcode`) VALUES ('2200','de_DE.utf8','Sonderausgaben beschr.abzugsf.','EIGENKAPITAL','');
INSERT INTO `chartmaster` (`accountcode`, `language`, `accountname`, `group_`, `groupcode`) VALUES ('2900','de_DE.utf8','Gezeichnetes Kapital','EIGENKAPITAL','');
INSERT INTO `chartmaster` (`accountcode`, `language`, `accountname`, `group_`, `groupcode`) VALUES ('2910','de_DE.utf8','Ausstehende Einlagen','EIGENKAPITAL','');
INSERT INTO `chartmaster` (`accountcode`, `language`, `accountname`, `group_`, `groupcode`) VALUES ('2970','de_DE.utf8','Gewinnvortrag vor Verwendung','EIGENKAPITAL','');
INSERT INTO `chartmaster` (`accountcode`, `language`, `accountname`, `group_`, `groupcode`) VALUES ('2978','de_DE.utf8','Verlustvortrag vor Verwendung','EIGENKAPITAL','');
INSERT INTO `chartmaster` (`accountcode`, `language`, `accountname`, `group_`, `groupcode`) VALUES ('3030','de_DE.utf8','Gewerbesteuerr','R','');
INSERT INTO `chartmaster` (`accountcode`, `language`, `accountname`, `group_`, `groupcode`) VALUES ('3070','de_DE.utf8','Sonstige R','R','');
INSERT INTO `chartmaster` (`accountcode`, `language`, `accountname`, `group_`, `groupcode`) VALUES ('3095','de_DE.utf8','R','R','');
INSERT INTO `chartmaster` (`accountcode`, `language`, `accountname`, `group_`, `groupcode`) VALUES ('3160','de_DE.utf8','Bankdarlehen','FREMDKAPITAL LANGFRISTIG','');
INSERT INTO `chartmaster` (`accountcode`, `language`, `accountname`, `group_`, `groupcode`) VALUES ('3280','de_DE.utf8','Erhaltene Anzahlungen','FREMDKAPITAL KURZFRISTIG','');
INSERT INTO `chartmaster` (`accountcode`, `language`, `accountname`, `group_`, `groupcode`) VALUES ('3310','de_DE.utf8','Kreditoren ohne Kontokorrent','FREMDKAPITAL KURZFRISTIG','');
INSERT INTO `chartmaster` (`accountcode`, `language`, `accountname`, `group_`, `groupcode`) VALUES ('3500','de_DE.utf8','Sonstige Verbindlichkeiten','FREMDKAPITAL KURZFRISTIG','');
INSERT INTO `chartmaster` (`accountcode`, `language`, `accountname`, `group_`, `groupcode`) VALUES ('3560','de_DE.utf8','Darlehen','FREMDKAPITAL KURZFRISTIG','');
INSERT INTO `chartmaster` (`accountcode`, `language`, `accountname`, `group_`, `groupcode`) VALUES ('3700','de_DE.utf8','Verbindl. Betr.steuern u.Abgaben','FREMDKAPITAL KURZFRISTIG','');
INSERT INTO `chartmaster` (`accountcode`, `language`, `accountname`, `group_`, `groupcode`) VALUES ('3720','de_DE.utf8','Verbindlichkeiten L','FREMDKAPITAL KURZFRISTIG','');
INSERT INTO `chartmaster` (`accountcode`, `language`, `accountname`, `group_`, `groupcode`) VALUES ('3730','de_DE.utf8','Verbindlichkeiten Lohnsteuer','FREMDKAPITAL KURZFRISTIG','');
INSERT INTO `chartmaster` (`accountcode`, `language`, `accountname`, `group_`, `groupcode`) VALUES ('3740','de_DE.utf8','Verbindlichkeiten Sozialversicherung','FREMDKAPITAL KURZFRISTIG','');
INSERT INTO `chartmaster` (`accountcode`, `language`, `accountname`, `group_`, `groupcode`) VALUES ('3800','de_DE.utf8','Umsatzsteuer','FREMDKAPITAL KURZFRISTIG','');
INSERT INTO `chartmaster` (`accountcode`, `language`, `accountname`, `group_`, `groupcode`) VALUES ('3801','de_DE.utf8','Umsatzsteuer 7%','FREMDKAPITAL KURZFRISTIG','');
INSERT INTO `chartmaster` (`accountcode`, `language`, `accountname`, `group_`, `groupcode`) VALUES ('3802','de_DE.utf8','Umsatzsteuer ig. Erwerb','FREMDKAPITAL KURZFRISTIG','');
INSERT INTO `chartmaster` (`accountcode`, `language`, `accountname`, `group_`, `groupcode`) VALUES ('3803','de_DE.utf8','Umsatzsteuer ig. Erwerb 16%','FREMDKAPITAL KURZFRISTIG','');
INSERT INTO `chartmaster` (`accountcode`, `language`, `accountname`, `group_`, `groupcode`) VALUES ('3805','de_DE.utf8','Umsatzsteuer 16%','FREMDKAPITAL KURZFRISTIG','');
INSERT INTO `chartmaster` (`accountcode`, `language`, `accountname`, `group_`, `groupcode`) VALUES ('3806','de_DE.utf8','Umsatzsteuer 15%','FREMDKAPITAL KURZFRISTIG','');
INSERT INTO `chartmaster` (`accountcode`, `language`, `accountname`, `group_`, `groupcode`) VALUES ('3815','de_DE.utf8','Umsatzsteuer nicht f','FREMDKAPITAL KURZFRISTIG','');
INSERT INTO `chartmaster` (`accountcode`, `language`, `accountname`, `group_`, `groupcode`) VALUES ('3816','de_DE.utf8','Umsatzsteuer nicht f','FREMDKAPITAL KURZFRISTIG','');
INSERT INTO `chartmaster` (`accountcode`, `language`, `accountname`, `group_`, `groupcode`) VALUES ('3820','de_DE.utf8','Umsatzsteuer-Vorauszahlungen','FREMDKAPITAL KURZFRISTIG','');
INSERT INTO `chartmaster` (`accountcode`, `language`, `accountname`, `group_`, `groupcode`) VALUES ('3841','de_DE.utf8','Umsatzsteuer Vorjahr','FREMDKAPITAL KURZFRISTIG','');
INSERT INTO `chartmaster` (`accountcode`, `language`, `accountname`, `group_`, `groupcode`) VALUES ('3900','de_DE.utf8','Aktive Rechnungsabgrenzung','FREMDKAPITAL KURZFRISTIG','');
INSERT INTO `chartmaster` (`accountcode`, `language`, `accountname`, `group_`, `groupcode`) VALUES ('5200','de_DE.utf8','Wareneingang ohne Vorsteuer','WARENEINGANG','');
INSERT INTO `chartmaster` (`accountcode`, `language`, `accountname`, `group_`, `groupcode`) VALUES ('5300','de_DE.utf8','Wareneingang 7%','WARENEINGANG','');
INSERT INTO `chartmaster` (`accountcode`, `language`, `accountname`, `group_`, `groupcode`) VALUES ('5400','de_DE.utf8','Wareneingang 15%','WARENEINGANG','');
INSERT INTO `chartmaster` (`accountcode`, `language`, `accountname`, `group_`, `groupcode`) VALUES ('5420','de_DE.utf8','ig.Erwerb 7% VoSt. und 7% USt.','WARENEINGANG','');
INSERT INTO `chartmaster` (`accountcode`, `language`, `accountname`, `group_`, `groupcode`) VALUES ('5425','de_DE.utf8','ig.Erwerb 15% VoSt. und 15% USt.','WARENEINGANG','');
INSERT INTO `chartmaster` (`accountcode`, `language`, `accountname`, `group_`, `groupcode`) VALUES ('5731','de_DE.utf8','Erhaltene Skonti 7% Vorsteuer','WARENEINGANG','');
INSERT INTO `chartmaster` (`accountcode`, `language`, `accountname`, `group_`, `groupcode`) VALUES ('5736','de_DE.utf8','Erhaltene Skonti 15% Vorsteuer','WARENEINGANG','');
INSERT INTO `chartmaster` (`accountcode`, `language`, `accountname`, `group_`, `groupcode`) VALUES ('5800','de_DE.utf8','Anschaffungsnebenkosten','WARENEINGANG','');
INSERT INTO `chartmaster` (`accountcode`, `language`, `accountname`, `group_`, `groupcode`) VALUES ('5900','de_DE.utf8','Fremdarbeiten','WARENEINGANG','');
INSERT INTO `chartmaster` (`accountcode`, `language`, `accountname`, `group_`, `groupcode`) VALUES ('6010','de_DE.utf8','L','PERSONALKOSTEN','');
INSERT INTO `chartmaster` (`accountcode`, `language`, `accountname`, `group_`, `groupcode`) VALUES ('6020','de_DE.utf8','Geh','PERSONALKOSTEN','');
INSERT INTO `chartmaster` (`accountcode`, `language`, `accountname`, `group_`, `groupcode`) VALUES ('6030','de_DE.utf8','Aushilfsl','PERSONALKOSTEN','');
INSERT INTO `chartmaster` (`accountcode`, `language`, `accountname`, `group_`, `groupcode`) VALUES ('6040','de_DE.utf8','Lohnsteuer Aushilfen','PERSONALKOSTEN','');
INSERT INTO `chartmaster` (`accountcode`, `language`, `accountname`, `group_`, `groupcode`) VALUES ('6080','de_DE.utf8','Verm','PERSONALKOSTEN','');
INSERT INTO `chartmaster` (`accountcode`, `language`, `accountname`, `group_`, `groupcode`) VALUES ('6090','de_DE.utf8','Fahrtkostenerst.Whg./Arbeitsst','PERSONALKOSTEN','');
INSERT INTO `chartmaster` (`accountcode`, `language`, `accountname`, `group_`, `groupcode`) VALUES ('6110','de_DE.utf8','Sozialversicherung','PERSONALKOSTEN','');
INSERT INTO `chartmaster` (`accountcode`, `language`, `accountname`, `group_`, `groupcode`) VALUES ('6120','de_DE.utf8','Berufsgenossenschaft','PERSONALKOSTEN','');
INSERT INTO `chartmaster` (`accountcode`, `language`, `accountname`, `group_`, `groupcode`) VALUES ('6130','de_DE.utf8','Freiw. Soz. Aufw. LSt- u. Soz.Vers.frei','PERSONALKOSTEN','');
INSERT INTO `chartmaster` (`accountcode`, `language`, `accountname`, `group_`, `groupcode`) VALUES ('6310','de_DE.utf8','Miete','RAUMKOSTEN','');
INSERT INTO `chartmaster` (`accountcode`, `language`, `accountname`, `group_`, `groupcode`) VALUES ('6315','de_DE.utf8','Pacht','RAUMKOSTEN','');
INSERT INTO `chartmaster` (`accountcode`, `language`, `accountname`, `group_`, `groupcode`) VALUES ('6320','de_DE.utf8','Heizung','RAUMKOSTEN','');
INSERT INTO `chartmaster` (`accountcode`, `language`, `accountname`, `group_`, `groupcode`) VALUES ('6325','de_DE.utf8','Gas Strom Wasser','RAUMKOSTEN','');
INSERT INTO `chartmaster` (`accountcode`, `language`, `accountname`, `group_`, `groupcode`) VALUES ('6330','de_DE.utf8','Reinigung','RAUMKOSTEN','');
INSERT INTO `chartmaster` (`accountcode`, `language`, `accountname`, `group_`, `groupcode`) VALUES ('6335','de_DE.utf8','Instandhaltung betriebliche R','RAUMKOSTEN','');
INSERT INTO `chartmaster` (`accountcode`, `language`, `accountname`, `group_`, `groupcode`) VALUES ('6345','de_DE.utf8','Sonstige Raumkosten','RAUMKOSTEN','');
INSERT INTO `chartmaster` (`accountcode`, `language`, `accountname`, `group_`, `groupcode`) VALUES ('6402','de_DE.utf8','Abschreibungen','SONSTIGE KOSTEN','');
INSERT INTO `chartmaster` (`accountcode`, `language`, `accountname`, `group_`, `groupcode`) VALUES ('6403','de_DE.utf8','Kaufleasing','SONSTIGE KOSTEN','');
INSERT INTO `chartmaster` (`accountcode`, `language`, `accountname`, `group_`, `groupcode`) VALUES ('6404','de_DE.utf8','Sofortabschreibung GWG','SONSTIGE KOSTEN','');
INSERT INTO `chartmaster` (`accountcode`, `language`, `accountname`, `group_`, `groupcode`) VALUES ('6405','de_DE.utf8','Sonstige Kosten','SONSTIGE KOSTEN','');
INSERT INTO `chartmaster` (`accountcode`, `language`, `accountname`, `group_`, `groupcode`) VALUES ('6410','de_DE.utf8','Versicherung','SONSTIGE KOSTEN','');
INSERT INTO `chartmaster` (`accountcode`, `language`, `accountname`, `group_`, `groupcode`) VALUES ('6420','de_DE.utf8','Beitr','SONSTIGE KOSTEN','');
INSERT INTO `chartmaster` (`accountcode`, `language`, `accountname`, `group_`, `groupcode`) VALUES ('6430','de_DE.utf8','Sonstige Abgaben','SONSTIGE KOSTEN','');
INSERT INTO `chartmaster` (`accountcode`, `language`, `accountname`, `group_`, `groupcode`) VALUES ('6470','de_DE.utf8','Rep. und Instandhaltung BGA','SONSTIGE KOSTEN','');
INSERT INTO `chartmaster` (`accountcode`, `language`, `accountname`, `group_`, `groupcode`) VALUES ('6520','de_DE.utf8','Kfz-Versicherung','SONSTIGE KOSTEN','');
INSERT INTO `chartmaster` (`accountcode`, `language`, `accountname`, `group_`, `groupcode`) VALUES ('6530','de_DE.utf8','Lfd. Kfz-Kosten','SONSTIGE KOSTEN','');
INSERT INTO `chartmaster` (`accountcode`, `language`, `accountname`, `group_`, `groupcode`) VALUES ('6540','de_DE.utf8','Kfz-Reparaturen','SONSTIGE KOSTEN','');
INSERT INTO `chartmaster` (`accountcode`, `language`, `accountname`, `group_`, `groupcode`) VALUES ('6560','de_DE.utf8','Fremdfahrzeuge','SONSTIGE KOSTEN','');
INSERT INTO `chartmaster` (`accountcode`, `language`, `accountname`, `group_`, `groupcode`) VALUES ('6570','de_DE.utf8','Sonstige Kfz-Kosten','SONSTIGE KOSTEN','');
INSERT INTO `chartmaster` (`accountcode`, `language`, `accountname`, `group_`, `groupcode`) VALUES ('6600','de_DE.utf8','Werbung','SONSTIGE KOSTEN','');
INSERT INTO `chartmaster` (`accountcode`, `language`, `accountname`, `group_`, `groupcode`) VALUES ('6610','de_DE.utf8','Kundengeschenke bis DM 75.','SONSTIGE KOSTEN','');
INSERT INTO `chartmaster` (`accountcode`, `language`, `accountname`, `group_`, `groupcode`) VALUES ('6620','de_DE.utf8','Kundengeschenke','SONSTIGE KOSTEN','');
INSERT INTO `chartmaster` (`accountcode`, `language`, `accountname`, `group_`, `groupcode`) VALUES ('6630','de_DE.utf8','Repr','SONSTIGE KOSTEN','');
INSERT INTO `chartmaster` (`accountcode`, `language`, `accountname`, `group_`, `groupcode`) VALUES ('6640','de_DE.utf8','Bewirtungskosten','SONSTIGE KOSTEN','');
INSERT INTO `chartmaster` (`accountcode`, `language`, `accountname`, `group_`, `groupcode`) VALUES ('6644','de_DE.utf8','Nicht abzugsf.Bewirtungskosten','SONSTIGE KOSTEN','');
INSERT INTO `chartmaster` (`accountcode`, `language`, `accountname`, `group_`, `groupcode`) VALUES ('6650','de_DE.utf8','Reisekosten Arbeitnehmer','SONSTIGE KOSTEN','');
INSERT INTO `chartmaster` (`accountcode`, `language`, `accountname`, `group_`, `groupcode`) VALUES ('6660','de_DE.utf8','Reisekosten Arbeitnehmer 12.3%','SONSTIGE KOSTEN','');
INSERT INTO `chartmaster` (`accountcode`, `language`, `accountname`, `group_`, `groupcode`) VALUES ('6665','de_DE.utf8','Reisekosten Arbeitnehmer 9.8%','SONSTIGE KOSTEN','');
INSERT INTO `chartmaster` (`accountcode`, `language`, `accountname`, `group_`, `groupcode`) VALUES ('6670','de_DE.utf8','Reisekosten Unternehmer','SONSTIGE KOSTEN','');
INSERT INTO `chartmaster` (`accountcode`, `language`, `accountname`, `group_`, `groupcode`) VALUES ('6680','de_DE.utf8','Reisekosten Unternehmer 12.3%','SONSTIGE KOSTEN','');
INSERT INTO `chartmaster` (`accountcode`, `language`, `accountname`, `group_`, `groupcode`) VALUES ('6685','de_DE.utf8','Reisekosten Unternehmer 9.8%','SONSTIGE KOSTEN','');
INSERT INTO `chartmaster` (`accountcode`, `language`, `accountname`, `group_`, `groupcode`) VALUES ('6688','de_DE.utf8','Reisekosten Unternehmer 5.7%','SONSTIGE KOSTEN','');
INSERT INTO `chartmaster` (`accountcode`, `language`, `accountname`, `group_`, `groupcode`) VALUES ('6690','de_DE.utf8','Km-Geld-Erstattung 8.2%','SONSTIGE KOSTEN','');
INSERT INTO `chartmaster` (`accountcode`, `language`, `accountname`, `group_`, `groupcode`) VALUES ('6710','de_DE.utf8','Verpackungsmaterial','SONSTIGE KOSTEN','');
INSERT INTO `chartmaster` (`accountcode`, `language`, `accountname`, `group_`, `groupcode`) VALUES ('6740','de_DE.utf8','Ausgangsfrachten','SONSTIGE KOSTEN','');
INSERT INTO `chartmaster` (`accountcode`, `language`, `accountname`, `group_`, `groupcode`) VALUES ('6780','de_DE.utf8','Fremdarbeiten','SONSTIGE KOSTEN','');
INSERT INTO `chartmaster` (`accountcode`, `language`, `accountname`, `group_`, `groupcode`) VALUES ('6800','de_DE.utf8','Porto','SONSTIGE KOSTEN','');
INSERT INTO `chartmaster` (`accountcode`, `language`, `accountname`, `group_`, `groupcode`) VALUES ('6805','de_DE.utf8','Telefon','SONSTIGE KOSTEN','');
INSERT INTO `chartmaster` (`accountcode`, `language`, `accountname`, `group_`, `groupcode`) VALUES ('6815','de_DE.utf8','B','SONSTIGE KOSTEN','');
INSERT INTO `chartmaster` (`accountcode`, `language`, `accountname`, `group_`, `groupcode`) VALUES ('6820','de_DE.utf8','Zeitschriften & B','SONSTIGE KOSTEN','');
INSERT INTO `chartmaster` (`accountcode`, `language`, `accountname`, `group_`, `groupcode`) VALUES ('6825','de_DE.utf8','Rechts- und Beratungskosten','SONSTIGE KOSTEN','');
INSERT INTO `chartmaster` (`accountcode`, `language`, `accountname`, `group_`, `groupcode`) VALUES ('6835','de_DE.utf8','Mieten f','SONSTIGE KOSTEN','');
INSERT INTO `chartmaster` (`accountcode`, `language`, `accountname`, `group_`, `groupcode`) VALUES ('6840','de_DE.utf8','Mietleasing','SONSTIGE KOSTEN','');
INSERT INTO `chartmaster` (`accountcode`, `language`, `accountname`, `group_`, `groupcode`) VALUES ('6845','de_DE.utf8','Werkzeuge und Kleinger','SONSTIGE KOSTEN','');
INSERT INTO `chartmaster` (`accountcode`, `language`, `accountname`, `group_`, `groupcode`) VALUES ('6850','de_DE.utf8','Betriebsbedarf','SONSTIGE KOSTEN','');
INSERT INTO `chartmaster` (`accountcode`, `language`, `accountname`, `group_`, `groupcode`) VALUES ('6852','de_DE.utf8','Gast','SONSTIGE KOSTEN','');
INSERT INTO `chartmaster` (`accountcode`, `language`, `accountname`, `group_`, `groupcode`) VALUES ('6855','de_DE.utf8','Nebenkosten des Geldverkehrs','SONSTIGE KOSTEN','');
INSERT INTO `chartmaster` (`accountcode`, `language`, `accountname`, `group_`, `groupcode`) VALUES ('6880','de_DE.utf8','Aufwendungen aus Kursdifferenzen','SONSTIGE KOSTEN','');
INSERT INTO `chartmaster` (`accountcode`, `language`, `accountname`, `group_`, `groupcode`) VALUES ('6885','de_DE.utf8','Erl','SONSTIGE KOSTEN','');
INSERT INTO `chartmaster` (`accountcode`, `language`, `accountname`, `group_`, `groupcode`) VALUES ('7610','de_DE.utf8','Gewerbesteuer','SONSTIGE KOSTEN','');
INSERT INTO `chartmaster` (`accountcode`, `language`, `accountname`, `group_`, `groupcode`) VALUES ('7685','de_DE.utf8','Kfz-Steuer','SONSTIGE KOSTEN','');
INSERT INTO `chartmaster` (`accountcode`, `language`, `accountname`, `group_`, `groupcode`) VALUES ('8120','de_DE.utf8','Steuerfreie Ums','ERTR','');
INSERT INTO `chartmaster` (`accountcode`, `language`, `accountname`, `group_`, `groupcode`) VALUES ('8125','de_DE.utf8','Steuerfreie ig. Lieferungen 1b UStG.','ERTR','');
INSERT INTO `chartmaster` (`accountcode`, `language`, `accountname`, `group_`, `groupcode`) VALUES ('8200','de_DE.utf8','Erl','ERTR','');
INSERT INTO `chartmaster` (`accountcode`, `language`, `accountname`, `group_`, `groupcode`) VALUES ('8300','de_DE.utf8','Erl','ERTR','');
INSERT INTO `chartmaster` (`accountcode`, `language`, `accountname`, `group_`, `groupcode`) VALUES ('8400','de_DE.utf8','Erl','ERTR','');
INSERT INTO `chartmaster` (`accountcode`, `language`, `accountname`, `group_`, `groupcode`) VALUES ('8500','de_DE.utf8','Provisionserl','ERTR','');
INSERT INTO `chartmaster` (`accountcode`, `language`, `accountname`, `group_`, `groupcode`) VALUES ('8630','de_DE.utf8','Entnahme sonstg. Leistungen 7% USt.','ERTR','');
INSERT INTO `chartmaster` (`accountcode`, `language`, `accountname`, `group_`, `groupcode`) VALUES ('8640','de_DE.utf8','Entnahme sonstg. Leistungen 15% USt.','ERTR','');
INSERT INTO `chartmaster` (`accountcode`, `language`, `accountname`, `group_`, `groupcode`) VALUES ('8731','de_DE.utf8','Gew. Skonti 7% USt.','ERTR','');
INSERT INTO `chartmaster` (`accountcode`, `language`, `accountname`, `group_`, `groupcode`) VALUES ('8736','de_DE.utf8','Gew. Skonti 15% USt.','ERTR','');
INSERT INTO `chartmaster` (`accountcode`, `language`, `accountname`, `group_`, `groupcode`) VALUES ('8840','de_DE.utf8','Ertr','ERTR','');
INSERT INTO `chartmaster` (`accountcode`, `language`, `accountname`, `group_`, `groupcode`) VALUES ('8845','de_DE.utf8','Erl','ERTR','');
INSERT INTO `chartmaster` (`accountcode`, `language`, `accountname`, `group_`, `groupcode`) VALUES ('8900','de_DE.utf8','Ertr','ERTR','');
INSERT INTO `chartmaster` (`accountcode`, `language`, `accountname`, `group_`, `groupcode`) VALUES ('9310','de_DE.utf8','Zinsen kurzfr. Verbindlichkeiten','NEUTRALE AUFWENDUNGEN','');
INSERT INTO `chartmaster` (`accountcode`, `language`, `accountname`, `group_`, `groupcode`) VALUES ('9320','de_DE.utf8','Zinsen langfr. Verbindlichkeiten','NEUTRALE AUFWENDUNGEN','');
INSERT INTO `chartmaster` (`accountcode`, `language`, `accountname`, `group_`, `groupcode`) VALUES ('9500','de_DE.utf8','Ausserordentl.Aufwendungen','NEUTRALE AUFWENDUNGEN','');
