<?php


/**
 * @file This file is a part of the CiviCPD extension.
 *
 * This file is the user's reporting control panel and provides a snapshot of
 * their CPD activities and credits based on the year in question.                                                       |
 */


require_once 'CRM/Core/Page.php';


class CRM_Civicpd_Page_CPDReport extends CRM_Core_Page
{

    static public $totalCredits = null;



    function run()
    {


        CRM_Core_Resources::singleton()->addStyleFile('ca.lunahost.civicpd', 'civicpd.css');

        CRM_Core_Resources::singleton()->addScriptFile('ca.lunahost.civicpd', 'js/report.js', 0, 'page-header');

        
        $session = CRM_Core_Session::singleton();


        civi_cpd_report_set_contact_id($session->get('userID'));

        civi_cpd_report_set_year();

        civi_cpd_report_unset_cpd_message();


        if (isset($_GET['action']) || isset($_POST['action'])) {

            switch ($_REQUEST['action']) {

                /** Import by CSV is no longer needed */

//                case 'import':

//                    civi_cpd_report_import_activity();

//                break;


                case 'insert':

                    civi_cpd_report_insert_activity();

                    break;


                case 'update':

                    civi_cpd_report_update_activity();

                    break;


                case 'edit':

                    civi_cpd_report_set_editable_activity();

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

        civi_cpd_report_set_membership_info();


        $this->assign('select_years', civi_cpd_report_get_years_drop_down_list());

        $this->assign('today', civi_cpd_report_get_date());

        $this->assign('display_name', civi_crm_report_get_display_name());

        $this->assign('membership_number', civi_crm_report_get_membership_number());

        $this->assign('output', civi_crm_report_get_content());

        $this->assign('total_credits', civi_cpd_report_get_total_credits());

        $this->assign('uploaded_activity_list', civi_cpd_report_get_uploaded_activity_list($session->get('userID')));

        $this->assign('approved', static::getApprovalStatus(civi_cpd_report_get_contact_id()));

        $this->assign('imageUrl', CPD_PATH . '/assets/approved.png');

        $this->assign('logoUrl', CPD_PATH . '/assets/logo.jpg');

        $this->assign('job_title', civi_crm_report_get_job_title());

        $this->assign('membership_types', civi_crm_report_get_membership_types());
        
        $this->assign('full_upload_hours', civi_cpd_report_get_full_upload_hours());


        civi_cpd_report_unset_session();


        parent::run();

    }


    /**
     * Get the total number of hours (credits) for the given contact for the currently selected year
     *
     * @param $cid
     *
     * @return null
     */

    public static function getTotalCredits($cid)
    {

        if (is_null(static::$totalCredits)) {

            $sql = "

          SELECT SUM(credits) total_credits

          FROM civi_cpd_activities

          WHERE

            contact_id = %0

            AND YEAR(credit_date) = %1

         ";


            $params = array(

                array((int)$cid, 'Integer'),

                array((int)$_SESSION['report_year'], 'Integer')

            );


            $dao = CRM_Core_DAO::executeQuery($sql, $params);

            $dao->fetch();

            static::$totalCredits = $dao->total_credits;


            $dao->free();

        }


        return static::$totalCredits;

    }


    /**
     * Get CPD approval status for the given contact for the currently selected year
     *
     * @param int $cid
     *
     * @return bool
     */

    static public function getApprovalStatus($cid)
    {

        $sql = "

            SELECT approved

            FROM civi_cpd_activities

            WHERE

                contact_id = %0

           	    AND YEAR(credit_date) = %1

        ";


        $params = array(

            array((int)$cid, 'Integer'),

            array((int)$_SESSION['report_year'], 'Integer')

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


    /**
     * Whether uploading full CPD is allowed. This currently only returns true if a full CPD has *not* been uploaded yet.
     *
     * @param int $cid
     *
     * @return bool
     */

    public static function isFullCpdUploadAllowed($cid)
    {

        $sql = "

        SELECT COUNT(*) count

        FROM civi_cpd_activities

        WHERE

          contact_id = %0

          AND category_id = %1

          AND YEAR(credit_date) = %2

        ";


        $params = array(

            array((int)$cid, 'Integer'),

            array(CPD_FULL_CPD_CATEGORY_ID, 'Integer'),

            array((int)$_SESSION['report_year'], 'Integer')

        );


        $dao = CRM_Core_DAO::executeQuery($sql, $params);

        $dao->fetch();

        $count = $dao->count;


        return $count == 0;

    }


    public static function isFileTypeAllowed($type)
    {

        return strtolower($type) == 'application/pdf';

    }


    /**
     * Redirect to the report
     */

    public static function redirectToReport()
    {

        CRM_Utils_System::redirect('report');

    }

}


/**
 * Get HTML for category headings
 *
 * @param $dao
 *
 * @return string
 */

function civi_cpd_report_get_category($dao)
{

    $member_update_limit = civi_crm_report_get_member_update_limit();

    if (isPrintView()) {
        $count_class = 'print-hour-counter';
        $count_output = number_format($dao->credits, 2) . ' hours';
    } else {
        $count_class = 'CPDindividualHours';
        $count_output = number_format($dao->credits, 2) . 'h';
    }


    $category = '<tr id="category-' . $dao->id . '" class="category" valign="top">

    <td>



      <h1 class="category-title">

        <a href class="toggle-activity-list" style="background-image: url(' . CPD_PATH . '/assets/collapse-sprite.gif)">Show</a>'

        . $dao->category . '<div class="'.$count_class.'">'.$count_output.'</div>';


    $category .= '</h1>';


    if ($_SESSION['report_year'] > (date("Y") - $member_update_limit) || $member_update_limit == 0) {

        $category .= '<a class="new-activity-item CPDnewActivityItem" href="#" data-category-id="' . $dao->id . '">

			Add new CPD activity

			</a>';

    }


    $category .= '<p>' . $dao->description . '</p>';


    $category .= '<div class="clear"></div>';


    return $category;

}


/**
 * Get HTML for the overall progress bar
 *
 * @return string
 */

function civi_cpd_report_get_progress()
{

    $minCredits = civi_crm_report_get_cpd_hours_min();

    $maxCredits = civi_crm_report_get_cpd_hours_max();

    $credits = CRM_Civicpd_Page_CPDReport::getTotalCredits(civi_cpd_report_get_contact_id());


    $progressPercentage = 100 * $credits / $maxCredits;

    $minPercentage = 100 * $minCredits / $maxCredits;

    $color = $progressPercentage < $minPercentage ? 'red' : 'green';


    $progress =

        '<tr valign="top">

          <td>

            <div class="graphcont">

              <div class="graph">

                <strong class="bar" style="width: ' . $progressPercentage . '%; background: ' . $color . '">'

        . number_format($credits, 0) . 'h

                </strong>

                <span class="marker cpdMinTarget" style="width: ' . $minPercentage . '%;"><span>Minimum target ' . $minCredits . 'h</span></span>

                <span class="marker cpdMinTarget" style="width: 100%;"><span>' . $maxCredits . 'h</span></span>

              </div>

            </div>

            <div class="clear"></div>

          </td>

       </tr>';


    return $progress;

}


/**
 * Get HTML for listing of activities, excluding full CPD activity
 *
 * @param $category_id
 *
 * @return string
 */

function civi_cpd_report_get_activity_table($category_id)
{

    $sql = "SELECT civi_cpd_categories.category

        , civi_cpd_activities.id AS activity_id
        
        , civi_cpd_activities.start_date

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


    $activity_table = '<div id="category-' . $category_id . '-activities" class="activity-list">' .

        civi_cpd_report_get_edit_activity_response($category_id) .
        '<table border="0" cellspacing="0" cellpadding="0" class="CPDactivityTable"><tr>' .
        '<th>Date</th><th>Hours</th><th>Activity</th>' .
        '<th>Reflection</th><th>Evidence</th>'. (isPrintView() ? '' : '<th>Action</th>') .'</tr>';

    if ($dao->N > 0) {

        while ($dao->fetch()) {

            $dao->category_id = $category_id;

            $activity_table .= civi_cpd_report_get_activities_list($dao);

        }

    }


    $activity_table .= '</table></div>';


    return $activity_table;

}


/**
 * Get HTML form for uploading a new full CPD record
 *
 * @return string
 */

function civi_cpd_report_get_pdf_import()
{


    // PDF post URL

    $pdf_post_url = CRM_Utils_System::url("civicrm/civicpd/report", null, true, null, false, true);


    $pdf_import = '<div class="activity-item-import-pdf">

    <form  method="post" action="' . $pdf_post_url . '"  enctype="multipart/form-data">

        <input type="hidden" value="insert" name="action">

        <input type="hidden" value="' . CPD_FULL_CPD_CATEGORY_ID . '" id="full-cpd-import-category-id" name="category_id">

        <input title="Full CPD Record" required type="file" name="file" id="file">

        <input required size="8" maxlength="4" type="input" name="full_cpd_credits" placeholder="Hours">

        <input type="submit" value="Upload PDF" class="validate form-submit default" name="Submit">

    </form>

    </div>';


    return CRM_Civicpd_Page_CPDReport::isFullCpdUploadAllowed(civi_cpd_report_get_contact_id()) ? $pdf_import : '';

}


function civi_cpd_report_get_total_credits()
{

    $sql = "SELECT SUM(credits) as total_credits 

    FROM civi_cpd_activities 

    WHERE contact_id = " . civi_cpd_report_get_contact_id() . " 

    AND EXTRACT(YEAR FROM civi_cpd_activities.credit_date) = " . $_SESSION['report_year'];


    $dao = CRM_Core_DAO::executeQuery($sql);


    while ($dao->fetch()) {

        $total_credits = abs($dao->total_credits);

    }


    return empty($total_credits) ? 0 : $total_credits;

}


/**
 * Delete an activity (individual or full CPD) along with the supporting PDF
 */

function civi_cpd_report_delete_activity()
{

    if (isset($_GET['activity_id']) && isset($_GET['category_id'])) {

        if ($_GET['category_id'] == 0) {

            civi_cpd_report_delete_full_cpd_pdf();

        } else {

            civi_cpd_report_delete_evidence_pdf();

        }


        CRM_Core_DAO::executeQuery("DELETE FROM civi_cpd_activities WHERE id =" . $_GET['activity_id']);


        civi_cpd_report_set_edit_activity_response('delete', $_GET['category_id'], TRUE);

        CRM_Core_Session::setStatus(' ', 'Activity deleted', 'success', array('expires' => 2000));


        CRM_Civicpd_Page_CPDReport::redirectToReport();

    } else {

        civi_cpd_report_set_edit_activity_response('delete', $_GET['category_id'], FALSE);

    }

}


function civi_cpd_report_set_activity_upload_response($action, $success)
{

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


function civi_cpd_report_set_edit_activity_response($action, $category_id, $success = FALSE)
{

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


function civi_cpd_report_get_activity_upload_response()
{

    return isset($_SESSION['civi_crm_report']['uploaded_activity_response']) ?

        $_SESSION['civi_crm_report']['uploaded_activity_response'] : NULL;

}


function civi_cpd_report_get_edit_activity_response($category_id)
{

    if ($category_id == civi_cpd_report_get_edit_activity_response_category_id()) {

        return isset($_SESSION['civi_crm_report']['edit_response']) ?

            $_SESSION['civi_crm_report']['edit_response'] : NULL;

    }

}


function civi_cpd_report_get_edit_activity_response_category_id()
{

    return isset($_SESSION['civi_crm_report']['edit_response_category_id']) ?

        $_SESSION['civi_crm_report']['edit_response_category_id'] : NULL;

}


function civi_cpd_report_set_add_activity_response($type, $category_id, $success = FALSE)
{

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


function civi_cpd_report_get_add_activity_response($type, $category_id)
{

    if ($category_id == civi_cpd_report_get_add_activity_response_category_id()) {

        return isset($_SESSION['civi_crm_report'][$type . '-import-response']) ?

            $_SESSION['civi_crm_report'][$type . '-import-response'] : NULL;

    }

}


function civi_cpd_report_get_add_activity_response_category_id()
{

    return isset($_SESSION['civi_crm_report']['edit_response_category_id']) ?

        $_SESSION['civi_crm_report']['edit_response_category_id'] : NULL;

}


function civi_cpd_report_unset_session()
{

    unset($_SESSION['civi_crm_report']);

}


function civi_cpd_report_set_year()
{

    if (!isset($_SESSION['report_year'])) {

        $_SESSION['report_year'] = date('Y');

    }

}


function civi_cpd_report_get_years_drop_down_list()
{

    $select_years = '';

    $current_year = date('Y');


    for ($i = $current_year; $i >= ($current_year - 15); $i--) {

        $selected = '';

        if ($i == $_SESSION['report_year']) {

            $selected = "selected";


        }

        $select_years .= '<option value="' . $i . '" ' . $selected . '>' . $i .

            '</option>';

    }


    return $select_years;

}


function civi_cpd_report_get_date()
{

    return date('d-m-Y');

//    return date('Y-m-d');

}


function civi_cpd_report_unset_cpd_message()
{

    if (isset($_REQUEST['clear'])) {

        unset($_SESSION['cpd_message']);

    }

}


/**
 * Upload PDF document for full CPD activity
 *
 * @return null|string
 */

function civi_cpd_report_import_full_cpd_pdf()
{

    $fileName = NULL;


    if (isset($_FILES["file"])) {

        if (CRM_Civicpd_Page_CPDReport::isFileTypeAllowed($_FILES['file']['type'])) {

            $fileName = date('Y-m-d_His')

                . '-' . civi_cpd_report_get_contact_id()

                . '.pdf';


            $path = civi_cpd_report_get_full_cpd_pdf_path($fileName);

            if (!move_uploaded_file($_FILES['file']['tmp_name'], $path)) {
                CRM_Core_Session::setStatus(
                    'File could not be saved (possibly incorrect filesystem permissions)',
                    'Failed to save full CPD record',
                    'error',
                    array('expires' => 2000)
                );

                return FALSE;
            }
        } else {

            CRM_Core_Session::setStatus('File type not allowed', 'Failed to save full CPD record', 'error',

                array('expires' => 2000));


            return FALSE;

        }

    }


    return $fileName;

}


/**
 * Upload an evidence document set in the POST request and return its file name
 *
 * @return null|string
 */

function civi_cpd_report_import_activity_evidence_pdf()
{

    $fileName = NULL;


    if (isset($_FILES['evidence']) && $_FILES['evidence']['type']) {

        if (CRM_Civicpd_Page_CPDReport::isFileTypeAllowed($_FILES['evidence']['type']) && isset($_POST['category_id'])) {

            $fileName = date('Y-m-d-s') . '-'

                . civi_cpd_report_get_contact_id()

                . '-' . $_POST['category_id']

                . '.pdf';


            $path = civi_cpd_report_get_evidence_pdf_path($fileName);

            $fileName = move_uploaded_file($_FILES['evidence']['tmp_name'], $path)

                ? $fileName

                : NULL;

        } else {

            CRM_Core_Session::setStatus(' ', 'File type not allowed', 'error', array('expires' => 2000));


            return FALSE;

        }

    }


    return $fileName;

}


/**
 * Delete an evidence document corresponding to an activity with ID set in the GET request
 */

function civi_cpd_report_delete_evidence_pdf()
{

    $activityId = $_GET['activity_id'];

    $categoryId = $_GET['category_id'];


    $fileName = NULL;


    $dao = CRM_Core_DAO::executeQuery('SELECT evidence FROM civi_cpd_activities WHERE id = ' . $activityId);

    $dao->fetch();


    if ($fileName = $dao->evidence) {

        $absoluteFilePath = civi_cpd_report_get_evidence_pdf_path($fileName);


        if (file_exists($absoluteFilePath)) {

            unlink($absoluteFilePath);

            CRM_Core_DAO::executeQuery('UPDATE civi_cpd_activities SET evidence = NULL WHERE id = ' . $activityId);

            CRM_Core_Session::setStatus(' ', 'Evidence deleted', 'success', array('expires' => 2000));

        }

    }


    if (isset($_GET['redirect']) && $_GET['redirect'] == 'true') {

        CRM_Utils_System::redirect("report?action=edit&activity_id={$activityId}&category_id={$categoryId}");

    }

}


/**
 * Delete PDF for a full CPD activity
 */

function civi_cpd_report_delete_full_cpd_pdf()
{

    $activityId = $_GET['activity_id'];

    $categoryId = CPD_FULL_CPD_CATEGORY_ID;


    $fileName = NULL;


    $dao = CRM_Core_DAO::executeQuery('SELECT evidence FROM civi_cpd_activities WHERE id = ' . $activityId);

    $dao->fetch();


    if ($fileName = $dao->evidence) {

        $absoluteFilePath = civi_cpd_report_get_evidence_pdf_path($fileName);


        if (file_exists($absoluteFilePath)) {

            unlink($absoluteFilePath);

            CRM_Core_DAO::executeQuery('UPDATE civi_cpd_activities SET evidence = NULL WHERE id = ' . $activityId);

            CRM_Core_Session::setStatus(' ', 'Evidence deleted', 'success', array('expires' => 2000));

        }

    }


    if (isset($_GET['redirect']) && $_GET['redirect'] == 'true') {

        CRM_Utils_System::redirect("report?action=edit&activity_id={$activityId}&category_id={$categoryId}");

    }

}


function civi_cpd_report_get_evidence_pdf_path($fileName)
{

    return CPD_DIR . 'uploads/evidence/' . $fileName;

}


function civi_cpd_report_get_evidence_pdf_url($fileName)
{

    return CPD_PATH . '/uploads/evidence/' . $fileName;

}


function civi_cpd_report_get_full_cpd_pdf_path($fileName)
{

    return CPD_DIR . 'uploads/activity/' . $fileName;

}


function civi_cpd_report_get_full_cpd_pdf_url($fileName)
{

    return CPD_PATH . '/uploads/activity/' . $fileName;

}


/**
 * Get HTML for full CPD activity listing
 *
 * @param $user_id
 *
 * @return string
 */

function civi_cpd_report_get_uploaded_activity_list($user_id)
{
    
    $full_upload_hours = 0;

    $sql = "

        SELECT civi_cpd_activities.id,

            civi_cpd_activities.credits,

            civi_cpd_activities.credit_date,

            civi_cpd_activities.evidence



        FROM civi_cpd_activities



        WHERE

            category_id = " . CPD_FULL_CPD_CATEGORY_ID . "

            AND contact_id = " . $user_id . "

            AND YEAR(credit_date) = " . $_SESSION['report_year'] . "



        ORDER BY credit_date";


    $dao = CRM_Core_DAO::executeQuery($sql);


    $pdf_upload_table = '<h3>Upload full CPD record:</h3>'

        . '<a name="pdflink"></a><p>If you have already recorded your CPD you can upload a record of it as a PDF here.</p>'

        . civi_cpd_report_get_pdf_import($user_id)

        . '<p><em>' . civi_cpd_report_get_activity_upload_response() . '</em></p>'

        . '<span class="cpd-message">Please enter the total number of hours your record covers with your upload</span>';


    if ($dao->N > 0) {
        $pdf_upload_table .= '<table border="0" cellspacing="0" cellpadding="0"><tbody>'
            . '<tr>'
            . '<th>Date Uploaded</th>'
            . '<th>Total Hours in Uploaded Record</th>'
            . '<th>Action</th>'
            . '<th>&nbsp;</th>'
            . '</tr>';

    }


    while ($dao->fetch()) {
        // Reading date from file name both formats; Y-m-d and Y-m-d_His
        $uploadDate = $dao->evidence;
        if (strpos($dao->evidence, '_') !== false) {
            $uploadDate = substr($uploadDate, 0, strrpos($uploadDate, '_'));
        }else{
            $uploadDate = substr($uploadDate, 0, strrpos($uploadDate, '-'));
        }

        $pdf_upload_table .= '<tr>' .
            '<td valign="top">' . date("M d, Y", strtotime($uploadDate)) . '</td>' .
            '<td valign="top">' . abs($dao->credits) . '</td>' .
            '<td valign="top">
          <a href="' . civi_cpd_report_get_full_cpd_pdf_url($dao->evidence) . '" target="_blank"> view </a>';


        $member_update_limit = civi_crm_report_get_member_update_limit();


        if ($_SESSION['report_year'] > (date("Y") - $member_update_limit) || $member_update_limit == 0) {

            $pdf_upload_table .= '| <a class="delete"

      href="' . '/civicrm/civicpd/report?action=delete&activity_id=' . $dao->id . '&category_id='

                . CPD_FULL_CPD_CATEGORY_ID . '">delete</a>';

        } else {

            $pdf_upload_table .= 'locked';

        }


        $pdf_upload_table .= '</td></tr>';
        
        $full_upload_hours .= $dao->credits;

    }


    $pdf_upload_table .= '</tbody></table>';


    /**
     * Horrible hack to provide the total hours by uploaded PDF, otherwise
     * we duplicate code which is not desirable..
     */
    $_SESSION['civi_crm_report']['full_upload_hours'] = $full_upload_hours;

    return $pdf_upload_table;

}


/**
 * Insert a new activity - either a normal or full CPD activity. This also takes care of directing PDF uploads.
 */

function civi_cpd_report_insert_activity()
{

    if (isset($_POST['credit_date'])) {

        $_POST['credit_date'] = civi_crm_report_convert_uk_to_mysql_date($_POST['credit_date']);

    }

    if (isset($_POST['start_date']) && !empty($_POST['start_date'])) {
        $_POST['start_date'] = civi_crm_report_convert_uk_to_mysql_date($_POST['start_date']);
    } else {
        // If start date wasn't set, then we set null
        $_POST['start_date'] = null;
    }


    if (isset($_POST['category_id'])) {

        // Save full CPD record

        if ($_POST['category_id'] == CPD_FULL_CPD_CATEGORY_ID

            && civi_cpd_report_validate_number($_POST['full_cpd_credits'])

            && CRM_Civicpd_Page_CPDReport::isFullCpdUploadAllowed(civi_cpd_report_get_contact_id())

        ) {

            $contactId = civi_cpd_report_get_contact_id();

            $categoryId = $_POST['category_id'];
            $creditDate = $_SESSION['report_year'] . '-01-01';
            $credits = number_format($_POST['full_cpd_credits'], 2, '.', '');

            $evidenceFileName = civi_cpd_report_import_full_cpd_pdf();


            if ($evidenceFileName === false) {

                CRM_Civicpd_Page_CPDReport::redirectToReport();

            }


            $sql = "

                  INSERT INTO civi_cpd_activities

                  (

                      contact_id,

                      category_id,

                      credit_date,

                      credits,

                      evidence

                  )

                  VALUES( %0, %1, %2, %3, %4 )";


            $params = array(

                array($contactId, 'Integer'),

                array($categoryId, 'Integer'),

                array(str_replace('-', '', $creditDate), 'Date'),

                array($credits, 'Float'),

                array($evidenceFileName, 'String'),

            );


            CRM_Core_DAO::executeQuery($sql, $params);

            civi_cpd_report_set_add_activity_response('manual', $_POST['category_id'], TRUE);


            CRM_Core_Session::setStatus(' ', 'Full CPD record uploaded', 'success', array('expires' => 2000));

        } // Save activity

        elseif ($_POST['category_id'] != CPD_FULL_CPD_CATEGORY_ID

            && civi_cpd_report_validate_number($_POST['credits'])

            && isset($_POST['credit_date'])

            && civi_cpd_report_validate_date($_POST['credit_date'])

            && civi_cpd_report_validate_start_date($_POST['start_date'])

            && civi_cpd_report_validate_date_ranges($_POST['start_date'], $_POST['credit_date'])

            && !empty($_POST['notes'])

            && !empty($_POST['activity'])

        ) {

            $contactId = civi_cpd_report_get_contact_id();

            $categoryId = $_POST['category_id'];

            $creditDate = $_POST['credit_date'];

            $startDate = $_POST['start_date'];

            $credits = number_format($_POST['credits'], 2, '.', '');

            $activity = $_POST['activity'];

            $notes = $_POST['notes'];

            $evidenceFileName = civi_cpd_report_import_activity_evidence_pdf();


            if ($evidenceFileName === false) {

                CRM_Civicpd_Page_CPDReport::redirectToReport();

            }


            $evidenceFileName = $evidenceFileName ?: 'NULL';


            $sql = "

            INSERT INTO civi_cpd_activities

            (

                contact_id,

                category_id,
                
                start_date,

                credit_date,

                credits,

                activity,

                notes,

                evidence

            )

            VALUES( %0, %1, %2, %3, %4, %5, %6, %7 )";


            $params = array(

                array($contactId, 'Integer'),

                array($categoryId, 'Integer'),

                array(str_replace('-', '', $startDate), 'Date'),

                array(str_replace('-', '', $creditDate), 'Date'),

                array($credits, 'Float'),

                array($activity, 'String'),

                array($notes, 'String'),

                array($evidenceFileName, 'String'),

            );


            CRM_Core_DAO::executeQuery($sql, $params);

            civi_cpd_report_set_add_activity_response('manual', $_POST['category_id'], TRUE);

        }

    } else {

        CRM_Core_Session::setStatus('Please check details and try again', 'Failed to create activity!', 'error');

        civi_cpd_report_set_add_activity_response('manual', $_POST['category_id'], FALSE);

    }


    CRM_Civicpd_Page_CPDReport::redirectToReport();

}


function civi_cpd_report_update_activity()
{

    if (isset($_POST['cancel'])) {

        return;

    }


    if (isset($_POST['credit_date'])) {

        $_POST['credit_date'] = civi_crm_report_convert_uk_to_mysql_date($_POST['credit_date']);

    }

    if (isset($_POST['start_date']) && !empty($_POST['start_date'])) {
        $_POST['start_date'] = civi_crm_report_convert_uk_to_mysql_date($_POST['start_date']);
    } else {
        // If start date wasn't set, then we set null
        $_POST['start_date'] = null;
    }

    if (!civi_cpd_report_validate_date_ranges($_POST['start_date'], $_POST['credit_date'])) {
        civi_cpd_report_set_edit_activity_response('update', $_POST['category_id'], FALSE);
        return false;
    }


    if (!empty($_POST['activity_id']) && !empty($_POST['category_id']) && civi_cpd_report_validate_date($_POST['credit_date']) && civi_cpd_report_validate_number($_POST['credits']) && !empty($_POST['notes']) && !empty($_POST['activity'])) {
        $evidence = civi_cpd_report_import_activity_evidence_pdf();
        if ($evidence === false) {
            CRM_Civicpd_Page_CPDReport::redirectToReport();
        }

        $updateCpdActivityQuery = "UPDATE civi_cpd_activities SET 
                                   start_date = %1,
                                   credit_date = %2,
                                   credits = %3,
                                   activity = %4,
                                   notes = %5,
                                   evidence = %6 
                                   WHERE id = %7";

        $startDate = NULL;
        if (
          !empty($_POST['start_date'])
          && civi_cpd_report_validate_start_date($_POST['start_date'])
        ) {
            $startDate = CRM_Utils_Request::retrieve('start_date', 'String');
            $startDate = CRM_Utils_Date::processDate($startDate, NULL, FALSE, 'Ymd');
        }

        $creditDate = CRM_Utils_Request::retrieve('credit_date', 'String');
        $creditDate = CRM_Utils_Date::processDate($creditDate, NULL, FALSE, 'Ymd');

        $credits = number_format($_POST['credits'], 2, '.', '');

        $params = array(
            1 => array($startDate, 'Date'),
            2 => array($creditDate, 'Date'),
            3 => array($credits, 'String'),
            4 => array($_POST['activity'], 'String'),
            5 => array($_POST['notes'], 'String'),
            6 => array($evidence, 'String'),
            7 => array($_POST['activity_id'], 'Integer'),
        );

        CRM_Core_DAO::executeQuery($updateCpdActivityQuery, $params);

        CRM_Core_Session::setStatus(' ', 'Activity updated', 'success', array('expires' => 2000));

        civi_cpd_report_set_edit_activity_response('update', $_POST['category_id'], TRUE);

        CRM_Civicpd_Page_CPDReport::redirectToReport();

    }
    else {
        civi_cpd_report_set_edit_activity_response('update', $_POST['category_id'], FALSE);
    }

}


function civi_cpd_report_get_editable_activity($category_id)
{

    if ($category_id == civi_cpd_report_get_editable_activity_category_id()) {

        return $_SESSION['civi_crm_report']['edit_activity'];

    }

}


function civi_cpd_report_get_editable_activity_category_id()
{

    return isset($_SESSION['civi_crm_report']['edit_activity_category_id']) ?

        $_SESSION['civi_crm_report']['edit_activity_category_id'] : NULL;

}


/**
 * Get HTML for the form for editing an existing activity
 */

function civi_cpd_report_set_editable_activity()
{

    $activity_id = $_GET['activity_id'];


    if (isset($activity_id)) {

        $sql = "

            SELECT

                civi_cpd_categories.id AS id,

                civi_cpd_categories.category,

                civi_cpd_activities.id AS activity_id,
                
                civi_cpd_activities.start_date,

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

        if (!empty($dao->evidence) && $dao->evidence!='NULL') {

            $evidenceUrl = civi_cpd_report_get_evidence_pdf_url($dao->evidence);


            $evidenceHtml = '<a target="_blank" href="' . $evidenceUrl . '">View</a> | ';

            $evidenceHtml .= '<a href="/civicrm/civicpd/report?action=delete_evidence&activity_id=' .

                $dao->activity_id . '&category_id=' . $dao->id . '&redirect=true">delete</a>';

        } else {

            $evidenceHtml = '<input type="file" name="evidence" id="evidence">';

        }


        $_SESSION['civi_crm_report']['edit_activity_category_id'] = $dao->id;

        $_SESSION['civi_crm_report']['edit_activity'] = '<div class="update-activity">

            <form class="crm-form-block" method="post" action="/civicrm/civicpd/report" enctype="multipart/form-data">

                <input type="hidden" value="update" name="action">

                <input type="hidden" value="' . $dao->id . '" name="category_id">

                <input type="hidden" value="' . $activity_id . '" name="activity_id">
                <table cellspacing="0" cellpadding="0" border="0" align="center">
                   <h3>Update Activity:</h3>

                    <tbody>
                    
                    
                    <tr>
                            <td valign="top" nowrap="nowrap">Start date:</td>
                            <td>
                                <input type="text" size="30" class="frm restricted-date" ' .

            'name="start_date"

                                    value=' . civi_crm_report_convert_mysql_to_uk_date($dao->start_date) . '>

                             </td>

                        </tr>

                        <tr>
                            <td valign="top" nowrap="nowrap">Date / End date:</td>
                            <td>
                                <input type="text" size="30" class="frm restricted-date" ' .

            'name="credit_date"

                                    value=' . civi_crm_report_convert_mysql_to_uk_date($dao->credit_date) . '>

                             </td>

                        </tr>

                        <tr>
                            <td valign="top" nowrap="nowrap">Activity:</td>
                            <td><input type="text" size="30" class="frm" ' .
            'name="activity" value="' . $dao->activity . '"></td>				

                        </tr>

                        <tr>
                            <td valign="top">Hours:</td>
                            <td><input type="text" maxlength="4" size="30" ' .
            'class="frm" name="credits" value="' . $dao->credits . '"></td>

                        </tr>

                        <tr>
                            <td valign="top" nowrap="nowrap">Reflection:</td>
                            <td><textarea maxlength="10000" class="frm" rows="4" cols="39" ' .
            'name="notes">' . $dao->notes . '</textarea></td>

                        </tr>

                        <tr>
                            <td valign="top" nowrap="nowrap">Evidence (optional):</td>
                            <td>' . $evidenceHtml . '</td>
                        </tr>

                        <tr>

                            <td colspan="2">

                                <input type="submit" value="Submit" class="validate form-submit default" name="submit">

                                <input type="submit" value="Cancel" class="validate form-submit default" name="cancel">

                            </td>

                        </tr>

                    </tbody>

                </table>

            </form></div>';

    }

}


/**
 * Format a date formatted to UK standard format from MySQL
 *
 * @param $date
 *
 * @return string
 */

function civi_crm_report_convert_mysql_to_uk_date($date)
{

    if (!$date) return null;


    $date = DateTime::createFromFormat('Y-m-d H:i:s', $date);

    return $date->format('d-m-Y');

}


/**
 * Format a date formatted to MySQL format from UK standard format
 *
 * @param $date
 *
 * @return string
 */

function civi_crm_report_convert_uk_to_mysql_date($date)
{

    if (!$date) return null;


    $date = DateTime::createFromFormat('d-m-Y', $date);

    return $date->format('Y-m-d');

}


function civi_crm_report_set_default_titles(array $arr_defaults)
{

    $_SESSION['civi_crm_report']['member_update_limit'] = isset($arr_defaults['member_update_limit']) ?

        $arr_defaults['member_update_limit'] : 0;

    $_SESSION['civi_crm_report']['organization_member_number_field'] = isset($arr_defaults['organization_member_number']) ?

        $arr_defaults['organization_member_number'] : 'civicrm_contact.external_identifier';

    $_SESSION['civi_crm_report']['long_name'] = isset($arr_defaults['long_name']) ?

        $arr_defaults['long_name'] : 'Continuing Professional Development';

    $_SESSION['civi_crm_report']['short_name'] = isset($arr_defaults['short_name']) ?

        $arr_defaults['short_name'] : 'CPD';

    $_SESSION['civi_crm_report']['cpd_hours_min'] = isset($arr_defaults['cpd_hours_min']) ?

        $arr_defaults['cpd_hours_min'] : 30;

    $_SESSION['civi_crm_report']['cpd_hours_max'] = isset($arr_defaults['cpd_hours_max']) ?

        $arr_defaults['cpd_hours_max'] : 250;

}


function civi_crm_report_get_member_update_limit()
{

    return $_SESSION['civi_crm_report']['member_update_limit'];

}


function civi_crm_report_get_organization_member_number_field()
{

    return $_SESSION['civi_crm_report']['organization_member_number_field'];

}


function civi_crm_report_get_short_name()
{

    return $_SESSION['civi_crm_report']['short_name'];

}


function civi_crm_report_get_cpd_hours_min()
{

    return $_SESSION['civi_crm_report']['cpd_hours_min'];

}


function civi_crm_report_get_cpd_hours_max()
{

    return $_SESSION['civi_crm_report']['cpd_hours_max'];

}


function civi_crm_report_get_default_variables()
{

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


function civi_crm_report_get_display_name()
{

    return $_SESSION['civi_crm_report']['display_name'];

}


function civi_crm_report_get_membership_number()
{

    return $_SESSION['civi_crm_report']['membership_number'];

}

function civi_crm_report_get_job_title()
{
    if(isset($_SESSION['civi_crm_report']['job_title'])) {
        return $_SESSION['civi_crm_report']['job_title'];
    }
    return 'Not available';
}

function civi_crm_report_get_membership_types()
{
    if(isset($_SESSION['civi_crm_report']['membership_types'])) {
        return $_SESSION['civi_crm_report']['membership_types'];
    }
    return 'Not available';
}


function civi_crm_report_set_user_details()
{

    switch (civi_crm_report_get_organization_member_number_field()) {

        case 'civicrm_contact.external_identifier':

            $sql = "SELECT display_name, external_identifier AS membership_number, job_title FROM civicrm_contact " .

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

        /**
         * Scott Pashley 23-05-2016
         * I dislike using the session like this but I'm just continuing the practice that exists
         */
        $_SESSION['civi_crm_report']['job_title'] = isset($dao->job_title) ? $dao->job_title : null;
    }

}

function civi_cpd_report_set_contact_id($contact_id)
{

    $_SESSION['civi_cpd_report']['contact_id'] = $contact_id;

}

function civi_cpd_report_set_membership_info()
{
    $cid= preg_replace('/[^0-9]/', '', civi_cpd_report_get_contact_id());

    $sql = "SELECT civicrm_membership_type.name as mem_types FROM civicrm_membership
            LEFT JOIN civicrm_membership_type
            ON civicrm_membership_type.id = civicrm_membership.membership_type_id
            WHERE civicrm_membership.contact_id ='$cid' and civicrm_membership.is_test = 0
            AND civicrm_membership.status_id IN (SELECT id FROM civicrm_membership_status WHERE
            is_current_member = 1) ";

    $dao = CRM_Core_DAO::executeQuery($sql);

    while ($dao->fetch()) {
        $_SESSION['civi_crm_report']['membership_types'][] = isset($dao->mem_types) ? $dao->mem_types : null;
    }

}


function civi_cpd_report_get_contact_id()
{

    return $_SESSION['civi_cpd_report']['contact_id'];

}

function civi_cpd_report_get_full_upload_hours()
{
    if(isset($_SESSION['civi_crm_report']['full_upload_hours'])) {
        return $_SESSION['civi_crm_report']['full_upload_hours'];
    }
    return 0;
}


function civi_cpd_report_no_activities_action()
{

    $_SESSION['cpd_message'] = "You don't appear to have any CPD activities " .

        'recorded for this category in ' . $_SESSION['report_year'] . '. Please ' .

        'use the form below to record your CPD activities.';


    header("Location: /civicrm/civicpd/report");

}


/**
 * Get HTML for a single activity in the activity listing
 *
 * @param $dao
 *
 * @return string
 */

function civi_cpd_report_get_activities_list($dao)
{

    $activity_list = '<tr>';

    if (isset($dao->start_date) && !empty($dao->start_date)) {
        $activity_list .= '<td valign="top" class="column1">' . date("M d, Y", strtotime("$dao->start_date"))
            . ' - ' . date("M d, Y", strtotime("$dao->credit_date")) . '</td>';
    } else {
        $activity_list .= '<td valign="top" class="column1">' . date("M d, Y", strtotime("$dao->credit_date")) . '</td>';
    }

    $activity_list .= '<td valign="top" class="column2">' . abs($dao->credits) . '</td>';

    $activity_list .= '<td valign="top" class="column3">' . $dao->activity . '</td>';

    $activity_list .= '<td valign="top" class="column4"><div class="notes-render">' . $dao->notes . '</div></td>';

    $activity_list .= '<td valign="top" class="column5">';

    /**
     * Because the evidence field has been set to an actual string (value of "NULL") when
     * there is no evidence (rather than an actual NULL value) we need to do extra checks.
     * Also, when evidence is deleted, the value becomes a string of zero length. Fun!
     */
    if (isset($dao->evidence) && !empty($dao->evidence) && $dao->evidence !== 'NULL') {
        if (isPrintView()) {
            $evidence_link = '<a target="_blank" href="' . civi_cpd_report_get_evidence_pdf_url($dao->evidence) . '">Link</a>';
        } else {
            $evidence_link = '<a target="_blank" href="' . civi_cpd_report_get_evidence_pdf_url($dao->evidence) . '">View</a>';
        }
    } else {
        $evidence_link = '(no evidence provided)';
    }

    $activity_list .= $evidence_link;

    $activity_list .= '</td>';

    if (!isPrintView()) {

        $activity_list .= '<td valign="top">';


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

    }


    return $activity_list;

}

/**
 * Funciton to SUM(Activities) from the database for this contact, for this
 * year, GROUP BY Categories
 */

function civi_crm_report_get_content()
{

    $contactId = civi_cpd_report_get_contact_id();

    $year = $_SESSION['report_year'];


    $sql = "

      SELECT

        civi_cpd_categories.id AS id,

        civi_cpd_categories.category AS category,

        SUM(civi_cpd_activities.credits) AS credits,

        civi_cpd_categories.minimum,

        civi_cpd_categories.maximum,

        civi_cpd_categories.description



      FROM civi_cpd_categories



      LEFT OUTER JOIN civi_cpd_activities

        ON civi_cpd_activities.category_id = civi_cpd_categories.id

        AND civi_cpd_activities.contact_id = $contactId

        AND EXTRACT(YEAR FROM civi_cpd_activities.credit_date) = $year



      GROUP BY civi_cpd_categories.id";


    $dao = CRM_Core_DAO::executeQuery($sql);

    $content = '';

    $i = 1;

    $content = '<tr><td>If you have already recorded your CPD activities you can upload this at the <a href="#pdflink">bottom of the page</a>.</td></tr>';


    while ($dao->fetch()) {

        $content .= civi_cpd_report_get_category($dao);

        $content .= civi_cpd_report_get_activity_table($dao->id);

        $content .= civi_cpd_report_get_editable_activity($dao->id);


        $content .= '</tr>';

        $i++;

    }


    $content = civi_cpd_report_get_progress() . $content;


    return $content;

}


function civi_cpd_report_validate_date($date)
{

    $pattern = '/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/';

    if (preg_match($pattern, $date)) {
        $d = DateTime::createFromFormat('Y-m-d', $date);
        $d->format('Y');
        if ($d->format('Y') === $_SESSION['report_year']) {
            return true;
        }
    }

    // Absolutely not where we want to do this, but it isn't working in the spaghetti code above...

    CRM_Core_Session::setStatus(
        'The date you provided was invalid, please check it and try again',
        'Failed to save record',
        'error',
        array('expires' => 2000)
    );
}

function civi_cpd_report_validate_start_date($date)
{
    if($date === null) {
        return true;
    }

    $pattern = '/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/';

    $d = DateTime::createFromFormat('Y-m-d', $date);
    $d->format('Y');

    if ((preg_match($pattern, $date) && $d->format('Y') === $_SESSION['report_year'] )) {
        return true;
    }

    // Absolutely not where we want to do this, but it isn't working in the spaghetti code above...

    CRM_Core_Session::setStatus(
        'The date you provided was invalid, please check it and try again',
        'Failed to save record',
        'error',
        array('expires' => 2000)
    );
}


function civi_cpd_report_validate_date_ranges($start, $end)
{
    if (!empty($start) && !empty($end)) {
        if ($end > $start) {
            return true;
        }
    }

    if (empty($start) && !empty($end)) {
        return true;
    }

    // Absolutely not where we want to do this, but it isn't working in the spaghetti code above...

    CRM_Core_Session::setStatus(
        'The date range you provided is invalid, please check it and try again',
        'Failed to save record',
        'error',
        array('expires' => 2000)
    );
}


function civi_cpd_report_validate_number($var)
{

    if (is_numeric($var)) {

        return TRUE;

    }

}

/**
 * Determines if the request was for the print version of the report
 *
 * @return bool
 */
function isPrintView()
{
    if (isset($_GET['snippet'])) {
        return true;
    }
    return false;
}

