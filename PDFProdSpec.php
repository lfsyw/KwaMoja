<?php
/* $Id: PDFProdSpec.php 1 2014-09-15 06:31:08Z agaluski $ */

include ('includes/session.php');
include ('includes/SQL_CommonFunctions.php');

if (isset($_GET['KeyValue'])) {
	$SelectedProdSpec = $_GET['KeyValue'];
} elseif (isset($_POST['KeyValue'])) {
	$SelectedProdSpec = $_POST['KeyValue'];
}

//Get Out if we have no product specification
if (!isset($SelectedProdSpec) || $SelectedProdSpec == "") {
	$Title = _('Select Product Specification To Print');
	include ('includes/header.php');
	echo '<p class="page_title_text">
			<img src="', $RootPath, '/css/', $_SESSION['Theme'], '/images/printer.png" title="', _('Print'), '" alt="" />', ' ', $Title, '
		</p>';

	echo '<form action="', htmlspecialchars(basename(__FILE__), ENT_QUOTES, 'UTF-8'), '" method="post">';
	echo '<input type="hidden" name="FormID" value="', $_SESSION['FormID'], '" />';

	echo '<fieldset>
			<field>
				<label for="KeyValue">', _('Enter Specification Name'), ':</label>
				<input type="text" name="KeyValue" size="25" maxlength="25" />
			</field>
		</fieldset>';

	echo '<div class="centre">
			<input type="submit" name="pickspec" value="', _('Submit'), '" />
		</div>
	</form>';

	echo '<form action="', htmlspecialchars(basename(__FILE__), ENT_QUOTES, 'UTF-8'), '" method="post">';
	echo '<input type="hidden" name="FormID" value="', $_SESSION['FormID'], '" />';

	$SQLSpecSelect = "SELECT DISTINCT(keyval),
							description
						FROM prodspecs LEFT OUTER JOIN stockmaster
						ON stockmaster.stockid=prodspecs.keyval";

	$ResultSelection = DB_query($SQLSpecSelect);
	echo '<fieldset>
			<field>
				<label for="KeyValue">', _('Or Select Existing Specification'), ':</label>
				<select name="KeyValue">';
	while ($MyRowSelection = DB_fetch_array($ResultSelection)) {
		echo '<option value="', $MyRowSelection['keyval'], '">', $MyRowSelection['keyval'], ' - ', htmlspecialchars($MyRowSelection['description'], ENT_QUOTES, 'UTF-8', false), '</option>';
	}
	echo '</select>
		</field>
	</fieldset>';

	echo '<div class="centre">
			<input type="submit" name="pickspec" value="', _('Submit'), '" />
		</div>
	</form>';
	include ('includes/footer.php');
	exit();
}

/*retrieve the order details from the database to print */
$ErrMsg = _('There was a problem retrieving the Product Specification') . ' ' . $SelectedProdSpec . ' ' . _('from the database');

$SQL = "SELECT keyval,
				description,
				longdescription,
				prodspecs.testid,
				name,
				method,
				qatests.units,
				type,
				numericvalue,
				prodspecs.targetvalue,
				prodspecs.rangemin,
				prodspecs.rangemax,
				groupby
			FROM prodspecs
			INNER JOIN qatests
				ON qatests.testid=prodspecs.testid
			LEFT OUTER JOIN stockmaster
				ON stockmaster.stockid=prodspecs.keyval
			WHERE prodspecs.keyval='" . $SelectedProdSpec . "'
				AND prodspecs.showonspec='1'
			ORDER by groupby, prodspecs.testid";

$Result = DB_query($SQL, $ErrMsg);

//If there are no rows, there's a problem.
if (DB_num_rows($Result) == 0) {
	$Title = _('Print Product Specification Error');
	include ('includes/header.php');
	prnMsg(_('Unable to Locate Specification') . ' : ' . $_SelectedProdSpec . ' ', 'error');
	echo '<div class="centre">
			<a href="', $RootPath, '/PDFProdSpec.php">', _('Product Specifications'), '</a>
		</div>';
	include ('includes/footer.php');
	exit;
}
$PaperSize = 'Letter';

