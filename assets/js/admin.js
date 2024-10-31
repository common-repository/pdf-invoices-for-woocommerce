jQuery(function($){

    var tiptip_args = {
        'attribute': 'data-tip',
        'fadeIn': 50,
        'fadeOut': 50,
        'delay': 200
    };

    $('.help_tip').tipTip(tiptip_args);

    var frame,row_id;

    $('.thickbox').on( 'click', function( event ){

        row_id = $(this).attr('id');

        event.preventDefault();
        // If the media frame already exists, reopen it.
        if ( frame ) {
            frame.open();
            return;
        }

        // Create a new media frame
        frame = wp.media({
            title: 'Select or Upload Store Logo',
            button: {
                text: 'Use this media'
            },
            multiple: false  // Set to true to allow multiple files to be selected
        });

        frame.on( 'select', function() {

            var attachment = frame.state().get('selection').first().toJSON();
            $('#preview_'+row_id).html('<img src="'+attachment.url+'" alt="" style="max-width:100%;"/>');
            $('#preview_'+row_id).append("<a class='remove-bg' img-id="+row_id+" href='javascript:void(0);'>&times;</a>");
            $('#imgid_'+row_id).val(attachment.id);
        });

        frame.open();

    });

    jQuery('.wrap').on('click','.remove-bg', function( event ){
        var rrow_id = jQuery(this).attr('img-id');
        jQuery('#preview_'+rrow_id).html('');
        jQuery('#imgid_'+rrow_id).val('');
        jQuery(this).remove();
    });

    $('.jem-order-action .jem-in-download').click(function() {

        if(!jem_invoice_generated){

            if(confirm(jem_download_in)) {
                jem_invoice_generated = true;
                return true;
            }
            else
            {
                return false;
            }

        }
        
    });

    $('.jem-order-action .jem-in-delete').click(function() {

        if(jem_invoice_generated){

            if(confirm(jem_delete_in)){
                jem_delete_in = true;
                return true;
            }
            else
            {
                return false;
            }

        }
        else
        {
            alert("Invoice already deleted.");
            return false;
        }

    });

    $('.jem-order-action .jem-pr-download').click(function() {

        if(!jem_proforma_generated){

            if(confirm(jem_proforma_in)) {
                jem_proforma_generated = true;
                return true;
            }
            else
            {
                return false;
            }

        }

    });

    $('.jem-order-action .jem-pr-delete').click(function() {

        if(jem_proforma_generated){

            if(confirm(jem_delete_pr)) {
                jem_delete_pr = true;
                return true;
            }
            else
            {
                return false;
            }

        }
        else
        {
            alert("Proforma Invoice already deleted.");
            return false;
        }

    });


    // Handle the invoice update
    $('.jem-pdf-update-invoice').click(function(event) {

        event.preventDefault();
        $(".overlay").show();

        //add the two fields to the URL and punch it.
        var date = $("#jem-invoice-date").val();
        var number = $("#jem-invoice-number").val();

        var url = $(this).attr('href');

        url = url + "&invoice-date=" + date;
        url = url + "&invoice-number=" + number;

        $.ajax({
            url: url,
            success: function(response) {
                //alert(response);
            }
        })
            .done(function( data){
                $(".overlay").hide();

        });

    });


});