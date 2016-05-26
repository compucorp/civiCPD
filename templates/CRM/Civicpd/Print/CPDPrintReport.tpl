<div id="page">
    <!-- Top header section -->
    <div id="cpd-header">
        <h2>Your {$smarty.session.report_year} {$civi_cpd_short_name} report</h2>
    </div>
    <img id="cpd-ies-logo" src="{$logoUrl}" alt="IES Logo">
    <!-- End of top header section -->

    <!-- Personal details section -->
    <div id="cpd-personal-details">
        <table>
            <tr>
                <td>Name: {$display_name}</td>
                <td>Membership Number: {$membership_number}</td>
                <td>Date: {$today}</td>
            </tr>
            <tr>
                <td>Job Title: {$job_title}</td>
                <td>Membership Types:
                    {foreach from=$membership_types key=myId item=type name=types}
                        {if !$smarty.foreach.types.last}
                            {$type},
                            {else}
                            {$type}
                        {/if}
                    {/foreach}

                   </td>
            </tr>
        </table>
    </div>
    <!-- End of personal details section -->

    <!-- CPD hours summary section -->
    <div id="cpd-hours-summary">
        <table>
        <tr>
            <td><strong>Total Hours: {$total_credits}</strong></td>
        </tr>
        </table>
    </div>
    <!-- End of CPD hours summary section -->

    <!-- CPD summary listing section -->

    <div id="cpd-summary">
        <table id="category-list">
            <tr>
                <td>{$output}</td>
            </tr>
        </table>
    </div>
    <!-- End of CPD summary listing section -->

    <!-- Full upload hours section -->
    <div id="full-upload-hours">
        Additional hours uploaded by PDF:  {$full_upload_hours} hours
    </div>
    <!-- End of full upload hours section -->

</div> <!-- End of page div -->


{literal}
    <script type="text/javascript">
        if (window.location.href.indexOf('snippet=2') != -1) {

            var hideElements = [
                '.cpd-message',
                '.edit-activity-response',
                '.edit-activity-buttons',
                '.activity-item',
                '.upload-activity-buttons',
                '.activity-item-import-pdf',
                '.uploaded-activity-response',
                '.rating',
                '.ui-progressbar-value.ui-widget-header.ui-corner-left.ui-corner-right',
                '.toggle-activity-list',
                '.new-activity-item',
                'p'
            ];

            for (var i = 0, len = hideElements.length; i < len; i++) {
                cj(hideElements[i]).hide();
            }
        }
    </script>
{/literal}
