<h3>Review and update your {$civi_cpd_long_name} activities.</h3>
{if $approved }
    <img style="display: block; height: 30px; position: absolute; top: 23px; left: 215px;" src="{$imageUrl}"
         alt="Approved" title="Approved"/>
{/if}
<table id="category-list" width="100%" cellspacing="0" cellpadding="0" border="0">
  <tbody>
  	<tr valign="top">
            <th nowrap="">{$civi_cpd_long_name} Activities for: <select class="cpd-frm" name="select_year" id="select_year">{$select_years}</select>
            <p class="cpd-message">This report is for the year {$smarty.session.report_year}. To choose another year to report, click on the year above.</p></th>
        </tr>
        <tr valign="top"><td>&nbsp;</td></tr>

            {$output}

    <tr valign="top">
      <td colspan="7">{$civi_cpd_short_name} hours for activities undertaken
        in the calendar year {$smarty.session.report_year}: <strong>{$total_credits}</strong></td>
    </tr>
    <tr valign="top">
      <td nowrap="">
        <table width="100%" cellspacing="0" cellpadding="0" border="0">
          <tbody>
            <tr>
              <td nowrap="nowrap">{$display_name}</td>
              <td width="3%" nowrap="nowrap">&nbsp;</td>
              <td nowrap="nowrap">Membership Number: {$membership_number}</td>
              <td width="3%" nowrap="nowrap">&nbsp;</td>
              <td nowrap="nowrap">Date: {$today}</td>
            </tr>
          </tbody>
        </table>
      </td>
    </tr>
    <tr valign="top">
      <td>{$uploaded_activity_list}</td>
    </tr>
    <tr valign="top">
      <td>&nbsp;</td>
    </tr>
  </tbody>
</table>

<div id="cover"></div>
<div id="progressbar" class="ui-progressbar ui-widget ui-widget-content ui-corner-all" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="100">
    <div class="ui-progressbar-value ui-widget-header ui-corner-left ui-corner-right" style="display: block; width: 20em; height: 1em;"></div>
</div>


<div class="activity-item" title="Add new activity record">
    <div class="activity-item-manual">
        <p>Note: The fields marked with (*) are required</p>
        <form method="post" action="/civicrm/civicpd/report" enctype="multipart/form-data" novalidate>
            <p class="hidden failure">Please fix the error(s) below:</p>
            <input type="hidden" value="insert" name="action">
            <input type="hidden" value="" id="manual-import-category-id" name="category_id">
            <table width="50%" cellspacing="0" cellpadding="0" border="0" align="center">
                <tbody>
                <tr>
                    <td width="5%" valign="top" nowrap="nowrap"><label for="credit_date">Date *:</label></td>
                    <td width="60%">
                        <input required class="dateplugin frm" type="text" size="30" name="credit_date" value="{$today}">
                    </td>
                </tr>
                <tr>
                    <td width="5%" valign="top" nowrap="nowrap"><label for="activity">Title of activity *:</label></td>
                    <td width="60%"><input required type="text" size="30" class="frm" name="activity">
                    </td>
                </tr>
                <tr>
                    <td width="5%" valign="top"><label for="credits">Number of hours *:</label></td>
                    <td width="60%">
                        <input type="text" required maxlength="4" size="30" class="frm" name="credits">
                    </td>
                </tr>
                <tr>
                    <td width="5%" valign="top" nowrap="nowrap">
                        <label for="notes">Notes and reflection on activity *:</label>
                    </td>
                    <td width="60%">
                        <textarea class="frm" required rows="4" cols="39" name="notes"></textarea></td>
                </tr>
                <tr>
                    <td><label for="evidence">Evidence (optional):</label></td>
                    <td><input type="file" name="evidence" id="evidence"></td>
                </tr>
                <tr>
                    <td colspan="2" align="center">
                        <input class="validate form-submit default form-submit-inline" type="submit" value="Submit" name="Submit">
                        <input class="validate form-submit default form-submit-inline" type="button" value="Cancel"
                               id="cancel-new-activity">
                    </td>
                </tr>
                </tbody>
            </table>
        </form>
    </div>
</div>
{literal}

<script type="text/javascript">
    <!-- 

    if (window.location.href.indexOf('snippet=2') != -1) {
        cj('.cpd-message').hide();
        cj('.edit-activity-response').hide();
        cj('.edit-activity-buttons').hide();
        cj('.activity-item').hide();
        cj('.upload-activity-buttons').hide();
        cj('.activity-item-import-pdf').hide();
        cj('.uploaded-activity-response').hide();
        cj('.rating').hide();
        cj('.activity-list').show();
        cj('.activity-list td:nth-child(5)').hide();
        cj('.activity-list th:nth-child(5)').hide();
        cj('.ui-progressbar-value.ui-widget-header.ui-corner-left.ui-corner-right').hide();
    }

    //-->
</script>

{/literal} 