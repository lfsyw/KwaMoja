<?php

$PricesSecurity = 1000;
include('includes/session.inc');
$Title = _('Search Outstanding Sales Orders');
/* Manual links before header.inc */
$ViewTopic = 'SalesOrders';
$BookMark = 'SelectSalesOrder';
include('includes/header.inc');
include('includes/SQL_CommonFunctions.inc');

if (isset($_GET['SelectedStockItem'])) {
	$SelectedStockItem = $_GET['SelectedStockItem'];
} elseif (isset($_POST['SelectedStockItem'])) {
	$SelectedStockItem = $_POST['SelectedStockItem'];
} else {
	unset($SelectedStockItem);
}

if (isset($_GET['SelectedCustomer'])) {
	$SelectedCustomer = $_GET['SelectedCustomer'];
} elseif (isset($_POST['SelectedCustomer'])) {
	$SelectedCustomer = $_POST['SelectedCustomer'];
} else {
	unset($SelectedCustomer);
}

if (isset($_POST['PlacePO'])) {
	/*user hit button to place PO for selected orders */

	/*Note the button would not have been displayed if the user had no authority to create purchase orders */
	$OrdersToPlacePOFor = '';
	for ($i = 1; $i < count($_POST); $i++) {
		if (isset($_POST['PlacePO_' . $i])) { //checkboxes only set if they are checked
			if ($OrdersToPlacePOFor == '') {
				$OrdersToPlacePOFor .= " orderno='" . $_POST['OrderNo_PO_' . $i] . "'";
			} else {
				$OrdersToPlacePOFor .= " OR orderno='" . $_POST['OrderNo_PO_' . $i] . "'";
			}
		}
	}
	if (mb_strlen($OrdersToPlacePOFor) == '') {
		prnMsg(_('There were no sales orders checked to place purchase orders for. No purchase orders will be created.'), 'info');
	} else {
		/*  Now build SQL of items to purchase with purchasing data and preferred suppliers - sorted by preferred supplier */
		$SQL = "SELECT purchdata.supplierno,
						purchdata.stockid,
						purchdata.price,
						purchdata.suppliers_partno,
						purchdata.supplierdescription,
						purchdata.conversionfactor,
						purchdata.leadtime,
						purchdata.suppliersuom,
						stockmaster.grossweight,
						stockmaster.volume,
						stockcategory.stockact,
						SUM(salesorderdetails.quantity-salesorderdetails.qtyinvoiced) AS orderqty
				FROM purchdata
				INNER JOIN salesorderdetails
					ON purchdata.stockid = salesorderdetails.stkcode
				INNER JOIN stockmaster
					ON purchdata.stockid = stockmaster.stockid
				INNER JOIN stockcategory
					ON stockmaster.categoryid = stockcategory.categoryid
				WHERE purchdata.preferred=1
					AND purchdata.effectivefrom <=CURRENT_DATE
					AND (" . $OrdersToPlacePOFor . ")
				GROUP BY purchdata.supplierno,
						purchdata.stockid,
						purchdata.price,
						purchdata.suppliers_partno,
						purchdata.supplierdescription,
						purchdata.conversionfactor,
						purchdata.leadtime,
						purchdata.suppliersuom,
						stockmaster.grossweight,
						stockmaster.volume,
						stockcategory.stockact
				ORDER BY purchdata.supplierno,
						purchdata.stockid";

		$ErrMsg = _('Unable to retrieve the items on the selected orders for creating purchase orders for');
		$ItemResult = DB_query($SQL, $ErrMsg);

		$ItemArray = array();

		while ($MyRow = DB_fetch_array($ItemResult)) {
			$ItemArray[$MyRow['stockid']] = $MyRow;
		}

		/* Now figure out if there are any components of Assembly items that  need to be ordered too */
		$SQL = "SELECT purchdata.supplierno,
						purchdata.stockid,
						purchdata.price,
						purchdata.suppliers_partno,
						purchdata.supplierdescription,
						purchdata.conversionfactor,
						purchdata.leadtime,
						purchdata.suppliersuom,
						stockmaster.grossweight,
						stockmaster.volume,
						stockcategory.stockact,
						SUM(bom.quantity *(salesorderdetails.quantity-salesorderdetails.qtyinvoiced)) AS orderqty
				FROM purchdata
				INNER JOIN bom
					ON purchdata.stockid=bom.component
				INNER JOIN salesorderdetails
					ON bom.parent=salesorderdetails.stkcode
				INNER JOIN stockmaster
					ON purchdata.stockid = stockmaster.stockid
				INNER JOIN stockmaster AS stockmaster2
					ON stockmaster2.stockid=salesorderdetails.stkcode
				INNER JOIN stockcategory
					ON stockmaster.categoryid = stockcategory.categoryid
				WHERE purchdata.preferred=1
					AND stockmaster2.mbflag='A'
					AND bom.loccode ='" . $_SESSION['UserStockLocation'] . "'
					AND purchdata.effectivefrom <=CURRENT_DATE
					AND bom.effectiveafter <=CURRENT_DATE
					AND bom.effectiveto > CURRENT_DATE
					AND (" . $OrdersToPlacePOFor . ")
				GROUP BY purchdata.supplierno,
						purchdata.stockid,
						purchdata.price,
						purchdata.suppliers_partno,
						purchdata.supplierdescription,
						purchdata.conversionfactor,
						purchdata.leadtime,
						purchdata.suppliersuom,
						stockmaster.grossweight,
						stockmaster.volume,
						stockcategory.stockact
				ORDER BY purchdata.supplierno,
						purchdata.stockid";
		$ErrMsg = _('Unable to retrieve the items on the selected orders for creating purchase orders for');
		$ItemResult = DB_query($SQL, $ErrMsg);

		/* add any assembly item components from salesorders to the ItemArray */
		while ($MyRow = DB_fetch_array($ItemResult)) {
			if (isset($ItemArray[$MyRow['stockid']])) {
				/* if the item is already in the ItemArray then just add the quantity to the existing item */
				$ItemArray[$MyRow['stockid']]['orderqty'] += $MyRow['orderqty'];
			} else {
				/*it is not already in the ItemArray so add it */
				$ItemArray[$MyRow['stockid']] = $MyRow;
			}
		}


		/* We need the items to order to be in supplier order so that only a single order is created for a supplier - so need to sort the multi-dimensional array to ensure it is listed by supplier sequence. To use array_multisort we need to get arrays of supplier with the same keys as the main array of rows
		 */
		foreach ($ItemArray as $Key => $row) {
			//to make the Supplier array with the keys of the $ItemArray
			$SupplierArray[$Key] = $row['supplierno'];
		}

		/* Use array_multisort to Sort the ItemArray with supplierno ascending
		Add $ItemArray as the last parameter, to sort by the common key
		*/
		array_multisort($SupplierArray, SORT_ASC, $ItemArray);

		if (count($ItemArray) == 0) {
			prnMsg(_('There might be no supplier purchasing data set up for any items on the selected sales order(s). No purchase orders have been created'), 'warn');
		} else {
			/*Now get the default delivery address details from the users default stock location */
			$SQL = "SELECT locationname,
							deladd1,
							deladd2,
							deladd3,
							deladd4,
							deladd5,
							deladd6,
							tel,
							contact
						FROM locations
						INNER JOIN locationusers
							ON locationusers.loccode=locations.loccode
							AND locationusers.userid='" . $_SESSION['UserID'] . "'
							AND locationusers.canupd=1
						WHERE locations.loccode = '" . $_SESSION['UserStockLocation'] . "'";
			$ErrMsg = _('The delivery address for the order could not be obtained from the user default stock location');
			$DelAddResult = DB_query($SQL, $ErrMsg);
			$DelAddRow = DB_fetch_array($DelAddResult);

			$SupplierID = '';

			if (IsEmailAddress($_SESSION['UserEmail'])) {
				$UserDetails = ' <a href="mailto:' . $_SESSION['UserEmail'] . '">' . $_SESSION['UsersRealName'] . '</a>';
			} else {
				$UserDetails = ' ' . $_SESSION['UsersRealName'] . ' ';
			}

			foreach ($ItemArray as $ItemRow) {

				if ($SupplierID != $ItemRow['supplierno']) {
					/* This order item is purchased from a different supplier so need to finish off the authorisation of the previous order and start a new order */

					if ($SupplierID != '' and $_SESSION['AutoAuthorisePO'] == 1) {
						/* if an order is/has been created already and the supplier of this item has changed - so need to finish off the order */
						//if the user has authority to authorise the PO then it should be created as authorised
						$AuthSQL = "SELECT authlevel
					 				FROM purchorderauth
								    WHERE userid='" . $_SESSION['UserID'] . "'
									AND currabrev='" . $SuppRow['currcode'] . "'";

						$AuthResult = DB_query($AuthSQL);
						$AuthRow = DB_fetch_array($AuthResult);
						if ($AuthRow['authlevel'] = '') {
							$AuthRow['authlevel'] = 0;
						}

						if (DB_num_rows($AuthResult) > 0 and $AuthRow['authlevel'] > $Order_Value) { //user has authority to authrorise as well as create the order
							$StatusComment = date($_SESSION['DefaultDateFormat']) . ' - ' . _('Order Created and Authorised by') . ' ' . $UserDetails . ' - ' . _('Auto created from sales orders') . '<br />';
							$ErrMsg = _('Could not update purchase order status to Authorised');
							$DbgMsg = _('The SQL that failed was');
							$Result = DB_query("UPDATE purchorders SET allowprint=1,
												   status='Authorised',
												   stat_comment='" . $StatusComment . "'
												WHERE orderno='" . $PO_OrderNo . "'", $ErrMsg, $DbgMsg, true);
						} else { // no authority to authorise this order
							if (DB_num_rows($AuthResult) == 0) {
								$AuthMessage = _('Your authority to approve purchase orders in') . ' ' . $SuppRow['currcode'] . ' ' . _('has not yet been set up') . '<br />';
							} else {
								$AuthMessage = _('You can only authorise up to') . ' ' . $SuppRow['currcode'] . ' ' . $AuthRow['authlevel'] . '.<br />';
							}

							prnMsg(_('You do not have permission to authorise this purchase order') . '.<br />' . _('This order is for') . ' ' . $SuppRow['currcode'] . ' ' . $Order_Value . '. ' . $AuthMessage . _('if you think this is a mistake please contact the systems administrator') . '<br />' . _('The order has been created with a status of pending and will require authorisation'), 'warn');
						}
					} //end of authorisation status settings

					if ($SupplierID != '') { //then we have just added a purchase order
						echo '<br />';
						prnMsg(_('Purchase Order') . ' ' . $PO_OrderNo . ' ' . _('on') . ' ' . $SupplierID . ' ' . _('has been created'), 'success');
						DB_Txn_Commit();
					}

					/*Starting a new purchase order with a different supplier */
					$Result = DB_Txn_Begin();

					$PO_OrderNo = GetNextTransNo(18); //get the next PO number

					$SupplierID = $ItemRow['supplierno'];
					$Order_Value = 0;
					/*Now get all the required details for the supplier */
					$SQL = "SELECT address1,
        							address2,
        							address3,
        							address4,
        							address5,
        							address6,
        							telephone,
        							paymentterms,
        							currcode,
        							rate
					        FROM suppliers INNER JOIN currencies
						    ON suppliers.currcode = currencies.currabrev
						    WHERE supplierid='" . $SupplierID . "'";

					$ErrMsg = _('Could not get the supplier information for the order');
					$SuppResult = DB_query($SQL, $ErrMsg);
					$SuppRow = DB_fetch_array($SuppResult);

					$StatusComment = date($_SESSION['DefaultDateFormat']) . ' - ' . _('Order Created by') . ' ' . $UserDetails . ' - ' . _('Auto created from sales orders') . '<br />';
					/*Insert to purchase order header record */
					$SQL = "INSERT INTO purchorders ( orderno,
                									  supplierno,
                									  orddate,
                									  rate,
                									  initiator,
                									  intostocklocation,
                									  deladd1,
                									  deladd2,
                									  deladd3,
                									  deladd4,
                									  deladd5,
                									  deladd6,
                									  tel,
                									  suppdeladdress1,
                									  suppdeladdress2,
                									  suppdeladdress3,
                									  suppdeladdress4,
                									  suppdeladdress5,
                									  suppdeladdress6,
                									  supptel,
                									  version,
                									  revised,
                									  deliveryby,
                									  status,
                									  stat_comment,
                									  deliverydate,
                									  paymentterms,
                									  allowprint)
                									VALUES(	'" . $PO_OrderNo . "',
                										'" . $SupplierID . "',
                										CURRENT_DATE,
                										'" . $SuppRow['rate'] . "',
                										'" . $_SESSION['UserID'] . "',
                										'" . $_SESSION['UserStockLocation'] . "',
                										'" . $DelAddRow['locationname'] . "',
                										'" . $DelAddRow['deladd1'] . "',
                										'" . $DelAddRow['deladd2'] . "',
                										'" . $DelAddRow['deladd3'] . "',
                										'" . $DelAddRow['deladd4'] . "',
                										'" . $DelAddRow['deladd5'] . ' ' . $DelAddRow['deladd6'] . "',
                										'" . $DelAddRow['tel'] . "',
                										'" . $SuppRow['address1'] . "',
                										'" . $SuppRow['address2'] . "',
                										'" . $SuppRow['address3'] . "',
                										'" . $SuppRow['address4'] . "',
                										'" . $SuppRow['address5'] . "',
                										'" . $SuppRow['address6'] . "',
                										'" . $SuppRow['telephone'] . "',
                										'1.0',
                										CURRENT_DATE,
                										'" . $_SESSION['Default_Shipper'] . "',
                										'Pending',
                										'" . $StatusComment . "',
                										CURRENT_DATE,
                										'" . $SuppRow['paymentterms'] . "',
                										0)";

					$ErrMsg = _('The purchase order header record could not be inserted into the database because');
					$DbgMsg = _('The SQL statement used to insert the purchase order header record and failed was');
					$Result = DB_query($SQL, $ErrMsg, $DbgMsg, true);
				} //end if it's a new supplier and PO to create

				/*reminder we are in a loop of the total of each item to place a purchase order for based on a selection of sales orders */
				$DeliveryDate = DateAdd(Date($_SESSION['DefaultDateFormat']), 'd', $ItemRow['leadtime']);
				$SQL = "INSERT INTO purchorderdetails ( orderno,
                    									itemcode,
                    									deliverydate,
                    									itemdescription,
                    									glcode,
                    									unitprice,
                    									quantityord,
                    									suppliersunit,
                    									suppliers_partno,
                    									conversionfactor )
                                         VALUES ('" . $PO_OrderNo . "',
                    						     '" . $ItemRow['stockid'] . "',
                    						     '" . FormatDateForSQL($DeliveryDate) . "',
                    						     '" . $ItemRow['suppliers_partno'] . '  ' . $ItemRow['supplierdescription'] . "',
                    						     '" . $ItemRow['stockact'] . "',
                    						     '" . $ItemRow['price'] . "',
                    						     '" . $ItemRow['orderqty'] . "',
                    						     '" . $ItemRow['suppliersuom'] . "',
                    						     '" . $ItemRow['suppliers_partno'] . "',
                    						     '" . $ItemRow['conversionfactor'] . "')";
				$ErrMsg = _('One of the purchase order detail records could not be inserted into the database because');
				$DbgMsg = _('The SQL statement used to insert the purchase order detail record and failed was');

				$Result = DB_query($SQL, $ErrMsg, $DbgMsg, true);
				$Order_Value += ($ItemRow['price'] * $ItemRow['orderqty']);
			}
			/* end of the loop round the items on the sales order  that we wish to place purchase orders for */


			/* The last line to be purchase ordered was reach so there will be an order which is not yet completed in progress now to completed it */

			if ($SupplierID != '' and $_SESSION['AutoAuthorisePO'] == 1) {
				//if the user has authority to authorise the PO then it should be created as authorised
				$AuthSQL = "SELECT authlevel
							FROM purchorderauth
							WHERE userid='" . $_SESSION['UserID'] . "'
							AND currabrev='" . $SuppRow['currcode'] . "'";

				$AuthResult = DB_query($AuthSQL);
				$AuthRow = DB_fetch_array($AuthResult);
				if ($AuthRow['authlevel'] = '') {
					$AuthRow['authlevel'] = 0;
				}

				if (DB_num_rows($AuthResult) > 0 and $AuthRow['authlevel'] > $Order_Value) { //user has authority to authrorise as well as create the order
					$StatusComment = date($_SESSION['DefaultDateFormat']) . ' - ' . _('Order Created and Authorised by') . $UserDetails . ' - ' . _('Auto created from sales orders') . '<br />';
					$ErrMsg = _('Could not update purchase order status to Authorised');
					$DbgMsg = _('The SQL that failed was');
					$Result = DB_query("UPDATE purchorders SET allowprint=1,
															status='Authorised',
															stat_comment='" . $StatusComment . "'
									                      WHERE orderno='" . $PO_OrderNo . "'", $ErrMsg, $DbgMsg, true);
				} else { // no authority to authorise this order
					if (DB_num_rows($AuthResult) == 0) {
						$AuthMessage = _('Your authority to approve purchase orders in') . ' ' . $SuppRow['currcode'] . ' ' . _('has not yet been set up') . '<br />';
					} else {
						$AuthMessage = _('You can only authorise up to') . ' ' . $SuppRow['currcode'] . ' ' . $AuthRow['authlevel'] . '.<br />';
					}

					prnMsg(_('You do not have permission to authorise this purchase order') . '.<br />' . _('This order is for') . ' ' . $SuppRow['currcode'] . ' ' . $Order_Value . '. ' . $AuthMessage . _('if you think this is a mistake please contact the systems administrator') . '<br />' . _('The order has been created with a status of pending and will require authorisation'), 'warn');
				}
			} //end of authorisation status settings

			if ($SupplierID != '') { //then we have just added a purchase order irrespective of autoauthorise status
				echo '<br />';
				prnMsg(_('Purchase Order') . ' ' . $PO_OrderNo . ' ' . _('on') . ' ' . $SupplierID . ' ' . _('has been created'), 'success');
				DB_Txn_Commit();
			}
			$Result = DB_query("UPDATE salesorders SET poplaced=1 WHERE " . $OrdersToPlacePOFor);
		}
		/*There were items that had purchasing data set up to create POs for */
	}
	/* there were sales orders checked to place POs for */
}
/*end of purchase order creation code */
/* ******************************************************************************************* */

