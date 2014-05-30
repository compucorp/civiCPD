cj(function(){	
    var sorter=new table.sorter("sorter");
    sorter.init("sorter",1);
    
    cj.fn.center = function () {
       this.css("position","fixed");
       this.css("top", (cj(window).height() / 2) - (this.outerHeight() / 2));
       this.css("left", (cj(window).width() / 2) - (this.outerWidth() / 2));
       return this;
    }  
    
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
            url: '/civicrm/civicpd/reportyear?reset=1&snippet=2',
            data: { new_year : reportyear },
            success: function(data){
                window.setTimeout('location.reload()', 0);
            },
            error: function(){
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
			
});