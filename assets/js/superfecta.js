$("li.scheme i").click(function() {
  var type = $(this).data("type"), name = $(this).parents("li.scheme").data("name"), $this = this;
  switch (type) {
    case "power":
      if ($(this).hasClass("fa-toggle-on")) {
        $.getJSON("ajax.php?module=superfecta&command=power&scheme=" + name, function(data) {
          if (data.status) {
            $($this).removeClass("fa-toggle-on").addClass("fa-toggle-off");
          }
        });
      } else {
        $.getJSON("ajax.php?module=superfecta&command=power&scheme=" + name, function(data) {
          if (data.status) {
            $($this).removeClass("fa-toggle-off").addClass("fa-toggle-on");
          }
        });
      }
    break;
    case "up":
      console.log("up");
    break;
    case "down":
      console.log("down");
    break;
    case "duplicate":
      if (confirm("Are you sure you wish to duplicate this scheme?")) {
      }
    break;
    case "delete":
      if (confirm("Are you sure you wish to delete this scheme?")) {
        $.getJSON("ajax.php?module=superfecta&command=delete&scheme=" + name, function(data) {
          if (data.status) {
            $($this).parents("li.scheme").fadeOut("slow");
          }
        });
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
  var type = $(this).data("type"), row = $(this).parents("tr"), name = row.data("name"), scheme = row.data("scheme"), $this = this;
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
        width: 550,
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
          $.getJSON("ajax.php?module=superfecta&command=options&scheme=" + scheme + "&source=" + name, function(data) {
            if(data.status) {
              $($this).html(data.html);
              $("a.info").each(function(){$(this).after('<span class="help"><i class="fa fa-question-circle"></i><span>'+$(this).find('span').html()+'</span></span>');$(this).find('span').remove();$(this).replaceWith($(this).html())})
              $(".help").on('mouseenter',function(){side=fpbx.conf.text_dir=='lrt'?'left':'right';var pos=$(this).offset();var offset=(200-pos.side)+"px";$(this).find("span").css(side,offset).stop(true,true).delay(500).animate({opacity:"show"},750);}).on('mouseleave',function(){$(this).find("span").stop(true,true).animate({opacity:"hide"},"fast");});
            }
          });
        },
        close: function() {
          $(this).dialog( "close" );
          $(this).remove();
        }
      });
    break;
  }
});

$("#configure").click(function() {
  $( "#scheme-dialog-form" ).dialog( "open" );
})

$(function() {
  $( "#scheme-dialog-form" ).dialog({
    autoOpen: false,
    height: 500,
    width: 600,
    modal: true,
    buttons: {
      "Agree and Save": function() {

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
  $.getJSON("ajax.php?module=superfecta&command=update_sources&scheme=" + scheme, {
    data: source_order
  },
  function(json) {
  });
}
