<h3>{$civi_cpd_long_name} Activities for: <select class="cpd-frm" name="select_year" id="select_year">{$select_years} <input type="search" class="light-table-filter" data-table="order-table" placeholder="Filter">{$export_table}</select></h3>
<p id="intro-text">This report is for the year {$smarty.session.report_year}. To choose another year to report, click on the year in the sub-title above.</p>

{$report_table}

<div id="cover"></div>
<div id="progressbar" class="ui-progressbar ui-widget ui-widget-content ui-corner-all" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="100">
    <div class="ui-progressbar-value ui-widget-header ui-corner-left ui-corner-right" style="display: block; width: 20em; height: 1em;"></div>
</div>

{literal}

<script type="text/javascript">
    var sorter=new table.sorter("sorter");
    sorter.init("sorter",1);
    <!-- 

    if (window.location.href.indexOf('snippet=2')  != -1) {
        cj('.ui-progressbar-value.ui-widget-header.ui-corner-left.ui-corner-right').hide();
        cj('#export-csv').hide();
        cj('.light-table-filter').hide();
        cj('#intro-text').hide();
    }

    //-->
</script>

{/literal} 