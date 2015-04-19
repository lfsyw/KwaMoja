<?php

include('includes/session.inc');

$Title = _('Units Of Measure');

include('includes/header.inc');
echo '<p class="page_title_text" ><img src="' . $RootPath . '/css/' . $_SESSION['Theme'] . '/images/magnifier.png" title="' . _('Search') . '" alt="" />' . ' ' . $Title . '</p>';

if (isset($_GET['SelectedMeasureID']))
	$SelectedMeasureID = $_GET['SelectedMeasureID'];
elseif (isset($_POST['SelectedMeasureID']))
	$SelectedMeasureID = $_POST['SelectedMeasureID'];

if (isset($_POST['Submit'])) {

	//initialise no input errors assumed initially before we test

	$InputError = 0;

	/* actions to take once the user has clicked the submit button
	ie the page has called itself with some user input */

	//first off validate inputs sensible

	if (trim($_POST['MeasureName']) == '') {
		$InputError = 1;
		prnMsg(_('The unit of measure may not be empty'), 'error');
	}

	if (isset($_POST['SelectedMeasureID']) and $_POST['SelectedMeasureID'] != '' and $InputError != 1) {


		/*SelectedMeasureID could also exist if submit had not been clicked this code would not run in this case cos submit is false of course  see the delete code below*/
		// Check the name does not clash
		$SQL = "SELECT count(*) FROM unitsofmeasure
				WHERE unitid <> '" . $SelectedMeasureID . "'
				AND unitname " . LIKE . " '" . $_POST['MeasureName'] . "'";
		$Result = DB_query($SQL);
		$MyRow = DB_fetch_row($Result);
		if ($MyRow[0] > 0) {
			$InputError = 1;
			prnMsg(_('The unit of measure can not be renamed because another with the same name already exist.'), 'error');
		} else {
			// Get the old name and check that the record still exist neet to be very carefull here
			// idealy this is one of those sets that should be in a stored procedure simce even the checks are
			// relavant
			$SQL = "SELECT unitname FROM unitsofmeasure
				WHERE unitid = '" . $SelectedMeasureID . "'";
			$Result = DB_query($SQL);
			if (DB_num_rows($Result) != 0) {
				// This is probably the safest way there is
				$MyRow = DB_fetch_row($Result);
				$OldMeasureName = $MyRow[0];
				$SQL = array();
				$SQL[] = "UPDATE unitsofmeasure
					SET unitname='" . $_POST['MeasureName'] . "'
					WHERE unitname " . LIKE . " '" . DB_escape_string($OldMeasureName) . "'";
				$SQL[] = "UPDATE stockmaster
					SET units='" . $_POST['MeasureName'] . "'
					WHERE units " . LIKE . " '" . DB_escape_string($OldMeasureName) . "'";
			} else {
				$InputError = 1;
				prnMsg(_('The unit of measure no longer exist.'), 'error');
			}
		}
		$Msg = _('Unit of measure changed');
	} elseif ($InputError != 1) {
		/*SelectedMeasureID is null cos no item selected on first time round so must be adding a record*/
		$SQL = "SELECT count(*) FROM unitsofmeasure
				WHERE unitname " . LIKE . " '" . $_POST['MeasureName'] . "'";
		$Result = DB_query($SQL);
		$MyRow = DB_fetch_row($Result);
		if ($MyRow[0] > 0) {
			$InputError = 1;
			prnMsg(_('The unit of measure can not be created because another with the same name already exists.'), 'error');
		} else {
			$SQL = "INSERT INTO unitsofmeasure (unitname )
					VALUES ('" . $_POST['MeasureName'] . "')";
		}
		$Msg = _('New unit of measure added');
	}

	if ($InputError != 1) {
		//run the SQL from either of the above possibilites
		if (is_array($SQL)) {
			$Result = DB_Txn_Begin();
			$tmpErr = _('Could not update unit of measure');
			$tmpDbg = _('The sql that failed was') . ':';
			foreach ($SQL as $stmt) {
				$Result = DB_query($stmt, $tmpErr, $tmpDbg, true);
				if (!$Result) {
					$InputError = 1;
					break;
				}
			}
			if ($InputError != 1) {
				$Result = DB_Txn_Commit();
			} else {
				$Result = DB_Txn_Rollback();
			}
		} else {
			$Result = DB_query($SQL);
		}
		prnMsg($Msg, 'success');
	}
	unset($SelectedMeasureID);
	unset($_POST['SelectedMeasureID']);
	unset($_POST['MeasureName']);

} elseif (isset($_GET['delete'])) {
	//the link to delete a selected record was clicked instead of the submit button
	// PREVENT DELETES IF DEPENDENT RECORDS IN 'stockmaster'
	// Get the original name of the unit of measure the ID is just a secure way to find the unit of measure
	$SQL = "SELECT unitname FROM unitsofmeasure
		WHERE unitid = '" . $SelectedMeasureID . "'";
	$Result = DB_query($SQL);
	if (DB_num_rows($Result) == 0) {
		// This is probably the safest way there is
		prnMsg(_('Cannot delete this unit of measure because it no longer exist'), 'warn');
	} else {
		$MyRow = DB_fetch_row($Result);
		$OldMeasureName = $MyRow[0];
		$SQL = "SELECT COUNT(*) FROM stockmaster WHERE units " . LIKE . " '" . DB_escape_string($OldMeasureName) . "'";
		$Result = DB_query($SQL);
		$MyRow = DB_fetch_row($Result);
		if ($MyRow[0] > 0) {
			prnMsg(_('Cannot delete this unit of measure because inventory items have been created using this unit of measure'), 'warn');
			echo '<br />' . _('There are') . ' ' . $MyRow[0] . ' ' . _('inventory items that refer to this unit of measure') . '</font>';
		} else {
			$SQL = "DELETE FROM unitsofmeasure WHERE unitname " . LIKE . "'" . DB_escape_string($OldMeasureName) . "'";
			$Result = DB_query($SQL);
			prnMsg($OldMeasureName . ' ' . _('unit of measure has been deleted') . '!', 'success');
		}
	} //end if account group used in GL accounts
	unset($SelectedMeasureID);
	unset($_GET['SelectedMeasureID']);
	unset($_GET['delete']);
	unset($_POST['SelectedMeasureID']);
	unset($_POST['MeasureID']);
	unset($_POST['MeasureName']);
}

