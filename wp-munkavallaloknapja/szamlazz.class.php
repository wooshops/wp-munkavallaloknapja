<?php

class szamlazz {

	public static function postXML($id) {

		$cookie_file = "/tmp/szamlazz_cookie.txt";
		$pdf_file = "/tmp/szamla_{$id}.pdf";
		$xmlfile = dirname(__FILE__)."/szamlazz_log/szamla_{$id}.xml";
		$agent_url = "https://www.szamlazz.hu/szamla/";
		$szamlaletoltes = false;

		if (!file_exists($cookie_file)) {
			file_put_contents($cookie_file, '');
		}

		$ch = curl_init($agent_url);

		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_HEADER, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		//curl_setopt($ch, CURLOPT_POSTFIELDS, array('action-xmlagentxmlfile'=>new CURLFile($xmlfile, 'application/xml', $xmlfile)));
		curl_setopt($ch, CURLOPT_POSTFIELDS, array('action-xmlagentxmlfile'=>'@'.$xmlfile));
		//curl_setopt($ch, CURLOPT_SAFE_UPLOAD, true);
		curl_setopt($ch, CURLOPT_TIMEOUT, 30);
		curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file);

		if (file_exists($cookie_file) && filesize($cookie_file) > 0) {
			curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file);
		}

		$agent_response = curl_exec($ch);
		$http_error = curl_error($ch);
		$agent_header = '';
		$agent_body = '';
		$agent_http_code = '';
		$agent_http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
		$agent_header = substr($agent_response, 0, $header_size);
		$agent_body = substr($agent_response, $header_size);

		curl_close($ch);

		$header_array = explode("\n", $agent_header);

		$volt_hiba = false;

		$agent_error = '';
		$agent_error_code = '';

		$szamlazz_log = dirname(__FILE__)."/szamlazz_log/szamla_{$id}.log";
		if(!is_dir(dirname($szamlazz_log))) mkdir(dirname($szamlazz_log));
		file_put_contents($szamlazz_log, "BEGIN TX $id\n", FILE_APPEND);

		foreach ($header_array as $val) {
			if (substr($val, 0, strlen('szlahu')) === 'szlahu') {
				file_put_contents($szamlazz_log, urldecode($val)."\n", FILE_APPEND);
				if (substr($val, 0, strlen('szlahu_error:')) === 'szlahu_error:') {
					$volt_hiba = true;
					$agent_error = substr($val, strlen('szlahu_error:'));
				}
				if (substr($val, 0, strlen('szlahu_error_code:')) === 'szlahu_error_code:') {
					$volt_hiba = true;
					$agent_error_code = substr($val, strlen('szlahu_error_code:'));
				}
			}
		}

		if ($http_error != "") {
			file_put_contents($szamlazz_log, "Http hiba történt: $http_error\n", FILE_APPEND);
		} else {
			if ($volt_hiba) {
				file_put_contents($szamlazz_log, "Számlakészítés sikertelen\nAgent hibakód: $agent_error_code\nAgent hibaüzenet: ".urldecode($agent_error)."\nAgent válasz: ".urldecode($agent_body)."\n", FILE_APPEND);
			} else {
				file_put_contents($szamlazz_log, "Számlakészítés sikeres\n", FILE_APPEND);
				if ($szamlaletoltes) {
					file_put_contents($pdf_file, $agent_body);
				} else {
					file_put_contents($szamlazz_log, urldecode($agent_body)."\n", FILE_APPEND);
				}
			}
		}
		file_put_contents($szamlazz_log, "END TX $id\n", FILE_APPEND);
	}

	public static function generateXML($trs) {

		$user = get_option("exp_szamlazz_user"); $pw = get_option("exp_szamlazz_pass"); $et = get_option("exp_szamlazz_et");
		$id = $trs["Id"];
		$xmlfile = dirname(__FILE__)."/szamlazz_log/szamla_{$id}.xml";
		if(!is_dir(dirname($xmlfile))) mkdir(dirname($xmlfile));
		$nettoegys = round($trs["TotalAmount"] / $trs["Count"] * 0.7874);
		$netto = round($trs["TotalAmount"] * 0.7874);
		$afa = $trs["TotalAmount"] - $netto;
		$date = substr($trs["Date"], 0, 10);
		$xml = <<<EOF
<?xml version="1.0" encoding="UTF-8"?>
<xmlszamla xmlns="http://www.szamlazz.hu/xmlszamla" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.szamlazz.hu/xmlszamla xmlszamla.xsd ">
	<beallitasok>
		<felhasznalo>{$user}</felhasznalo>
		<jelszo>{$pw}</jelszo>
		<eszamla>true</eszamla>
		<kulcstartojelszo></kulcstartojelszo>
		<szamlaLetoltes>false</szamlaLetoltes>
		<szamlaLetoltesPld>1</szamlaLetoltesPld>
		<valaszVerzio>1</valaszVerzio>
		<aggregator></aggregator>
	</beallitasok>
	<fejlec>
		<keltDatum>{$date}</keltDatum>
		<teljesitesDatum>{$date}</teljesitesDatum>
		<fizetesiHataridoDatum>{$date}</fizetesiHataridoDatum>
		<fizmod>Átutalás</fizmod>
		<penznem>Ft</penznem>
		<szamlaNyelve>hu</szamlaNyelve>
		<megjegyzes></megjegyzes>
		<arfolyamBank>MNB</arfolyamBank>
		<arfolyam>0.0</arfolyam>
		<rendelesSzam>{$id}</rendelesSzam>
		<elolegszamla>false</elolegszamla>
		<vegszamla>false</vegszamla>
		<dijbekero>false</dijbekero>
		<szamlaszamElotag>{$et}</szamlaszamElotag>
		<fizetve>false</fizetve>
	</fejlec>
	<elado>
	</elado>
	<vevo>
		<nev>{$trs["BillingName"]}</nev>
		<irsz>{$trs["BillingZipCode"]}</irsz>
		<telepules>{$trs["BillingCity"]}</telepules>
		<cim>{$trs["BillingAddress"]}</cim>
		<email>{$trs["EmailAddress"]}</email>
		<sendEmail>true</sendEmail>
		<adoszam></adoszam>
		<postazasiNev>{$trs["Name"]}</postazasiNev>
		<postazasiIrsz>{$trs["ZipCode"]}</postazasiIrsz>
		<postazasiTelepules>{$trs["City"]}</postazasiTelepules>
		<postazasiCim>{$trs["Address"]}</postazasiCim>
		<alairoNeve></alairoNeve>
		<telefonszam>{$trs["Telephone"]}</telefonszam>
		<megjegyzes></megjegyzes>
	</vevo>
	<tetelek>
		<tetel>
			<megnevezes>{$trs["ProductName"]}</megnevezes>
			<mennyiseg>{$trs["Count"]}</mennyiseg>
			<mennyisegiEgyseg>db</mennyisegiEgyseg>
			<nettoEgysegar>{$nettoegys}</nettoEgysegar>
			<afakulcs>27</afakulcs>
			<nettoErtek>{$netto}</nettoErtek>
			<afaErtek>{$afa}</afaErtek>
			<bruttoErtek>{$trs["TotalAmount"]}</bruttoErtek>
			<megjegyzes></megjegyzes>
		</tetel>
	</tetelek>
</xmlszamla>
EOF;
		file_put_contents($xmlfile, $xml);
	}
}
