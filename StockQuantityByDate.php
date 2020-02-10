<?php
include ('includes/session.php');
$Title = _('Stock On Hand By Date');
include ('includes/header.php');

echo '<p class="page_title_text">
		<img src="', $RootPath, '/css/', $_SESSION['Theme'], '/images/inventory.png" title="', _('Inventory'), '" alt="" /><b>', $Title, '</b>
	</p>';

echo '<form action="', htmlspecialchars(basename(__FILE__), ENT_QUOTES, 'UTF-8'), '" method="post">';
echo '<input type="hidden" name="FormID" value="', $_SESSION['FormID'], '" />';

echo '<fieldset>
		<legend class="search">', _('Report Criteria'), '</legend>';

$SQL = "SELECT categoryid, categorydescription FROM stockcategory";
$ResultStkLocs = DB_query($SQL);

echo '<field>
		<label for="StockCategory">', _('For Stock Category'), ':</label>
		<select required="required" autofocus="autofocus" name="StockCategory">
			<option value="All">', _('All'), '</option>';

while ($MyRow = DB_fetch_array($ResultStkLocs)) {
	if (isset($_POST['StockCategory']) and $_POST['StockCategory'] != 'All') {
		if ($MyRow['categoryid'] == $_POST['StockCategory']) {
			echo '<option selected="selected" value="', $MyRow['categoryid'], '">', $MyRow['categorydescription'], '</option>';
		} else {
			echo '<option value="', $MyRow['categoryid'], '">', $MyRow['categorydescription'], '</option>';
		}
	} else {
		echo '<option value="', $MyRow['categoryid'], '">', $MyRow['categorydescription'], '</option>';
	}
}
echo '</select>
	<fieldhelp>', _('Select the stock category to search through. To search over all stock categories select All'), '</fieldhelp>
</field>';

$SQL = "SELECT locationname,
				locations.loccode
			FROM locations
			INNER JOIN locationusers
				ON locationusers.loccode=locations.loccode
				AND locationusers.userid='" . $_SESSION['UserID'] . "'
				AND locationusers.canview=1";

$ResultStkLocs = DB_query($SQL);

echo '<field>
		<label for="StockLocation">', _('For Stock Location'), ':</label>
		<select required="required" name="StockLocation"> ';

while ($MyRow = DB_fetch_array($ResultStkLocs)) {
	if (isset($_POST['StockLocation'])) {
		if ($MyRow['loccode'] == $_POST['StockLocation']) {
			echo '<option selected="selected" value="', $MyRow['loccode'], '">', $MyRow['locationname'], '</option>';
		} else {
			echo '<option value="', $MyRow['loccode'], '">', $MyRow['locationname'], '</option>';
		}
	} elseif ($MyRow['loccode'] == $_SESSION['UserStockLocation']) {
		echo '<option selected="selected" value="', $MyRow['loccode'], '">', $MyRow['locationname'], '</option>';
		$_POST['StockLocation'] = $MyRow['loccode'];
	} else {
		echo '<option value="', $MyRow['loccode'], '">', $MyRow['locationname'], '</option>';
	}
}
echo '</select>
	<fieldhelp>', _('Select the location of the stock items to report on'), '</fieldhelp>
</field>';

if (!isset($_POST['OnHandDate'])) {
	$_POST['OnHandDate'] = Date($_SESSION['DefaultDateFormat'], Mktime(0, 0, 0, Date('m'), 0, Date('y')));
}

echo '<field>
		<label for="OnHandDate">', _('On-Hand On Date'), ':</label>
		<input type="text" class="date" name="OnHandDate" size="12" required="required" maxlength="10" value="', $_POST['OnHandDate'], '" /></td>
		<fieldhelp>', _('Show stock quantities up to this date'), '</fieldhelp>
	</field>';

if (isset($_POST['ShowZeroStocks'])) {
	$Checked = 'checked="checked"';
} else {
	$Checked = '';
}

