<?php

/**
 * @file This file is a part of the CiviCPD extension.        
 *               
 * This file is the user's reporting control panel and provides a snapshot of 
 * their CPD activities and credits based on the year in question.                                                       |
*/

require_once 'CRM/Core/Page.php';

class CRM_Civicpd_Page_CPDReport extends CRM_Core_Page {
    static public $totalCredits = 0;
  
    function run() {
        
        CRM_Core_Resources::singleton()->addStyleFile('ca.lunahost.civicpd', 'civicpd.css');
        CRM_Core_Resources::singleton()->addScriptFile('ca.lunahost.civicpd', 'js/report.js', 0 , 'page-header');
        
        $session = CRM_Core_Session::singleton();

        civi_cpd_report_set_contact_id($session->get('userID'));
        civi_cpd_report_set_year();
        civi_cpd_report_unset_cpd_message();

        if(isset($_GET['action']) || isset($_POST['action'])) {
            switch ($_REQUEST['action']) {
                /** Import by CSV is no longer needed */
//                case 'import':
//                    civi_cpd_report_import_activity();
//                break;
                case 'import-pdf':
                    civi_cpd_report_import_activity_pdf();
                break;
            
                case 'insert':  
                    civi_cpd_report_insert_activity();
                    break;

                case 'update':
                    civi_cpd_report_update_activity();
                    break;

                case 'edit':
                    civi_cpd_report_set_editable_activity();
                    break;
                
                case 'download_pdf':
                    civi_cpd_report_download_pdf_activity();
                    break;
                
                case 'delete':
                    civi_cpd_report_delete_activity();
                    break;

                case 'delete_evidence':
                    civi_cpd_report_delete_evidence_pdf();
                    break;
            }
        }

        civi_crm_report_set_default_titles(civi_crm_report_get_default_variables());
        CRM_Utils_System::setTitle(ts(civi_crm_report_get_short_name() . ' Reporting'));
        civi_crm_report_set_user_details($session->get('userID'));

        $this->assign('select_years', civi_cpd_report_get_years_drop_down_list());
        $this->assign('today', civi_cpd_report_get_date());
        $this->assign('display_name', civi_crm_report_get_display_name());
        $this->assign('membership_number', civi_crm_report_get_membership_number());
        $this->assign('output', civi_crm_report_get_content());
        $this->assign('total_credits', civi_cpd_report_get_total_credits());
        $this->assign('uploaded_activity_list', civi_cpd_report_get_uploaded_activity_list(
            $session->get('userID')));
        $this->assign('approved', static::getApprovalStatus(civi_cpd_report_get_contact_id()));
        $this->assign('imageUrl', CPD_PATH . '/assets/approved.png');

        civi_cpd_report_unset_session();

        parent::run();
    }

    public static function incrementTotalCredits($credits) {
        static::$totalCredits += (int) $credits;
    }

    public static function getTotalCredits() {
        return static::$totalCredits;
    }

    static public function getApprovalStatus($cid) {
        $sql
          = "
            SELECT a.approved
            FROM civi_cpd_activities a
            WHERE
                a.contact_id = %0
           	    AND YEAR(a.credit_date) = %1
        ";

        $params = array(
          array((int) $cid, 'Integer'),
          array((int) $_SESSION['report_year'], 'Integer')
        );

        $dao = CRM_Core_DAO::executeQuery($sql, $params);

        $total = $approved = 0;
        while ($dao->fetch()) {
            if ($dao->approved) {
                $approved++;
            }

            $total++;
        }

        $dao->free();

        return ($total && $total == $approved);
    }
}

function civi_cpd_report_download_pdf_activity() {
    if (isset($_GET['upload_id']) && substr($_GET['upload_id'], 11) == civi_cpd_report_get_contact_id()) {
        $file = CPD_DIR . 'uploads/activity/' . $_GET['upload_id'] . '.pdf';
        
        if (file_exists($file)) { 
            header("Content-type:application/pdf");
            header("Content-Disposition:attachment;filename='uploaded_activity.pdf'");
            readfile($file); 
        }
    }
}