/*To the sales order selection form */

echo '<p class="page_title_text">
		<img src="', $RootPath, '/css/', $_SESSION['Theme'], '/images/sales.png" title="', _('Sales'), '" alt="" />', '&nbsp;', _('Outstanding Sales Orders'), '
	</p> ';

echo '<form action="', htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'), '" method="post">';
echo '<input type="hidden" name="FormID" value="', $_SESSION['FormID'], '" />';

if (isset($_POST['ResetPart'])) {
	unset($SelectedStockItem);
}

if (isset($_GET['OrderNumber'])) {
	$OrderNumber = $_GET['OrderNumber'];
} elseif (isset($_POST['OrderNumber'])) {
	$OrderNumber = $_POST['OrderNumber'];
} else {
	unset($OrderNumber);
}

if (isset($OrderNumber) and $OrderNumber != '') {
	$OrderNumber = trim($OrderNumber);
	if (!is_numeric($OrderNumber)) {
		prnMsg(_('The Order Number entered MUST be numeric'), 'error');
		unset($OrderNumber);
		include('includes/footer.inc');
		exit;
	} else {
		echo _('Order Number'), '&nbsp;-&nbsp;', $OrderNumber;
	}
} else {
	if (isset($SelectedCustomer)) {
		echo _('For customer'), ':&nbsp;', $SelectedCustomer, '&nbsp;', _('and'), '&nbsp;';
		echo '<input type="hidden" name="SelectedCustomer" value="', $SelectedCustomer, '" />';
	}
	if (isset($SelectedStockItem)) {
		echo _('for the part'), ':&nbsp;', $SelectedStockItem, '&nbsp;', _('and'), '&nbsp;<input type="hidden" name="SelectedStockItem" value="', $SelectedStockItem, '" />';
	}
}