include ('includes/PDFStarter.php');
$PDF->addInfo('Title', _('Product Specification'));
$PDF->addInfo('Subject', _('Product Specification') . ' ' . $SelectedProdSpec);
$FontSize = 12;
$PageNumber = 1;
$HeaderPrinted = 0;
$LineHeight = $FontSize * 1.25;
$RectHeight = 12;
$SectionHeading = 0;
$CurSection = 'NULL';
$SectionTitle = '';
$SectionTrailer = '';

$SectionsArray = array(array('PhysicalProperty', 3, _('Technical Data Sheet Properties'), _('* Data herein is typical and not to be construed as specifications.'), array(260, 110, 135), array(_('Physical Property'), _('Value'), _('Test Method')), array('left', 'center', 'center')), array('', 3, _('Header'), _('* Trailer'), array(260, 110, 135), array(_('Physical Property'), _('Value'), _('Test Method')), array('left', 'center', 'center')), array('Processing', 2, _('Injection Molding Processing Guidelines'), _('* Desicant type dryer required.'), array(240, 265), array(_('Setting'), _('Value')), array('left', 'center')), array('RegulatoryCompliance', 2, _('Regulatory Compliance'), '', array(240, 265), array(_('Regulatory Compliance'), _('Value')), array('left', 'center')));

