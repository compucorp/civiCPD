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

    /**
     * Configure the modal dialog for entering full CPD record
     */
    cj('.activity-item').dialog({
        autoOpen: false,
        minWidth: 500
    });

    /**
     * Process manual submission of an activity record
     */
    cj('.activity-item-manual').find('form').on('submit', function(e) {
        var form = cj(this);

        var validationCandidates = form.find('input, textarea');
        validateFormFields(form, validationCandidates, e);
    });

    /**
     * Process full CPD upload
     */
    cj('.activity-item-import-pdf').find('form').on('submit', function (e) {
        var form = cj(this);

        var validationCandidates = form.find('input, textarea');
        validateFormFields(form, validationCandidates, e);
    });

    /**
     * Validate a collection for fields in a form
     *
     * @param form
     * @param fields
     * @param e
     *
     * @returns {{hasErrors: boolean, errors: Array}}
     */
    var validateFormFields = function(form, fields, e) {
        var hasErrors = false;
        var errors = [];

        fields.each(function (index, element) {
            var elementObj = cj(element);

            var validationResult = validateInput(elementObj);

            if (!validationResult.isValid) {
                errors.push(validationResult.errors.join(', '));
                hasErrors = true;
            }
        });

        if (hasErrors) {
            handleValidationFailure(form, errors, e);
        } else {
            handleValidationSuccess(form);
        }

        return {
            hasErrors: hasErrors,
            errors: errors
        };
    };

    /**
     * Validate a form field
     *
     * @param input
     * @returns {{isValid: boolean, errors: Array}}
     */
    var validateInput = function(input) {
        var validated = true;
        var errors = [];

        if (input.attr('type') === 'file' && input.val()) {
            if (input.val().split('.').pop().toLowerCase() !== 'pdf') {
                input.addClass('error');

                validated = false;
                errors.push('Only PDF file format is allowed for ' + input.attr('title'));
            } else {
                input.removeClass('error');
            }
        }

        if (input.prop('required') === true) {
            if (!input.val()) {
                input.addClass('error');

                errors.push(input.attr('title') + ' is required');
                validated = false;
            } else {
                input.removeClass('error');
            }
        }

        return {
            isValid: validated,
            errors: errors
        };
    };

    /**
     * Handle the event when form validation fails
     *
     * @param form
     * @param errors
     * @param e
     */
    var handleValidationFailure = function(form, errors, e) {
        var errorHtml = '<div class="errors"><p class="failure">Please fix the error(s) below:<br>';

        cj.each(errors, function () {
            errorHtml += this + '<br>';
        });

        errorHtml += '</p></div>';

        var errorBlock = form.find('.errors').first();

        if (errorBlock.length) {
            errorBlock.html(errorHtml);
        } else {
            form.prepend(errorHtml);
        }

        e.preventDefault();
    };

    var handleValidationSuccess = function (form) {
        var errorBlock = form.find('.errors').first();

        if (errorBlock.length) {
            errorBlock.remove();
        }
    };

    /**
     * Show/hide activities within a category upon clicking on the category title
     */
    cj('.category-title').on('click', function (e) {
        e.preventDefault();

        var category = cj(this).parents('.category');
        var activityList = category.find('.activity-list');

        var icon = cj(this).find('.toggle-activity-list');

        icon.toggleClass('contracted');
        activityList.slideToggle();
    });

    /**
     * Show the modal popup for manually adding activity record
     */
    cj('.new-activity-item').on('click', function(e) {
        e.preventDefault();
        e.stopPropagation();

        var categoryId = cj(this).data('category-id');
        var activityForm = cj('.activity-item');

        activityForm.find('#manual-import-category-id').val(categoryId);

        activityForm.dialog('open');
    });

    /**
     * Cancel adding full CPD report and close the modal popup
     */
    cj('#cancel-new-activity').on('click', function() {
        cj('.activity-item').dialog('close');
    });

    /**
     * Add text for printer friendly link
     */
    cj('#printer-friendly').find('a')
      .addClass('button')
      .prepend('<i class="icon print-icon"></i>Print this year\'s record')
      .find('div')
      .hide();

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