if (!isset($SelectedMeasureID)) {

	/* An unit of measure could be posted when one has been edited and is being updated
	or GOT when selected for modification
	SelectedMeasureID will exist because it was sent with the page in a GET .
	If its the first time the page has been displayed with no parameters
	then none of the above are true and the list of account groups will be displayed with
	links to delete or edit each. These will call the same page again and allow update/input
	or deletion of the records*/

	$SQL = "SELECT unitid,
			unitname
			FROM unitsofmeasure
			ORDER BY unitid";

	$ErrMsg = _('Could not get unit of measures because');
	$Result = DB_query($SQL, $ErrMsg);

	echo '<table class="selection">
			<tr>
				<th class="SortableColumn">' . _('Units of Measure') . '</th>
			</tr>';

	$k = 0; //row colour counter
	while ($MyRow = DB_fetch_row($Result)) {

		if ($k == 1) {
			echo '<tr class="EvenTableRows">';
			$k = 0;
		} else {
			echo '<tr class="OddTableRows">';
			++$k;
		}

		echo '<td>' . $MyRow[1] . '</td>';
		echo '<td><a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?SelectedMeasureID=' . urlencode($MyRow[0]) . '">' . _('Edit') . '</a></td>';
		echo '<td><a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?SelectedMeasureID=' . urlencode($MyRow[0]) . '&amp;delete=1" onclick="return MakeConfirm(\'' . _('Are you sure you wish to delete this unit of measure?') . '\', \'Confirm Delete\', this);">' . _('Delete') . '</a></td>';
		echo '</tr>';

	} //END WHILE LIST LOOP
	echo '</table>';
} //end of ifs and buts!


if (isset($SelectedMeasureID)) {
	echo '<div class="centre">
			<a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '">' . _('Review Units of Measure') . '</a>
		</div>';
}

if (!isset($_GET['delete'])) {

	echo '<form method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '">';
	echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';

	if (isset($SelectedMeasureID)) {
		//editing an existing section

		$SQL = "SELECT unitid,
				unitname
				FROM unitsofmeasure
				WHERE unitid='" . $SelectedMeasureID . "'";

		$Result = DB_query($SQL);
		if (DB_num_rows($Result) == 0) {
			prnMsg(_('Could not retrieve the requested unit of measure, please try again.'), 'warn');
			unset($SelectedMeasureID);
		} else {
			$MyRow = DB_fetch_array($Result);

			$_POST['MeasureID'] = $MyRow['unitid'];
			$_POST['MeasureName'] = $MyRow['unitname'];

			echo '<input type="hidden" name="SelectedMeasureID" value="' . $_POST['MeasureID'] . '" />';
			echo '<table class="selection">';
		}

	} else {
		$_POST['MeasureName'] = '';
		echo '<table>';
	}
	echo '<tr>
		<td>' . _('Unit of Measure') . ':' . '</td>
		<td><input type="text" name="MeasureName" size="15" required="required" maxlength="15" value="' . $_POST['MeasureName'] . '" /></td>
		</tr>';
	echo '</table>';

	echo '<div class="centre">
			<input type="submit" name="Submit" value="' . _('Enter Information') . '" />
		</div>';

	echo '</form>';

} //end if record deleted no point displaying form to add record

include('includes/footer.inc');
?>