function civi_cpd_report_get_category($dao) {
    $pluspercent = (int)(($dao->credits / $dao->minimum) * 100);
    $category = '<tr valign="top">
    <td height="18">
      <h1>' . $dao->category . ': Total hours recorded: ' . $dao->credits . 'h</h1>'
      . '<p>' . $dao->description . '</p>'
      . '<div class="clear"></div>
    </td>
    </tr>
    <tr valign="top">
        <td><!-- put in buttons for add edit view -->
            <div class="edit-activity-buttons"> <a href="#" class="show-activity-list"' .
                ' id="category-' . $dao->id . '">Show</a>';

    $member_update_limit = civi_crm_report_get_member_update_limit();

    if ($_SESSION['report_year'] > (date("Y") - $member_update_limit) || $member_update_limit==0) {
        $category .= ' | <a class="new-activity-item" href="#" id="category-' . 
            $dao->id . '">New</a></div>';
    } 

    return $category;
}
//function civi_cpd_report_get_category($dao, $i) {
//    $pluspercent = (int)(($dao->credits / $dao->minimum) * 100);
//    $category = '<tr valign="top">
//    <td height="18"><span class="CE">' . $i . '. ' . $dao->category . '</span><br/>
//        <strong>' . abs($dao->credits) . ' hours </strong><br/>
//        (target ' . abs($dao->minimum) . ' hours)
//        <br/>' .  $dao->description . '
//        <div class="rating"><div class="graphcont"><div class="graph"><strong ' .
//            'class="bar" style="width:' . $pluspercent . '%;">' . $pluspercent .
//            '%</strong></div></div>
//        </div>
//        <div class="clear"></div>
//    </td>
//    </tr>
//    <tr valign="top">
//        <td><!-- put in buttons for add edit view -->
//            <div class="edit-activity-buttons"> <a href="#" class="show-activity-list"' .
//                ' id="category-' . $dao->id . '">Show</a>';
//
//    $member_update_limit = civi_crm_report_get_member_update_limit();
//
//    if ($_SESSION['report_year'] > (date("Y") - $member_update_limit) || $member_update_limit==0) {
//        $category .= ' | <a class="new-activity-item" href="#" id="category-' .
//            $dao->id . '">New</a></div>';
//    }
//
//    return $category;
//}

function civi_cpd_report_get_progress() {
    $credits = CRM_Civicpd_Page_CPDReport::getTotalCredits();
    $progressPercentage = 100 * $credits / CPD_MAX_CREDITS;
    $minPercentage = 100 * CPD_MIN_CREDITS / CPD_MAX_CREDITS;
    $color = $progressPercentage < $minPercentage ? 'red' : 'green';

    $progress =
       '<tr valign="top">
          <td height="18">
            <div class="graphcont">
              <div class="graph">
                <strong class="bar" style="width: ' . $progressPercentage . '%; background: ' . $color .'">' . $credits . ' h</strong>
                <span class="marker" style="width: ' .$minPercentage . '%;"><span>Target ' . CPD_MIN_CREDITS . ' h</span></span>
                <span class="marker" style="width: 100%;"><span>' . CPD_MAX_CREDITS . ' h</span></span>
              </div>
            </div>
            <div class="clear"></div>
          </td>
       </tr>';

    return $progress;
}

function civi_cpd_report_get_activity_table($category_id) {
    $sql = "SELECT civi_cpd_categories.category
        , civi_cpd_activities.id AS activity_id
        , civi_cpd_activities.credit_date
        , civi_cpd_activities.credits
        , civi_cpd_activities.activity
        , civi_cpd_activities.notes 
        , civi_cpd_activities.evidence
        FROM civi_cpd_categories
        INNER JOIN civi_cpd_activities 
        ON civi_cpd_categories.id = civi_cpd_activities.category_id 
        WHERE civi_cpd_activities.category_id = " . $category_id . " 
        AND contact_id = " . civi_cpd_report_get_contact_id() . " 
        AND EXTRACT(YEAR FROM credit_date) = " . $_SESSION['report_year'] . " 
        ORDER BY credit_date";   	

    $dao = CRM_Core_DAO::executeQuery($sql);

    $activity_table = '<div id="category-' . $category_id . '" class="activity-list">' . 
        civi_cpd_report_get_edit_activity_response($category_id) . 
        '<table width="100%" border="0" cellspacing="0" cellpadding="0"><tr>' .
        '<th width="15%">Date</th><th width="5%">Hours</th><th width="15%">Activity</th>' .
        '<th width="35%">Reflection</th><th width="15">Evidence</th><th width="15%">Action</th></tr>';

    if ($dao->N > 0) {
        while ($dao->fetch()) { 
            $dao->category_id = $category_id;
            $activity_table .= civi_cpd_report_get_activities_list($dao);
        }
    }
    
    $activity_table .= '</table></div>';

    return $activity_table;
}

