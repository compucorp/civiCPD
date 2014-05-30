jQuery(function(){
       
    cj.fn.center = function () {
        this.css('position', 'fixed');
        this.css('top', (cj(window).height() / 2) - (this.outerHeight() / 2));
        this.css('left', (cj(window).width() / 2) - (this.outerWidth() / 2));
        
        return this;
    }

    if (cj('.add-activity-response').length > 0) {
        var id = '#' + this.id;
        cj(id + '.activity-item').show();
        cj(id + '.new-activity-item').html('Hide');
    }


    cj('.show-activity-list').click(function(e) {
        var id = '#' + this.id;
        cj(id + '.activity-item').hide();
        cj(id +  '.new-activity-item').html('New');
        cj('.update-activity').hide();

        if (cj(id + '.activity-list').is(':visible')) {
            cj(id + '.activity-list').hide();
            cj(this).html('Show');
        } else {
            cj(id + '.activity-list').show();
            cj(this).html('Hide');
        }
    });

    cj('.new-activity-item').click(function(e) {
        var id = '#' + this.id;
        cj('.update-activity').hide();

        if (cj(id + '.activity-item').is(':visible')) {
            cj(id + '.activity-item').hide();
            cj(this).html('New');
        } else {
            if (cj(id + '.activity-list').is(':visible')) {
               cj(id + '.activity-list').hide(); 
               cj(id + '.show-activity-list').html('Show');
            }

            cj(id + '.activity-item').show();
            cj(this).html('Hide');
        }
    });

    cj('.upload-new-activity-item').click(function(e) {
        if (cj('.activity-item-import-pdf').is(':visible')) {
            cj('.activity-item-import-pdf').hide();
            cj(this).html('New');
        } else {
            cj('.activity-item-import-pdf').show();
            cj(this).html('Hide');
        }
    });
    
    cj('#branding div.breadcrumb').html('<a href="/">Home</a> » <a href="/civicrm' + 
        '/user">Contact Dashboard</a> » CPD Reporting'); 

    cj('#select_year').change(function() {
        var reportyear = cj(this).attr('value');

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

        cj.ajax({
            type: 'POST',
            url: '/civicrm/civicpd/reportyear&reset=1&snippet=2',
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
