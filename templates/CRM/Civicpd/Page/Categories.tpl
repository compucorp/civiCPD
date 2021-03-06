{* Output Categories *}

<h3>Existing {$civi_cpd_long_name} Categories</h3> 
<div><a class="new-category-button" href="#"">New CPD category</a></div>

<p>&nbsp;</p>
<table class="civi-cpd-list" border="0" cellspacing="0" cellpadding="0">
    
    <tr>
        <th class="sorting" rowspan="1" colspan="1">Category</th>
        <th class="sorting" rowspan="1" colspan="1">Description</th>
        <th class="" rowspan="1" colspan="1" style="text-align: center;">Hours (min)</th>
        <th class="" rowspan="1" colspan="1" style="text-align: center;">Action</th>
    </tr>

    {$categories}

</table>
        
<div id="dialog" title="Add CPD category">
    <div class="add-new-category">
        <p><em>Add Category: {$add-response}</em></p>
        <form  method="post" action={crmURL p="civicrm/civicpd/categories"}>
            <input type="hidden" value="insert" name="action">
            <table height="180px" cellspacing="0" cellpadding="0" border="0" align="center">
                <tbody>
                    <tr>
                        <td valign="top" nowrap="nowrap">Category:</td>
                        <td><input type="text" class="required" size="30" id="category" name="category"></td>
                    </tr>
                    <tr>
                        <td valign="top" nowrap="nowrap">Description:</td>
                        <td><textarea id="description" class="required" rows="4" cols="31" name="description"></textarea></td>
                    </tr>
                        <td valign="top">Target Hours:</td>
                        <td><input type="text" maxlength="4" size="5" name="minimum" id="minimum" class="required"></td>
                    </tr>
                    <tr>
                        <td align="center"><input class="validate form-submit default" type="submit" value="Submit" class="form-submit-inline" name="Submit"></td>
                        <td></td>
                    </tr>
                </tbody>
            </table>
        </form>
    </div>
</div>
 