function civi_cpd_report_get_manual_import($category_id) {
    $manual_import = '<div class="activity-item-manual">
        <form  method="post" action="/civicrm/civicpd/report" enctype="multipart/form-data">
            <input type="hidden" value="insert" name="action">
            <input type="hidden" value="'. $category_id .'" name="category_id">
            <table height="180px" width="50%" cellspacing="0" cellpadding="0" border="0" align="center">
                <tbody>
                    <tr>
                            <td width="5%" valign="top" nowrap="nowrap">Date (YYYY-MM-DD):</td>
                            <td width="60%"><input type="text" class="frm" size="30" ' . 
                                'id="credit_date" name="credit_date" value=' . 
                                civi_cpd_report_get_date() . '></td>
                    </tr>
                    <tr>
                            <td width="5%" valign="top" nowrap="nowrap">Title of activity:</td>
                            <td width="60%"><input type="text" size="30" class="frm" ' . 
                                'name="activity"></td>
                    </tr>
                            <td width="5%" valign="top">Number of hours:</td>
                            <td width="60%"><input type="text" maxlength="4" size="30" ' . 
                                'class="frm" name="credits"></td>
                    </tr>
                    <tr>
                            <td width="5%" valign="top" nowrap="nowrap">Notes and reflection on activity:</td>
                            <td width="60%"><textarea class="frm" rows="4" cols="39" ' . 
                                'name="notes"></textarea></td>
                    </tr>
                    <tr>
                            <td>Evidence</td>
                            <td><input type="file" name="evidence" id="evidence"></td>
                    </tr>
                    <tr>
                            <td align="center"><input class="validate form-submit ' . 
                                'default" type="submit" value="Submit" class="form-submit-inline"' . 
                                ' name="Submit"></td>
                            <td></td>
                    </tr>
                </tbody>
            </table>
        </form>
        </div> ';

    return $manual_import;
}

function civi_cpd_report_get_csv_import($category_id) {
    $csv_import = '<div class="activity-item-import">
    <p><em>Import CSV: ' . civi_cpd_report_get_add_activity_response('csv', $category_id) . '</em></p>
    <form  method="post" action="/civicrm/civicpd/report" enctype="multipart/form-data">
        <input type="hidden" value="import" name="action">
        <input type="hidden" value="' . $category_id . '" name="category_id">
        <table height="180px" width="50%" cellspacing="0" cellpadding="0" border="0"' . 
            ' align="center">
            <tbody>
                <tr>
                    <td colspan="2">
                        <p>Please follow the same stucture as this <a href="' . 
                        CPD_PATH . 'inc/example.csv">example CPD CSV file</a>.</p>
                    </td>
                </tr>
                <tr>
                    <td width="5%" valign="top" nowrap="nowrap">Import CSV:</td>
                    <td width="60%"><input type="file" name="file" id="file"></td>
                </tr>
                <tr>
                    <td align="center"><input type="submit" value="Submit" ' . 
                    'class="validate form-submit default" name="Submit"></td>
                    <td></td>
                </tr>
            </tbody>
        </table>
    </form>
    </div>';

    return $csv_import;
}

function civi_cpd_report_get_pdf_import($user_id) {
    
    // PDF post URL
    $pdf_post_url = CRM_Utils_System::url("civicrm/civicpd/report", null, true, null, false, true);
    
    $pdf_import = '<div class="activity-item-import-pdf">
    <form  method="post" action="' . $pdf_post_url . '"  enctype="multipart/form-data">
        <input type="hidden" value="import-pdf" name="action">
        <input type="hidden" value="' . $user_id . '" name="user_id">
        <input type="file" name="file" id="file">
        <input type="submit" value="Upload PDF" class="validate form-submit ' . 
            'default" name="Submit">
    </form>
    </div>';

    return $pdf_import;
}

function civi_cpd_report_get_add_activity_table($category_id) {
    $manual_import = civi_cpd_report_get_manual_import($category_id);
    $import = '<div class="activity-item" id="category-' . $category_id . '">' .
        $manual_import . '</div>';

    return $import;
}
//function civi_cpd_report_get_add_activity_table($category_id) {
//    $manual_import = civi_cpd_report_get_manual_import($category_id);
//    $csv_import = civi_cpd_report_get_csv_import($category_id);
//    $import = '<div class="activity-item" id="category-' . $category_id . '">' .
//        $manual_import . $csv_import . '</div>';
//
//    return $import;
//}

function civi_cpd_report_get_total_credits() {
    $sql = "SELECT SUM(credits) as total_credits 
    FROM civi_cpd_activities 
    WHERE contact_id = ". civi_cpd_report_get_contact_id() ." 
    AND EXTRACT(YEAR FROM civi_cpd_activities.credit_date) = " . $_SESSION['report_year'];

    $dao = CRM_Core_DAO::executeQuery($sql);

    while( $dao->fetch( ) ) {    
        $total_credits = abs($dao->total_credits);
    }

    return empty($total_credits) ? 0 : $total_credits;
}

function civi_cpd_report_delete_activity() {
    if (isset($_GET['upload_id'])) { 
        $path = CPD_DIR . 'uploads/activity/' . $_GET['upload_id'] . '.pdf';
        
        if (file_exists($path)) {
            $result = unlink($path);
            civi_cpd_report_set_activity_upload_response('delete', $result);
        } else {
            civi_cpd_report_set_activity_upload_response('delete', FALSE);
        }
    } elseif(isset($_GET['activity_id']) && isset($_GET['category_id'])) {
        civi_cpd_report_delete_evidence_pdf();

        CRM_Core_DAO::executeQuery("DELETE FROM civi_cpd_activities WHERE id =" . $_GET['activity_id']);
        civi_cpd_report_set_edit_activity_response('delete', $_GET['category_id'],
            TRUE);
    } else {
        civi_cpd_report_set_edit_activity_response('delete', $_GET['category_id'], 
            FALSE);
    } 
}

