<?php
/* $Id: GLCashFlowsSetup.php 7672 2016-11-17 03:27:51Z rchacon $ */
/* Classifies accounts in any of the three sections of statement of cash flows to assign each account to an activity */

// BEGIN: Procedure division ---------------------------------------------------
include('includes/session.php');
$Title = _('Cash Flows Activities Maintenance');
$ViewTopic = 'GeneralLedger';
$BookMark = 'GLCashFlowsSetup';
include('includes/header.php');

// Merges gets into posts:
if (isset($_GET['Action'])) {
	$_POST['Action'] = $_GET['Action'];
} elseif (!isset($_POST['Action'])) {
	$_POST['Action'] = '';
}

if (isset($_GET['PeriodProfitAccount'])) {
	$_POST['PeriodProfitAccount'] = $_GET['PeriodProfitAccount'];
}
if (isset($_GET['RetainedEarningsAccount'])) {
	$_POST['RetainedEarningsAccount'] = $_GET['RetainedEarningsAccount'];
}
// Do selected action:
switch ($_POST['Action']) {
	case _('Update'):
		// Updates config accounts:
		if ($_SESSION['PeriodProfitAccount'] != $_POST['PeriodProfitAccount']) {
			$SQL = "UPDATE config SET confvalue = '" . $_POST['PeriodProfitAccount'] . "' WHERE confname = 'PeriodProfitAccount'";
			$ErrMsg = _('Can not update chartmaster.cashflowsactivity because');
			$Result = DB_query($SQL, $ErrMsg);
			if ($Result) {
				$_SESSION['PeriodProfitAccount'] = $_POST['PeriodProfitAccount'];
				prnMsg(_('The net profit of the period GL account was updated'), 'success');
			}
		}
		if (isset($_POST['RetainedEarningsAccount']) and $_SESSION['RetainedEarningsAccount'] != $_POST['RetainedEarningsAccount']) {
			$SQL = "UPDATE companies SET retainedearnings = '" . $_POST['RetainedEarnings'] . "' WHERE coycode = 1";
			$ErrMsg = _('Can not update chartmaster.cashflowsactivity because');
			$Result = DB_query($SQL, $ErrMsg);
			if ($Result) {
				$_SESSION['RetainedEarningsAccount'] = $_POST['RetainedEarningsAccount'];
				prnMsg(_('The retained earnings GL account was updated'), 'success');
			}
		}
		break; // END Update.
	case _('Reset'):
		$SQL = "UPDATE `chartmaster` SET `cashflowsactivity`='-1'";
		$ErrMsg = _('Can not update chartmaster.cashflowsactivity because');
		$Result = DB_query($SQL, $ErrMsg);
		if ($Result) {
			prnMsg(_('The cash flow activity was reset in all accounts'), 'success');
		}
		break; // END Reset.
	case _('Automatic'):
		// Loads the criteria for assigning the cash flow activity to the account:
		// The last criterion overwrites the previous criteria. E.g.:
		// In English, use singular to englobe singular and plural (e.g. Loan vs. Loans).
		// Leave penultimate: Interests (e.g. Loan interests vs. Loans).
		// Leave last: depreciations, amortisations and adjustments (Building depreciation vs. Buildings).
		// Comment: MySQL queries are not case-sensitive by default.

		$Criterion = array();
		$i = 0;

		$Criterion[$i]['AccountLike'] = _('Cash');
		$Criterion[$i++]['CashFlowsActivity'] = 4;

		$Criterion[$i]['AccountLike'] = _('Bank');
		$Criterion[$i++]['CashFlowsActivity'] = 4;

		$Criterion[$i]['AccountLike'] = _('Investment');
		$Criterion[$i++]['CashFlowsActivity'] = 4;

		$Criterion[$i]['AccountLike'] = _('Commission');
		$Criterion[$i++]['CashFlowsActivity'] = 3;

		$Criterion[$i]['AccountLike'] = _('Share');
		$Criterion[$i++]['CashFlowsActivity'] = 3;

		$Criterion[$i]['AccountLike'] = _('Dividend');
		$Criterion[$i++]['CashFlowsActivity'] = 3;

		$Criterion[$i]['AccountLike'] = _('Interest');
		$Criterion[$i++]['CashFlowsActivity'] = 3;

		$Criterion[$i]['AccountLike'] = _('Loan');
		$Criterion[$i++]['CashFlowsActivity'] = 3;

		$Criterion[$i]['AccountLike'] = _('Building');
		$Criterion[$i++]['CashFlowsActivity'] = 2;

		$Criterion[$i]['AccountLike'] = _('Equipment');
		$Criterion[$i++]['CashFlowsActivity'] = 2;

		$Criterion[$i]['AccountLike'] = _('Land');
		$Criterion[$i++]['CashFlowsActivity'] = 2;

		$Criterion[$i]['AccountLike'] = _('Vehicle');
		$Criterion[$i++]['CashFlowsActivity'] = 2;

		$Criterion[$i]['AccountLike'] = _('Sale');
		$Criterion[$i++]['CashFlowsActivity'] = 1;

		$Criterion[$i]['AccountLike'] = _('Cost');
		$Criterion[$i++]['CashFlowsActivity'] = 1;

		$Criterion[$i]['AccountLike'] = _('Receivable');
		$Criterion[$i++]['CashFlowsActivity'] = 1;

		$Criterion[$i]['AccountLike'] = _('Inventory');
		$Criterion[$i++]['CashFlowsActivity'] = 1;

		$Criterion[$i]['AccountLike'] = _('Payable');
		$Criterion[$i++]['CashFlowsActivity'] = 1;

		$Criterion[$i]['AccountLike'] = _('Adjustment');
		$Criterion[$i++]['CashFlowsActivity'] = 0;

		$Criterion[$i]['AccountLike'] = _('Amortisation');
		$Criterion[$i++]['CashFlowsActivity'] = 0;

		$Criterion[$i]['AccountLike'] = _('Depreciation');
		$Criterion[$i++]['CashFlowsActivity'] = 0;

		foreach ($Criterion as $Criteria) {
			$SQL = "UPDATE `chartmaster`
				SET `cashflowsactivity`=" . $Criteria['CashFlowsActivity'] . "
				WHERE `accountname` LIKE '%" . addslashes(_($Criteria['AccountLike'])) . "%'
				AND `cashflowsactivity`=-1"; // Uses cashflowsactivity=-1 to NOT overwrite.
			$ErrMsg = _('Can not update chartmaster.cashflowsactivity. Error code:');
			$Result = DB_query($SQL, $ErrMsg);
			// RChacon: Count replacements.
		}
		if ($Result) {
			prnMsg(_('The cash flow activity was updated in some accounts'), 'success'); // RChacon: Show replacements done.
		}
		break; // END Automatic.
	case _('Manual'):
		echo "<script>window.location = 'GLAccounts.php';</script>";
		die();
	default:
		// No reset , nor Automatic
}