while ($MyRow = DB_fetch_array($Result)) {
	if ($MyRow['description'] == '') {
		$MyRow['description'] = $MyRow['keyval'];
	}
	$Spec = $MyRow['description'];
	$SpecDesc = $MyRow['longdescription'];
	foreach ($SectionsArray as $Row) {
		if ($MyRow['groupby'] == $Row[0]) {
			$SectionColSizes = $Row[4];
			$SectionColLabs = $Row[5];
			$SectionAlign = $Row[6];
		}
	}
	$TrailerPrinted = 1;
	if ($HeaderPrinted == 0) {
		include ('includes/PDFProdSpecHeader.php');
		$HeaderPrinted = 1;
	}

	if ($CurSection != $MyRow['groupby']) {
		$SectionHeading = 0;
		if ($CurSection != 'NULL' and $PrintTrailer == 1) {
			$PDF->line($XPos + 1, $YPos + $RectHeight, $XPos + 506, $YPos + $RectHeight);
		}
		$PrevTrailer = $SectionTrailer;
		$CurSection = $MyRow['groupby'];
		foreach ($SectionsArray as $Row) {
			if ($MyRow['groupby'] == $Row[0]) {
				$SectionTitle = $Row[2];
				$SectionTrailer = $Row[3];
			}
		}
	}

	if ($SectionHeading == 0) {
		$XPos = 65;
		if ($PrevTrailer > '' and $PrintTrailer == 1) {
			$PrevFontSize = $FontSize;
			$FontSize = 8;
			$LineHeight = $FontSize * 1.25;
			$LeftOvers = $PDF->addTextWrap($XPos + 5, $YPos, 500, $FontSize, $PrevTrailer, 'left');
			$FontSize = $PrevFontSize;
			$LineHeight = $FontSize * 1.25;
			$YPos-= $LineHeight;
			$YPos-= $LineHeight;
		}
		if ($YPos < ($Bottom_Margin + 90)) { // Begins new page
			$PrintTrailer = 0;
			$PageNumber++;
			include ('includes/PDFProdSpecHeader.php');
		}
		$LeftOvers = $PDF->addTextWrap($XPos, $YPos, 500, $FontSize, $SectionTitle, 'center');
		$YPos-= $LineHeight;
		$PDF->setFont('', 'B');
		$PDF->SetFillColor(200, 200, 200);
		$i = 0;
		foreach ($SectionColLabs as $CurColLab) {
			$ColLabel = $CurColLab;
			$ColWidth = $SectionColSizes[$i];
			++$i;
			$LeftOvers = $PDF->addTextWrap($XPos + 1, $YPos, $ColWidth, $FontSize, $ColLabel, 'center', 1, 'fill');
			$XPos+= $ColWidth;
		}
		$SectionHeading = 1;
		$YPos-= $LineHeight;
		$PDF->setFont('', '');
	} //$SectionHeading==0
	$XPos = 65;
	$Value = '';
	if ($MyRow['targetvalue'] > '') {
		$Value = $MyRow['targetvalue'];
	} elseif ($MyRow['rangemin'] > '' or $MyRow['rangemax'] > '') {
		if ($MyRow['rangemin'] > '' and $MyRow['rangemax'] == '') {
			$Value = '> ' . $MyRow['rangemin'];
		} elseif ($MyRow['rangemin'] == '' and $MyRow['rangemax'] > '') {
			$Value = '< ' . $MyRow['rangemax'];
		} else {
			$Value = $MyRow['rangemin'] . ' - ' . $MyRow['rangemax'];
		}
	}
	if (strtoupper($Value) <> 'NB' and strtoupper($Value) <> 'NO BREAK') {
		$Value.= ' ' . $MyRow['units'];
	}
	$i = 0;

	foreach ($SectionColLabs as $CurColLab) {
		$ColLabel = $CurColLab;
		$ColWidth = $SectionColSizes[$i];
		$ColAlign = $SectionAlign[$i];
		switch ($i) {
			case 0;
			$DispValue = $MyRow['name'];
		break;
		case 1;
		$DispValue = $Value;
	break;
	case 2;
	$DispValue = $MyRow['method'];
break;
}
$LeftOvers = $PDF->addTextWrap($XPos + 1, $YPos, $ColWidth, $FontSize, $DispValue, $ColAlign, 1);
$XPos+= $ColWidth;
++$i;
}
$YPos-= $LineHeight;
$XPos = 65;
$PrintTrailer = 1;
if ($YPos < ($Bottom_Margin + 80)) { // Begins new page
	$PDF->line($XPos + 1, $YPos + $RectHeight, $XPos + 506, $YPos + $RectHeight);
	$PrintTrailer = 0;
	$PageNumber++;
	include ('includes/PDFProdSpecHeader.php');
}
//echo 'PrintTrailer'.$PrintTrailer.' '.$PrevTrailer.'<br>' ;

} //while loop
$PDF->line($XPos + 1, $YPos + $RectHeight, $XPos + 506, $YPos + $RectHeight);
if ($SectionTrailer > '') {
	$PrevFontSize = $FontSize;
	$FontSize = 8;
	$LineHeight = $FontSize * 1.25;
	$LeftOvers = $PDF->addTextWrap($XPos + 5, $YPos, 500, $FontSize, $SectionTrailer, 'left');
	$FontSize = $PrevFontSize;
	$LineHeight = $FontSize * 1.25;
	$YPos-= $LineHeight;
	$YPos-= $LineHeight;
}
if ($YPos < ($Bottom_Margin + 85)) { // Begins new page
	$PageNumber++;
	include ('includes/PDFProdSpecHeader.php');
}
$Disclaimer = _('The information provided on this datasheet should only be used as a guideline. Actual lot to lot values will vary.');
$FontSize = 8;
$LineHeight = $FontSize * 1.25;
$YPos-= $LineHeight;
$LeftOvers = $PDF->addTextWrap($XPos + 5, $YPos, 500, $FontSize, $Disclaimer);
$YPos-= $LineHeight;
$YPos-= $LineHeight;
$SQL = "SELECT confvalue
			FROM config
			WHERE confname='QualityProdSpecText'";

$Result = DB_query($SQL, $ErrMsg);
$MyRow = DB_fetch_array($Result);
$Disclaimer = $MyRow[0];
$LeftOvers = $PDF->addTextWrap($XPos + 5, $YPos, 500, $FontSize, $Disclaimer);
while (mb_strlen($LeftOvers) > 1) {
	$YPos-= $LineHeight;
	$LeftOvers = $PDF->addTextWrap($XPos + 5, $YPos, 500, $FontSize, $LeftOvers, 'left');
}

$PDF->OutputI($_SESSION['DatabaseName'] . '_ProductSpecification_' . date('Y-m-d') . '.pdf');
$PDF->__destruct();

?>