function civi_cpd_report_set_activity_upload_response($action, $success) {
    if ($success) {
        switch ($action) {
            case 'delete':
                $message = 'Activity has been successfully deleted';
                break;
            case 'add':
                $message = 'Activity has been successfully added.';
                break;
        }
        
        $class = 'success';
    } else {
        switch ($action) {
            case 'delete':
                $message = 'Activity has failed to be deleted';
                break;
            case 'add':
                $message = 'Activity has failed to be added, only one upload per a user allowed.';
                break;
        }
        
        $class = 'failure';
    }
    
    $_SESSION['civi_crm_report']['uploaded_activity_response'] = '<div ' .
        'class="uploaded-activity-response ' . $class . '">' . $message . '</div>';
}

function civi_cpd_report_set_edit_activity_response($action, $category_id, $success = FALSE) {
   if ($success) {
       switch ($action) {
           case 'delete':
               $message = 'Activity has been successfully deleted';
               break;
           case 'update':
               $message = 'Activity has been successfully updated';
       }
       $class = 'success';
   } else {
       switch ($action) {
           case 'delete':
               $message = 'Activity has failed to be deleted';
               break;
           case 'update':
               $message = 'Activity has failed to be updated';
       }
       $class = 'failure'; 
   }
   
   $_SESSION['civi_crm_report']['edit_response_category_id'] = $category_id;
   $_SESSION['civi_crm_report']['edit_response'] = '<div class="edit-activity-response ' . 
        $class . '" ' . 'id="category-' . $category_id . '">' . $message . '</div>';
}

function civi_cpd_report_get_activity_upload_response() {
    return isset($_SESSION['civi_crm_report']['uploaded_activity_response']) ? 
        $_SESSION['civi_crm_report']['uploaded_activity_response'] : NULL;
}

function civi_cpd_report_get_edit_activity_response($category_id) {
    if ($category_id == civi_cpd_report_get_edit_activity_response_category_id()) {
        return isset($_SESSION['civi_crm_report']['edit_response']) ? 
            $_SESSION['civi_crm_report']['edit_response'] : NULL;
    }
}

function civi_cpd_report_get_edit_activity_response_category_id() {
    return isset($_SESSION['civi_crm_report']['edit_response_category_id']) ? 
        $_SESSION['civi_crm_report']['edit_response_category_id'] : NULL;
}

function civi_cpd_report_set_add_activity_response($type, $category_id, $success = FALSE) {
    if ($success) {
        $class = 'success';
        $message = 'CSV file has been successfully imported';
    } else {
        $class = 'failure'; 
        $message = 'CSV file has failed to be imported';
    }
    
    $_SESSION['civi_crm_report']['edit_response_category_id'] = $category_id;
    $_SESSION['civi_crm_report'][$type . '-import-response'] = '<span ' . 
        'class="add-activity-response ' . $class . '" ' . 'id="category-' . 
        $category_id . '">' . $message . '</span>';
}

function civi_cpd_report_get_add_activity_response($type, $category_id) {
    if ($category_id == civi_cpd_report_get_add_activity_response_category_id()) {
        return isset($_SESSION['civi_crm_report'][$type . '-import-response']) ? 
            $_SESSION['civi_crm_report'][$type . '-import-response'] : NULL;
    }
}

function civi_cpd_report_get_add_activity_response_category_id() {
    return isset($_SESSION['civi_crm_report']['edit_response_category_id']) ? 
        $_SESSION['civi_crm_report']['edit_response_category_id'] : NULL;
}

function civi_cpd_report_unset_session() {
    unset($_SESSION['civi_crm_report']);
}

function civi_cpd_report_set_year() {
    if(!isset($_SESSION['report_year'])) {
        $_SESSION['report_year'] = date('Y');
    }
}

function civi_cpd_report_get_years_drop_down_list() {
    $select_years = '';
    $current_year = date('Y');

    for($i=$current_year; $i>=($current_year-15); $i--) {
        $selected = '';
        if ($i == $_SESSION['report_year']) { 
            $selected = "selected"; 

        }
        $select_years .= '<option value="' . $i . '" ' . $selected . '>' . $i . 
            '</option>';
    } 

    return $select_years;
}

function civi_cpd_report_get_date() {
    return date('Y-m-d');
}

function civi_cpd_report_unset_cpd_message() {
    if(isset($_REQUEST['clear'])) {
        unset($_SESSION['cpd_message']);
    }
}

