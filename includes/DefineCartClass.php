<?php
/* $Id: DefineCartClass.php 7444 2016-01-13 07:32:36Z daintree $*/

/* Definition of the cart class
this class can hold all the information for:

i)   a sales order
ii)  an invoice
iii) a credit note

*/

class Cart {

	var $LineItems;
	/*array of objects of class LineDetails using the product id as the pointer */
	var $total;
	/*total cost of the items ordered */
	var $totalVolume;
	var $totalWeight;
	var $LineCounter;
	var $ItemsOrdered;
	/*no of different line items ordered */
	var $DeliveryDate;
	var $DefaultSalesType;
	var $SalesTypeName;
	var $DefaultCurrency;
	var $CurrDecimalPlaces;
	var $PaymentTerms;
	var $DeliverTo;
	var $DelAdd1;
	var $DelAdd2;
	var $DelAdd3;
	var $DelAdd4;
	var $DelAdd5;
	var $DelAdd6;
	var $SalesPerson;
	var $PhoneNo;
	var $Email;
	var $CustRef;
	var $Comments;
	var $Location;
	var $LocationName;
	var $DebtorNo;
	var $CustomerName;
	var $Orig_OrderDate;
	var $Branch;
	var $TransID;
	var $ShipVia;
	var $FreightCost;
	var $FreightTaxes;
	var $OrderNo;
	var $Consignment;
	var $Quotation;
	var $DeliverBlind;
	var $CreditAvailable; //in customer currency
	var $TaxGroup;
	var $DispatchTaxProvince;
	var $DefaultPOLine;
	var $DeliveryDays;
	var $TaxTotals;
	var $TaxGLCodes;
	var $BuyerName;
	var $SpecialInstructions;
	var $WarehouseDefined;

	function __construct() {
		/*Constructor function initialises a new shopping cart */
		$this->LineItems = array();
		$this->total = 0;
		$this->ItemsOrdered = 0;
		$this->LineCounter = 0;
		$this->DefaultSalesType = '';
		$this->FreightCost = 0;
		$this->FreightTaxes = array();
		$this->CurrDecimalPlaces = 2; //default
		
	}

