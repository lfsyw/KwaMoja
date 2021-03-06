<?php
/* $Revision: 1.0 $ */
//include_once('includes/printerrmsg.php');
$PageSecurity = 10;
include ('includes/session.php');
include ('includes/prlFunctions.php');
$Title = _('Employees Loan Deduction Entry');
include ('includes/header.php');
echo "<A HREF='" . $RootPath . '/prlSelectLoan.php?' . SID . "'>" . _('Back to View Loan File Records') . '</A><BR>';
if (isset($_GET['SelectedID'])) {
	$SelectedID = $_GET['SelectedID'];
} elseif (isset($_POST['SelectedID'])) {
	$SelectedID = $_POST['SelectedID'];
}

if (isset($_POST['submit'])) {

	//initialise no input errors assumed initially before we test
	$InputError = 0;
	$LoanBal = $_POST['LoanAmount'] - $_POST['YTDDeduction'];
	if ($LoanBal < 0) {
		$InputError = 1;
		prnMsg(_('Can not post. Total Deduction is greater that Loan Amount by') . ' ' . $LoanBal);
	}

	if ($InputError != 1) {
		//printerr($_POST['LoanTableID']);
		$SQL_LoanDate = FormatDateForSQL($_POST['LoanDate']);
		$SQL_StartDeduction = FormatDateForSQL($_POST['StartDeduction']);
		if (!isset($_POST['New'])) {
			$SQL = "UPDATE prlloanfile SET
					loanfiledesc='" . DB_escape_string($_POST['LoanFileDesc']) . "',
					employeeid='" . DB_escape_string($_POST['EmployeeID']) . "',
					loandate='$SQL_LoanDate',
					loantableid='" . DB_escape_string($_POST['LoanTableID']) . "',
					loanamount='" . DB_escape_string($_POST['LoanAmount']) . "',
					amortization='" . DB_escape_string($_POST['Amortization']) . "',
					startdeduction='$SQL_StartDeduction',
					amortization='" . DB_escape_string($_POST['Amortization']) . "',
					loanbalance='$LoanBal',
					accountcode='" . DB_escape_string($_POST['AccountCode']) . "'
				WHERE counterindex = '$SelectedID'";
			$ErrMsg = _('The employee loan could not be updated because');
			$DbgMsg = _('The SQL that was used to update the employee loan but failed was');
			$Result = DB_query($SQL, $ErrMsg, $DbgMsg);
			prnMsg(_('The employee loan master record for') . ' ' . $LoanFileId . ' ' . _('has been updated'), 'success');

		} else { //its a new employee
			//new record
			
		}

	} else {

		prnMsg(_('Validation failed') . _('no updates or deletes took place'), 'warn');

	}

} elseif (isset($_POST['delete']) and $_POST['delete'] != '') {

	//the link to delete a selected record was clicked instead of the submit button
	$CancelDelete = 0;
	if ($_SESSION['Status'] == 'Closed') {
		$CancelDelete = 1;
		prnMsg(_('Payroll has been assigned,closed and can not be deleted :') . ' Name :' . $_POST['FullName'] . ' Payroll :' . $_SESSION['PayDesc'], 'error');
	}
	if ($CancelDelete == 0) {
		$SQL = "DELETE FROM prlloanfile WHERE counterindex = '$SelectedID'";
		$Result = DB_query($SQL);
		prnMsg(_('Employee loan record for') . ' ' . $LoanFileId . ' ' . _('has been deleted'), 'success');
		unset($SelectedID);
		unset($_SESSION['SelectedID']);
		unset($LoanFileId);
		unset($_POST['LoanFileDesc']);
		unset($_POST['EmployeeID']);
		unset($_POST['LoanDate']);
		unset($_POST['LoanTableID']);
		unset($_POST['LoanAmount']);
		unset($_POST['Amortization']);
		unset($_POST['StartDeduction']);
		unset($_POST['AccountCode']);
	} //end if Delete employee
	
} //end of (isset($_POST['submit']))
if (!isset($SelectedID)) {
	//new loan
	
} else {
	//SupplierID exists - either passed when calling the form or from the form itself
	echo "<form method='post' action='" . basename(__FILE__) . '?' . SID . "'>";
	echo '<table>';
	if (!isset($_POST['New'])) {
		$SQL = "SELECT  loanfileid,
						loanfiledesc,
						employeeid,
						loandate,
						loantableid,
						loanamount,
						amortization,
						startdeduction,
						ytddeduction,
						accountcode
			FROM prlloanfile
			WHERE counterindex = '$SelectedID'";
		$Result = DB_query($SQL);
		$MyRow = DB_fetch_array($Result);
		$_POST['LoanFileDesc'] = $MyRow['loanfiledesc'];
		$_POST['EmployeeID'] = $MyRow['employeeid'];
		$_POST['LoanDate'] = ConvertSQLDate($MyRow['loandate']);
		$_POST['LoanTableID'] = $MyRow['loantableid'];
		$_POST['LoanAmount'] = $MyRow['loanamount'];
		$_POST['Amortization'] = $MyRow['amortization'];
		$_POST['StartDeduction'] = ConvertSQLDate($MyRow['startdeduction']);
		$_POST['YTDDeduction'] = $MyRow['ytddeduction'];
		$_POST['AccountCode'] = $MyRow['accountcode'];
		echo "<input type=HIDDEN name='SelectedID' value='$SelectedID'>";
	} else {
		// its a new supplier being added
		
	}
	echo '<tr><td>' . _('Description') . ':</td>
		<td><input type="text" name="LoanFileDesc" value="' . $_POST['LoanFileDesc'] . '" size=42 maxlength=40></td></tr>';
	echo '<tr><td>' . _('Employee Name') . ":</td><td><select name='EmployeeID'>";
	DB_data_seek($Result, 0);
	$SQL = 'SELECT employeeid, lastname, firstname FROM prlemployeemaster ORDER BY lastname, firstname';
	$Result = DB_query($SQL);
	while ($MyRow = DB_fetch_array($Result)) {
		if ($MyRow['employeeid'] == $_POST['EmployeeID']) {
			echo '<option selected="selected" value=';
		} else {
			echo '<option value=';
		}
		echo $MyRow['employeeid'] . '>' . $MyRow['lastname'] . ',' . $MyRow['firstname'];
	} //end while loop
	echo '</select></td></tr><tr><td>' . _('Loan Date:') . ' (' . $_SESSION['DefaultDateFormat'] . '):</td>
	<td><input type="text" name="LoanDate" size=12 maxlength=10 value="' . $_POST['LoanDate'] . '"></td></tr>';
	echo '<tr><td>' . _('Loan Type') . ":</td><td><select name='LoanTableID'>";
	DB_data_seek($Result, 0);
	$SQL = 'SELECT loantableid, loantabledesc FROM prlloantable';
	$Result = DB_query($SQL);
	while ($MyRow = DB_fetch_array($Result)) {
		if ($MyRow['loantableid'] == $_POST['LoanTableID']) {
			echo '<option selected="selected" value=';
		} else {
			echo '<option value=';
		}
		echo $MyRow['loantableid'] . '>' . $MyRow['loantabledesc'];
	} //end while loop
	echo '<tr><td>' . _('Loan Amount') . ':</td>
		<td><input type="text" name="LoanAmount" size=14 maxlength=12 value="' . $_POST['LoanAmount'] . '"></td></tr>';
	echo '<tr><td>' . _('Amortization') . ':</td>
		<td><input type="text" name="Amortization" size=14 maxlength=12 value="' . $_POST['Amortization'] . '"></td></tr>';
	echo '</select></td></tr><tr><td>' . _('Start Deduction') . ' (' . $_SESSION['DefaultDateFormat'] . '):</td>
	<td><input type="text" name="StartDeduction" size=12 maxlength=10 value="' . $_POST['StartDeduction'] . '"></td></tr>';
	echo '<tr><td>' . _('Account Code') . ":</td><td><select name='AccountCode'>";
	DB_data_seek($Result, 0);
	$SQL = 'SELECT accountcode, accountname FROM chartmaster';
	$Result = DB_query($SQL);
	while ($MyRow = DB_fetch_array($Result)) {
		if ($MyRow['accountcode'] == $_POST['AccountCode']) {
			echo '<option selected="selected" value=';
		} else {
			echo '<option value=';
		}
		echo $MyRow['accountcode'] . '>' . $MyRow['accountname'];
	} //end while loop
	if (isset($_POST['New'])) {
		echo '</table><P><input type="submit" name="submit" value="' . _('Add These New Employee Loan Details') . '"></form>';
	} else {
		echo '</table><P><input type="submit" name="submit" value="' . _('Update Employee Loan') . '">';
		echo '<P><FONT COLOR=red><B>' . _('WARNING') . ': ' . _('There is no second warning if you hit the delete button below') . '. ' . _('However checks will be made to ensure there are no outstanding purchase orders or existing accounts payable transactions before the deletion is processed') . '<BR></FONT></B>';
		echo '<input type="submit" name="delete" value="' . _('Delete Employee Loan') . '"onclick=\"return confirm("' . _('Are you sure you wish to delete this employee loan?') . '");\"></form>';
		//echo "<BR><A HREF='$RootPath/SupplierContacts.php?" . SID . "SupplierID=$SupplierID'>" . _('Review Contact Details') . '</A>';
		
	}
} // end of main ifs
include ('includes/footer.php');
?>
