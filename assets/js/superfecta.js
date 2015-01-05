$("li.scheme i").click(function() {
	var type = $(this).data("type"), row = $(this).parents("li.scheme"), name = row.data("name"), $this = this;
	switch (type) {
		case "power":
			if ($(this).hasClass("fa-toggle-on")) {
				$.post("ajax.php?module=superfecta&command=power&scheme=" + encodeURIComponent(name), {}, function(data) {
					if (data.status) {
						$($this).removeClass("fa-toggle-on").addClass("fa-toggle-off");
					}
				}, "json");
			} else {
				$.post("ajax.php?module=superfecta&command=power&scheme=" + encodeURIComponent(name), {}, function(data) {
					if (data.status) {
						$($this).removeClass("fa-toggle-off").addClass("fa-toggle-on");
					}
				}, "json");
			}
		break;
		case "up":
			$.post("ajax.php?module=superfecta&command=sort&scheme=" + encodeURIComponent(name), {position: "up"}, function(data) {
				if (data.status) {
					row.fadeOut('slow', function() {
						row.insertBefore(row.prev());
						row.fadeIn('slow');
						sort_scheme();
					})
				}
			}, "json");
		break;
		case "down":
			$.post("ajax.php?module=superfecta&command=sort&scheme=" + encodeURIComponent(name), {position: "down"}, function(data) {
				if (data.status) {
					row.fadeOut('slow', function() {
						row.insertAfter(row.next());
						row.fadeIn('slow');
						sort_scheme();
					})
				}
			}, "json");
		break;
		case "duplicate":
			if (confirm("Are you sure you wish to duplicate this scheme?")) {
				$.post("ajax.php?module=superfecta&command=copy&scheme=" + encodeURIComponent(name), {}, function(data) {
					if (data.status) {
						document.location.href = data.redirect;
					}
				}, "json");
			}
		break;
		case "delete":
			if (confirm("Are you sure you wish to delete this scheme?")) {
				$.post("ajax.php?module=superfecta&command=delete&scheme=" + encodeURIComponent(name), {}, function(data) {
					if (data.status) {
						if(typeof scheme !== "undefined" && scheme == name) {
							document.location.href = "config.php?display=superfecta";
						} else {
							$($this).parents("li.scheme").fadeOut("slow", function() {
								$(this).remove();
								sort_scheme();
							});
						}
					}
				}, "json");
			}
		break;
	}
});

$(".enabled input").click(function() {
	var val = $(this).val(), row = $(this).parents("tr"), parent_id = row.attr("id");
	switch(val) {
		case "on":
			$("#sources tr").each(function(index) {
				var eid = $(this).attr("id"), $this = this;
				if (($(this).attr("id") != "row_header") && !$("#" + eid + "_enabled_yes").is(":checked") && parent_id != eid) {
					row.fadeOut("slow", function() {
						row.insertBefore($($this));
						row.fadeIn("slow");
						source_order();
					});
					return false;
				}
			});
		break;
		case "off":
			row.find(".fa-arrow-down").addClass("hidden");
			row.find(".fa-arrow-up").addClass("hidden");
			$("#sources tr").each(function(index) {
				var eid = $(this).attr("id"), $this = this;
				if (($(this).attr("id") != "row_header") && !$("#" + eid + "_enabled_yes").is(":checked") && parent_id != eid) {
					row.fadeOut("slow", function() {
						row.insertBefore($($this));
						row.fadeIn("slow");
						source_order();
					});
					return false;
				}
			});
		break;
	}
});

