var processing = false;
$("#scheme-list").on("post-body.bs.table",function() {
	$(".scheme-actions i").click(function() {
		if(processing) {
			alert("A command is already processing, please wait");
			return;
		}
		processing = true;
		var type = $(this).data("type"), row = $(this).parents("tr.scheme"), name = row.prop("id"), $this = this;
		switch (type) {
			case "power":
				if ($(this).hasClass("fa-toggle-on")) {
					$.post("ajax.php?module=superfecta&command=power&scheme=" + encodeURIComponent(name), {}, function(data) {
						if (data.status) {
							$($this).removeClass("fa-toggle-on").addClass("fa-toggle-off");
						}
					}, "json").always(function() {
						processing = false;
					});
				} else {
					$.post("ajax.php?module=superfecta&command=power&scheme=" + encodeURIComponent(name), {}, function(data) {
						if (data.status) {
							$($this).removeClass("fa-toggle-off").addClass("fa-toggle-on");
						}
					}, "json").always(function() {
						processing = false;
					});
				}
			break;
			case "up":
				row.fadeOut('slow');
				$.post("ajax.php?module=superfecta&command=sort&scheme=" + encodeURIComponent(name), {position: "up"}, function(data) {
					if (data.status) {
						row.insertBefore(row.prev());
						row.fadeIn('slow');
						sort_scheme();
					} else {
						row.fadeIn('slow');
					}
				}, "json").fail(function() {
					row.fadeIn('slow');
				}).always(function() {
					processing = false;
				});
			break;
			case "down":
				row.fadeOut('slow');
				$.post("ajax.php?module=superfecta&command=sort&scheme=" + encodeURIComponent(name), {position: "down"}, function(data) {
					if (data.status) {
						row.insertAfter(row.next());
						row.fadeIn('slow');
						sort_scheme();
					} else {
						row.fadeIn('slow');
					}
				}, "json").fail(function() {
					row.fadeIn('slow');
				}).always(function() {
					processing = false;
				});
			break;
			case "duplicate":
				if (confirm("Are you sure you wish to duplicate this scheme?")) {
					$.post("ajax.php?module=superfecta&command=copy&scheme=" + encodeURIComponent(name), {}, function(data) {
						if (data.status) {
							document.location.href = data.redirect;
						}
					}, "json").always(function() {
						processing = false;
					});
				} else {
					processing = false;
				}
			break;
			case "delete":
				if (confirm("Are you sure you wish to delete this scheme?")) {
					$.post("ajax.php?module=superfecta&command=delete&scheme=" + encodeURIComponent(name), {}, function(data) {
						if (data.status) {
							if(typeof scheme !== "undefined" && scheme == name) {
								document.location.href = "config.php?display=superfecta";
							} else {
								$('table').bootstrapTable('removeByUniqueId', name);
							}
						}
					}, "json").always(function() {
						processing = false;
					});
				} else {
					processing = false;
				}
			break;
		}
	});
});

$(".enabled input").click(function() {
	if(processing) {
		alert("A command is already processing, please wait");
		return;
	}
	processing = true;
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
			});
		break;
		case "up":
			row.fadeOut('slow', function() {
				row.insertBefore(row.prev());
				row.fadeIn('slow');
				source_order();
			});
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
							$("a.info").each(function(){
								$(this).after('<span class="help"><i class="fa fa-question-circle"></i><span>' + $(this).find('span').html() + '</span></span>');
								$(this).find('span').remove();
								$(this).replaceWith($(this).html());
							});
							$(".help").on('mouseenter',function(){
								side = (fpbx.conf.text_dir == 'lrt') ? 'left' : 'right';
								var pos = $(this).offset();
								var offset = (200 - pos.side) + "px";
								$(this).find("span").css(side,offset).stop(true,true).delay(500).animate( { opacity: "show" },750);
							}).on('mouseleave',function(){
								$(this).find("span").stop(true,true).animate( { opacity: "hide" },"fast");
							});
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

$("form[name=new_scheme]").submit(function() {
	if ($("input[name=scheme_name]").val().trim() === "") {
		warnInvalid($("input[name=scheme_name]"),_("Scheme Name can not be blank!"));
		return false;
	}
});

if ($("#enableInterceptor_on").is(":checked")) {
	$("#InterceptorVector").show();
} else {
	$("#InterceptorVector").hide();
}
$("#enableInterceptor_on, #enableInterceptor_off").click(function() {
	if ($("#enableInterceptor_on").is(":checked")) {
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
$("#super-debug").click(function() {
	$( "#debug-dialog" ).dialog( "open" );
});
$( "#debug-dialog" ).dialog({
	autoOpen: false,
	height: 600,
	width: 650,
	modal: true,
	buttons: {
		"Run This Scheme": function() {
			runDebug(scheme);
		},
		"Run All Schemes": function() {
			runDebug("ALL");
		},
		Cancel: function() {
			$(this).dialog( "close" );
		}
	},
	open: function() {
		if($("#DID").val().trim() !== "") {
			$('#debug-dialog .debug-window').css('height', '252px');
			$("#didnumber").show();
		} else {
			$('#debug-dialog .debug-window').css('height','')
			$("#didnumber").hide();
		}
	},
	close: function() {
		$(this).dialog( "close" );
	}
});

function runDebug(scheme) {
	if ($("#thenumber").val().trim() === "") {
		alert("Please enter a valid phone number!");
		$("#thenumber").focus();
		return;
	}
	var urlStr = "ajax.php?module=superfecta&command=debug&scheme=" + encodeURIComponent(scheme) + "&level=" + $("#debug_level").val() + "&tel=" + $("#thenumber").val() + "&thedid=" + $("#thedid").val();
	$('#debug-dialog .debug-window').html('Loading..<i class="fa fa-spinner fa-spin fa-2x">');
	var xhr = new XMLHttpRequest(),
	timer = null;
	xhr.open('POST', urlStr, true);
	xhr.send(null);
	timer = window.setInterval(function() {
		if (xhr.readyState == XMLHttpRequest.DONE) {
			window.clearTimeout(timer);
		}
		if (xhr.responseText.length > 0) {
			if ($('#debug-dialog .debug-window').html() != xhr.responseText) {
				$('#debug-dialog .debug-window').html(xhr.responseText);
				$("#debug-dialog .debug-window").prop({ scrollTop: $("#debug-dialog .debug-window").prop("scrollHeight") });
			}
		}
		if (xhr.readyState == XMLHttpRequest.DONE) {
			$("#debug-dialog .debug-window").css("overflow", "auto");
			$("#debug-dialog .debug-window").prop({ scrollTop: $("#debug-dialog .debug-window").prop("scrollHeight") });
		}
	}, 100);
}

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

	}, "json").always(function() {
		processing = false;
	});
}
