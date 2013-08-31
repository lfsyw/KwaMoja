<?php

/*Functions to get the GL codes to post the transaction to */
include('includes/GetSalesTransGLCodes.inc');
/*defines the structure of the data required to hold the transaction as a session variable */
include('includes/DefineCartClass.php');
include('includes/DefineSerialItems.php');
/* Session started in header.inc for password checking and authorisation level check */
include('includes/session.inc');

$Title = _('Credit An Invoice');
include('includes/header.inc');
include('includes/SQL_CommonFunctions.inc');

if (empty($_GET['identifier'])) {
	/*unique session identifier to ensure that there is no conflict with other credit entry sessions on the same machine  */
	$identifier = date('U');
} else {
	$identifier = $_GET['identifier'];
}

if (!isset($_GET['InvoiceNumber']) and !$_SESSION['ProcessingCredit']) {
	/* This page can only be called with an invoice number for crediting*/
	prnMsg(_('This page can only be opened if an invoice has been selected for crediting') . '. ' . _('Please select an invoice first') . ' - ' . _('from the customer inquiry screen click the link to credit an invoice'), 'info');
	include('includes/footer.inc');
	exit;

} elseif (isset($_GET['InvoiceNumber'])) {
	$_GET['InvoiceNumber'] = intval($_GET['InvoiceNumber']);
	unset($_SESSION['CreditItems' . $identifier]->LineItems);
	unset($_SESSION['CreditItems' . $identifier]);

	$_SESSION['ProcessingCredit'] = intval($_GET['InvoiceNumber']);
	$_SESSION['CreditItems' . $identifier] = new cart;

	/*read in all the guff from the selected invoice into the Items cart	*/


	$InvoiceHeaderSQL = "SELECT DISTINCT
								debtortrans.id as transid,
								debtortrans.debtorno,
								debtorsmaster.name,
								debtortrans.branchcode,
								debtortrans.reference,
								debtortrans.invtext,
								debtortrans.order_,
								debtortrans.trandate,
								debtortrans.tpe,
								debtortrans.shipvia,
								debtortrans.ovfreight,
								debtortrans.rate AS currency_rate,
								debtorsmaster.currcode,
								custbranch.defaultlocation,
								custbranch.taxgroupid,
								salesorders.salesperson,
								stockmoves.loccode,
								locations.taxprovinceid,
								currencies.decimalplaces
							FROM debtortrans
							INNER JOIN debtorsmaster
								ON debtortrans.debtorno = debtorsmaster.debtorno
							INNER JOIN custbranch
								ON debtortrans.branchcode = custbranch.branchcode
								AND debtortrans.debtorno = custbranch.debtorno
							INNER JOIN currencies
								ON debtorsmaster.currcode = currencies.currabrev
							INNER JOIN stockmoves
								ON stockmoves.transno=debtortrans.transno
								AND stockmoves.type=debtortrans.type
							INNER JOIN salesorders
								ON debtortrans.order_=salesorders.orderno
							INNER JOIN locations
								ON stockmoves.loccode = locations.loccode
							WHERE debtortrans.transno = '" . intval($_GET['InvoiceNumber']) . "'
								AND stockmoves.type=10";

	$ErrMsg = _('A credit cannot be produced for the selected invoice') . '. ' . _('The invoice details cannot be retrieved because');
	$DbgMsg = _('The SQL used to retrieve the invoice details was');
	$GetInvHdrResult = DB_query($InvoiceHeaderSQL, $db, $ErrMsg, $DbgMsg);

	if (DB_num_rows($GetInvHdrResult) == 1) {

		$myrow = DB_fetch_array($GetInvHdrResult);

		/*CustomerID variable registered by header.inc */
		$_SESSION['CreditItems' . $identifier]->DebtorNo = $myrow['debtorno'];
		$_SESSION['CreditItems' . $identifier]->TransID = $myrow['transid'];
		$_SESSION['CreditItems' . $identifier]->Branch = $myrow['branchcode'];
		$_SESSION['CreditItems' . $identifier]->CustomerName = $myrow['name'];
		$_SESSION['CreditItems' . $identifier]->CustRef = $myrow['reference'];
		$_SESSION['CreditItems' . $identifier]->Comments = $myrow['invtext'];
		$_SESSION['CreditItems' . $identifier]->DefaultSalesType = $myrow['tpe'];
		$_SESSION['CreditItems' . $identifier]->DefaultCurrency = $myrow['currcode'];
		$_SESSION['CreditItems' . $identifier]->Location = $myrow['loccode'];
		$_SESSION['Old_FreightCost'] = $myrow['ovfreight'];
		$_SESSION['CurrencyRate'] = $myrow['currency_rate'];
		$_SESSION['CreditItems' . $identifier]->OrderNo = $myrow['order_'];
		$_SESSION['CreditItems' . $identifier]->ShipVia = $myrow['shipvia'];
		$_SESSION['CreditItems' . $identifier]->TaxGroup = $myrow['taxgroupid'];
		$_SESSION['CreditItems' . $identifier]->FreightCost = $myrow['ovfreight'];
		$_SESSION['CreditItems' . $identifier]->DispatchTaxProvince = $myrow['taxprovinceid'];
		$_SESSION['CreditItems' . $identifier]->GetFreightTaxes();
		$_SESSION['CreditItems' . $identifier]->CurrDecimalPlaces = $myrow['decimalplaces'];
		$_SESSION['CreditItems' . $identifier]->SalesPerson = $myrow['salesperson'];

		DB_free_result($GetInvHdrResult);

		/*now populate the line items array with the stock movement records for the invoice*/

		$LineItemsSQL = "SELECT stockmoves.stkmoveno,
								stockmoves.stockid,
								stockmaster.description,
								stockmaster.longdescription,
								stockmaster.volume,
								stockmaster.grossweight,
								stockmaster.mbflag,
								stockmaster.controlled,
								stockmaster.serialised,
								stockmaster.decimalplaces,
								stockmaster.taxcatid,
								stockmaster.units,
								stockmaster.discountcategory,
								(stockmoves.price * " . $_SESSION['CurrencyRate'] . ") AS price, -
								stockmoves.qty as quantity,
								stockmoves.discountpercent,
								stockmoves.trandate,
								stockmaster.materialcost
									+ stockmaster.labourcost
									+ stockmaster.overheadcost AS standardcost,
								stockmoves.narrative
							FROM stockmoves, stockmaster
							WHERE stockmoves.stockid = stockmaster.stockid
							AND stockmoves.transno ='" . $_GET['InvoiceNumber'] . "'
							AND stockmoves.type=10
							AND stockmoves.show_on_inv_crds=1";

		$ErrMsg = _('This invoice can not be credited using this program') . '. ' . _('A manual credit note will need to be prepared') . '. ' . _('The line items of the order cannot be retrieved because');
		$Dbgmsg = _('The SQL used to get the transaction header was');

		$LineItemsResult = DB_query($LineItemsSQL, $db, $ErrMsg, $DbgMsg);

		if (DB_num_rows($LineItemsResult) > 0) {

			while ($myrow = DB_fetch_array($LineItemsResult)) {

				$LineNumber = $_SESSION['CreditItems' . $identifier]->LineCounter;

				$_SESSION['CreditItems' . $identifier]->add_to_cart($myrow['stockid'], $myrow['quantity'], $myrow['description'], $myrow['longdescription'], $myrow['price'], $myrow['discountpercent'], $myrow['units'], $myrow['volume'], $myrow['grossweight'], 0, $myrow['mbflag'], $myrow['trandate'], 0, $myrow['discountcategory'], $myrow['controlled'], $myrow['serialised'], $myrow['decimalplaces'], $myrow['narrative'], 'No', -1, $myrow['taxcatid'], '', '', '', $myrow['standardcost']);

				$_SESSION['CreditItems' . $identifier]->GetExistingTaxes($LineNumber, $myrow['stkmoveno']);

				if ($myrow['controlled'] == 1) {
					/* Populate the SerialItems array too*/

					$SQL = "SELECT 	serialno,
									moveqty
							FROM stockserialmoves
							WHERE stockmoveno='" . $myrow['stkmoveno'] . "'
							AND stockid = '" . $myrow['stockid'] . "'";

					$ErrMsg = _('This invoice can not be credited using this program') . '. ' . _('A manual credit note will need to be prepared') . '. ' . _('The line item') . ' ' . $myrow['stockid'] . ' ' . _('is controlled but the serial numbers or batch numbers could not be retrieved because');
					$DbgMsg = _('The SQL used to get the controlled item details was');
					$SerialItemsResult = DB_query($SQL, $db, $ErrMsg, $DbgMsg);

					while ($SerialItemsRow = DB_fetch_array($SerialItemsResult)) {
						$_SESSION['CreditItems' . $identifier]->LineItems[$LineNumber]->SerialItems[$SerialItemsRow['serialno']] = new SerialItem($SerialItemsRow['serialno'], -$SerialItemsRow['moveqty']);
						$_SESSION['CreditItems' . $identifier]->LineItems[$LineNumber]->QtyDispatched -= $SerialItemsRow['moveqty'];
					}
				}
				/* end if the item is a controlled item */
			}
			/* loop thro line items from stock movement records */

		} else {
			/* there are no stock movement records created for that invoice */

			echo '<div class="centre"><a href="' . $RootPath . '/index.php">' . _('Back to the menu') . '</a></div>';
			prnMsg(_('There are no line items that were retrieved for this invoice') . '. ' . _('The automatic credit program can not create a credit note from this invoice'), 'warn');
			include('includes/footer.inc');
			exit;
		} //end of checks on returned data set
		DB_free_result($LineItemsResult);
	} else {
		prnMsg(_('This invoice can not be credited using the automatic facility') . '<br />' . _('CRITICAL ERROR') . ': ' . _('Please report that a duplicate DebtorTrans header record was found for invoice') . ' ' . $SESSION['ProcessingCredit'], 'warn');
		include('includes/footer.inc');
		exit;
	} //valid invoice record returned from the entered invoice number

}

