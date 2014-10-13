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
      <td height="18" colspan="7">{$civi_cpd_short_name} hours for activities undertaken 
        in the calendar year {$smarty.session.report_year}: <strong>{$total_credits}</strong></td>
    </tr>
    <tr valign="top">
      <td nowrap="" height="18">
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
      <td nowrap="" height="18">{$uploaded_activity_list}</td>
    </tr>
    <tr valign="top">
      <td nowrap="" height="18">&nbsp;</td>
    </tr>
  </tbody>
</table>

<div id="cover"></div>
<div id="progressbar" class="ui-progressbar ui-widget ui-widget-content ui-corner-all" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="100">
    <div class="ui-progressbar-value ui-widget-header ui-corner-left ui-corner-right" style="display: block; width: 20em; height: 1em;"></div>
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