function civi_cpd_report_import_activity_pdf() {
    if (isset($_FILES["file"]) && strtolower($_FILES['file']['type']) == 'application/pdf' && isset($_POST['user_id'])) {
        $uploads = scandir(CPD_DIR . 'uploads/activity/');
        
        foreach($uploads as $upload) {
            if (substr($upload, -3) == 'pdf' && $_POST['user_id'] == substr($upload, 11, -4)) {
                civi_cpd_report_set_activity_upload_response('add', FALSE);
                $limit_reached = TRUE;
            }
        }
        
        if (!isset($limit_reached)) {
            $file = CPD_DIR . 'uploads/activity/' . date('Y-m-d') . '-' . $_POST['user_id'] . '.pdf';
            $result = move_uploaded_file($_FILES['file']['tmp_name'], $file); 
            civi_cpd_report_set_activity_upload_response('add', $result);
        } 
    } 
}

/**
 * Upload an evidence document set in the POST request and return its file name
 *
 * @return null|string
 */
function civi_cpd_report_import_activity_evidence_pdf() {
    $fileName = null;

    if (isset($_FILES["evidence"])
      && strtolower($_FILES['evidence']['type']) == 'application/pdf'
      && isset($_POST['category_id'])) {
        $fileName = date('Y-m-d-s') . '-'  . $_POST['category_id'] . '-' . civi_cpd_report_get_contact_id() . '.pdf';
        $path = civi_cpd_report_get_evidence_pdf_path($fileName);
        $fileName = move_uploaded_file($_FILES['evidence']['tmp_name'], $path)
          ? $fileName
          : null;
    }

    return $fileName;
}

/**
 * Delete an evidence document corresponding to an activity with ID set in the GET request
 */
function civi_cpd_report_delete_evidence_pdf() {
    $activityId = $_GET['activity_id'];

    $fileName = NULL;

    $dao = CRM_Core_DAO::executeQuery('SELECT evidence FROM civi_cpd_activities WHERE id = ' . $activityId);
    $dao->fetch();

    if ($fileName = $dao->evidence) {
        $absoluteFilePath = civi_cpd_report_get_evidence_pdf_path($fileName);

        if (file_exists($absoluteFilePath)) {
            unlink($absoluteFilePath);
            CRM_Core_DAO::executeQuery('UPDATE civi_cpd_activities SET evidence = NULL WHERE id = ' . $activityId);
        }
    }
}

function civi_cpd_report_get_evidence_pdf_path($fileName) {
    return CPD_DIR . 'uploads/evidence/' . $fileName;
}

function civi_cpd_report_get_evidence_pdf_url($fileName) {
    return CPD_PATH . '/uploads/evidence/' . $fileName;
}

function civi_cpd_report_get_uploaded_activity_list($user_id) {
    $pdf_upload_table = '<h3>Upload full CPD record:</h3>'
        . '<p>If you have already recorded your CPD in a different format you can upload it in
pdf format here.</p>'
        . '<div class="upload-activity-buttons">'
        . '<a class="upload-new-activity-item" href="#"">Add new CPD activity</a></div>'
        .  civi_cpd_report_get_pdf_import($user_id)   
        . '<p><em>' . civi_cpd_report_get_activity_upload_response() . '</em></p>';
    
    $uploads = scandir(CPD_DIR . 'uploads/activity/');
        
    foreach($uploads as $upload) {
        if (substr($upload, -3) == 'pdf' && $user_id == substr($upload, 11, -4)) {
            $pdf_upload_table .= '<table width="100%" border="0" cellspacing="0" cellpadding="0"><tbody>'
                . '<tr>'
                .   '<th width="40%">Date</th>'
                .   '<th width="20%">Action</th>'
                .    '<th width="40%">&nbsp;</th>'
                . '</tr>';  

                $pdf_upload_table .= '<tr>' .
                    '<td width="40%" valign="top">' . date("M d, Y", strtotime(substr($upload, 0, 10))) . 
                        '</td>' .
                    '<td width="20%" valign="top""><a href="/civicrm/civicpd/report&action=download_pdf&upload_id=' .
                        substr($upload, 0, -4) . '"> view </a>';

                $member_update_limit = civi_crm_report_get_member_update_limit();

                if ($_SESSION['report_year'] > (date("Y") - $member_update_limit) || $member_update_limit == 0) {
                    $pdf_upload_table .= '| <a class="delete" href="' . 
                        '/civicrm/civicpd/report?action=delete&upload_id=' . substr($upload, 0, -4) . 
                        '">delete</a>';
                } else {
                    $pdf_upload_table .= 'locked';
                }		

                $pdf_upload_table .= '</td><td width="40%" nowrap="nowrap"">&nbsp;' . 
                    '</td></tr>';

                $pdf_upload_table .= '</tbody></table>';
        }
    } 
    
    return $pdf_upload_table;
}

/**
 * CSV import no longer being used
 */
