/* 
 * CORE main functions
 */

/* links in edit containers */
function DoEdits(s) {
    /* link in the show button */
    $("#"+s+"_button_add").click(function() {
        $(this).hide();
        $("#"+s+"_button_close").slideDown("fast");
        $("#"+s+"_container").slideDown("slow");
    });
    /* Link in the Hide Button */
    $("#"+s+"_button_close").click(function() {
       $(this).hide();
       $("#"+s+"_button_add").slideDown("fast");
       $("#"+s+"_container").slideUp("slow");
    });
}

function ShowEdits(s) {
    $("#"+s+"_button_add").hide();
    $("#"+s+"_button_close").show();
    $("#"+s+"_container").slideDown("slow");
    TriggerError();
}
/* Triggers the error mask */
function TriggerError() {
    var em = $("#errormask");
    $(em).fadeIn("slow");
    $(em).fadeOut(3500);
}

/* Shows the ajax graphic */
function ShowAjaxLoader(c) {
    $("#"+c).html('<div class="dpad tcenter"><img src="/mod/coresurvey/images/ajax-loader.gif"/></div>');
}

/* ajax load admin role survey */
function LoadRoles() {
    /* Show the ajax loader */
    ShowAjaxLoader('rolecontainer');
    $.ajax({
       url: '/mod/coresurvey/ajax/load_roles.php',
       dataType: 'html',
       success: function(resp) {
           $("#rolecontainer").html(resp);
       }

    });
}

/* ajax load admin role survey */
function LoadSkills() {
    /* Show the ajax loader */
    ShowAjaxLoader('skillcontainer');
    $.ajax({
       url: '/mod/coresurvey/ajax/load_skills.php',
       dataType: 'html',
       success: function(resp) {
           $("#skillcontainer").html(resp);
       }

    });
}
function CreateSlider(str, v) {
    /* display the alignment */
    $("#aspect_weighting_slider_"+str).click(function() {
	alert("click");
	$(this).slider({
	    animate: true,
	    min: 0,
	    max: 100,
	    step: 1,
	    value: v,
	    slide: function(event, ui) {
		    $("#aspect_weighting_header_"+str).html(ui.value+"%");
		    $("#aspect_weighting_"+str).val(ui.value);
		   }
	});
    });
}