echo '<p class="page_title_text"><img alt="" src="', $RootPath, '/css/', $_SESSION['Theme'], '/images/maintenance.png" title="', $Title, '" /> ', $Title, '</p>';
// BEGIN menu.
if (!isset($page_help) OR $page_help) {
	// If it is not set the $page_help parameter OR it is TRUE, shows the page help text:
	echo '<div class="page_help_text">', _('The statement of cash flows, using direct and indirect methods, is partitioned into three sections: operating activities, investing activities and financing activities.'), '<br />', _('You must classify all accounts in any of those three sections of the cash flow statement, or as no effect on cash flow, or as cash or cash equivalent.'), '</div>';
}
// Show a form to allow input of the action for the script to do:
echo '<form action="', htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'), '" method="post">', '<input name="FormID" type="hidden" value="', $_SESSION['FormID'], '" />', // Form's head.

// Input table:
	'<table class="selection">',
// Content of the header and footer of the output table:
	'<thead>
			<tr>
				<th colspan="2">', _('Action to do'), '</th>
			</tr>
		</thead><tfoot>
			<tr>
				<td colspan="2">', '<div class="centre">', '<input name="Action" type="submit" value="', _('Update'), '" />', // "Update" button.
	'<input name="Action" type="submit" value="', _('Reset'), '" />', // "Reset values" button.
	'<input name="Action" type="submit" value="', _('Automatic'), '" />', // "Automatic setup" button.
	'<input name="Action" type="submit" value="', _('Manual'), '" />', // "Manual setup" button.
	'</div>', '</td>
			</tr>
		</tfoot><tbody>';