//function civi_cpd_report_import_activity() {
//    if (isset($_FILES["file"]) && strtolower($_FILES['file']['type']) == 'text/csv') {
//        $filename = $_FILES['file']['tmp_name'];
//        $handle = fopen($filename, "r");
//        $row = 0;
//        $error = FALSE;
//        $sql = 'INSERT INTO civi_cpd_activities(contact_id, category_id, credit_date, ' .
//            'credits, activity, notes) VALUES';
//
//        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE && !$error) {
//            $row++;
//            if($row > 1 && !empty($data[0]) &&  !empty($data[1]) && !empty($data[2]) && !empty($data[3])) {
//                if (civi_cpd_report_validate_date($data[0]) &&  civi_cpd_report_validate_number($data[1])) {
//
//                    $category_id = $_POST['category_id'];
//                    $credit_date = $data[0];
//                    $credits     = number_format($data[1], 2, '.', '');
//                    $activity    = mysql_real_escape_string($data[2]);
//                    $notes       = mysql_real_escape_string($data[3]);
//
//                    $sql .= "(" . civi_cpd_report_get_contact_id() . "," . $category_id .
//                        ",'" . $credit_date . "'," . $credits . ",'" . $activity .
//                        "','" . $notes . "'),";
//                } else {
//                    $error = TRUE;
//                }
//            }
//        }
//        if ($error) {
//            civi_cpd_report_set_add_activity_response('csv', $category_id, FALSE);
//        } else {
//            $sql = substr($sql, 0, -1) . ';';
//            CRM_Core_DAO::executeQuery($sql);
//            fclose($handle);
//            civi_cpd_report_set_add_activity_response('csv', $category_id, TRUE);
//        }
//     } else {
//         civi_cpd_report_set_add_activity_response('csv', $category_id, FALSE);
//     }
//}

function civi_cpd_report_insert_activity()  {
    if(!empty($_POST['category_id'])
      && civi_cpd_report_validate_date($_POST['credit_date'])
      && civi_cpd_report_validate_number($_POST['credits'])
      && !empty($_POST['notes'])
      && !empty($_POST['activity'])) {
        $contactId = civi_cpd_report_get_contact_id();
        $categoryId = $_POST['category_id'];
        $creditDate = $_POST['credit_date'];
        $credits = number_format($_POST['credits'], 2, '.', '');
        $activity = $_POST['activity'];
        $notes = $_POST['notes'];
        $evidenceFileName = civi_cpd_report_import_activity_evidence_pdf();

        $sql = "
            INSERT INTO civi_cpd_activities
            (
                contact_id,
                category_id,
                credit_date,
                credits,
                activity,
                notes,
                evidence
            )
            VALUES(
               '$contactId',
               '$categoryId',
               '$creditDate',
               '$credits',
               '$activity',
               '$notes',
               '$evidenceFileName'
            )";

        CRM_Core_DAO::executeQuery($sql);
        civi_cpd_report_set_add_activity_response('manual', $_POST['category_id'],
            TRUE);
    } else {
        civi_cpd_report_set_add_activity_response('manual', $_POST['category_id'], 
            FALSE);
    }
}

function civi_cpd_report_update_activity() {
    if(!empty($_POST['activity_id']) && !empty($_POST['category_id']) && civi_cpd_report_validate_date($_POST['credit_date']) &&  civi_cpd_report_validate_number($_POST['credits']) && !empty($_POST['notes']) && !empty($_POST['activity'])) {
        $activityId = $_POST['activity_id'];
        $creditDate = $_POST['credit_date'];
        $credits = number_format($_POST['credits'], 2, '.', '');
        $activity = $_POST['activity'];
        $notes = $_POST['notes'];
        $evidence = civi_cpd_report_import_activity_evidence_pdf();

        $sql = "
            UPDATE civi_cpd_activities

            SET
                credit_date = '$creditDate',
                credits = '$credits',
                activity = '$activity',
                notes = '$notes',
                evidence = '$evidence'

            WHERE id = $activityId";

         CRM_Core_DAO::executeQuery($sql); 
         civi_cpd_report_set_edit_activity_response('update', $_POST['category_id'], 
            TRUE);    
     } else {
         civi_cpd_report_set_edit_activity_response('update', $_POST['category_id'], 
            FALSE);
     } 
 }

function civi_cpd_report_get_editable_activity($category_id) {
    if ($category_id == civi_cpd_report_get_editable_activity_category_id()) {
        return $_SESSION['civi_crm_report']['edit_activity'];
    }
} 
 
function civi_cpd_report_get_editable_activity_category_id() {
    return isset($_SESSION['civi_crm_report']['edit_activity_category_id']) ? 
        $_SESSION['civi_crm_report']['edit_activity_category_id'] : NULL;
}