$(".source i").click(function() {
	var type = $(this).data("type"), row = $(this).parents("tr"), name = row.data("name"), $this = this;
	switch (type) {
		case "down":
			row.fadeOut('slow', function() {
				row.insertAfter(row.next());
				row.fadeIn('slow');
				source_order();
			})
		break;
		case "up":
			row.fadeOut('slow', function() {
				row.insertBefore(row.prev());
				row.fadeIn('slow');
				source_order();
			})
		break;
		case "configure":
			$( '<div id="dialog-form" title="Configure ' + name + '">Loading...<i class="fa fa-spinner fa-spin"></i></div>' ).dialog({
				autoOpen: true,
				height: 400,
				width: 650,
				modal: true,
				buttons: {
					Save: function() {
						var $this = this;
						$("#dialog-form form").ajaxSubmit({
							success: function(responseText, statusText, xhr, $form) {
								$($this).dialog( "close" );
								$($this).remove();
							}
						});
					},
					Cancel: function() {
						$(this).dialog( "close" );
						$(this).remove();
					}
				},
				open: function() {
					var $this = this;
					$.post("ajax.php?module=superfecta&command=options&scheme=" + encodeURIComponent(scheme) + "&source=" + name, {}, function(data) {
						if (data.status) {
							$($this).html(data.html);
							$("a.info").each(function(){$(this).after('<span class="help"><i class="fa fa-question-circle"></i><span>'+$(this).find('span').html()+'</span></span>');$(this).find('span').remove();$(this).replaceWith($(this).html())})
							$(".help").on('mouseenter',function(){side=fpbx.conf.text_dir=='lrt'?'left':'right';var pos=$(this).offset();var offset=(200-pos.side)+"px";$(this).find("span").css(side,offset).stop(true,true).delay(500).animate({opacity:"show"},750);}).on('mouseleave',function(){$(this).find("span").stop(true,true).animate({opacity:"hide"},"fast");});
						}
					}, "json");
				},
				close: function() {
					$(this).dialog( "close" );
					$(this).remove();
				}
			});
		break;
	}
});

$(function() {
	$("form[name=new_scheme]").submit(function() {
		if ($("input[name=scheme_name]").val().trim() === "") {
			alert("Scheme Name can not be blank!");
			$("input[name=scheme_name]").focus();
			return false;
		}
	});
	if ($("#enableInterceptor").is(":checked")) {
		$("#InterceptorVector").show();
	} else {
		$("#InterceptorVector").hide();
	}
	$("#enableInterceptor").click(function() {
		if ($(this).is(":checked")) {
			$("#InterceptorVector").show();
		} else {
			$("#InterceptorVector").hide();
		}
	});
	$("#configure").click(function() {
		$( "#scheme-dialog-form" ).dialog( "open" );
	});
	$( "#scheme-dialog-form" ).dialog({
		autoOpen: false,
		height: 500,
		width: 650,
		modal: true,
		buttons: {
			Save: function() {
				var $this = this;
				$("#scheme-dialog-form form").ajaxSubmit({
					success: function(responseText, statusText, xhr, $form) {
						if(responseText.status) {
							if(responseText.redirect !== "") {
								document.location.href = responseText.redirect;
							}
							$($this).dialog( "close" );
						}
					}
				});
			},
			Cancel: function() {
				$(this).dialog( "close" );
			}
		},
		close: function() {
			$(this).dialog( "close" );
		}
	});
});

function sort_scheme() {
	$("#schemeorder_list li.scheme i.fa-arrow-down").removeClass("hidden");
	$("#schemeorder_list li.scheme i.fa-arrow-down").removeClass("hidden");
	$("#schemeorder_list li.scheme:last i.fa-arrow-down").addClass("hidden");
	$("#schemeorder_list li.scheme:last i.fa-arrow-up").removeClass("hidden");
	$("#schemeorder_list li.scheme:first i.fa-arrow-down").removeClass("hidden");
	$("#schemeorder_list li.scheme:first i.fa-arrow-up").addClass("hidden");
}

function source_order() {
	var total = $("#sources tr").size(), source_order = [];
	$("#sources tr").each(function(index) {
		var id = $(this).attr("id"), up = $(this).find(".fa-arrow-up"), down = $(this).find(".fa-arrow-down");
		if (($(this).attr("id") != "row_header") && $("#" + id + "_enabled_yes").is(":checked")) {
			if(index == 1) {
				up.addClass("hidden");
				var nextid = $(this).next().attr("id");
				if($("#" + nextid + "_enabled_yes").is(":checked")) {
					down.removeClass("hidden");
				} else {
					down.addClass("hidden");
				}
			} else {
				var nextid = $(this).next().attr("id");
				if($("#" + nextid + "_enabled_yes").is(":checked")) {
					down.removeClass("hidden");
				} else {
					down.addClass("hidden");
				}
				up.removeClass("hidden");
			}
			source_order.push(id);
		}
	});
	$.post("ajax.php?module=superfecta&command=update_sources&scheme=" + encodeURIComponent(scheme), {data: source_order}, function(data) {

	}, "json");
}
