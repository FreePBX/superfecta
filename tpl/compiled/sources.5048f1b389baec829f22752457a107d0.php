<?php if(!class_exists('raintpl')){exit;}?><script type="text/javascript" src="<?php echo $web_path;?>"></script>
<script language="javascript">
    function change(source, type) {
        if(type == 'enable') {
            var row = $("#"+source).html();
            var frow = '';
            var found = false;
            $.ajaxSetup({ cache: false });
            $.getJSON("config.php?quietmode=1&handler=file&module=superfecta&file=ajax.html.php&type=add&scheme=<?php echo $scheme;?>&source="+source, function(json) {
                if(json.success) {
                    $("#sources :checked").each(function(){
                        if(($(this).val() == 'disabled') && (!found) ) {
                            frow = $(this).attr('id').replace("_disabled","");
                            found = true;
                        }
                    });
                    $('#'+source).remove();
                    $('#'+frow).before('<tr id="'+source+'" class="enabled">' + row + '<tr>');
                    $('input[name="'+source+'"]').val(['enabled']);
                } else {
                    $('input[name="'+source+'"]').val(['disabled']);
                    $('#message').html('Error');
                }
            });
        } else if(type == 'disable') {
            var row = $("#"+source).html();
            $.ajaxSetup({ cache: false });
            $.getJSON("config.php?quietmode=1&handler=file&module=superfecta&file=ajax.html.php&type=remove&scheme=<?php echo $scheme;?>&source="+source, function(json) {
                if(json.success) {
                    $('#'+source).remove();
                    $('#sources tr:last').after('<tr id="'+source+'" class="disabled">' + row + '<tr>');
                    $('input[name="'+source+'"]').val(['enabled']);
                } else {
                     $('input[name="'+source+'"]').val(['enabled']);
                    $('#message').html('Error');
                }
            });
        }
    }
    
    function options(source) {
        $('#options').fadeOut('slow', function() {
            $.ajaxSetup({ cache: false });
            $.getJSON("config.php?quietmode=1&handler=file&module=superfecta&file=ajax.html.php&type=options&scheme=<?php echo $scheme;?>&source="+source, function(json) {
                if(json.success && json.show) {
                    $('#options').fadeIn('slow').html(json.data);
                    $('#form_options_'+source).ajaxForm(function() { 
                        alert("Saved!"); 
                    }); 
                }
            });
        });
    }
    
    function move(source,action) {
        $.ajaxSetup({ cache: false });
        $.getJSON("config.php?quietmode=1&handler=file&module=superfecta&file=ajax.html.php&type=move&a="+action+"&scheme=<?php echo $scheme;?>&source="+source, function(json) {
            if(json.success) {
                $('#'+source).remove();
                //$('#'+frow).before('<tr id="'+source+'" class="enabled">' + row + '<tr>');
            }
        });
    }
</script>
<input type="hidden" name="src_up" value="">
<input type="hidden" name="src_down" value="">
<input type="hidden" name="selected_source" value="">
<input type="hidden" name="update_file" value="">
<input type="hidden" name="delete_file" value="">
<input type="hidden" name="revert_file" value="">
<span id="message" style="color:red;font-weight:bolder"><?php echo $update_site_message;?></span></br>
<font size=2><input type="checkbox" name="check_updates" value="yes" <?php echo $check_updates_check;?>">&nbsp;Check for Data Source File updates online.<br></font>

<table>
    <tr>
        <td>
            <table border="0" id="sources" cellspacing="0" cellpadding="2">
                <tr>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td><strong>Data Source Name</strong></td>
                    <td align="center"><strong>Disabled</strong></td>
                    <td align="center"><strong>Enabled</strong></td>
                </tr>
                <?php $counter1=-1; if( isset($sources) && is_array($sources) && sizeof($sources) ) foreach( $sources as $key1 => $value1 ){ $counter1++; ?>

                <tr id="<?php echo $value1["source_name"];?>" class="<?php echo $value1["status"];?>">
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td><?php if( $value1["showdown"] ){ ?><a href="#" onclick="move('<?php echo $value1["source_name"];?>', 'down')"><img src="http://192.168.1.5/admin/images/scrolldown.gif"></a><?php } ?></td>
                    <td><?php if( $value1["showup"] ){ ?><a href="#" onclick="move('<?php echo $value1["source_name"];?>', 'up')"><img src="http://192.168.1.5/admin/images/scrollup.gif"></a><?php } ?></td>
                    <td><a onclick="options('<?php echo $value1["source_name"];?>')"><?php echo $value1["pretty_source_name"];?></a></td>
                    <td align="center"><input type="radio" id="<?php echo $value1["source_name"];?>_disabled" name="<?php echo $value1["source_name"];?>" value="disabled" onclick="change('<?php echo $value1["source_name"];?>', 'disable')" <?php if( $value1["enabled"] === FALSE ){ ?>checked<?php } ?>/></td>
                    <td align="center"><input type="radio" id="<?php echo $value1["source_name"];?>_enabled" name="<?php echo $value1["source_name"];?>" value="enabled" onclick="change('<?php echo $value1["source_name"];?>','enable')" <?php if( $value1["enabled"] === TRUE ){ ?>checked<?php } ?>/></td>
                </tr>
                <?php } ?>

            </table>
        </td>
        <td valign="top">
            <div id="options" style="background: #C0C0C0"></div>
        </td>
    </tr>
</table>