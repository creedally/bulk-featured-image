jQuery(document).ready(function($) {
    $('.bfie-select2').select2();

    jQuery('#bfi_posttyps').change(function () {
        var val = $(this).val();
        $(".enable-default-image").parent().hide();
        $.each(val, function( index, value ) {
            $("#enable_default_image_"+value).parent().show();
        });
        
    }).change();
});

function bfi_drag_drop(event, id ='' ) {
    
    var preview_id = 'bfi_upload_preview';
    if( parseInt(id) > 0 ) {
        preview_id += '_'+id;
        jQuery('#post_thumbnail_url_'+id).parent().remove();
        jQuery('#no_thumbnail_url_'+id).remove();
        
    }

    var fileName = URL.createObjectURL(event.target.files[0]);
    var preview = document.getElementById(preview_id);
    var previewImg = document.createElement("img");
    previewImg.setAttribute("src", fileName);
    preview.innerHTML = "";
    preview.appendChild(previewImg);
}

function bfi_drag( event, id ='') {
    var upload_file = 'bfi_upload_file';
    if( parseInt(id) > 0 ) {
        upload_file += '_'+id;        
    }
    //document.getElementById(upload_file).parentNode.className = 'draging dragBox';
}
function bfi_drop( event, id ='') {
    var upload_file = 'bfi_upload_file';
    if( parseInt(id) > 0 ) {
        upload_file += '_'+id;        
    }
    //document.getElementById(upload_file).parentNode.className = 'dragBox';
}