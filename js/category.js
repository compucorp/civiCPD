cj(function() {
    cj('.new-category-button').click(function(e) {
        if (cj('.add-new-category').is(':visible')) {
            cj('.add-new-category').hide();
            cj(this).html("New");
        } else {
            cj('.add-new-category').show();
            cj(this).html("Hide");
        }
    });
});