if (isset($_POST['Location'])) {
	$_SESSION['CreditItems' . $identifier]->Location = $_POST['Location'];

	$NewDispatchTaxProvResult = DB_query("SELECT taxprovinceid FROM locations WHERE loccode='" . $_POST['Location'] . "'", $db);
	$myrow = DB_fetch_array($NewDispatchTaxProvResult);

	$_SESSION['CreditItems' . $identifier]->DispatchTaxProvince = $myrow['taxprovinceid'];

	foreach ($_SESSION['CreditItems' . $identifier]->LineItems as $LineItem) {
		$_SESSION['CreditItems' . $identifier]->GetTaxes($LineItem->LineNumber);
	}
}
if (isset($_POST['ChargeFreightCost'])) {
	$_SESSION['CreditItems' . $identifier]->FreightCost = filter_number_format($_POST['ChargeFreightCost']);
}

foreach ($_SESSION['CreditItems' . $identifier]->FreightTaxes as $FreightTaxLine) {
	if (isset($_POST['FreightTaxRate' . $FreightTaxLine->TaxCalculationOrder])) {
		$_SESSION['CreditItems' . $identifier]->FreightTaxes[$FreightTaxLine->TaxCalculationOrder]->TaxRate = filter_number_format($_POST['FreightTaxRate' . $FreightTaxLine->TaxCalculationOrder]) / 100;
	}
}

if ($_SESSION['CreditItems' . $identifier]->ItemsOrdered > 0 or isset($_POST['NewItem'])) {

	if (isset($_GET['Delete'])) {
		$_SESSION['CreditItems' . $identifier]->remove_from_cart($_GET['Delete']);
	}

	foreach ($_SESSION['CreditItems' . $identifier]->LineItems as $LineItem) {

		if (isset($_POST['Quantity_' . $LineItem->LineNumber])) {

			$Narrative = $_POST['Narrative_' . $LineItem->LineNumber];
			$Quantity = filter_number_format($_POST['Quantity_' . $LineItem->LineNumber]);
			$Price = filter_number_format($_POST['Price_' . $LineItem->LineNumber]);
			$DiscountPercentage = filter_number_format($_POST['Discount_' . $LineItem->LineNumber]);

			if ($Quantity < 0 or $Price < 0 or $DiscountPercentage > 100 or $DiscountPercentage < 0) {
				prnMsg(_('The item could not be updated because you are attempting to set the quantity credited to less than 0 or the price less than 0 or the discount more than 100% or less than 0%'), 'error');
			} else {
				$_SESSION['CreditItems' . $identifier]->LineItems[$LineItem->LineNumber]->QtyDispatched = $Quantity;
				$_SESSION['CreditItems' . $identifier]->LineItems[$LineItem->LineNumber]->Price = $Price;
				$_SESSION['CreditItems' . $identifier]->LineItems[$LineItem->LineNumber]->DiscountPercent = ($DiscountPercentage / 100);
				$_SESSION['CreditItems' . $identifier]->LineItems[$LineItem->LineNumber]->Narrative = $Narrative;
			}
			foreach ($LineItem->Taxes as $TaxLine) {
				if (isset($_POST[$LineItem->LineNumber . $TaxLine->TaxCalculationOrder . '_TaxRate'])) {
					$_SESSION['CreditItems' . $identifier]->LineItems[$LineItem->LineNumber]->Taxes[$TaxLine->TaxCalculationOrder]->TaxRate = filter_number_format($_POST[$LineItem->LineNumber . $TaxLine->TaxCalculationOrder . '_TaxRate']) / 100;
				}
			}
		}
	}
}

/* Always display credit quantities
NB QtyDispatched in the LineItems array is used for the quantity to credit */
echo '<p class="page_title_text noPrint" ><img src="' . $RootPath . '/css/' . $Theme . '/images/credit.gif" title="' . _('Search') . '" alt="" />' . $Title . '</p>';

if (!isset($_POST['ProcessCredit'])) {

	echo '<form onSubmit="return VerifyForm(this);" action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?identifier=' . $identifier . '" method="post" class="noPrint">';
	echo '<div>';
	echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';


	echo '<table cellpadding="2" class="selection">';
	echo '<tr>
			<th colspan="13">
				<div class="centre"><b>' . _('Credit Invoice') . ' ' . $_SESSION['ProcessingCredit'] . '</b>
					<b>' . ' - ' . $_SESSION['CreditItems' . $identifier]->CustomerName . '</b>
		 				- ' . _('Credit Note amounts stated in') . ' ' . $_SESSION['CreditItems' . $identifier]->DefaultCurrency . '</div>
		 	</th>
		 </tr>';
	echo '<tr>
			<th>' . _('Item Code') . '</th>
			<th>' . _('Item Description') . '</th>
			<th>' . _('Invoiced') . '</th>
			<th>' . _('Units') . '</th>
			<th>' . _('Credit') . '<br />' . _('Quantity') . '</th>
			<th>' . _('Price') . '</th>
			<th>' . _('Discount') . ' %' . '</th>
			<th>' . _('Total') . '<br />' . _('Excl Tax') . '</th>
			<th>' . _('Tax Authority') . '</th>
			<th>' . _('Tax') . ' %' . '</th>
			<th>' . _('Tax') . '<br />' . _('Amount') . '</th>
			<th>' . _('Total') . '<br />' . _('Incl Tax') . '</th>
		</tr>';

}
$_SESSION['CreditItems' . $identifier]->total = 0;
$_SESSION['CreditItems' . $identifier]->totalVolume = 0;
$_SESSION['CreditItems' . $identifier]->totalWeight = 0;

$TaxTotals = array();
$TaxGLCodes = array();
$TaxTotal = 0;

/*show the line items on the invoice with the quantity to credit and price being available for modification */

$k = 0; //row colour counter
$j = 0; //row counter

