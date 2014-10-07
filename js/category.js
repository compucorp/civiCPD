cj(function() {
    cj('.new-category-button').click(function(e) {
        if (cj('.add-new-category').is(':visible')) {
            cj('.add-new-category').hide();
            cj(this).html("New CPD category");
        } else {
            
            cj(this).html("");
            cj('.add-new-category').show();
            
            // Show the dialog for adding categories
            cj("#dialog").dialog({
                            width: 400,
                            beforeClose: function(event, ui) {
                                // Call show add form
                                cj('.new-category-button').html("New CPD category");
                                cj('.add-new-category').show();
                                console.log('close');
                            }
                         });
                         
        }
    });
});