	function add_to_cart($StockId, $Qty, $Descr, $LongDescr, $Price, $Disc = 0, $UOM, $Volume, $Weight, $QOHatLoc = 0, $MBflag = 'B', $ActDispatchDate = NULL, $QtyInvoiced = 0, $DiscCat = '', $Controlled = 0, $Serialised = 0, $DecimalPlaces = 0, $Narrative = '', $UpdateDB = 'No', $LineNumber = - 1, $TaxCategory = 0, $vtigerProductID = '', $ItemDue = '', $POLine = '', $StandardCost = 0, $EOQ = 1, $NextSerialNo = 0, $ExRate = 1, $Identifier = 0) {

		if (isset($StockId) and $StockId != "" and $Qty > 0 and isset($Qty)) {

			if ($Price < 0) {
				/*madness check - use a credit note to give money away!*/
				$Price = 0;
			}

			if ($LineNumber == - 1) {
				$LineNumber = $this->LineCounter;
			}

			$this->LineItems[$LineNumber] = new LineDetails($LineNumber, $StockId, $Descr, $LongDescr, $Qty, $Price, $Disc, $UOM, $Volume, $Weight, $QOHatLoc, $MBflag, $ActDispatchDate, $QtyInvoiced, $DiscCat, $Controlled, $Serialised, $DecimalPlaces, $Narrative, $TaxCategory, $ItemDue, $POLine, $StandardCost, $EOQ, $NextSerialNo, $ExRate);
			$this->ItemsOrdered++;

			if ($UpdateDB == 'Yes') {
				/*ExistingOrder !=0 set means that an order is selected or created for entry
				of items - ExistingOrder is set to 0 in scripts that should not allow
				adding items to the order - New orders have line items added at the time of
				committing the order to the DB in DeliveryDetails.php
				GET['ModifyOrderNumber'] is only set when the items are first
				being retrieved from the DB - dont want to add them again - would return
				errors anyway */

				$SQL = "INSERT INTO salesorderdetails (orderlineno,
														orderno,
														stkcode,
														quantity,
														unitprice,
														discountpercent,
														itemdue,
														poline)
													VALUES(
														'" . $LineNumber . "',
														'" . $_SESSION['ExistingOrder' . $Identifier] . "',
														'" . trim(mb_strtoupper($StockId)) . "',
														'" . $Qty . "',
														'" . $Price . "',
														'" . $Disc . "',
														'" . FormatDateForSQL($ItemDue) . "',
														'" . $POLine . "'
													)";
				$Result = DB_query($SQL, _('The order line for') . ' ' . mb_strtoupper($StockId) . ' ' . _('could not be inserted'));
			}

			$this->LineCounter = $LineNumber + 1;
			return 1;
		}
		return 0;
	}

	function update_cart_item($UpdateLineNumber, $Qty, $Price, $Disc, $Narrative, $UpdateDB = 'No', $ItemDue, $POLine, $GPPercent, $Identifier) {

		if ($Qty > 0) {
			$this->LineItems[$UpdateLineNumber]->Quantity = $Qty;
		}
		$this->LineItems[$UpdateLineNumber]->Price = $Price;
		$this->LineItems[$UpdateLineNumber]->DiscountPercent = $Disc;
		$this->LineItems[$UpdateLineNumber]->Narrative = $Narrative;
		$this->LineItems[$UpdateLineNumber]->ItemDue = $ItemDue;
		$this->LineItems[$UpdateLineNumber]->POLine = $POLine;
		$this->LineItems[$UpdateLineNumber]->GPPercent = $GPPercent;
		if ($UpdateDB == 'Yes') {
			$Result = DB_query("UPDATE salesorderdetails SET quantity='" . $Qty . "',
															unitprice='" . $Price . "',
															discountpercent='" . $Disc . "',
															narrative ='" . $Narrative . "',
															itemdue = '" . FormatDateForSQL($ItemDue) . "',
															poline = '" . $POLine . "'
								WHERE orderno=" . $_SESSION['ExistingOrder' . $Identifier] . "
								AND orderlineno=" . $UpdateLineNumber, _('The order line number') . ' ' . $UpdateLineNumber . ' ' . _('could not be updated'));
		}
	}

	function remove_from_cart($LineNumber, $UpdateDB = 'No', $Identifier = 0) {

		if (!isset($LineNumber) or $LineNumber == '' or $LineNumber < 0) {
			/* over check it */
			prnMsg(_('No Line Number passed to remove_from_cart, so nothing has been removed.'), 'error');
			return;
		}
		if ($UpdateDB == 'Yes') {
			if ($this->Some_Already_Delivered($LineNumber) == 0) {
				/* nothing has been delivered, delete it. */
				$Result = DB_query("DELETE FROM salesorderdetails
									WHERE orderno='" . $_SESSION['ExistingOrder' . $Identifier] . "'
									AND orderlineno='" . $LineNumber . "'", _('The order line could not be deleted because'));
				prnMsg(_('Deleted Line Number') . ' ' . $LineNumber . ' ' . _('from existing Order Number') . ' ' . $_SESSION['ExistingOrder' . $Identifier], 'success');
			} else {
				/* something has been delivered. Clear the remaining Qty and Mark Completed */
				$Result = DB_query("UPDATE salesorderdetails SET quantity=qtyinvoiced,
																completed=1
									WHERE orderno='" . $_SESSION['ExistingOrder' . $Identifier] . "'
									AND orderlineno='" . $LineNumber . "'", _('The order line could not be updated as completed because'));
				prnMsg(_('Removed Remaining Quantity and set Line Number ') . ' ' . $LineNumber . ' ' . _('as Completed for existing Order Number') . ' ' . $_SESSION['ExistingOrder'], 'success');
			}
		}
		/* Since we need to check the LineItem above and might affect the DB, don't unset until after DB is updates occur */
		$this->CreditAvailable+= ($this->LineItems[$LineNumber]->Quantity * $this->LineItems[$LineNumber]->Price * (1 - $this->LineItems[$LineNumber]->DiscountPercent));
		unset($this->LineItems[$LineNumber]);
		$this->ItemsOrdered--;

	} //remove_from_cart()
	function Get_StockID_List() {
		/* Makes a comma seperated list of the stock items ordered
		 for use in SQL expressions */

		$StockId_List = '';
		foreach ($this->LineItems as $StockItem) {
			$StockId_List.= ",'" . $StockItem->StockID . "'";
		}

		return mb_substr($StockId_List, 1);

	}

	function Any_Already_Delivered() {
		/* Checks if there have been deliveries of line items */

		foreach ($this->LineItems as $StockItem) {
			if ($StockItem->QtyInv != 0) {
				return 1;
			}
		}

		return 0;

	}

	function Some_Already_Delivered($LineNumber) {
		/* Checks if there have been deliveries of a specific line item */

		if ($this->LineItems[$LineNumber]->QtyInv != 0) {
			return $this->LineItems[$LineNumber]->QtyInv;
		}
		return 0;
	}

	function AllDummyLineItems() {
		foreach ($this->LineItems as $StockItem) {
			if ($StockItem->MBflag != 'D') {
				return false;
			}
		}
		return true;
	}

	function GetExistingTaxes($LineNumber, $stkmoveno) {

		/*Gets the Taxes and rates applicable to this line from the TaxGroup of the branch and TaxCategory of the item
		 and the taxprovince of the dispatch location */

		$SQL = "SELECT stockmovestaxes.taxauthid,
						taxauthorities.description,
						taxauthorities.taxglcode,
						stockmovestaxes.taxcalculationorder,
						stockmovestaxes.taxontax,
						stockmovestaxes.taxrate
					FROM stockmovestaxes
					INNER JOIN taxauthorities
						ON stockmovestaxes.taxauthid = taxauthorities.taxid
					WHERE stkmoveno = '" . $stkmoveno . "'
					ORDER BY taxcalculationorder";

		$ErrMsg = _('The taxes and rates for this item could not be retrieved because');
		$GetTaxRatesResult = DB_query($SQL, $ErrMsg);
		$i = 1;
		while ($MyRow = DB_fetch_array($GetTaxRatesResult)) {

			$this->LineItems[$LineNumber]->Taxes[$i] = new Tax($MyRow['taxcalculationorder'], $MyRow['taxauthid'], $MyRow['description'], $MyRow['taxrate'], $MyRow['taxontax'], $MyRow['taxglcode']);
			$i++;
		}
	} //end method GetExistingTaxes
	function GetTaxes($LineNumber) {

		/*Gets the Taxes and rates applicable to this line from the TaxGroup of the branch and TaxCategory of the item
		 and the taxprovince of the dispatch location */

		$SQL = "SELECT taxgrouptaxes.calculationorder,
						taxauthorities.description,
						taxgrouptaxes.taxauthid,
						taxauthorities.taxglcode,
						taxgrouptaxes.taxontax,
						taxauthrates.taxrate
					FROM taxauthrates
					INNER JOIN taxgrouptaxes
						ON taxauthrates.taxauthority=taxgrouptaxes.taxauthid
					INNER JOIN taxauthorities
						ON taxauthrates.taxauthority=taxauthorities.taxid
					WHERE taxgrouptaxes.taxgroupid=" . $this->TaxGroup . "
						AND taxauthrates.dispatchtaxprovince=" . $this->DispatchTaxProvince . "
						AND taxauthrates.taxcatid = " . $this->LineItems[$LineNumber]->TaxCategory . "
					ORDER BY taxgrouptaxes.calculationorder";

		$ErrMsg = _('The taxes and rates for this item could not be retrieved because');
		$GetTaxRatesResult = DB_query($SQL, $ErrMsg);
		unset($this->LineItems[$LineNumber]->Taxes);
		if (DB_num_rows($GetTaxRatesResult) == 0) {
			prnMsg(_('It appears that taxes are not defined correctly for this customer tax group'), 'error');
		} else {
			$i = 1;
			while ($MyRow = DB_fetch_array($GetTaxRatesResult)) {
				$this->LineItems[$LineNumber]->Taxes[$i] = new Tax($MyRow['calculationorder'], $MyRow['taxauthid'], $MyRow['description'], $MyRow['taxrate'], $MyRow['taxontax'], $MyRow['taxglcode']);
				$i++;
			} //end loop around different taxes
			
		} //end if there are some taxes defined
		
	} //end method GetTaxes
	function GetFreightTaxes() {

		/*Gets the Taxes and rates applicable to the freight based on the tax group of the branch combined with the tax category for this particular freight
		 and SESSION['FreightTaxCategory'] the taxprovince of the dispatch location */

		$SQL = "SELECT freighttaxcatid AS taxcatid FROM taxprovinces WHERE taxprovinceid='" . $this->DispatchTaxProvince . "'"; // This tax category is hardcoded inside the database.
		$TaxCatQuery = DB_query($SQL);

		if ($TaxCatRow = DB_fetch_array($TaxCatQuery)) {
			$TaxCatID = $TaxCatRow['taxcatid'];
		} else {
			prnMsg(_('Cannot find tax category Freight which must always be defined'), 'error');
			exit();
		}

		$SQL = "SELECT taxgrouptaxes.calculationorder,
						taxauthorities.description,
						taxgrouptaxes.taxauthid,
						taxauthorities.taxglcode,
						taxgrouptaxes.taxontax,
						taxauthrates.taxrate
					FROM taxauthrates
					INNER JOIN taxgrouptaxes
						ON taxauthrates.taxauthority=taxgrouptaxes.taxauthid
					INNER JOIN taxauthorities
						ON taxauthrates.taxauthority=taxauthorities.taxid
					WHERE taxgrouptaxes.taxgroupid='" . $this->TaxGroup . "'
						AND taxauthrates.dispatchtaxprovince='" . $this->DispatchTaxProvince . "'
						AND taxauthrates.taxcatid = '" . $TaxCatID . "'
					ORDER BY taxgrouptaxes.calculationorder";

		$ErrMsg = _('The taxes and rates for this item could not be retrieved because');
		$GetTaxRatesResult = DB_query($SQL, $ErrMsg);
		$i = 1;
		while ($MyRow = DB_fetch_array($GetTaxRatesResult)) {

			$this->FreightTaxes[$i] = new Tax($MyRow['calculationorder'], $MyRow['taxauthid'], $MyRow['description'], $MyRow['taxrate'], $MyRow['taxontax'], $MyRow['taxglcode']);
			$i++;
		}
	} //end method GetFreightTaxes()
	
}
/* end of cart class defintion */

class LineDetails {
	var $LineNumber;
	var $StockId;
	var $ItemDescription;
	var $LongDescription;
	var $Quantity;
	var $Price;
	var $DiscountPercent;
	var $Units;
	var $Volume;
	var $Weight;
	var $ActDispDate;
	var $QtyInv;
	var $QtyDispatched;
	var $StandardCost;
	var $QOHatLoc;
	var $MBflag;
	/*Make Buy Dummy, Assembly or Kitset */
	var $DiscCat;
	/* Discount Category of the item if any */
	var $Controlled;
	var $Serialised;
	var $DecimalPlaces;
	var $SerialItems;
	var $Narrative;
	var $TaxCategory;
	var $Taxes;
	var $WorkOrderNo;
	var $ItemDue;
	var $POLine;
	var $EOQ;
	var $NextSerialNo;
	var $GPPercent;
	var $Container;

	function __construct($LineNumber, $StockItem, $Descr, $LongDescr, $Qty, $Prc, $DiscPercent, $UOM, $Volume, $Weight, $QOHatLoc, $MBflag, $ActDispatchDate, $QtyInvoiced, $DiscCat, $Controlled, $Serialised, $DecimalPlaces, $Narrative, $TaxCategory, $ItemDue, $POLine, $StandardCost, $EOQ, $NextSerialNo, $ExRate) {

		/* Constructor function to add a new LineDetail object with passed params */
		$this->LineNumber = $LineNumber;
		$this->StockID = $StockItem;
		$this->ItemDescription = $Descr;
		$this->LongDescription = $LongDescr;
		$this->Quantity = $Qty;
		$this->Price = $Prc;
		$this->DiscountPercent = $DiscPercent;
		$this->Units = $UOM;
		$this->Volume = $Volume;
		$this->Weight = $Weight;
		$this->ActDispDate = $ActDispatchDate;
		$this->QtyInv = $QtyInvoiced;
		if ($Controlled == 1) {
			$this->QtyDispatched = 0;
		} else {
			if ($_SESSION['InvoiceQuantityDefault'] == 1) {
				$this->QtyDispatched = $Qty - $QtyInvoiced;
			} else {
				$this->QtyDispathced = 0;
			}
		}
		$this->QOHatLoc = $QOHatLoc;
		$this->MBflag = $MBflag;
		$this->DiscCat = $DiscCat;
		$this->Controlled = $Controlled;
		$this->Serialised = $Serialised;
		$this->DecimalPlaces = $DecimalPlaces;
		$this->SerialItems = array();
		$this->Narrative = $Narrative;
		$this->Taxes = array();
		$this->TaxCategory = $TaxCategory;
		$this->WorkOrderNo = 0;
		$this->ItemDue = $ItemDue;
		$this->POLine = $POLine;
		$this->StandardCost = $StandardCost;
		$this->EOQ = $EOQ;
		$this->NextSerialNo = $NextSerialNo;

		if ($Prc > 0) {
			$this->GPPercent = ((($Prc * (1 - $DiscPercent)) - ($StandardCost * $ExRate)) * 100) / $Prc;
		} else {
			$this->GPPercent = 0;
		}
	} //end constructor function for LineDetails
	
}

class Tax {
	var $TaxCalculationOrder;
	/*the index for the array */
	var $TaxAuthID;
	var $TaxAuthDescription;
	var $TaxRate;
	var $TaxOnTax;
	var $TaxGLCode;

	function __construct($TaxCalculationOrder, $TaxAuthID, $TaxAuthDescription, $TaxRate, $TaxOnTax, $TaxGLCode) {

		$this->TaxCalculationOrder = $TaxCalculationOrder;
		$this->TaxAuthID = $TaxAuthID;
		$this->TaxAuthDescription = $TaxAuthDescription;
		$this->TaxRate = $TaxRate;
		$this->TaxOnTax = $TaxOnTax;
		$this->TaxGLCode = $TaxGLCode;
	}
}

?>