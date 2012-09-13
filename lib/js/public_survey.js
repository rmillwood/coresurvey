/* Public Functions */
function CreateSlider(str, v) {
    /* display the alignment */
    $("#slider_"+str).click(function() {
	$(this).unbind('click');
	$("#slider_header_"+str+" span").show();
	$("#statement_"+str+"_50").show();
	$("#slider_"+str).slider({
	    animate: true,
	    min: 0,
	    max: 100,
	    step: 25,
	    value: v,
	    slide: function(event, ui) {
		    $("#slider_header_"+str).html(GetAlignment(ui.value));
		    $("#answer_"+str).val(ui.value);
		    ShowAlignmentStatement(str, ui.value);
		   }
	});
    });
    
}
function CreateSliderTest(str, v) {
    $("#slider_"+str).slider({
	    animate: true,
	    min: 0,
	    max: 100,
	    step: 25,
	    value: v,
	    start: function(event, ui) {
		if (! $("#slider_"+str+" a").hasClass("ui-slider-handle")) {
		    $("#slider_"+str+" a").addClass("ui-slider-handle ui-state-default ui-corner-all");
		    $("#slider_header_"+str).html(GetAlignment(ui.value));
		    ShowAlignmentStatement(str, ui.value);
		} 
	    },
	    slide: function(event, ui) {
		$("#slider_header_"+str).html(GetAlignment(ui.value));
		$("#answer_"+str).val(ui.value);
		ShowAlignmentStatement(str, ui.value);
	    }
	});
	$("#slider_"+str+" a").removeClass("ui-slider-handle ui-state-default ui-corner-all");
}
function CreateSliderVertical(str, v) {
    /* display the alignment */
    
	$("#slider_"+str).slider({
	    animate: true,
	    orientation: "vertical",
	    min: 0,
	    max: 100,
	    step: 25,
	    value: v,
	    start: function(event, ui) {
		if (! $("#slider_"+str+" a").hasClass("ui-slider-handle")) {
		    $("#slider_"+str+" a").addClass("ui-slider-handle ui-state-default ui-corner-all");
		    $("#slider_header_"+str).html(GetAlignment(ui.value));
		    ShowAlignmentStatement(str, ui.value);
		}
	    },
	    slide: function(event, ui) {
		$("#slider_header_"+str).html(GetAlignment(ui.value));
		$("#answer_"+str).val(ui.value);
		ShowAlignmentStatementVertical(str, ui.value);
	    }
	});
	$("#slider_"+str+" a").removeClass("ui-slider-handle ui-state-default ui-corner-all");
        
}
/* Paused vertical slider */
function CreateSliderVerticalPaused(str, v) {
    /* display the alignment */

	$("#slider_header_"+str).html(GetAlignment(v));
	$("#statement_"+str+"_"+v).show();
	ShowAlignmentStatementVertical(str, v);
	$("#slider_"+str).slider({
	    animate: true,
	    orientation: "vertical",
	    min: 0,
	    max: 100,
	    step: 25,
	    value: v,
	    slide: function(event, ui) {
		    $("#slider_header_"+str).html(GetAlignment(ui.value));
		    $("#answer_"+str).val(ui.value);
		    ShowAlignmentStatementVertical(str, ui.value);
		   }
	});

}
/* For finished surveys */
function CreateSliderFinished(str, v) {
    /* display the alignment */
    $("#statement_"+str+"_"+v).show();
    $("#slider_"+str).slider({
        animate: true,
        min: 0,
        max: 100,
        step: 25,
        value: v,
        slide: function(event, ui) {
		$("#slider_header_"+str).html(GetAlignment(ui.value));
                $("#answer_"+str).val(ui.value);
                ShowAlignmentStatement(str, ui.value);
               },
        stop: function(event, ui) {
            /* Set the slider, header and text back to original */
            $(this).slider("value", v);
            $("#slider_header_"+str).html(GetAlignment(v));
            ShowAlignmentStatement(str, v);
        }
    });
}

/* returns the alignment text */
function GetAlignment(v) {
    return myalign[v];
}

/* shows the alignment statement */
function ShowAlignmentStatement(str, v) {
    $("#statement_container_"+str+" .align_statement").hide();
    $("#statement_"+str+"_"+v).show();
}
function ShowAlignmentStatementVertical(str, v) {
    $("#statement_container_"+str+" .align_statement").hide();
    $("#statement_"+str+"_"+v).show();
}

