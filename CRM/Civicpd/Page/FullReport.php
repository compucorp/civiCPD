<?php

require_once 'CRM/Core/Page.php';

class CRM_Civicpd_Page_FullReport extends CRM_Core_Page {


    function run() {
        CRM_Core_Resources::singleton()->addStyleFile('ca.lunahost.civicpd', 'civicpd.css');
        CRM_Core_Resources::singleton()->addScriptFile('ca.lunahost.civicpd', 'js/table_sorter.js', 0 , 'page-header');
        CRM_Core_Resources::singleton()->addScriptFile('ca.lunahost.civicpd', 'js/table_filter.js', 0, 'page-header');
        CRM_Core_Resources::singleton()->addScriptFile('ca.lunahost.civicpd', 'js/full_report.js', 0, 'page-header');

        // initialise CiviCRM session object
        $session = CRM_Core_Session::singleton();

        if (isset($_POST)) {
            $out = '';

            if (isset($_POST['csv_hdr'])) {
                // CSV header
                $out .= $_POST['csv_hdr'];
                $out .= "\n";

                if (isset($_POST['csv_output'])) {
                    $out .= $_POST['csv_output'];
                }

                $filename = 'CPD Full Report' . '_' . date("Y-m-d",time());

                // IES-7:Fixed by obsulete these lines
                /* 
                header("Content-type: application/vnd.ms-excel");
                header("Content-disposition: csv" . date("Y-m-d") . ".csv");
                header("Content-disposition: filename=".$filename.".csv");
                print $out;
                */

                $csvResult = $session->get("cpdCsvExport");
                if(isset($csvResult)){
                    // Prepare header
                    $header = explode(', ', $_POST['csv_hdr']);
                    // Generate CSV using CiviCRM function
                    CRM_Core_Report_Excel::writeCSVFile($filename, $header, $csvResult);
                    CRM_Utils_System::civiExit();
                }

                exit();
           }

            $this->runAction();
        }

        $sql = "SELECT * FROM civi_cpd_defaults";
        $dao = CRM_Core_DAO::executeQuery($sql);
        $arr_defaults = array();
        $x = 0;

        while ($dao->fetch()) {
           	$arr_defaults[$dao->name] = $dao->value;
           	$x++;
        }

        if (is_array ($arr_defaults)) {

        	if (isset($arr_defaults['organization_member_number'])) {
                $organization_member_number_field = $arr_defaults['organization_member_number'];
        	} else {
                $organization_member_number_field = 'civicrm_contact.external_identifier';
        	}

        	if(isset($arr_defaults['long_name'])) {
                $long_name = $arr_defaults['long_name'];
        	} else {
                $long_name = 'Continuing Professional Development';
        	}

        	if(isset($arr_defaults['short_name'])) {
                $short_name = $arr_defaults['short_name'];
        	} else {
                $short_name = 'CPD';
        	}

        } else {
            $organization_member_number_field = 'civicrm_contact.external_identifier';
            $long_name = 'Continuing Professional Development';
            $short_name = 'CPD';
        }

        $sql = 'SELECT membership_id FROM civi_cpd_membership_type';
        $dao = CRM_Core_DAO::executeQuery($sql);
        $arr_membership_types = array();
        $x = 0;

        while ($dao->fetch()) {
           	$arr_membership_types[$x] = $dao->membership_id;
           	$x++;
        }

        $current_year = date("Y");

        if (!isset($_SESSION["report_year"])) {
            $_SESSION["report_year"] = $current_year;
        }

        $select_years = '';

        for($i=$current_year; $i>=($current_year-15); $i--) {
            $selected = '';

            if ($i == $_SESSION["report_year"]) {
                $selected = 'selected';
            }

            $select_years .= '<option value="' . $i . '" ' . $selected . '>' . $i .
                '</option>';
        }

        $this->assign('select_years', $select_years);
        $sql = "SELECT id, category FROM civi_cpd_categories ORDER BY id ASC";
        $dao = CRM_Core_DAO::executeQuery($sql);
        $arr_categories = array();
        $x = 0;

        while ($dao->fetch()) {
           	$arr_categories[$x]["category"] = $dao->category;
           	$x++;
        }

        $report_table = '<table class="sortable order-table table" id="sorter"  ' .
            'border="0" cellspacing="0" cellpadding="0"><tr>' .
            '<th class="head" nowrap>Approve</th>' .
            '<th class="head" nowrap>Last Name</th>' .
            '<th class="head" nowrap>First Name</th>' .
        //        '<th class="head" nowrap>Member Number</th>' .
            '<th class="head" nowrap>Membership Type</th>' .
            '<th class="head" nowrap>Member Since</th>' .
            '<th class="head" nowrap>Uploaded Document</th>' .
            '<th class="head" nowrap>Total Hours</th>'.
            '<th class="head" nowrap>Uploaded Activity</th>' ;

        //CSV Header
        $csv_hdr = 'Last Name, First Name, Display Name, Contact ID, Email, Membership Type, Member Since, Uploaded Activity, ';

        for($x =0; $x < count($arr_categories); $x++ ) {
            $report_table .= '<th class="head" nowrap>' . $arr_categories[$x]["category"] .
                '</th>';
            $csv_hdr .= $arr_categories[$x]["category"] . ', ';
        }

        //CSV Header
        $csv_hdr .= 'Total Hours, Uploaded Document, Approved';
        $report_table .= '</tr>';
                // civicrm_membership_type.name AS member_type,
        $sql = "
            SELECT civicrm_contact.id,
                civicrm_contact.last_name,
                civicrm_contact.first_name,
                civicrm_contact.display_name,
                civicrm_contact.external_identifier,
                civicrm_contact.user_unique_id,
                civicrm_membership.membership_type_id,
                civicrm_membership.id AS membership_id,
                MIN(civicrm_membership.join_date) AS member_since,
                
                group_concat(civicrm_membership_type.name, ' ' ORDER BY civicrm_membership_type.name ASC) as member_type,

                civicrm_email.email,
                activities.credit_date,
                activities.evidence

            FROM civicrm_contact

            INNER JOIN civicrm_membership
                ON civicrm_contact.id = civicrm_membership.contact_id

            INNER JOIN civicrm_membership_type
                ON civicrm_membership.membership_type_id = civicrm_membership_type.id

            LEFT JOIN civicrm_membership_status
                ON civicrm_membership_status.id = civicrm_membership.status_id

            LEFT JOIN civicrm_email
                ON ( civicrm_email.contact_id = civicrm_contact.id AND civicrm_email.is_primary = 1 )

            LEFT JOIN civi_cpd_activities AS activities
                ON (
                    activities.contact_id = civicrm_contact.id
                    AND activities.category_id = " . CPD_FULL_CPD_CATEGORY_ID . "
                    AND YEAR(activities.credit_date) = " . $_SESSION['report_year'] . "
                )

            WHERE
                civicrm_contact.first_name IS NOT NULL
                AND civicrm_contact.last_name IS NOT NULL
                AND civicrm_membership_status.name NOT IN ('Expired', 'Deceased', 'Resigned')
                AND civicrm_membership.join_date < DATE('".$_SESSION["report_year"]."-01-01')
            GROUP BY civicrm_contact.id 

            ORDER BY civicrm_contact.last_name ";

        $dao = CRM_Core_DAO::executeQuery($sql);
        $last_contact_id = "";
        $arr_members = array();

        $tempsql = "SELECT * FROM civi_cpd_activities WHERE civi_cpd_activities." .
            "credit_date >= '" . $_SESSION["report_year"] . "-01-01' AND " .
            "civi_cpd_activities.credit_date  < '" . ($_SESSION["report_year"] + 1) .
            "-01-01'";

        $tempQuery = 'CREATE TEMPORARY TABLE civi_cpd_activities_temp1 AS ' . $tempsql;

        CRM_Core_DAO::executeQuery($tempQuery);
        $csv_output = "";

        //Populate and format result into table tag
        while ($dao->fetch()) {
            if(!is_null($dao->last_name) || !is_null($dao->first_name)) {
                if(in_array ( $dao->membership_type_id , $arr_membership_types ) && $dao->id != $last_contact_id) {
                    $approved = CRM_Civicpd_Page_CPDReport::getApprovalStatus($dao->id);
                    $last_contact_id = $dao->id;
                    $report_table .= '<tr>';
                    $report_table .= '<td><input class="approve" type="checkbox"';
                    $report_table .= $approved ? ' checked="checked" ' : ' ';
                    $report_table .= 'data-cid="' . $dao->id . '"/></td>';
                    $report_table .= '<td><a href="/civicrm/contact/view?cid=' . $dao->id . '">' . $dao->last_name . '</a></td>';
                    $report_table .= '<td>' . $dao->first_name . '</td>';
                    $csv_output .= $dao->last_name . ', ' . $dao->first_name . ', ' . $dao->display_name . ', ';
                    $csv_output .= $dao->id . ', ' . $dao->email . ', ';

        //                if($organization_member_number_field == 'civicrm_contact.user_unique_id'){
        //                    $report_table .= '<td>' . $dao->user_unique_id . '</td>';
        //                    $csv_output .= $dao->user_unique_id . ', ';
        //                }
        //                elseif($organization_member_number_field == 'civicrm_membership.id') {
        //                    $report_table .= '<td>' . $dao->membership_id . '</td>';
        //                    $csv_output .= $dao->membership_id . ', ';
        //                }
        //                else {
        //                    $report_table .= '<td>' . $dao->external_identifier . '</td>';
        //                    $csv_output .= $dao->external_identifier . ', ';
        //                }

                    $report_table .= '<td>' . $dao->member_type . '</td>';
                    $memberSince = $dao->member_since
                      ? DateTime::createFromFormat('Y-m-d', $dao->member_since)->format('d-m-Y')
                      : '';
                    $report_table .= '<td>' . $memberSince . '</td>';
                    $csv_output .= $dao->member_type . ', ' . $dao->member_since . ', ';            

                    $sql = "SELECT civi_cpd_categories.id AS id" .
                        ", civi_cpd_categories.category AS category" .
                        ", SUM(civi_cpd_activities_temp1.credits) AS credits" .
                        ", civi_cpd_categories.minimum" .
                        ", civi_cpd_categories.description " .
                        // Fixed IES-46: CPD Report shows incorrect activity hours
                        "FROM (SELECT * FROM civi_cpd_categories ".
                        "UNION ALL ".
                        "SELECT 0 as id, 'Upload activity' as category, 'Uploaded Activity' as description, 100 as minimum, null as maximum) as civi_cpd_categories ".
                        "LEFT OUTER JOIN civi_cpd_activities_temp1 " .
                        "ON civi_cpd_activities_temp1.category_id = civi_cpd_categories.id " .
                        "AND civi_cpd_activities_temp1.contact_id = " . $dao->id .
                        " GROUP BY civi_cpd_categories.id";

                    $subdao = CRM_Core_DAO::executeQuery($sql);

                    $total_credits = 0;
                    $sub_cells = "";

                    while ($subdao->fetch()) {
                        $total_credits += abs($subdao->credits);
                        $sub_cells .= '<td>' . abs($subdao->credits) . '</td>';
                        $csv_output .= abs($subdao->credits) . ', ';
                    }

                    // $report_table .= '<td><a href="' . '">' . '</a></td>';
                    $report_table .= '<td>';
                    $report_table .= $dao->evidence
                      ? '<a target="_blank" href="' . civi_cpd_report_get_full_cpd_pdf_url($dao->evidence) . '"> '
                            . civi_crm_report_convert_mysql_to_uk_date($dao->credit_date) . '</a>'
                      : '';
                    $report_table .= '</td>';

                    $report_table .= '<td class="total-credits">' . $total_credits . '</td>';
                    $report_table .= $sub_cells;
                    $report_table .= '</tr>';
                    $csv_output .= $total_credits  . ', ';
                    $csv_output .= ($dao->evidence ? civi_crm_report_convert_mysql_to_uk_date($dao->credit_date) : '') . ', ';
                    $csv_output .= ($approved ? 'Yes' : 'No') . "\n";
                    
                }
            }
        }

        // Compose result for CiviCRM CSV Export function
        $civiCSVResult = array();
        $csvOutputs = explode("\n", $csv_output);
        foreach ($csvOutputs as $key => $item) {
            // Prepare values
            $csvItem = explode(", ", $item);
            $csvRow = array();
            // Prepare header
            $csvHeaders = explode(", ", $csv_hdr);
            foreach ($csvHeaders as $key => $field) {
                // Map header with value
                $csvRow[$field] = $csvItem[$key];
            }
            // Put row in list
            $civiCSVResult[] = $csvRow;
        }

        // Store in session
        $session->set("cpdCsvExport",$civiCSVResult);

        // Prepare result for CSV export
        $report_table .= '</table>';
        $export_table = '<div id="export-csv"><form name="export" action="/civicrm/civicpd/fullreport" method="POST">
            <input type="submit" value="Export to CSV" id="button" alt="Export Search Results to CSV File"/>
            <input type="hidden" value="' . $csv_hdr . '" name="csv_hdr"/>
            </form></div>';
            // <input type="hidden" value="' . $csv_output . '" name="csv_output"/>

        $this->assign( 'export_table', $export_table );
        CRM_Utils_System::setTitle(ts('Review ' . $short_name . ' Full Report'));
        $this->assign( 'report_table', $report_table );

        parent::run();
    }


    public function approveActivity($approved) {
        $sql
          = "
            UPDATE civi_cpd_activities
            SET approved = %0
            WHERE
            	contact_id = %1
        	    AND YEAR(credit_date) = %2
        ";

        $params = array(
          array((int) $approved, 'Integer'),
          array((int) $_REQUEST['cid'], 'Integer'),
          array((int) $_REQUEST['year'], 'Integer')
        );

        try {
            CRM_Core_DAO::executeQuery($sql, $params);
            echo json_encode(array('status' => 1));
        } catch (Exception $e) {
            echo json_encode(array('status' => 0, 'error_msg' => $e->getMessage()));
        }
        exit;
    }


    private function runAction() {
        if (isset($_REQUEST['action'])) {
            switch ($_REQUEST['action']) {
                case 'approve':
                    $this->approveActivity(true);
                    break;

                case 'disapprove':
                    $this->approveActivity(false);
                    break;
            }
        }
    }

}