foreach ($_SESSION['CreditItems' . $identifier]->LineItems as $LnItm) {
	$LineTotal = $LnItm->QtyDispatched * $LnItm->Price * (1 - $LnItm->DiscountPercent);
	$_SESSION['CreditItems' . $identifier]->total += $LineTotal;
	$_SESSION['CreditItems' . $identifier]->totalVolume += ($LnItm->QtyDispatched * $LnItm->Volume);
	$_SESSION['CreditItems' . $identifier]->totalWeight += ($LnItm->QtyDispatched * $LnItm->Weight);

	if (!isset($_POST['ProcessCredit'])) {
		if ($k == 1) {
			$RowStarter = 'class="EvenTableRows"';
			$k = 0;
		} else {
			$RowStarter = 'class="OddTableRows"';
			$k = 1;
		}
		$j++;

		echo '<tr ' . $RowStarter . '><td>' . $LnItm->StockID . '</td>
			<td title="' . $LnItm->LongDescription . '">' . $LnItm->ItemDescription . '</td>
			<td class="number">' . locale_number_format($LnItm->Quantity, $LnItm->DecimalPlaces) . '</td>
			<td>' . $LnItm->Units . '</td>';

		if ($LnItm->Controlled == 1) {

			echo '<td><input type="hidden" name="Quantity_' . $LnItm->LineNumber . '"  value="' . locale_number_format($LnItm->QtyDispatched, $LnItm->DecimalPlaces) . '" /><a href="' . $RootPath . '/CreditItemsControlled.php?LineNo=' . $LnItm->LineNumber . '&amp;CreditInvoice=Yes&amp;identifier=' . $identifier . '">' . locale_number_format($LnItm->QtyDispatched, $LnItm->DecimalPlaces) . '</a></td>';

		} else {

			echo '<td><input tabindex="' . $j . '" type="text" class="number" name="Quantity_' . $LnItm->LineNumber . '" required="required" minlength="1" maxlength="6" size="6" value="' . locale_number_format($LnItm->QtyDispatched, $LnItm->DecimalPlaces) . '" /></td>';

		}

		$DisplayLineTotal = locale_number_format($LineTotal, $_SESSION['CreditItems' . $identifier]->CurrDecimalPlaces);

		$j++;
		echo '<td><input tabindex="' . $j . '" type="text" class="number" name="Price_' . $LnItm->LineNumber . '" required="required" minlength="1" maxlength="12" size="6" value="' . locale_number_format($LnItm->Price, $_SESSION['CreditItems' . $identifier]->CurrDecimalPlaces) . '" /></td>
		<td><input tabindex="' . $j . '" type="text" class="number" name="Discount_' . $LnItm->LineNumber . '" required="required" minlength="1" maxlength="3" size="3" value="' . locale_number_format(($LnItm->DiscountPercent * 100), 2) . '" /></td>
		<td class="number">' . $DisplayLineTotal . '</td>';

		/*Need to list the taxes applicable to this line */
		echo '<td>';
		$i = 0;
		if (is_array($_SESSION['CreditItems' . $identifier]->LineItems[$LnItm->LineNumber]->Taxes)) {
			foreach ($_SESSION['CreditItems' . $identifier]->LineItems[$LnItm->LineNumber]->Taxes as $Tax) {
				if ($i > 0) {
					echo '<br />';
				}
				echo $Tax->TaxAuthDescription;
				$i++;
			}
		}
		echo '</td>';
		echo '<td class="number">';

	}
	$i = 0; // initialise the number of taxes iterated through
	$TaxLineTotal = 0; //initialise tax total for the line
	if (is_array($LnItm->Taxes)) {
		foreach ($LnItm->Taxes as $Tax) {
			if ($i > 0) {
				echo '<br />';
			}
			if (!isset($_POST['ProcessCredit'])) {
				echo '<input type="text" class="number" name="' . $LnItm->LineNumber . $Tax->TaxCalculationOrder . '_TaxRate" maxlength="4" size="4" value="' . locale_number_format($Tax->TaxRate * 100, 2) . '" />';
			}
			$i++;
			if ($Tax->TaxOnTax == 1) {
				$TaxTotals[$Tax->TaxAuthID] += ($Tax->TaxRate * ($LineTotal + $TaxLineTotal));
				$TaxLineTotal += ($Tax->TaxRate * ($LineTotal + $TaxLineTotal));
			} else {
				$TaxTotals[$Tax->TaxAuthID] += ($Tax->TaxRate * $LineTotal);
				$TaxLineTotal += ($Tax->TaxRate * $LineTotal);
			}
			$TaxGLCodes[$Tax->TaxAuthID] = $Tax->TaxGLCode;
		}
	}
	$TaxTotal += $TaxLineTotal;

	$DisplayTaxAmount = locale_number_format($TaxLineTotal, $_SESSION['CreditItems' . $identifier]->CurrDecimalPlaces);
	$DisplayGrossLineTotal = locale_number_format($LineTotal + $TaxLineTotal, $_SESSION['CreditItems' . $identifier]->CurrDecimalPlaces);

	if (!isset($_POST['ProcessCredit'])) {
		echo '</td>';

		echo '<td class="number">' . $DisplayTaxAmount . '</td>
			<td class="number">' . $DisplayGrossLineTotal . '</td>
			<td><a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?identifier=' . $identifier . '&Delete=' . $LnItm->LineNumber . '"  onclick="return MakeConfirm(\'' . _('Are you sure you wish to delete this item from the credit?') . '\', \'Confirm Delete\', this);">' . _('Delete') . '</a></td>
			</tr>';

		echo '<tr ' . $RowStarter . '>
				<td colspan="12"><textarea tabindex="' . $j . '"  name="Narrative_' . $LnItm->LineNumber . '" cols="100%" rows="1">' . $LnItm->Narrative . '</textarea>
				<br />
				<hr /></td>
			</tr>';
		$j++;
	}
}
/*end foreach loop displaying the invoice lines to credit */

if (!isset($_POST['ChargeFreightCost']) and !isset($_SESSION['CreditItems' . $identifier]->FreightCost)) {
	$_POST['ChargeFreightCost'] = 0;
}

if (!isset($_POST['ProcessCredit'])) {
	echo '<tr>
		<td colspan="3" class="number">' . _('Freight cost charged on invoice') . '</td>
		<td class="number">' . locale_number_format($_SESSION['Old_FreightCost'], $_SESSION['CreditItems' . $identifier]->CurrDecimalPlaces) . '</td>
		<td></td>
		<td colspan="2" class="number">' . _('Credit Freight Cost') . '</td>
		<td><input tabindex="' . $j . '" type="text" class="number" size="6" maxlength="6" name="ChargeFreightCost" value="' . locale_number_format($_SESSION['CreditItems' . $identifier]->FreightCost, $_SESSION['CreditItems' . $identifier]->CurrDecimalPlaces) . '" /></td>
		<td></td>
		<td>';

	$i = 0; // initialise the number of taxes iterated through
	foreach ($_SESSION['CreditItems' . $identifier]->FreightTaxes as $FreightTaxLine) {
		if ($i > 0) {
			echo '<br />';
		}
		echo $FreightTaxLine->TaxAuthDescription;
		$i++;
	}
}
$FreightTaxTotal = 0; //initialise tax total

$i = 0;
foreach ($_SESSION['CreditItems' . $identifier]->FreightTaxes as $FreightTaxLine) {
	if ($i > 0) {
		echo '<br />';
	}

	if (!isset($_POST['ProcessCredit'])) {
		echo '<input type="text" class="number" name="FreightTaxRate' . $FreightTaxLine->TaxCalculationOrder . '" maxlength="4" size="4" value="' . locale_number_format(($FreightTaxLine->TaxRate * 100), 2) . '" />';
	}
	if ($FreightTaxLine->TaxOnTax == 1) {
		$TaxTotals[$FreightTaxLine->TaxAuthID] += ($FreightTaxLine->TaxRate * ($_SESSION['CreditItems' . $identifier]->FreightCost + $FreightTaxTotal));
		$FreightTaxTotal += ($FreightTaxLine->TaxRate * ($_SESSION['CreditItems' . $identifier]->FreightCost + $FreightTaxTotal));
	} else {
		$TaxTotals[$FreightTaxLine->TaxAuthID] += ($FreightTaxLine->TaxRate * $_SESSION['CreditItems' . $identifier]->FreightCost);
		$FreightTaxTotal += ($FreightTaxLine->TaxRate * $_SESSION['CreditItems' . $identifier]->FreightCost);
	}
	$i++;
	$TaxGLCodes[$FreightTaxLine->TaxAuthID] = $FreightTaxLine->TaxGLCode;
}
if (!isset($_POST['ProcessCredit'])) {
	echo '</td>';
	echo '<td class="number">' . locale_number_format($FreightTaxTotal, $_SESSION['CreditItems' . $identifier]->CurrDecimalPlaces) . '</td>
		<td class="number">' . locale_number_format($FreightTaxTotal + $_SESSION['CreditItems' . $identifier]->FreightCost, $_SESSION['CreditItems' . $identifier]->CurrDecimalPlaces) . '</td>
		</tr>';
}

$TaxTotal += $FreightTaxTotal;
$DisplayTotal = locale_number_format($_SESSION['CreditItems' . $identifier]->total + $_SESSION['CreditItems' . $identifier]->FreightCost, $_SESSION['CreditItems' . $identifier]->CurrDecimalPlaces);

if (!isset($_POST['ProcessCredit'])) {
	echo '<tr>
			<td colspan="7" class="number">' . _('Credit Totals') . '</td>
			<td class="number"><hr /><b>' . $DisplayTotal . '</b><hr /></td>
			<td colspan="2"></td>
			<td class="number"><hr /><b>' . locale_number_format($TaxTotal, $_SESSION['CreditItems' . $identifier]->CurrDecimalPlaces) . '</b><hr /></td>
			<td class="number"><hr /><b>' . locale_number_format($TaxTotal + ($_SESSION['CreditItems' . $identifier]->total + $_SESSION['CreditItems' . $identifier]->FreightCost), $_SESSION['CreditItems' . $identifier]->CurrDecimalPlaces) . '</b>
			<hr /></td>
		</tr>
		</table>';
}
$DefaultDispatchDate = Date($_SESSION['DefaultDateFormat']);

$OKToProcess = true;

if ((isset($_POST['CreditType']) and $_POST['CreditType'] == 'WriteOff') and !isset($_POST['WriteOffGLCode'])) {
	prnMsg(_('The GL code to write off the credit value to must be specified. Please select the appropriate GL code for the selection box'), 'info');
	$OKToProcess = false;
}

if (isset($_POST['ProcessCredit']) and $OKToProcess == true) {

	/* SQL to process the postings for sales credit notes... First Get the area where the credit note is to from the branches table */

	$SQL = "SELECT area
				FROM custbranch
			WHERE custbranch.debtorno ='" . $_SESSION['CreditItems' . $identifier]->DebtorNo . "'
			AND custbranch.branchcode = '" . $_SESSION['CreditItems' . $identifier]->Branch . "'";

	$Result = DB_query($SQL, $db);
	$myrow = DB_fetch_row($Result);
	$Area = $myrow[0];
	DB_free_result($Result);

	/*company record is read in on login and has information on GL Links and debtors GL account*/

	if ($_SESSION['CompanyRecord'] == 0) {
		/*The company data and preferences could not be retrieved for some reason */
		prnMsg(_('The company information and preferences could not be retrieved') . ' - ' . _('see your system administrator'), 'error');
		include('includes/footer.inc');
		exit;
	}

	/*Now Get the next credit note number - function in SQL_CommonFunctions*/

	$CreditNo = GetNextTransNo(11, $db);
	$PeriodNo = GetPeriod($DefaultDispatchDate, $db);

	/*Start an SQL transaction */


	$Result = DB_Txn_Begin($db);

	$DefaultDispatchDate = FormatDateForSQL($DefaultDispatchDate);

	/*Calculate the allocation and see if it is possible to allocate to the invoice being credited */

	$SQL = "SELECT (ovamount+ovgst+ovfreight-ovdiscount-alloc) AS baltoallocate
			FROM debtortrans
			WHERE transno=" . $_SESSION['ProcessingCredit'] . "
			AND type=10";
	$Result = DB_query($SQL, $db);
	$myrow = DB_fetch_row($Result);

	/*Do some rounding */

	$_SESSION['CreditItems' . $identifier]->total = round($_SESSION['CreditItems' . $identifier]->total, $_SESSION['CreditItems' . $identifier]->CurrDecimalPlaces);
	$TaxTotal = round($TaxTotal, $_SESSION['CreditItems' . $identifier]->CurrDecimalPlaces);

	$Allocate_amount = 0;
	$Settled = 0;
	$SettledInvoice = 0;
	if ($myrow[0] > 0) {
		/*the invoice is not already fully allocated */

		if ($myrow[0] > ($_SESSION['CreditItems' . $identifier]->total + $_SESSION['CreditItems' . $identifier]->FreightCost + $TaxTotal)) {

			$Allocate_amount = $_SESSION['CreditItems' . $identifier]->total + $_SESSION['CreditItems' . $identifier]->FreightCost + $TaxTotal;
			$Settled = 1;
		} else if ($myrow[0] > ($_SESSION['CreditItems' . $identifier]->total + $_SESSION['CreditItems' . $identifier]->FreightCost + $TaxTotal)) {
			/*the balance left to allocate is less than the credit note value */
			$Allocate_amount = $myrow[0];
			$SettledInvoice = 1;
			$Settled = 0;
		} else {
			$Allocate_amount = $myrow[0];
			$SettledInvoice = 1;
			$Settled = 1;
		}

		/*Now need to update the invoice DebtorTrans record for the amount to be allocated and if the invoice is now settled*/

		$SQL = "UPDATE debtortrans
				SET alloc = alloc + " . $Allocate_amount . ",
					settled='" . $SettledInvoice . "'
				WHERE transno = '" . $_SESSION['ProcessingCredit'] . "'
				AND type=10";

		$ErrMsg = _('CRITICAL ERROR') . '! ' . _('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . _('The alteration to the invoice record to reflect the allocation of the credit note to the invoice could not be done because');
		$DbgMsg = _('The following SQL to update the invoice allocation was used');
		$Result = DB_query($SQL, $db, $ErrMsg, $DbgMsg, true);
	}

	/*Now insert the Credit Note into the DebtorTrans table with the allocations as calculated above*/
	$SQL = "INSERT INTO debtortrans (transno,
									type,
									debtorno,
									branchcode,
									trandate,
									inputdate,
									prd,
									reference,
									tpe,
									order_,
									ovamount,
									ovgst,
									ovfreight,
									rate,
									invtext,
									alloc,
									settled,
									salesperson)
						VALUES (" . $CreditNo . ",
							11,
							'" . $_SESSION['CreditItems' . $identifier]->DebtorNo . "',
							'" . $_SESSION['CreditItems' . $identifier]->Branch . "',
							'" . $DefaultDispatchDate . "',
							'" . date('Y-m-d H-i-s') . "',
							'" . $PeriodNo . "',
							'Inv-" . $_SESSION['ProcessingCredit'] . "',
							'" . $_SESSION['CreditItems' . $identifier]->DefaultSalesType . "',
							'" . $_SESSION['CreditItems' . $identifier]->OrderNo . "',
							'" . -$_SESSION['CreditItems' . $identifier]->total . "',
							'" . -$TaxTotal . "',
							'" . -$_SESSION['CreditItems' . $identifier]->FreightCost . "',
							'" . $_SESSION['CurrencyRate'] . "',
							'" . $_POST['CreditText'] . "',
							'" . -$Allocate_amount . "',
							'" . $Settled . "',
							'" . $_SESSION['CreditItems' . $identifier]->SalesPerson . "')";


	$ErrMsg = _('CRITICAL ERROR') . '! ' . _('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . _('The customer credit note transaction could not be added to the database because');
	$DbgMsg = _('The following SQL to insert the customer credit note was used');
	$Result = DB_query($SQL, $db, $ErrMsg, $DbgMsg, true);

	$CreditTransID = DB_Last_Insert_ID($db, 'debtortrans', 'id');

	/* Insert the tax totals for each tax authority where tax was charged on the invoice */
	foreach ($TaxTotals as $TaxAuthID => $TaxAmount) {

		$SQL = "INSERT INTO debtortranstaxes (
							debtortransid,
							taxauthid,
							taxamount)
				VALUES ('" . $CreditTransID . "',
					'" . $TaxAuthID . "',
					'" . (-$TaxAmount / $_SESSION['CurrencyRate']) . "')";

		$ErrMsg = _('CRITICAL ERROR') . '! ' . _('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . _('The debtor transaction taxes records could not be inserted because');
		$DbgMsg = _('The following SQL to insert the debtor transaction taxes record was used');
		$Result = DB_query($SQL, $db, $ErrMsg, $DbgMsg, true);
	}

	/*Now insert the allocation record if > 0 */
	if ($Allocate_amount != 0) {
		$SQL = "INSERT INTO custallocns (amt,
						transid_allocfrom,
						transid_allocto,
						datealloc)
			VALUES ('" . $Allocate_amount . "',
				'" . $CreditTransID . "',
				'" . $_SESSION['CreditItems' . $identifier]->TransID . "',
				'" . Date('Y-m-d') . "')";

		$ErrMsg = _('CRITICAL ERROR') . '! ' . _('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . _('The allocation record for the credit note could not be added to the database because');
		$DbgMsg = _('The following SQL to insert the allocation record for the credit note was used');
		$Result = DB_query($SQL, $db, $ErrMsg, $DbgMsg, true);

	}

	/* Update sales order details quantity invoiced less this credit quantity. */

	foreach ($_SESSION['CreditItems' . $identifier]->LineItems as $CreditLine) {

		if ($CreditLine->QtyDispatched > 0) {
			$LocalCurrencyPrice = round(($CreditLine->Price / $_SESSION['CurrencyRate']), $_SESSION['CompanyRecord']['decimalplaces']);

			/*Determine the type of stock item being credited */
			$SQL = "SELECT mbflag FROM stockmaster WHERE stockid = '" . $CreditLine->StockID . "'";
			$Result = DB_query($SQL, $db, _('Could not determine if the item') . ' ' . $CreditLine->StockID . ' ' . _('is purchased or manufactured'), _('The SQL used that failed was'), true);
			$MBFlagRow = DB_fetch_row($Result);
			$MBFlag = $MBFlagRow[0];
			if ($MBFlag == 'M' or $MBFlag == 'B') {
				/*Need to get the current location quantity will need it later for the stock movements */
				$SQL = "SELECT locstock.quantity
					FROM locstock
					WHERE locstock.stockid='" . $CreditLine->StockID . "'
					AND loccode= '" . $_SESSION['CreditItems' . $identifier]->Location . "'";
				$Result = DB_query($SQL, $db);
				if (DB_num_rows($Result) == 1) {
					$LocQtyRow = DB_fetch_row($Result);
					$QtyOnHandPrior = $LocQtyRow[0];
				} else {
					/*There must actually be some error this should never happen */
					$QtyOnHandPrior = 0;
				}
			} else {
				$QtyOnHandPrior = 0; //because its a dummy/assembly/kitset part
			}

			if ($_POST['CreditType'] == 'Return') {

				/* some want this some do not
				 * We cannot use the orderlineno to update with as it could be different when added to the credit note than it was when the order was created
				 * Also there could potentially be the same item on the order multiple times with different delivery dates
				 * So all up the SQL below is a bit hit and miss !!
				 * Probably right 99% of time with the item on the order only once */

				$SQL = "UPDATE salesorderdetails
							SET qtyinvoiced = qtyinvoiced - " . $CreditLine->QtyDispatched . ",
								completed=0
						WHERE orderno = '" . $_SESSION['CreditItems' . $identifier]->OrderNo . "'
						AND stkcode = '" . $CreditLine->StockID . "'
						AND quantity >=" . $CreditLine->QtyDispatched;


				$ErrMsg = _('CRITICAL ERROR') . '! ' . _('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . _('The sales order detail record could not be updated for the reduced quantity invoiced because');
				$DbgMsg = _('The following SQL to update the sales order detail record was used');
				$Result = DB_query($SQL, $db, $ErrMsg, $DbgMsg, true);

				/* Update location stock records if not a dummy stock item */

				if ($MBFlag == 'B' or $MBFlag == 'M') {

					$SQL = "UPDATE locstock
								SET locstock.quantity = locstock.quantity + " . $CreditLine->QtyDispatched . "
								WHERE locstock.stockid = '" . $CreditLine->StockID . "'
								AND loccode = '" . $_SESSION['CreditItems' . $identifier]->Location . "'";

					$ErrMsg = _('CRITICAL ERROR') . '! ' . _('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . _('Location stock record could not be updated because');
					$DbgMsg = _('The following SQL to update the location stock record was used');
					$Result = DB_query($SQL, $db, $ErrMsg, $DbgMsg, true);

				} else if ($MBFlag == 'A') {
					/* its an assembly */
					/*Need to get the BOM for this part and make stock moves for the components
					and of course update the Location stock balances */

					$StandardCost = 0;
					/*To start with - accumulate the cost of the comoponents for use in journals later on */
					$sql = "SELECT	bom.component,
									bom.quantity,
								stockmaster.materialcost
									+ stockmaster.labourcost
									+ stockmaster.overheadcost AS standard
								FROM bom,
									stockmaster
								WHERE bom.component=stockmaster.stockid
								AND bom.parent='" . $CreditLine->StockID . "'
								AND bom.effectiveto > '" . Date('Y-m-d') . "'
								AND bom.effectiveafter < '" . Date('Y-m-d') . "'";

					$ErrMsg = _('Could not retrieve assembly components from the database for') . ' ' . $CreditLine->StockID . ' ' . _('because');
					$DbgMsg = _('The SQL that failed was');
					$AssResult = DB_query($sql, $db, $ErrMsg, $DbgMsg, true);

					while ($AssParts = DB_fetch_array($AssResult, $db)) {

						$StandardCost += $AssParts['standard'];
						/*Determine the type of stock item being credited */
						$SQL = "SELECT mbflag
								FROM stockmaster
								WHERE stockid = '" . $AssParts['component'] . "'";
						$Result = DB_query($SQL, $db);
						$MBFlagRow = DB_fetch_row($Result);
						$Component_MBFlag = $MBFlagRow[0];

						/* Insert stock movements for the stock coming back in - with unit cost */
						if ($Component_MBFlag == 'M' or $Component_MBFlag == 'B') {
							/*Need to get the current location quantity will need it later for the stock movement */
							$SQL = "SELECT locstock.quantity
									FROM locstock
									WHERE locstock.stockid='" . $AssParts['component'] . "'
									AND loccode= '" . $_SESSION['CreditItems' . $identifier]->Location . "'";
							$Result = DB_query($SQL, $db, _('Could not get the current location stock of the assembly component') . ' ' . $AssParts['component'], _('The SQL that failed was'), true);
							if (DB_num_rows($Result) == 1) {
								$LocQtyRow = DB_fetch_row($Result);
								$QtyOnHandPrior = $LocQtyRow[0];
							} else {
								/*There must actually be some error this should never happen */
								$QtyOnHandPrior = 0;
							}
						} else {
							$QtyOnHandPrior = 0; //because its a dummy/assembly/kitset part
						}

						if ($Component_MBFlag == 'M' or $Component_MBFlag == 'B') {

							$SQL = "INSERT INTO stockmoves (stockid,
															type,
															transno,
															loccode,
															trandate,
															debtorno,
															branchcode,
															prd,
															reference,
															qty,
															standardcost,
															show_on_inv_crds,
															newqoh )
												VALUES ('" . $AssParts['component'] . "',
														11,
														'" . $CreditNo . "',
														'" . $_SESSION['CreditItems' . $identifier]->Location . "',
														'" . $DefaultDispatchDate . "',
														'" . $_SESSION['CreditItems' . $identifier]->DebtorNo . "',
														'" . $_SESSION['CreditItems' . $identifier]->Branch . "',
														'" . $PeriodNo . "',
														'" . _('Ex Inv') . ': ' . $_SESSION['ProcessingCredit'] . ' ' . _('Assembly') . ': ' . $CreditLine->StockID . "',
														'" . ($AssParts['quantity'] * $CreditLine->QtyDispatched) . "',
														'" . $AssParts['standard'] . "',
														0,
														'" . ($QtyOnHandPrior + ($AssParts['quantity'] * $CreditLine->QtyDispatched)) . "'
														)";
						} else {

							$SQL = "INSERT INTO stockmoves (stockid,
															type,
															transno,
															loccode,
															trandate,
															debtorno,
															branchcode,
															prd,
															reference,
															qty,
															standardcost,
															show_on_inv_crds)
												VALUES ('" . $AssParts['component'] . "',
														11,
														'" . $CreditNo . "',
														'" . $_SESSION['CreditItems' . $identifier]->Location . "',
														'" . $DefaultDispatchDate . "',
														'" . $_SESSION['CreditItems' . $identifier]->DebtorNo . "',
														'" . $_SESSION['CreditItems' . $identifier]->Branch . "',
														'" . $PeriodNo . "',
														'" . _('Ex Inv') . ': ' . $_SESSION['ProcessingCredit'] . ' ' . _('Assembly') . ': ' . $CreditLine->StockID . "',
														'" . ($AssParts['quantity'] * $CreditLine->QtyDispatched) . "',
														'" . $AssParts['standard'] . "',
														0)";
						}

						$ErrMsg = _('CRITICAL ERROR') . '! ' . _('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . _('Stock movement records for the assembly components of') . ' ' . $CreditLine->StockID . ' ' . _('could not be inserted because');
						$DbgMsg = _('The following SQL to insert the assembly components stock movement records was used');
						$Result = DB_query($SQL, $db, $ErrMsg, $DbgMsg, true);

						if ($Component_MBFlag == 'M' or $Component_MBFlag == 'B') {
							$SQL = "UPDATE locstock
									SET locstock.quantity = locstock.quantity + " . ($AssParts['quantity'] * $CreditLine->QtyDispatched) . "
									WHERE locstock.stockid = '" . $AssParts['component'] . "'
									AND loccode = '" . $_SESSION['CreditItems' . $identifier]->Location . "'";

							$ErrMsg = _('CRITICAL ERROR') . '! ' . _('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . _('Location stock record could not be updated for an assembly component because');
							$DbgMsg = _('The following SQL to update the components location stock record was used');
							$Result = DB_query($SQL, $db, $ErrMsg, $DbgMsg, true);
						}
					}
					/* end of assembly explosion and updates */
					/*Update the cart with the recalculated standard cost from the explosion of the assembly's components*/
					$_SESSION['CreditItems' . $identifier]->LineItems[$CreditLine->LineNumber]->StandardCost = $StandardCost;
					$CreditLine->StandardCost = $StandardCost;
				}

				/* Insert stock movements for the stock coming back in - with unit cost */

				if ($MBFlag == 'M' or $MBFlag == 'B') {
					$SQL = "INSERT INTO stockmoves (stockid,
													type,
													transno,
													loccode,
													trandate,
													debtorno,
													branchcode,
													price,
													prd,
													reference,
													qty,
													discountpercent,
													standardcost,
													newqoh,
													narrative)
											VALUES ('" . $CreditLine->StockID . "',
													11,
													'" . $CreditNo . "',
													'" . $_SESSION['CreditItems' . $identifier]->Location . "',
													'" . $DefaultDispatchDate . "',
													'" . $_SESSION['CreditItems' . $identifier]->DebtorNo . "',
													'" . $_SESSION['CreditItems' . $identifier]->Branch . "',
													'" . $LocalCurrencyPrice . "',
													'" . $PeriodNo . "',
													'" . _('Ex Inv') . ' - ' . $_SESSION['ProcessingCredit'] . "',
													'" . $CreditLine->QtyDispatched . "',
													'" . $CreditLine->DiscountPercent . "',
													'" . $CreditLine->StandardCost . "',
													'" . ($QtyOnHandPrior + $CreditLine->QtyDispatched) . "',
													'" . $CreditLine->Narrative . "')";
				} else {

					$SQL = "INSERT INTO stockmoves (stockid,
													type,
													transno,
													loccode,
													trandate,
													debtorno,
													branchcode,
													price,
													prd,
													reference,
													qty,
													discountpercent,
													standardcost,
													narrative)
											VALUES ('" . $CreditLine->StockID . "',
													11,
													'" . $CreditNo . "',
													'" . $_SESSION['CreditItems' . $identifier]->Location . "',
													'" . $DefaultDispatchDate . "',
													'" . $_SESSION['CreditItems' . $identifier]->DebtorNo . "',
													'" . $_SESSION['CreditItems' . $identifier]->Branch . "',
													'" . $LocalCurrencyPrice . "',
													'" . $PeriodNo . "',
													'" . _('Ex Inv') . " - " . $_SESSION['ProcessingCredit'] . "',
													'" . $CreditLine->QtyDispatched . "',
													'" . $CreditLine->DiscountPercent . "',
													'" . $CreditLine->StandardCost . "',
													'" . $CreditLine->Narrative . "')";
				}

				$ErrMsg = _('CRITICAL ERROR') . '! ' . _('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . _('Stock movement records could not be inserted because');
				$DbgMsg = _('The following SQL to insert the stock movement records was used');
				$Result = DB_query($SQL, $db, $ErrMsg, $DbgMsg, true);

				$StkMoveNo = DB_Last_Insert_ID($db, 'stockmoves', 'stkmoveno');
				/*Insert the StockSerialMovements and update the StockSerialItems  for controlled items*/
				//echo "<div align="left"><pre>"; var_dump($CreditLine); echo "</pre> </div>";
				if ($CreditLine->Controlled == 1) {
					foreach ($CreditLine->SerialItems as $Item) {
						/*We need to add the StockSerialItem record and The StockSerialMoves as well */
						$SQL = "SELECT quantity from stockserialitems
								WHERE stockid='" . $CreditLine->StockID . "'
								AND loccode='" . $_SESSION['CreditItems' . $identifier]->Location . "'
								AND serialno='" . $Item->BundleRef . "'";

						$ErrMsg = _('CRITICAL ERROR') . '! ' . _('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . _('The serial stock item record could not be selected because');
						$DbgMsg = _('The following SQL to select the serial stock item record was used');
						$Result = DB_query($SQL, $db, $ErrMsg, $DbgMsg, true);

						if (DB_num_rows($Result) == 0) {
							$SQL = "INSERT INTO stockserialitems (stockid,
																	loccode,
																	serialno,
																	quantity)
											VALUES
														('" . $CreditLine->StockID . "',
														 '" . $_SESSION['CreditItems' . $identifier]->Location . "',
														 '" . $Item->BundleRef . "',
														 '" . $Item->BundleQty . "')";

							$ErrMsg = _('CRITICAL ERROR') . '! ' . _('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . _('The serial stock item record could not be updated because');
							$DbgMsg = _('The following SQL to update the serial stock item record was used');
							$Result = DB_query($SQL, $db, $ErrMsg, $DbgMsg, true);
						} else {

							$SQL = "UPDATE stockserialitems
								SET quantity= quantity + " . $Item->BundleQty . "
								WHERE stockid='" . $CreditLine->StockID . "'
								AND loccode='" . $_SESSION['CreditItems' . $identifier]->Location . "'
								AND serialno='" . $Item->BundleRef . "'";
							$ErrMsg = _('CRITICAL ERROR') . '! ' . _('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . _('The serial stock item record could not be updated because');
							$DbgMsg = _('The following SQL to update the serial stock item record was used');
							$Result = DB_query($SQL, $db, $ErrMsg, $DbgMsg, true);
						}

						/* now insert the serial stock movement */

						$SQL = "INSERT INTO stockserialmoves (stockmoveno,
																stockid,
																serialno,
																moveqty)
													VALUES ('" . $StkMoveNo . "',
															'" . $CreditLine->StockID . "',
															'" . $Item->BundleRef . "',
															'" . $Item->BundleQty . "')";
						$ErrMsg = _('CRITICAL ERROR') . '! ' . _('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . _('The serial stock movement record could not be inserted because');
						$DbgMsg = _('The following SQL to insert the serial stock movement records was used');
						$Result = DB_query($SQL, $db, $ErrMsg, $DbgMsg, true);

					}
					/* foreach controlled item in the serialitems array */
				}
				/*end if the orderline is a controlled item */

			} elseif ($_POST['CreditType'] == 'WriteOff') {
				/*Insert a stock movement coming back in to show the credit note and
				a reversing stock movement to show the write off
				no mods to location stock records*/

				$SQL = "INSERT INTO stockmoves (stockid,
												type,
												transno,
												loccode,
												trandate,
												debtorno,
												branchcode,
												price,
												prd,
												reference,
												qty,
												discountpercent,
												standardcost,
												newqoh,
												narrative )
									VALUES ('" . $CreditLine->StockID . "',
											11,
											'" . $CreditNo . "',
											'" . $_SESSION['CreditItems' . $identifier]->Location . "',
											'" . $DefaultDispatchDate . "',
											'" . $_SESSION['CreditItems' . $identifier]->DebtorNo . "',
											'" . $_SESSION['CreditItems' . $identifier]->Branch . "',
											'" . $LocalCurrencyPrice . "',
											'" . $PeriodNo . "',
											'" . _('Ex Inv') . ' - ' . $_SESSION['ProcessingCredit'] . "',
											'" . $CreditLine->QtyDispatched . "',
											'" . $CreditLine->DiscountPercent . "',
											'" . $CreditLine->StandardCost . "',
											'" . ($QtyOnHandPrior + $CreditLine->QtyDispatched) . "',
											'" . $CreditLine->Narrative . "')";

				$ErrMsg = _('CRITICAL ERROR') . '! ' . _('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . _('Stock movement records could not be inserted because');
				$DbgMsg = _('The following SQL to insert the stock movement records was used');
				$Result = DB_query($SQL, $db, $ErrMsg, $DbgMsg, true);

				$SQL = "INSERT INTO stockmoves (stockid,
												type,
												transno,
												loccode,
												trandate,
												debtorno,
												branchcode,
												price,
												prd,
												reference,
												qty,
												discountpercent,
												standardcost,
												show_on_inv_crds,
												newqoh,
												narrative
												)
									VALUES ('" . $CreditLine->StockID . "',
											11,
											'" . $CreditNo . "',
											'" . $_SESSION['CreditItems' . $identifier]->Location . "',
											'" . $DefaultDispatchDate . "',
											'" . $_SESSION['CreditItems' . $identifier]->DebtorNo . "',
											'" . $_SESSION['CreditItems' . $identifier]->Branch . "',
											'" . $LocalCurrencyPrice . "',
											'" . $PeriodNo . "',
											'" . _('Written off ex Inv') . ' - ' . $_SESSION['ProcessingCredit'] . "',
											'" . -$CreditLine->QtyDispatched . "',
											'" . $CreditLine->DiscountPercent . "',
											'" . $CreditLine->StandardCost . "',
											0,
											'" . $QtyOnHandPrior . "',
											'" . $CreditLine->Narrative . "')";

				$ErrMsg = _('CRITICAL ERROR') . '! ' . _('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . _('Stock movement records could not be inserted because');
				$DbgMsg = _('The following SQL to insert the stock movement records was used');
				$Result = DB_query($SQL, $db, $ErrMsg, $DbgMsg, true);

			} elseif ($_POST['CreditType'] == 'ReverseOverCharge') {
				/*Insert a stock movement coming back in to show the credit note  - flag the stockmovement not to show on stock movement enquiries - its is not a real stock movement only for invoice line - also no mods to location stock records*/
				$SQL = "INSERT INTO stockmoves (stockid,
												type,
												transno,
												loccode,
												trandate,
												debtorno,
												branchcode,
												price,
												prd,
												reference,
												qty,
												discountpercent,
												standardcost,
												newqoh,
												hidemovt,
												narrative)
									VALUES ('" . $CreditLine->StockID . "',
											11,
											'" . $CreditNo . "',
											'" . $_SESSION['CreditItems' . $identifier]->Location . "',
											'" . $DefaultDispatchDate . "',
											'" . $_SESSION['CreditItems' . $identifier]->DebtorNo . "',
											'" . $_SESSION['CreditItems' . $identifier]->Branch . "',
											'" . $LocalCurrencyPrice . "',
											'" . $PeriodNo . "',
											'" . _('Ex Inv') . ' - ' . $_SESSION['ProcessingCredit'] . "',
											'" . $CreditLine->QtyDispatched . "',
											'" . $CreditLine->DiscountPercent . "',
											'" . $CreditLine->StandardCost . "',
											'" . $QtyOnHandPrior . "',
											1,
											'" . $CreditLine->Narrative . "')";


				$ErrMsg = _('CRITICAL ERROR') . '! ' . _('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . _('Stock movement records could not be inserted because');
				$DbgMsg = _('The following SQL to insert the stock movement records for the purpose of display on the credit note was used');

				$Result = DB_query($SQL, $db, $ErrMsg, $DbgMsg, true);
			}

			/*Get the ID of the StockMove... */
			$StkMoveNo = DB_Last_Insert_ID($db, 'stockmoves', 'stkmoveno');

			/*Insert the taxes that applied to this line */
			foreach ($CreditLine->Taxes as $Tax) {

				$SQL = "INSERT INTO stockmovestaxes (stkmoveno,
													taxauthid,
													taxrate,
													taxcalculationorder,
													taxontax)
					VALUES ('" . $StkMoveNo . "',
						'" . $Tax->TaxAuthID . "',
						'" . $Tax->TaxRate . "',
						'" . $Tax->TaxCalculationOrder . "',
						'" . $Tax->TaxOnTax . "')";

				$ErrMsg = _('CRITICAL ERROR') . '! ' . _('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . _('Taxes and rates applicable to this credit note line item could not be inserted because');
				$DbgMsg = _('The following SQL to insert the stock movement tax detail records was used');
				$Result = DB_query($SQL, $db, $ErrMsg, $DbgMsg, true);
			}

			/*Insert Sales Analysis records */

			$SQL = "SELECT COUNT(*),
						stkcategory,
						salesanalysis.area,
						salesperson
					FROM salesanalysis INNER JOIN custbranch
					ON salesanalysis.cust=custbranch.debtorno
						AND salesanalysis.custbranch=custbranch.branchcode
						AND salesanalysis.area=custbranch.area
						AND salesanalysis.salesperson=custbranch.salesman
					INNER JOIN stockmaster
					ON salesanalysis.stkcategory=stockmaster.categoryid
						AND salesanalysis.stockid=stockmaster.stockid
					WHERE typeabbrev ='" . $_SESSION['CreditItems' . $identifier]->DefaultSalesType . "'
					AND periodno='" . $PeriodNo . "'
					AND cust = '" . $_SESSION['CreditItems' . $identifier]->DebtorNo . "'
					AND custbranch = '" . $_SESSION['CreditItems' . $identifier]->Branch . "'
					AND salesanalysis.stockid = '" . $CreditLine->StockID . "'
					AND budgetoractual=1
					GROUP BY stkcategory,
							salesanalysis.area";

			$ErrMsg = _('The count to check for existing Sales analysis records could not run because');
			$DbgMsg = _('SQL to count the no of sales analysis records');

			$Result = DB_query($SQL, $db, $ErrMsg, $DbgMsg, true);

			$myrow = DB_fetch_row($Result);

			if ($myrow[0] > 0) {
				/*Update the existing record that already exists */

				if ($_POST['CreditType'] == 'ReverseOverCharge') {

					$SQL = "UPDATE salesanalysis
							SET amt=amt-" . $CreditLine->Price * $CreditLine->QtyDispatched / $_SESSION['CurrencyRate'] . ",
							disc=disc-" . ($CreditLine->DiscountPercent * $CreditLine->Price * $CreditLine->QtyDispatched / $_SESSION['CurrencyRate']) . "
							WHERE salesanalysis.area='" . $myrow[2] . "'
							AND salesanalysis.salesperson='" . $myrow[3] . "'
							AND typeabbrev ='" . $_SESSION['CreditItems' . $identifier]->DefaultSalesType . "'
							AND periodno = '" . $PeriodNo . "'
							AND cust = '" . $_SESSION['CreditItems' . $identifier]->DebtorNo . "'
							AND custbranch = '" . $_SESSION['CreditItems' . $identifier]->Branch . "'
							AND stockid = '" . $CreditLine->StockID . "'
							AND salesanalysis.stkcategory ='" . $myrow[1] . "'
							AND budgetoractual=1";

				} else {

					$SQL = "UPDATE salesanalysis
							SET amt=amt-" . $CreditLine->Price * $CreditLine->QtyDispatched / $_SESSION['CurrencyRate'] . ",
							cost=cost-" . $CreditLine->StandardCost * $CreditLine->QtyDispatched . ",
							qty=qty-" . $CreditLine->QtyDispatched . ",
							disc=disc-" . $CreditLine->DiscountPercent * $CreditLine->Price * $CreditLine->QtyDispatched / $_SESSION['CurrencyRate'] . "
							WHERE salesanalysis.area='" . $myrow[2] . "'
							AND salesanalysis.salesperson='" . $myrow[3] . "'
							AND typeabbrev ='" . $_SESSION['CreditItems' . $identifier]->DefaultSalesType . "'
							AND periodno = '" . $PeriodNo . "'
							AND cust = '" . $_SESSION['CreditItems' . $identifier]->DebtorNo . "'
							AND custbranch = '" . $_SESSION['CreditItems' . $identifier]->Branch . "'
							AND stockid = '" . $CreditLine->StockID . "'
							AND salesanalysis.stkcategory ='" . $myrow[1] . "'
							AND budgetoractual=1";
				}

			} else {
				/* insert a new sales analysis record */

				if ($_POST['CreditType'] == 'ReverseOverCharge') {

					$SQL = "INSERT INTO salesanalysis (typeabbrev,
														periodno,
														amt,
														cust,
														custbranch,
														qty,
														disc,
														stockid,
														area,
														budgetoractual,
														salesperson,
														stkcategory)
							SELECT '" . $_SESSION['CreditItems' . $identifier]->DefaultSalesType . "',
							'" . $PeriodNo . "',
							'" . -$CreditLine->Price * $CreditLine->QtyDispatched / $_SESSION['CurrencyRate'] . "',
							'" . $_SESSION['CreditItems' . $identifier]->DebtorNo . "',
							'" . $_SESSION['CreditItems' . $identifier]->Branch . "',
							0,
							'" . -$CreditLine->DiscountPercent * $CreditLine->Price * $CreditLine->QtyDispatched / $_SESSION['CurrencyRate'] . "',
							'" . $CreditLine->StockID . "',
							custbranch.area,
							1,
							custbranch.salesman,
							stockmaster.categoryid
							FROM stockmaster,
								custbranch
							WHERE stockmaster.stockid = '" . $CreditLine->StockID . "'
							AND custbranch.debtorno = '" . $_SESSION['CreditItems' . $identifier]->DebtorNo . "'
							AND custbranch.branchcode='" . $_SESSION['CreditItems' . $identifier]->Branch . "'";
				} else {

					$SQL = "INSERT INTO salesanalysis (typeabbrev,
								periodno,
								amt,
								cost,
								cust,
								custbranch,
								qty,
								disc,
								stockid,
								area,
								budgetoractual,
								salesperson,
								stkcategory)
						SELECT '" . $_SESSION['CreditItems' . $identifier]->DefaultSalesType . "',
							'" . $PeriodNo . "',
							'" . -$CreditLine->Price * $CreditLine->QtyDispatched / $_SESSION['CurrencyRate'] . "',
							'" . -$CreditLine->StandardCost * $CreditLine->QtyDispatched . "',
							'" . $_SESSION['CreditItems' . $identifier]->DebtorNo . "',
							'" . $_SESSION['CreditItems' . $identifier]->Branch . "',
							'" . -$CreditLine->QtyDispatched . "',
							'" . -$CreditLine->DiscountPercent * $CreditLine->Price * $CreditLine->QtyDispatched / $_SESSION['CurrencyRate'] . "',
							'" . $CreditLine->StockID . "',
							custbranch.area,
							1,
							custbranch.salesman,
							stockmaster.categoryid
						FROM stockmaster,
							custbranch
						WHERE stockmaster.stockid = '" . $CreditLine->StockID . "'
						AND custbranch.debtorno = '" . $_SESSION['CreditItems' . $identifier]->DebtorNo . "'
						AND custbranch.branchcode='" . $_SESSION['CreditItems' . $identifier]->Branch . "'";

				}
			}

			$ErrMsg = _('The sales analysis record for this credit note could not be added because');
			$DbgMsg = _('The following SQL to insert the sales analysis record was used');
			$Result = DB_query($SQL, $db, $ErrMsg, $DbgMsg, true);


			/* if GLLink_Stock then insert GLTrans to credit stock and debit cost of sales at standard cost*/

			if ($_SESSION['CompanyRecord']['gllink_stock'] == 1 and ($CreditLine->StandardCost != 0 or (isset($StandardCost) and $StandardCost != 0)) and $_POST['CreditType'] != 'ReverseOverCharge') {

				/*first the cost of sales entry*/

				$COGSAccount = GetCOGSGLAccount($Area, $CreditLine->StockID, $_SESSION['CreditItems' . $identifier]->DefaultSalesType, $db);

				$SQL = "INSERT INTO gltrans (type,
											typeno,
											trandate,
											periodno,
											account,
											narrative,
											amount)
						VALUES (11,
							'" . $CreditNo . "',
							'" . $DefaultDispatchDate . "',
							'" . $PeriodNo . "',
							'" . $COGSAccount . "',
							'" . $_SESSION['CreditItems' . $identifier]->DebtorNo . " - " . $CreditLine->StockID . " x " . $CreditLine->QtyDispatched . " @ " . $CreditLine->StandardCost . "',
							'" . -round($CreditLine->StandardCost * $CreditLine->QtyDispatched, $_SESSION['CompanyRecord']['decimalplaces']) . "'
							)";

				$ErrMsg = _('CRITICAL ERROR') . '! ' . _('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . _('The cost of sales GL posting could not be inserted because');
				$DbgMsg = _('The following SQL to insert the GLTrans record was used');
				$Result = DB_query($SQL, $db, $ErrMsg, $DbgMsg, true);

				/*now the stock entry*/


				if ($_POST['CreditType'] == 'WriteOff') {
					$SQL = "INSERT INTO gltrans (type,
												typeno,
												trandate,
												periodno,
												account,
												narrative,
												amount)
								VALUES (11,
									'" . $CreditNo . "',
									'" . $DefaultDispatchDate . "',
									'" . $PeriodNo . "',
									'" . $_POST['WriteOffGLCode'] . "',
									'" . $_SESSION['CreditItems' . $identifier]->DebtorNo . " - " . $CreditLine->StockID . " x " . $CreditLine->QtyDispatched . " @ " . $CreditLine->StandardCost . "',
									'" . round($CreditLine->StandardCost * $CreditLine->QtyDispatched, $_SESSION['CompanyRecord']['decimalplaces']) . "')";
				} else {
					$StockGLCode = GetStockGLCode($CreditLine->StockID, $db);
					$SQL = "INSERT INTO gltrans (type,
												typeno,
												trandate,
												periodno,
												account,
												narrative,
												amount)
								VALUES (11,
									'" . $CreditNo . "',
									'" . $DefaultDispatchDate . "',
									'" . $PeriodNo . "',
									'" . $StockGLCode['stockact'] . "',
									'" . $_SESSION['CreditItems' . $identifier]->DebtorNo . " - " . $CreditLine->StockID . " x " . $CreditLine->QtyDispatched . " @ " . $CreditLine->StandardCost . "',
									'" . round($CreditLine->StandardCost * $CreditLine->QtyDispatched, $_SESSION['CompanyRecord']['decimalplaces']) . "')";
				}

				$ErrMsg = _('CRITICAL ERROR') . '! ' . _('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . _('The stock side or write off of the cost of sales GL posting could not be inserted because');
				$DbgMsg = _('The following SQL to insert the GLTrans record was used');
				$Result = DB_query($SQL, $db, $ErrMsg, $DbgMsg, true);

			}
			/* end of if GL and stock integrated and standard cost !=0 */

			if ($_SESSION['CompanyRecord']['gllink_debtors'] == 1 and $CreditLine->Price != 0) {

				//Post sales transaction to GL credit sales
				$SalesGLAccounts = GetSalesGLAccount($Area, $CreditLine->StockID, $_SESSION['CreditItems' . $identifier]->DefaultSalesType, $db);

				$SQL = "INSERT INTO gltrans (type,
							typeno,
							trandate,
							periodno,
							account,
							narrative,
							amount)
					VALUES (11,
						'" . $CreditNo . "',
						'" . $DefaultDispatchDate . "',
						'" . $PeriodNo . "',
						'" . $SalesGLAccounts['salesglcode'] . "',
						'" . $_SESSION['CreditItems' . $identifier]->DebtorNo . " - " . $CreditLine->StockID . " x " . $CreditLine->QtyDispatched . " @ " . $CreditLine->Price . "',
						'" . round($CreditLine->Price * $CreditLine->QtyDispatched / $_SESSION['CurrencyRate'], $_SESSION['CompanyRecord']['decimalplaces']) . "'
						)";

				$ErrMsg = _('CRITICAL ERROR') . '! ' . _('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . _('The credit note GL posting could not be inserted because');
				$DbgMsg = _('The following SQL to insert the GLTrans record was used');
				$Result = DB_query($SQL, $db, $ErrMsg, $DbgMsg, true);

				if ($CreditLine->DiscountPercent != 0) {

					$SQL = "INSERT INTO gltrans (type,
								typeno,
								trandate,
								periodno,
								account,
								narrative,
								amount)
						VALUES (11,
							'" . $CreditNo . "',
							'" . $DefaultDispatchDate . "',
							'" . $PeriodNo . "',
							'" . $SalesGLAccounts['discountglcode'] . "',
							'" . $_SESSION['CreditItems' . $identifier]->DebtorNo . " - " . $CreditLine->StockID . " @ " . ($CreditLine->DiscountPercent * 100) . "%',
							'" . -round($CreditLine->Price * $CreditLine->QtyDispatched * $CreditLine->DiscountPercent / $_SESSION['CurrencyRate'], $_SESSION['CompanyRecord']['decimalplaces']) . "'
							)";
					$ErrMsg = _('CRITICAL ERROR') . '! ' . _('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . _('The credit note discount GL posting could not be inserted because');
					$DbgMsg = _('The following SQL to insert the GLTrans record was used');
					$Result = DB_query($SQL, $db, $ErrMsg, $DbgMsg, true);
				}
				/*end of if discount !=0 */
			}
			/*end of if sales integrated with debtors */
		}
		/*Quantity dispatched is more than 0 */
	}
	/*end of OrderLine loop */


	if ($_SESSION['CompanyRecord']['gllink_debtors'] == 1) {

		/*Post credit note transaction to GL credit debtors, debit freight re-charged and debit sales */
		if (($_SESSION['CreditItems' . $identifier]->total + $_SESSION['CreditItems' . $identifier]->FreightCost + $TaxTotal) != 0) {
			$SQL = "INSERT INTO gltrans (type,
							typeno,
							trandate,
							periodno,
							account,
							narrative,
							amount)
					VALUES (11,
						'" . $CreditNo . "',
						'" . $DefaultDispatchDate . "',
						'" . $PeriodNo . "',
						'" . $_SESSION['CompanyRecord']['debtorsact'] . "',
						'" . $_SESSION['CreditItems' . $identifier]->DebtorNo . "',
						'" . -round(($_SESSION['CreditItems' . $identifier]->total + $_SESSION['CreditItems' . $identifier]->FreightCost + $TaxTotal) / $_SESSION['CurrencyRate'], $_SESSION['CompanyRecord']['decimalplaces']) . "'
					)";

			$ErrMsg = _('CRITICAL ERROR') . '! ' . _('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . _('The total debtor GL posting for the credit note could not be inserted because');
			$DbgMsg = _('The following SQL to insert the GLTrans record was used');
			$Result = DB_query($SQL, $db, $ErrMsg, $DbgMsg, true);
		}


		/*Could do with setting up a more flexible freight posting schema that looks at the sales type and area of the customer branch to determine where to post the freight recovery */


		if (round($_SESSION['CreditItems' . $identifier]->FreightCost, $_SESSION['CreditItems' . $identifier]->CurrDecimalPlaces) != 0) {
			$SQL = "INSERT INTO gltrans (type,
							typeno,
							trandate,
							periodno,
							account,
							narrative,
							amount)
				VALUES (11,
					'" . $CreditNo . "',
					'" . $DefaultDispatchDate . "',
					'" . $PeriodNo . "',
					'" . $_SESSION['CompanyRecord']['freightact'] . "',
					'" . $_SESSION['CreditItems' . $identifier]->DebtorNo . "',
					'" . round($_SESSION['CreditItems' . $identifier]->FreightCost / $_SESSION['CurrencyRate'], $_SESSION['CompanyRecord']['decimalplaces']) . "'
					)";

			$ErrMsg = _('CRITICAL ERROR') . '! ' . _('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . _('The freight GL posting for this credit note could not be inserted because');
			$DbgMsg = _('The following SQL to insert the GLTrans record was used');
			$Result = DB_query($SQL, $db, $ErrMsg, $DbgMsg, true);
		}

		foreach ($TaxTotals as $TaxAuthID => $TaxAmount) {
			if ($TaxAmount != 0) {
				$SQL = "INSERT INTO gltrans (
						type,
						typeno,
						trandate,
						periodno,
						account,
						narrative,
						amount
						)
					VALUES (
						11,
						'" . $CreditNo . "',
						'" . $DefaultDispatchDate . "',
						'" . $PeriodNo . "',
						'" . $TaxGLCodes[$TaxAuthID] . "',
						'" . $_SESSION['CreditItems' . $identifier]->DebtorNo . "',
						'" . $TaxAmount / $_SESSION['CurrencyRate'] . "'
					)";

				$ErrMsg = _('CRITICAL ERROR') . '! ' . _('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . _('The tax GL posting could not be inserted because');
				$DbgMsg = _('The following SQL to insert the GLTrans record was used');
				$Result = DB_query($SQL, $db, $ErrMsg, $DbgMsg, true);
			}
		}

		EnsureGLEntriesBalance(11, $CreditNo, $db);

	}
	/*end of if Sales and GL integrated */

	$Result = DB_Txn_Commit($db);

	unset($_SESSION['CreditItems' . $identifier]->LineItems);
	unset($_SESSION['CreditItems' . $identifier]);
	unset($_SESSION['ProcessingCredit']);

	echo '<div class="centre">' . _('Credit Note number') . ' ' . $CreditNo . ' ' . _('has been processed');
	if ($_SESSION['InvoicePortraitFormat'] == 0) {
		echo '<br /><a href="' . $RootPath . '/PrintCustTrans.php?FromTransNo=' . $CreditNo . '&InvOrCredit=Credit&PrintPDF=True">' . _('Print this credit note') . '</a>';
	} else {
		echo '<br /><a href="' . $RootPath . '/PrintCustTransPortrait.php?FromTransNo=' . $CreditNo . '&InvOrCredit=Credit&PrintPDF=True">' . _('Print this credit note') . '</a>';
	}
	echo '</div>';
	/*end of process credit note */


} else {
	/*Process Credit NOT set so allow inputs to set up the credit note */

	echo '<br /><table class="selection">';

	echo '<tr><td>' . _('Credit Note Type') . '</td>
			<td><select minlength="0" tabindex="' . $j . '" name="CreditType">';

	if (!isset($_POST['CreditType']) or $_POST['CreditType'] == 'Return') {
		echo '<option selected="selected" value="Return">' . _('Goods returned to store') . '</option>';
		echo '<option value="WriteOff">' . _('Goods written off') . '</option>';
		echo '<option value="ReverseOverCharge">' . _('Reverse overcharge') . '</option>';
	} elseif ($_POST['CreditType'] == 'WriteOff') {
		echo '<option selected="selected" value="WriteOff">' . _('Goods written off') . '</option>';
		echo '<option value="Return">' . _('Goods returned to store') . '</option>';
		echo '<option value="ReverseOverCharge">' . _('Reverse overcharge') . '</option>';
	} else {
		echo '<option value="WriteOff">' . _('Goods written off') . '</option>';
		echo '<option value="Return">' . _('Goods returned to store') . '</option>';
		echo '<option selected="selected" value="ReverseOverCharge">' . _('Reverse overcharge') . '</option>';
	}
	echo '</select></td></tr>';
	$j++;

	if (!isset($_POST['CreditType']) or $_POST['CreditType'] == 'Return') {

		/*if the credit note is a return of goods then need to know which location to receive them into */

		echo '<tr><td>' . _('Goods returned to location') . '</td><td><select minlength="0" tabindex="' . $j . '" name="Location">';

		if ($_SESSION['RestrictLocations'] == 0) {
			$sql = "SELECT locationname,
							loccode
						FROM locations";
		} else {
			$sql = "SELECT locationname,
							loccode
						FROM locations
						INNER JOIN www_users
							ON locations.loccode=www_users.defaultlocation
						WHERE www_users.userid='" . $_SESSION['UserID'] . "'";
		}
		$Result = DB_query($sql, $db);

		if (!isset($_POST['Location'])) {
			$_POST['Location'] = $_SESSION['CreditItems' . $identifier]->Location;
		}
		while ($myrow = DB_fetch_array($Result)) {

			if ($_POST['Location'] == $myrow['loccode']) {
				echo '<option selected="selected" value="' . $myrow['loccode'] . '">' . $myrow['locationname'] . '</option>';
			} else {
				echo '<option value="' . $myrow['loccode'] . '">' . $myrow['locationname'] . '</option>';
			}
		}
		echo '</select></td></tr>';
		$j++;

	} elseif ($_POST['CreditType'] == 'WriteOff') {
		/* the goods are to be written off to somewhere */

		echo '<tr><td>' . _('Write off the cost of the goods to') . '</td><td><select minlength="0" tabindex="' . $j . '" name="WriteOffGLCode">';

		$SQL = "SELECT accountcode,
					accountname
				FROM chartmaster INNER JOIN accountgroups
				ON chartmaster.group_=accountgroups.groupname
				WHERE accountgroups.pandl=1
				ORDER BY chartmaster.accountcode";

		$Result = DB_query($SQL, $db);

		while ($myrow = DB_fetch_array($Result)) {

			if ($_POST['WriteOffGLCode'] == $myrow['accountcode']) {
				echo '<option selected="selected" value="' . $myrow['accountcode'] . '">' . $myrow['accountname'] . '</option>';
			} else {
				echo '<option value="' . $myrow['accountcode'] . '">' . $myrow['accountname'] . '</option>';
			}
		}
		echo '</select></td></tr>';
	}
	$j++;
	echo '<tr>
			<td>' . _('Sales person') . ':</td>
			<td><select minlength="0" tabindex="' . $j . '" name="SalesPerson">';
	$SalesPeopleResult = DB_query("SELECT salesmancode, salesmanname FROM salesman WHERE current=1", $db);
	/* SalesPerson will be set because it is an invoice being credited and the order salesperson would/should have been retrieved */
	while ($SalesPersonRow = DB_fetch_array($SalesPeopleResult)) {
		if ($SalesPersonRow['salesmancode'] == $_SESSION['CreditItems' . $identifier]->SalesPerson) {
			echo '<option selected="selected" value="' . $SalesPersonRow['salesmancode'] . '">' . $SalesPersonRow['salesmanname'] . '</option>';
		} else {
			echo '<option value="' . $SalesPersonRow['salesmancode'] . '">' . $SalesPersonRow['salesmanname'] . '</option>';
		}
	}

	echo '</select></td>
		</tr>';
	if (!isset($_POST['CreditText'])) {
		$_POST['CreditText'] = '';
	}
	echo '<tr>
			<td>' . _('Credit note text') . '</td>
			<td><textarea tabindex="' . $j . '"  name="CreditText" cols="31" rows="5">' . $_POST['CreditText'] . '</textarea></td>
		</tr>
		</table>
		<br />
		<div class="centre">
			<input tabindex="' . $j . '" type="submit" name="Update" value="' . _('Update') . '" />
			<br />';
	$j++;
	echo '<input type="submit" tabindex="' . $j++ . '" name="ProcessCredit" value="' . _('Process Credit') . '" />
		</div>';
}
echo '</div>';
echo '</form>';
include('includes/footer.inc');
?>