function civi_cpd_report_set_editable_activity() {
    //CRM_Utils_System::setTitle(ts('Edit ' . civi_crm_report_get_short_name() . ' Activity'));
    $activity_id = $_GET['activity_id'];

    if(isset($activity_id)) {
        $sql = "
            SELECT
                civi_cpd_categories.id AS id,
                civi_cpd_categories.category,
                civi_cpd_activities.id AS activity_id,
                civi_cpd_activities.credit_date,
                civi_cpd_activities.credits,
                civi_cpd_activities.activity,
                civi_cpd_activities.notes,
                civi_cpd_activities.evidence

            FROM civi_cpd_activities

            INNER JOIN civi_cpd_categories
            ON civi_cpd_categories.id = civi_cpd_activities.category_id

            WHERE civi_cpd_activities.id = $activity_id";

        $dao = CRM_Core_DAO::executeQuery($sql);   					
        $dao->fetch();

        $evidenceHtml = null;
        if ($dao->evidence) {
           $evidenceUrl = civi_cpd_report_get_evidence_pdf_url($dao->evidence);

           $evidenceHtml = '<a target="_blank" href="' . $evidenceUrl . '">View</a> | ';
           $evidenceHtml .= '<a href="/civicrm/civicpd/report?action=delete_evidence&activity_id=' .
                       $dao->activity_id . '">delete</a>';
        } else {
            $evidenceHtml = '<input type="file" name="evidence" id="evidence">';
        }

        $_SESSION['civi_crm_report']['edit_activity_category_id'] = $dao->id;
        $_SESSION['civi_crm_report']['edit_activity'] = '<div class="update-activity">
            <form method="post" action="/civicrm/civicpd/report?action=update&id=' . $activity_id . '"
                enctype="multipart/form-data">
                <input type="hidden" value="update" name="action">
                <input type="hidden" value="'. $dao->id .'" name="category_id">
                <input type="hidden" value="'. $activity_id .'" name="activity_id">
                <table width="100%" cellspacing="0" cellpadding="0" border="0" align="center">
                   <p><em>Update Activity:</em></p>
                    <tbody>
                        <tr>
                            <td width="5%" valign="top" nowrap="nowrap">Date:</td>
                            <td width="60%"><input type="text" size="30" class="frm" ' . 
                            'id="credit_date" name="credit_date" value=' . $dao->credit_date . 
                            '></td>
                        </tr>
                        <tr>
                            <td width="5%" valign="top" nowrap="nowrap">Activity:</td>
                            <td width="60%"><input type="text" size="30" class="frm" ' . 
                            'name="activity" value="' . $dao->activity . '"></td>				
                        </tr>
                        <tr>
                            <td width="15%" valign="top">Hours:</td>
                            <td width="60%"><input type="text" maxlength="4" size="30" ' . 
                            'class="frm" name="credits" value="' . $dao->credits . '"></td>
                        </tr>
                        <tr>
                            <td width="5%" valign="top" nowrap="nowrap">Reflection:</td>
                            <td width="60%"><textarea class="frm" rows="4" cols="39" ' . 
                            'name="notes">' . $dao->notes . '</textarea></td>
                        </tr>
                        <tr>
                            <td width="5%" valign="top" nowrap="nowrap">Evidence:</td>
                            <td width="60%">' . $evidenceHtml . '</td>
                        </tr>
                        <tr>
                            <td colspan="2">
                                <input type="submit" value="Submit" class="validate ' . 
                                'form-submit default" name="Submit">
                            </td>
                        </tr>
                    </tbody>
                </table>
            </form></div>';
    }
}

function civi_crm_report_set_default_titles(array $arr_defaults) {
    $_SESSION['civi_crm_report']['member_update_limit'] = isset($arr_defaults['member_update_limit']) ? 
        $arr_defaults['member_update_limit'] : 0;
    $_SESSION['civi_crm_report']['organization_member_number_field'] = isset($arr_defaults['organization_member_number']) ? 
        $arr_defaults['organization_member_number'] : 'civicrm_contact.external_identifier';
    $_SESSION['civi_crm_report']['long_name'] = isset($arr_defaults['long_name']) ? 
        $arr_defaults['long_name'] : 'Continuing Professional Development';
    $_SESSION['civi_crm_report']['short_name'] = isset($arr_defaults['short_name']) ? 
        $arr_defaults['short_name'] : 'CPD';
}

function civi_crm_report_get_member_update_limit() {
    return $_SESSION['civi_crm_report']['member_update_limit'];
} 

function civi_crm_report_get_organization_member_number_field() {
    return $_SESSION['civi_crm_report']['organization_member_number_field'];
}

function civi_crm_report_get_short_name() {
    return $_SESSION['civi_crm_report']['short_name'];
}

function civi_crm_report_get_default_variables() {
    $sql = "SELECT * FROM civi_cpd_defaults";
    $dao = CRM_Core_DAO::executeQuery($sql);
    $default_variables = array();
    $i = 0;
    while ($dao->fetch()) {   
        $default_variables[$dao->name] = $dao->value;
        $i++;	
    }

    return $default_variables;
}

function civi_crm_report_get_display_name() {
    return $_SESSION['civi_crm_report']['display_name'];
}

function civi_crm_report_get_membership_number() {
    return $_SESSION['civi_crm_report']['membership_number'];
}