echo '<field>
		<label for="ShowZeroStocks">', ('Include zero stocks'), '</label>
		<input type="checkbox" name="ShowZeroStocks" value="" ', $Checked, '  />
		<fieldhelp>', _('If you want to show all items including those with a zero balance, then tick here'), '</fieldhelp>
	</field>';

echo '</fieldset>';

echo '<div class="centre">
		<input type="submit" name="ShowStatus" value="', _('Show Stock Status'), '" />
	</div>
</form>';

$TotalQuantity = 0;

if (isset($_POST['ShowStatus']) and is_date($_POST['OnHandDate'])) {
	if ($_POST['StockCategory'] == 'All') {
		$SQL = "SELECT stockid,
						controlled,
				  description,
						decimalplaces,
						units
					 FROM stockmaster
					 WHERE (mbflag='M' OR mbflag='B')";
	} else {
		$SQL = "SELECT stockid,
						controlled,
				  description,
						decimalplaces,
						units
					 FROM stockmaster
					 WHERE categoryid = '" . $_POST['StockCategory'] . "'
					 AND (mbflag='M' OR mbflag='B')";
	}

	$ErrMsg = _('The stock items in the category selected cannot be retrieved because');
	$DbgMsg = _('The SQL that failed was');

	$StockResult = DB_query($SQL, $ErrMsg, $DbgMsg);

	$SQLOnHandDate = FormatDateForSQL($_POST['OnHandDate']);

	echo '<table>
			<tr>
				<th>', _('Item Code'), '</th>
				<th>', _('Description'), '</th>
				<th>', _('Qty On Hand'), '</th>
				<th>', _('Units'), '</th>
				<th>', _('Controlled'), '</th>
			</tr>';

	while ($MyRow = DB_fetch_array($StockResult)) {

		$SQL = "SELECT stockid,
						newqoh
					FROM stockmoves
					WHERE stockmoves.trandate <= '" . $SQLOnHandDate . "'
						AND stockid = '" . $MyRow['stockid'] . "'
						AND loccode = '" . $_POST['StockLocation'] . "'
					ORDER BY stkmoveno DESC LIMIT 1";

		$ErrMsg = _('The stock held as at') . ' ' . $_POST['OnHandDate'] . ' ' . _('could not be retrieved because');

		$LocStockResult = DB_query($SQL, $ErrMsg);

		$NumRows = DB_num_rows($LocStockResult);

		while ($LocQtyRow = DB_fetch_array($LocStockResult)) {

			if ($MyRow['controlled'] == 1) {
				$Controlled = _('Yes');
			} else {
				$Controlled = _('No');
			}

			if ($NumRows == 0) {
				echo '<tr class="striped_row">
						<td><a target="_blank" href="', $RootPath, '/StockStatus.php?StockID=', urlencode($MyRow['stockid']), '">', mb_strtoupper($MyRow['stockid']), '</a></td>
						<td>', $MyRow['description'], '</td>
						<td class="number">', 0, '</td>
						<td>', $MyRow['units'], '</td>
						<td>', $Controlled, '</td>
					</tr>';
			} elseif (isset($_POST['ShowZeroStocks']) or $LocQtyRow['newqoh'] > 0) {
				echo '<tr class="striped_row">
						<td><a target="_blank" href="', $RootPath, '/StockStatus.php?StockID=' . urlencode($MyRow['stockid']), '">', mb_strtoupper($MyRow['stockid']), '</a></td>
						<td>', $MyRow['description'], '</td>
						<td class="number">', locale_number_format($LocQtyRow['newqoh'], $MyRow['decimalplaces']), '</td>
						<td>', $MyRow['units'], '</td>
						<td>', $Controlled, '</td>
					</tr>';

				$TotalQuantity+= $LocQtyRow['newqoh'];
			}
			//end of page full new headings if
			
		}

	} //end of while loop
	echo '<tr>
			<td></td>
			<td>', _('Total Quantity'), ':</td>
			<td class="number">', $TotalQuantity, '</td>
		</tr>
	</table>';
}

include ('includes/footer.php');
?>