if (isset($_POST['SearchParts'])) {

	if ($_POST['Keywords'] and $_POST['StockCode']) {
		echo _('Stock description keywords have been used in preference to the Stock code extract entered');
	}
	$SearchString = '%' . str_replace(' ', '%', $_POST['Keywords']) . '%';

	$SQL = "SELECT stockmaster.stockid,
				stockmaster.description,
				stockmaster.decimalplaces,
				SUM(locstock.quantity) AS qoh,
				stockmaster.units
			FROM stockmaster
			INNER JOIN locstock
				ON stockmaster.stockid=locstock.stockid
			WHERE stockmaster.description " . LIKE . " '" . $SearchString . "'
			AND stockmaster.stockid " . LIKE . " '%" . $_POST['StockCode'] . "%'
			AND stockmaster.categoryid='" . $_POST['StockCat'] . "'
			GROUP BY stockmaster.stockid,
				stockmaster.description,
				stockmaster.decimalplaces,
				stockmaster.units
			ORDER BY stockmaster.stockid";


	$ErrMsg = _('No stock items were returned by the SQL because');
	$DbgMsg = _('The SQL used to retrieve the searched parts was');
	$StockItemsResult = DB_query($SQL, $ErrMsg, $DbgMsg);

}

if (isset($_POST['StockID'])) {
	$StockId = trim(mb_strtoupper($_POST['StockID']));
} elseif (isset($_GET['StockID'])) {
	$StockId = trim(mb_strtoupper($_GET['StockID']));
}