function civi_crm_report_set_user_details() {
    switch (civi_crm_report_get_organization_member_number_field()) {
        case 'civicrm_contact.external_identifier':
            $sql = "SELECT display_name, external_identifier AS membership_number FROM civicrm_contact " . 
                "WHERE id = " . civi_cpd_report_get_contact_id();
            break;

        case 'civicrm_contact.user_unique_id':
            $sql = "SELECT display_name, user_unique_id AS membership_number FROM civicrm_contact WHERE id = " . 
                civi_cpd_report_get_contact_id();
            break;

        case 'civicrm_membership.id':
            $sql = "SELECT civicrm_contact.display_name, civicrm_membership.id AS membership_number" .
                "FROM civicrm_membership INNER JOIN civicrm_contact ON civicrm_contact.id = " . 
                "civicrm_membership.contact_id WHERE civicrm_contact.id = " . 
                civi_cpd_report_get_contact_id();
            break;
    }

    $dao = CRM_Core_DAO::executeQuery($sql);

    while ($dao->fetch()) {
        $_SESSION['civi_crm_report']['display_name'] = $dao->display_name;
        $_SESSION['civi_crm_report']['membership_number'] = isset($dao->membership_number) ? 
            $dao->membership_number : NULL;
    } 
}
function civi_cpd_report_set_contact_id($contact_id) {
    $_SESSION['civi_cpd_report']['contact_id'] = $contact_id;
}

function civi_cpd_report_get_contact_id() {
    return $_SESSION['civi_cpd_report']['contact_id'];
}

function civi_cpd_report_no_activities_action() {
    $_SESSION['cpd_message'] = "You don't appear to have any CPD activities " .
     'recorded for this category in ' . $_SESSION['report_year'] . '. Please ' .
     'use the form below to record your CPD activities.';

     header("Location: /civicrm/civicpd/report");   
 }
 
 function civi_cpd_report_get_activities_list($dao) {
    $activity_list = '<tr>';
    $activity_list .= '<td valign="top">' . date("M d, Y", strtotime("$dao->credit_date")) . '</td>';
    $activity_list .= '<td valign="top">' . abs($dao->credits) . '</td>';
    $activity_list .= '<td valign="top">' . $dao->activity . '</td>';
    $activity_list .= '<td valign="top">' . $dao->notes . '</td>';
    $activity_list .= '<td valign="top"">';
    $activity_list .= $dao->evidence
      ? '<a target="_blank" href="' . civi_cpd_report_get_evidence_pdf_url($dao->evidence) . '">View</a>'
      : '';
    $activity_list .= '</td>';
    $activity_list .= '<td valign="top"">';

    $member_update_limit = civi_crm_report_get_member_update_limit();

    if ($_SESSION['report_year'] > (date("Y") - $member_update_limit) || $member_update_limit == 0) {
        $activity_list .= '<a href="/civicrm/civicpd/report?action=edit&activity_id=' . 
            $dao->activity_id . '&category_id=' . $dao->category_id . '">edit</a> | ';
        $activity_list .= '<a class="delete" href="/civicrm/civicpd/report?action=delete&activity_id=' . 
            $dao->activity_id . '&category_id=' . $dao->category_id . '">delete</a>';
    } else {
        $activity_list .= 'locked';
    }		

    $activity_list .= '</td></tr>';
    
    return $activity_list;
}
/**
 * Funciton to SUM(Activities) from the database for this contact, for this 
 * year, GROUP BY Categories
 */
function civi_crm_report_get_content() {
   $sql = "SELECT civi_cpd_categories.id AS id, civi_cpd_categories.category AS category, " .
       "SUM(civi_cpd_activities.credits) AS credits, civi_cpd_categories.minimum, " . 
       "civi_cpd_categories.maximum, civi_cpd_categories.description FROM civi_cpd_categories " . 
       "LEFT OUTER JOIN civi_cpd_activities ON civi_cpd_activities.category_id = civi_cpd_categories.id " . 
       "AND civi_cpd_activities.contact_id = " . civi_cpd_report_get_contact_id() . " " .
       "AND EXTRACT(YEAR FROM civi_cpd_activities.credit_date) = " . $_SESSION['report_year'] . " " .
       "GROUP BY civi_cpd_categories.id";

   $dao = CRM_Core_DAO::executeQuery($sql);
   $content = '';
   $i = 1;

   while ($dao->fetch()) {
       CRM_Civicpd_Page_CPDReport::incrementTotalCredits($dao->credits);

       $content .= civi_cpd_report_get_category($dao);
       $content .= civi_cpd_report_get_activity_table($dao->id);
       $content .= civi_cpd_report_get_add_activity_table($dao->id);
       $content .= civi_cpd_report_get_editable_activity($dao->id);
       
       $content	.= '</td></tr><tr valign="top"><td>&nbsp;</td></tr>';
       $i++;
   }

   $content = civi_cpd_report_get_progress() . $content;

   return $content;
}

function civi_cpd_report_validate_date($date) {
    if (preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/", $date)) {
        return TRUE;
    }
}

function civi_cpd_report_validate_number($var) {
    if (is_numeric($var)) {
        return TRUE;
    }
}
