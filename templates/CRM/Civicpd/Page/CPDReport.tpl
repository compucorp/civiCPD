<div="civitopbar"><h1>Manage Your Professional Development</h1></div>



<div class="civileftcolumn">

<h3>Record and review your {$civi_cpd_long_name} activities</h3>

{if $approved }

    <div class="CPDapprovedBlock"><img class="CPDapprovedIcon" src="{$imageUrl}" alt="Approved" title="Approved"/> <span class="CPDapprovedText">Your CPD activity for this year has been reviewed and approved</span></div>

{/if}

<table><tr>

              <td nowrap="nowrap">{$display_name}</td>

              <td nowrap="nowrap">Membership Number: {$membership_number}</td>

              <td nowrap="nowrap">Date: {$today}</td>

            </tr></table>
<table id="category-list" cellspacing="0" cellpadding="0" border="0">
  <tbody>

  	<tr valign="top" class="CPDTableHeading">

            <th nowrap="">View record for: 

              <select class="cpd-frm" name="select_year" id="select_year">{$select_years}</select>

            <p class="cpd-message">To choose another year select it from the drop-down menu above.</p></th><tr>

              <th nowrap=""><h2 class="CPDReportTitle">Your {$smarty.session.report_year} {$civi_cpd_short_name} report</span>

              <a href="/civicrm/civicpd/report?snippet=2" target="_blank" title="Printer-friendly view of this page." class="CPDprintButton">Print this report</a></h2>

</th></tr>

        </tr>

        <tr>

        	<td><div class="CPDHours">{$total_credits}</div><div class="CPDText"> hours recorded</div></td>

            </tr>


        <tr valign="top"><td>&nbsp;</td></tr>



            {$output}



   <!-- 

   <tr valign="top">

      <td colspan="7">{$civi_cpd_short_name} hours for activities undertaken

        in the calendar year {$smarty.session.report_year}: <strong>{$total_credits}</strong></td>

    </tr>

    -->

    

    <tr valign="top">

      <td nowrap="">

        <!-- 
        <table cellspacing="0" cellpadding="0" border="0">
          <tbody>

            <tr>

              <td nowrap="nowrap">{$display_name}</td>
              <td nowrap="nowrap">&nbsp;</td>
              <td nowrap="nowrap">Membership Number: {$membership_number}</td>
              <td nowrap="nowrap">&nbsp;</td>
              <td nowrap="nowrap">Date: {$today}</td>

            </tr>

          </tbody>

        </table>
        -->

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

</div>



<div class="activity-item" title="Add new activity record">

    <div class="activity-item-manual">

        <p>Note: The fields marked with (*) are required</p>

        <form method="post" action="/civicrm/civicpd/report" enctype="multipart/form-data" novalidate>

            <input type="hidden" value="insert" name="action">

            <input type="hidden" value="" id="manual-import-category-id" name="category_id">
            <table cellspacing="0" cellpadding="0" border="0" align="center">
                <tbody>

                <tr>
                    <td valign="top" nowrap="nowrap"><label for="credit_date">Date *:</label></td>
                    <td>
                        <input title="Date" required class="dateplugin frm" type="text" size="30" name="credit_date"

                               value="{$today}">

                    </td>

                </tr>

                <tr>
                    <td valign="top" nowrap="nowrap"><label for="activity">Title of activity *:</label></td>
                    <td>
                        <input title="Title of Activity" required type="text" size="30" class="frm" name="activity">

                    </td>

                </tr>

                <tr>
                    <td valign="top"><label for="credits">Number of hours *:</label></td>
                    <td>
                        <input title="Number of Hours" type="text" required maxlength="4" size="30" class="frm"

                               name="credits">

                    </td>

                </tr>

                <tr>
                    <td valign="top" nowrap="nowrap">
                        <label for="notes">Notes and reflection on activity *:</label>

                    </td>
                    <td>
                        <textarea maxlength="10000" title="Notes and Reflection" class="frm" required rows="4" cols="39"

                                  name="notes"></textarea></td>

                </tr>

                <tr>

                    <td><label for="evidence">Evidence (optional):</label></td>

                    <td><input title="Evidence" type="file" name="evidence" id="evidence"></td>

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



<div id="crm-notification-container" style="display:none">

    <div id="crm-notification-alert" class="#{ldelim}type{rdelim}">

        <div class="icon ui-notify-close" title="{ts}close{/ts}"></div>

        <a class="ui-notify-cross ui-notify-close" href="#" title="{ts}close{/ts}">x</a>



        <h1>#{ldelim}title{rdelim}</h1>



        <div class="notify-content">#{ldelim}text{rdelim}</div>

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