if (!isset($StockId)) {

	/* Not appropriate really to restrict search by date since may miss older
	ouststanding orders
	$OrdersAfterDate = Date('d/m/Y',Mktime(0,0,0,Date('m')-2,Date('d'),Date('Y')));
	*/

	if (!isset($OrderNumber) or $OrderNumber == '') {

		echo '<table class="selection">
				<tr>
					<td>', _('Order number'), ': </td>
					<td><input type="text" class="integer" name="OrderNumber" maxlength="8" size="9" /></td>
					<td>', _('From Stock Location'), ':</td>
					<td>
						<select name="StockLocation"> ';

		$SQL = "SELECT locationname,
						locations.loccode
					FROM locations
					INNER JOIN locationusers
						ON locationusers.loccode=locations.loccode
						AND locationusers.userid='" . $_SESSION['UserID'] . "'
						AND locationusers.canview=1";
		$ResultStkLocs = DB_query($SQL);

		while ($MyRow = DB_fetch_array($ResultStkLocs)) {
			if (isset($_POST['StockLocation'])) {
				if ($MyRow['loccode'] == $_POST['StockLocation']) {
					echo '<option selected="selected" value="', $MyRow['loccode'], '">', $MyRow['locationname'], '</option>';
				} else {
					echo '<option value="', $MyRow['loccode'], '">', $MyRow['locationname'], '</option>';
				}
			} elseif ($MyRow['loccode'] == $_SESSION['UserStockLocation']) {
				echo '<option selected="selected" value="', $MyRow['loccode'], '">', $MyRow['locationname'], '</option>';
			} else {
				echo '<option value="', $MyRow['loccode'], '">', $MyRow['locationname'], '</option>';
			}
		}

		echo '</select>
				</td>
				<td>
					<select name="Quotations">';

		if (isset($_GET['Quotations']) and $_GET['Quotations'] == 'Quotes_Only') {
			$_POST['Quotations'] = 'Quotes_Only';
		}

		if (isset($_POST['Quotations']) and $_POST['Quotations'] == 'Quotes_Only') {
			echo '<option selected="selected" value="Quotes_Only">', _('Quotations Only'), '</option>';
			echo '<option value="Orders_Only">', _('Orders Only'), '</option>';
		} else {
			echo '<option selected="selected" value="Orders_Only">', _('Orders Only'), '</option>';
			echo '<option value="Quotes_Only">', _('Quotations Only'), '</option>';
		}

		echo '</select> </td>
				<td><input type="submit" name="SearchOrders" value="', _('Search'), '" /></td>
				<td><a href="', $RootPath, '/SelectOrderItems.php?NewOrder=Yes">', _('Add Sales Order'), '</a></td>
			</tr>
		</table>';
	}

	$SQL = "SELECT categoryid,
			categorydescription
		FROM stockcategory
		ORDER BY categorydescription";

	$Result1 = DB_query($SQL);

	if (!isset($_POST['Keywords'])) {
		$_POST['Keywords'] = '';
	}
	if (!isset($_POST['StockCode'])) {
		$_POST['StockCode'] = '';
	}

	echo '<table class="selection">
			<tr>
				<th colspan="6"><h3>', _('To search for sales orders for a specific part use the part selection facilities below'), '</h3></th>
			</tr>
			<tr>
				<td>', _('Select a stock category'), ':
						<select name="StockCat">';

	while ($MyRow1 = DB_fetch_array($Result1)) {
		if (isset($_POST['StockCat']) and $_POST['StockCat'] == $MyRow1['categoryid']) {
			echo '<option selected="selected" value="', $MyRow1['categoryid'], '">', $MyRow1['categorydescription'], '</option>';
		} else {
			echo '<option value="', $MyRow1['categoryid'], '">', $MyRow1['categorydescription'], '</option>';
		}
	}

	echo '</select></td>
			<td>', _('Enter text extract(s) in the description'), ':</td>
			<td><input type="text" name="Keywords" size="20" maxlength="25" value="', $_POST['Keywords'], '" /></td>
		</tr>
      	<tr>
      		<td colspan="2" class="number"><b>', _('OR'), ' </b>', _('Enter extract of the Stock Code'), ':</td>
      		<td><input type="text" name="StockCode" size="15" maxlength="18" value="', $_POST['StockCode'], '" /></td>
      	</tr>
      </table>';
	echo '<div class="centre">
			<input type="submit" name="SearchParts" value="', _('Search Parts Now'), '" />
			<input type="submit" name="ResetPart" value="', _('Show All'), '" />
		</div>';

	if (isset($StockItemsResult) and DB_num_rows($StockItemsResult) > 0) {

		echo '<table cellpadding="2" class="selection">
				<thead>
					<tr>
						<th class="SortedColumn" >', _('Code'), '</th>
						<th class="SortedColumn" >', _('Description'), '</th>
						<th>', _('On Hand'), '</th>
						<th>', _('Units'), '</th>
					</tr>
				</thead>';

		$k = 0; //row colour counter
		echo '<tbody>';
		while ($MyRow = DB_fetch_array($StockItemsResult)) {

			if ($k == 1) {
				echo '<tr class="EvenTableRows">';
				$k = 0;
			} else {
				echo '<tr class="OddTableRows">';
				++$k;
			}

			echo '<td><input type="submit" name="SelectedStockItem" value="', $MyRow['stockid'], '" /></td>
				<td>', $MyRow['description'], '</td>
				<td class="number">', locale_number_format($MyRow['qoh'], $MyRow['decimalplaces']), '</td>
				<td>', $MyRow['units'], '</td>
			</tr>';
			//end of page full new headings if
		}
		//end of while loop

		echo '</tbody>';
		echo '</table>';

	}
	//end if stock search results to show
	else {

		//figure out the SQL required from the inputs available
		if (isset($_POST['Quotations']) and $_POST['Quotations'] == 'Orders_Only') {
			$Quotations = 0;
		} else {
			$Quotations = 1;
		}
		if (!isset($_POST['StockLocation'])) {
			$_POST['StockLocation'] = '';
		}

		if ($_SESSION['SalesmanLogin'] != '') {
			$SalesMan = '=\'' . $_SESSION['SalesmanLogin'] . '\'';
		} else {
			$SalesMan = ' LIKE \'%\'';
		}

		/*Harmonize the ordervalue with SUM function since it is allowed to
		 * have the same items appeared several times in one sales orders. If there
		 * is no sum value, this situation not inclued.*/
		$SQL = "SELECT salesorders.orderno,
					debtorsmaster.name,
					custbranch.brname,
					salesorders.customerref,
					salesorders.orddate,
					salesorders.deliverydate,
					salesorders.deliverto,
					salesorders.printedpackingslip,
					salesorders.poplaced,
					SUM(salesorderdetails.unitprice*(salesorderdetails.quantity-salesorderdetails.qtyinvoiced)*(1-salesorderdetails.discountpercent)/currencies.rate) AS ordervalue,
					pickreq.prid
				FROM salesorders
				INNER JOIN salesorderdetails
					ON salesorders.orderno = salesorderdetails.orderno
				INNER JOIN debtorsmaster
					ON salesorders.debtorno = debtorsmaster.debtorno
				INNER JOIN custbranch
					ON debtorsmaster.debtorno = custbranch.debtorno
					AND salesorders.branchcode = custbranch.branchcode
				INNER JOIN currencies
					ON debtorsmaster.currcode = currencies.currabrev
				LEFT OUTER JOIN pickreq
					ON pickreq.orderno=salesorders.orderno
					AND pickreq.closed=0
				WHERE salesorderdetails.completed=0";
		//Add salesman role control
		if ($_SESSION['SalesmanLogin'] != '') {
			$SQL .= " AND salesorders.salesperson='" . $_SESSION['SalesmanLogin'] . "'";
		}

		if (isset($OrderNumber) and $OrderNumber != '') {

			$SQL .= "AND salesorders.orderno=" . $OrderNumber . " AND salesorders.quotation=" . $Quotations;
		} else {
			/* $DateAfterCriteria = FormatDateforSQL($OrdersAfterDate); */
			if (isset($SelectedCustomer)) {
				if (isset($SelectedStockItem)) {
					$SQL .= " AND salesorders.quotation=" . $Quotations . "
                                               AND salesorderdetails.stkcode='" . $SelectedStockItem . "'
                                               AND salesorders.debtorno='" . $SelectedCustomer . "'
                                               AND salesorders.fromstkloc='" . $_POST['StockLocation'] . "'";
				} else {
					$SQL .= " AND  salesorders.quotation =" . $Quotations . "
                                               AND salesorders.debtorno='" . $SelectedCustomer . "'
                                               AND salesorders.fromstkloc = '" . $_POST['StockLocation'] . "'";

				}
			} else { //no customer selected
				if (isset($SelectedStockItem)) {
					$SQL .= " AND salesorders.quotation =" . $Quotations . "
                                               AND salesorderdetails.stkcode='" . $SelectedStockItem . "'
                                               AND salesorders.fromstkloc = '" . $_POST['StockLocation'] . "'";
				} else {
					$SQL .= " AND salesorders.quotation =" . $Quotations . "
                                               AND salesorders.fromstkloc = '" . $_POST['StockLocation'] . "'";
				}
			} //end selected customer

			$SQL .= " GROUP BY salesorders.orderno,
						debtorsmaster.name,
						custbranch.brname,
						salesorders.customerref,
						salesorders.orddate,
						salesorders.deliverydate,
						salesorders.deliverto,
						salesorders.printedpackingslip,
						salesorders.poplaced
					ORDER BY salesorders.orderno";

		} //end not order number selected

		$ErrMsg = _('No orders or quotations were returned by the SQL because');
		$SalesOrdersResult = DB_query($SQL, $ErrMsg);

		/*show a table of the orders returned by the SQL */
		if (DB_num_rows($SalesOrdersResult) > 0) {

			/* Get users authority to place POs */
			$AuthSQL = "SELECT cancreate
					FROM purchorderauth
					WHERE userid='" . $_SESSION['UserID'] . "'";

			/*we don't know what currency these orders might be in but if no authority at all then don't show option*/
			$AuthResult = DB_query($AuthSQL);
			$AuthRow = DB_fetch_array($AuthResult);

			echo '<table cellpadding="2" width="95%" class="selection">';
			if (is_null($AuthRow['cancreate']) or !isset($AuthRow)) {
				$AuthRow['cancreate'] = 1;
			}
			echo '<thead>';
			$PrintPickLabel = '';
			if ($_SESSION['RequirePickingNote'] == 1) {
				$PrintPickLabel = '<th>' . _('Pick Lists') . '</th>';
			}
			if (isset($_POST['Quotations']) and $_POST['Quotations'] == 'Orders_Only') {
				echo '<tr>
						<th class="SortedColumn">', _('Modify'), '</th>
						<th>', _('Acknowledge'), '</th>
						', $PrintPickLabel, '
						<th>', _('Invoice'), '</th>
						<th>', _('Dispatch Note'), '</th>
						<th>', _('Labels'), '</th>
						<th class="SortedColumn">', _('Customer'), '</th>
						<th class="SortedColumn">', _('Branch'), '</th>
						<th>', _('Cust Order'), ' #</th>
						<th>', _('Order Date'), '</th>
						<th>', _('Req Del Date'), '</th>
						<th>', _('Delivery To'), '</th>
						<th>', _('Order Total'), '<br />', $_SESSION['CompanyRecord']['currencydefault'], '</th>';

				if ($AuthRow['cancreate'] == 0) { //if cancreate==0 then this means the user can create orders hmmm!!
					echo '<th>' . _('Place PO') . '</th>
						<th>', _('Attachment'), '</th>
						</tr>';
				} else {
					echo '
						<th>', _('Attachment'), '</th>
						</tr>';
				}
			} else {
				/* displaying only quotations */
				echo '<tr>
						<th class="SortedColumn">', _('Modify'), '</th>
						<th>', _('Print Quote'), '</th>
						<th class="SortedColumn">', _('Customer'), '</th>
						<th class="SortedColumn">', _('Branch'), '</th>
						<th>', _('Cust Ref'), ' #</th>
						<th>', _('Quote Date'), '</th>
						<th>', _('Req Del Date'), '</th>
						<th>', _('Delivery To'), '</th>
						<th>', _('Quote Total'), '<br />' . $_SESSION['CompanyRecord']['currencydefault'] . '</th>
					</tr>';
			}
			echo '</thead>';
			$i = 0;
			$k = 0; //row colour counter
			$OrdersTotal = 0;
			echo '<tbody>';
			while ($MyRow = DB_fetch_array($SalesOrdersResult)) {


				if ($k == 1) {
					echo '<tr class="EvenTableRows">';
					$k = 0;
				} else {
					echo '<tr class="OddTableRows">';
					++$k;
				}

				$ModifyPage = $RootPath . '/SelectOrderItems.php?ModifyOrderNumber=' . $MyRow['orderno'];
				$Confirm_Invoice = $RootPath . '/ConfirmDispatch_Invoice.php?OrderNumber=' . $MyRow['orderno'];
				$PrintPickList = '';
				$PrintPickLabel = '';
				$PrintDummyFlag = '<input type="hidden" name="dummy" value="%s" />';
				if ($_SESSION['RequirePickingNote'] == 1) {
					$PrintPickList = $RootPath . '/GeneratePickingList.php?TransNo=' . $MyRow['orderno'];
					if (isset($MyRow['prid']) and $MyRow['prid'] > '') {
						$PrintPickLabel = '<td><a href="%s">' . str_pad($MyRow['prid'], 10, '0', STR_PAD_LEFT) . '</a></td>';
					} else {
						$PrintPickLabel = '<td><a href="%s">' . _('Pick') . '</a></td>';
					}
					$PrintDummyFlag = '';
				}

				if ($_SESSION['PackNoteFormat'] == 1) {
					/*Laser printed A4 default */
					$PrintDispatchNote = $RootPath . '/PrintCustOrder_generic.php?TransNo=' . $MyRow['orderno'];
				} else {
					/*pre-printed stationery default */
					$PrintDispatchNote = $RootPath . '/PrintCustOrder.php?TransNo=' . $MyRow['orderno'];
				}
				$PrintQuotation = $RootPath . '/PDFQuotation.php?QuotationNo=' . $MyRow['orderno'];
				$PrintQuotationPortrait = $RootPath . '/PDFQuotationPortrait.php?QuotationNo=' . $MyRow['orderno'];
				$FormatedDelDate = ConvertSQLDate($MyRow['deliverydate']);
				$FormatedOrderDate = ConvertSQLDate($MyRow['orddate']);
				$FormatedOrderValue = locale_number_format($MyRow['ordervalue'], $_SESSION['CompanyRecord']['decimalplaces']);
				$OrdersTotal += $MyRow['ordervalue'];
				$PrintAck = $RootPath . '/PDFAck.php?AcknowledgementNo=' . $MyRow['orderno'];

				if (!isset($PricesSecurity) or !in_array($PricesSecurity, $_SESSION['AllowedPageSecurityTokens'])) {
					$FormatedOrderValue = '---------';
				}

				if ($MyRow['printedpackingslip'] == 0) {
					$PrintText = _('Print');
				} else {
					$PrintText = _('Reprint');
				}

				$PrintLabels = $RootPath . '/PDFShipLabel.php?Type=Sales&ORD=' . $MyRow['orderno'];

				if (file_exists('companies/' . $_SESSION['DatabaseName'] . '/Attachments/' . $MyRow['orderno'] . '.pdf')) {
					$AttachmentText = '<a href="companies/' . $_SESSION['DatabaseName'] . '/Attachments/' . $MyRow['orderno'] . '.pdf">' . _('View attachment') . '</a>';
				} else {
					$AttachmentText = _('No attachment');
				}

				if ($_POST['Quotations'] == 'Orders_Only') {

					/*Check authority to create POs if user has authority then show the check boxes to select sales orders to place POs for otherwise don't provide this option */
					if ($AuthRow['cancreate'] == 0 and $MyRow['poplaced'] == 0) { //cancreate==0 if the user can create POs and not already placed
						printf('<td><a href="%s">%s</a></td>
						<td><a href="%s" target="_blank">' . _('Acknowledge') . '</a>' . $PrintDummyFlag . '</td>
						' . $PrintPickLabel . '
						<td><a href="%s">' . _('Invoice') . '</a></td>
        				<td><a target="_blank" href="%s">' . $PrintText . ' <img src="' . $RootPath . '/css/' . $_SESSION['Theme'] . '/images/pdf.png" title="' . _('Click for PDF') . '" alt="" /></a></td>
						<td><a href="%s">' . _('Labels') . '</a></td>
        				<td>%s</td>
        				<td>%s</td>
        				<td>%s</td>
        				<td>%s</td>
        				<td>%s</td>
        				<td>%s</td>
        				<td class="number">%s</td>
        				<td><input type="checkbox" name="PlacePO_%s" /><input type="hidden" name="OrderNo_PO_%s" value="%s" /></td>
        				<td>' . $AttachmentText . '</td>
        				</tr>', $ModifyPage, $MyRow['orderno'], $PrintAck, $PrintPickList, $Confirm_Invoice, $PrintDispatchNote, $PrintLabels, $MyRow['name'], $MyRow['brname'], $MyRow['customerref'], $FormatedOrderDate, $FormatedDelDate, html_entity_decode($MyRow['deliverto'], ENT_QUOTES, 'UTF-8'), $FormatedOrderValue, $i, $i, $MyRow['orderno']);
					} else {
						/*User is not authorised to create POs so don't even show the option */
						printf('<td><a href="%s">%s</a></td>
							<td><a href="%s">' . _('Acknowledge') . '</a></td>
							<td>' . $PrintDummyFlag . '</td>
							' . $PrintPickLabel . '
							<td><a href="%s">' . _('Invoice') . '</a></td>
							<td><a target="_blank" href="%s">' . $PrintText . ' <img src="' . $RootPath . '/css/' . $_SESSION['Theme'] . '/images/pdf.png" title="' . _('Click for PDF') . '" alt="" /></a></td>
							<td><a href="%s">' . _('Labels') . '</a></td>
							<td>%s</td>
							<td>%s</td>
							<td>%s</td>
							<td>%s</td>
							<td>%s</td>
							<td>%s</td>
							<td class="number">%s</td>
							<td>' . $AttachmentText . '</td>
							</tr>', $ModifyPage, $MyRow['orderno'], $PrintAck, $PrintPickList, $Confirm_Invoice, $PrintDispatchNote, $PrintLabels, $MyRow['name'], $MyRow['brname'], $MyRow['customerref'], $FormatedOrderDate, $FormatedDelDate, html_entity_decode($MyRow['deliverto'], ENT_QUOTES, 'UTF-8'), $FormatedOrderValue);
					}

				} else {
					/*must be quotes only */
					printf('<td><a href="%s">%s</a></td>
						<td><a target="_blank" href="%s">' . _('Landscape') . '</a>&nbsp;&nbsp;<a target="_blank" href="%s">' . _('Portrait') . '</a></td>
						<td>%s</td>
						<td>%s</td>
						<td>%s</td>
						<td>%s</td>
						<td>%s</td>
						<td>%s</td>
						<td class="number">%s</td>
						</tr>', $ModifyPage, $MyRow['orderno'], $PrintQuotation, $PrintQuotationPortrait, $MyRow['name'], $MyRow['brname'], $MyRow['customerref'], $FormatedOrderDate, $FormatedDelDate, html_entity_decode($MyRow['deliverto'], ENT_QUOTES, 'UTF-8'), $FormatedOrderValue);
				}
				++$i;
				//end of page full new headings if
			} //end while loop through orders to display
			echo '</tbody>';

			echo '<tr>
					<td colspan="12" class="number">';
			if ($_POST['Quotations'] == 'Orders_Only') {
				echo '<b>' . _('Total Order(s) Value in');
			} else {
				echo '<b>' . _('Total Quotation(s) Value in');
			}
			if (!isset($PricesSecurity) or !in_array($PricesSecurity, $_SESSION['AllowedPageSecurityTokens'])) {
				$OrdersTotal = '---------';
			}
			echo ' ' . $_SESSION['CompanyRecord']['currencydefault'] . ' :</b></td>
			<td class="number"><b>' . locale_number_format($OrdersTotal, $_SESSION['CompanyRecord']['decimalplaces']) . '</b></td>';
			if ($_POST['Quotations'] == 'Orders_Only' and $AuthRow['cancreate'] == 0) { //cancreate==0 means can create POs

				echo '<td class="number">
							<input type="submit" name="PlacePO" value="' . _('Place') . " " . _('PO') . '" onclick="return MakeConfirm(\'' . _('This will create purchase orders for all the items on the checked sales orders above, based on the preferred supplier purchasing data held in the system. Are You Absolutely Sure?') . '\');" />
						</td>
					</tr>';
			}
			echo '</table>';
		} //end if there are some orders to show
	}

	echo '</form>';

} //end StockID already selected

include('includes/footer.inc');
?>