$SQL = "SELECT accountcode,
				accountname
			FROM chartmaster
			INNER JOIN accountgroups
				ON chartmaster.groupcode=accountgroups.groupcode
				AND chartmaster.language=accountgroups.language
			WHERE accountgroups.pandl=0
				AND chartmaster.Language='" . $_SESSION['ChartLanguage'] . "'
			ORDER BY chartmaster.accountcode";
$GLAccounts = DB_query($SQL);
// Setups the net profit for the period GL account:
echo '<tr>
		<td><label for="PeriodProfitAccount">', _('Net profit for the period GL account'), ':</label></td>
		<td><select id="PeriodProfitAccount" name="PeriodProfitAccount" required="required">';
if (!isset($_SESSION['PeriodProfitAccount'])) {
	$_SESSION['PeriodProfitAccount'] = '';
	$SQL = "SELECT confvalue FROM `config` WHERE confname ='PeriodProfitAccount'";
	$Result = DB_query($SQL);
	$MyRow = DB_fetch_array($Result);
	if ($MyRow) {
		$_SESSION['PeriodProfitAccount'] = $MyRow['confvalue'];
	} else {
		// RChacon: Search account with _('period') in accountname.
	}
}
foreach ($GLAccounts as $MyRow) {
	echo '<option', ($MyRow['accountcode'] == $_SESSION['PeriodProfitAccount'] ? ' selected="selected"' : ''), ' value="', $MyRow['accountcode'], '">', $MyRow['accountcode'], ' - ', $MyRow['accountname'], '</option>';
}
echo '</select>', (!isset($field_help) || $field_help ? _('GL account to post the net profit for the period') : ''), // If it is not set the $field_help parameter OR it is TRUE, shows the page help text.*/
	'</td>
</tr>';
// Setups the retained earnings GL account:
echo '<tr>
		<td><label for="RetainedEarningsAccount">', _('Retained earnings GL account'), ':</label></td>
		<td><select id="RetainedEarningsAccount" name="RetainedEarningsAccount" required="required">';
if (!isset($_SESSION['RetainedEarningsAccount'])) {
	$_SESSION['RetainedEarningsAccount'] = '';
	$SQL = "SELECT retainedearnings FROM companies WHERE coycode = 1";
	$Result = DB_query($SQL);
	$MyRow = DB_fetch_array($Result);
	if ($MyRow) {
		$_SESSION['RetainedEarningsAccount'] = $MyRow['confvalue'];
	} else {
		// RChacon: Search account with _('earnings') in accountname.
	}
}
foreach ($GLAccounts as $MyRow) {
	echo '<option', ($MyRow['accountcode'] == $_SESSION['RetainedEarningsAccount'] ? ' selected="selected"' : ''), ' value="', $MyRow['accountcode'], '">', $MyRow['accountcode'], ' - ', $MyRow['accountname'], '</option>';
}
echo '</select>', (!isset($field_help) || $field_help ? _('GL account to post the retained earnings') : ''), // If it is not set the $field_help parameter OR it is TRUE, shows the page help text.*/
	'</td>
			</tr>
		</tbody>
		</table>
	</form>';

include('includes/footer.php');
// END: Procedure division -----------------------------------------------------
?>