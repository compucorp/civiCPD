jQuery(function(){
       
    cj.fn.center = function () {
        this.css('position', 'fixed');
        this.css('top', (cj(window).height() / 2) - (this.outerHeight() / 2));
        this.css('left', (cj(window).width() / 2) - (this.outerWidth() / 2));
        
        return this;
    };

    cj('.dateplugin').each(function () {
          cj(this).datepicker({
              dateFormat: 'dd-mm-yy',
              changeMonth: true,
              changeYear: true,
              yearRange: '-100:+20'
          });
      }
    );

    cj('.activity-item').dialog({
        autoOpen: false,
        minWidth: 460
    });

    cj('.category-title').on('click', function (e) {
        e.preventDefault();

        var category = cj(this).parents('.category');
        var activityList = category.find('.activity-list');

        var icon = cj(this).find('.toggle-activity-list');

        icon.toggleClass('contracted');
        activityList.slideToggle();
    });

    cj('.new-activity-item').on('click', function(e) {
        e.preventDefault();
        e.stopPropagation();

        var categoryId = cj(this).data('category-id');
        var activityForm = cj('.activity-item');

        activityForm.find('#manual-import-category-id').val(categoryId);

        activityForm.dialog('open');
    });

    cj('#cancel-new-activity').on('click', function() {
        cj('.activity-item').dialog('close');
    });

    cj('#branding div.breadcrumb').html('<a href="/">Home</a> » <a href="/civicrm' +
        '/user">Contact Dashboard</a> » CPD Reporting'); 

    cj('#select_year').change(function() {
        
        // Get the selected year from the dropdown option list
        var get_option_index = document.getElementById("select_year").selectedIndex;
        var reportyear = document.getElementsByTagName("option")[get_option_index].value

        cj('#cover').css({
            'width': '100%', 
            'height': '100%', 
            'left': 0, 
            'top': 0, 
            'background-color': '#000000', 
            'opacity': '0.8'
        }).fadeIn( 'slow', function() {
            cj('.ui-progressbar-value').css({
                'text-align': 'center',
                'font-size': '1em',
                'font-weight': 'normal', 
                'padding':'0.15em 0 0.35em'
            }).text('loading . . .');
            cj('#progressbar').css({
                'padding': 0,
                'height': '1.4em'
            }).fadeIn('slow').center();
        });
        
        if (!window.location.origin) window.location.origin = window.location.protocol+"//"+window.location.host;
 
        // Set the year filter URL (this is saved to SESSION as well)
        var year_filter_url = CRM.url('civicrm/civicpd/reportyear');
        console.log(reportyear);

        cj.ajax({
            type: 'POST',
            url: window.location.origin + year_filter_url + '?reset=1&snippet=2',
            data: { new_year : reportyear },
            success: function(data) {
                window.setTimeout('location.reload()', 0);
            },
            error: function() {
                alert('There was a problem changing the year. \nPlease refresh ' + 
                    'the page and try again.');
            }
        });

    });

    cj(window).resize(function(){
        cj('#progressbar').center();
    });

    cj('#print_button').click(function() {
       window.print();
    });	

    cj('.delete').click(function() {
        var url = cj(this).attr('href');

        cj('<div></div>').appendTo('body')
            .html('<div><p>Once an activity has been removed, it cannot be ' + 
                'restored<br/>. Please confirm this deletion.</p></div>')
            .dialog({
                modal: true, 
                title: 'DELETE CONFIRMATION', 
                zIndex: 10000, 
                autoOpen: true,
                width: 'auto', 
                resizable: false,
            buttons: {
                Yes: function () {
                    cj(this).dialog('close');
                    top.location = url;
                },
                No: function () {
                    cj(this).dialog('close');
                }
            },
            close: function (event, ui) {
                cj(this).remove();
            }
        });

        return false;
    });

});
