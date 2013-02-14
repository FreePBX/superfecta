<?php if(!class_exists('raintpl')){exit;}?><script language="javascript">
    //Turn Scheme on/off
    function power_scheme(id) {
        //We are inside the <a> tag and need to popout of it to find the li 'parent' element
        var parent_id = $('#' + id).parent().attr("id");
        var state = $('#' + id).attr('src');
        if (state == 'assets/superfecta/images/on.gif') {
            $('#' + id).attr('src','assets/superfecta/images/off.gif');
            state = 'ON';
            $.ajaxSetup({ cache: false }); //FreePBX < 2.8
            $.getJSON("config.php?quietmode=1&handler=file&module=superfecta&file=ajax.html.php&type=power_scheme&scheme="+parent_id, 
            function(json) {

            });
        } else {
            $('#' + id).attr('src','assets/superfecta/images/on.gif');
            state = 'OFF';
            $.ajaxSetup({ cache: false }); //FreePBX < 2.8
            $.getJSON("config.php?quietmode=1&handler=file&module=superfecta&file=ajax.html.php&type=power_scheme&scheme="+parent_id, 
            function(json) {

            });
        }
        //send state over ajax stream now!
    }
    
    //Delete Scheme
    function delete_scheme(id) {
        if(confirm('Are you sure you want to delete this Scheme?')) {
            var parent_id = $('#' + id).parent().attr("id");
            $('#' + parent_id).fadeOut('slow', function() {
                $('#' + parent_id).remove();
            });
            $.ajaxSetup({ cache: false });
            $.getJSON("config.php?quietmode=1&handler=file&module=superfecta&file=ajax.html.php&type=delete_scheme&scheme="+parent_id, 
            function(json) {

            });
        }
    }
    
    //Move Scheme up
    function moveup_scheme(id) {
        //This is suprisingly the BEST movement jquery method I have found! EVER
        var li = $('#' + id).parents("li:first");
        var parent_id = $('#' + id).parent().attr("id");
        $('#' + parent_id).fadeOut('slow', function() {
            li.insertBefore(li.prev());
            $('#' + parent_id).fadeIn('slow');
            scheme_order();
        })
    }
    
    //Move Scheme down
    function movedown_scheme(id) {
        //This is suprisingly the BEST movement jquery method I have found! EVER
        var li = $('#' + id).parents("li:first");
        var parent_id = $('#' + id).parent().attr("id"); 
        $('#' + parent_id).fadeOut('slow', function() {
            li.insertAfter(li.next()); 
            $('#' + parent_id).fadeIn('slow');
            scheme_order();
        })
    }
    
    //Fix graphics display and send content
    function scheme_order() {
        //Get order here and then re-do all of the gfx to make sense again to the gui
        var total = $('#schemeorder_list li').size();
        var scheme_json="[";
        $('#schemeorder_list li').each(function(index) {
            if($(this).attr("id") != 'add_new') {
                var id = $(this).attr("id");
                if(index == 1 && total > 1) {
                    $('#' + id + '_moveup').hide();
                    $('#' + id + '_movedown').show();
                    scheme_json = scheme_json + '"'+ id +'",'
                } else if(index == 1 && total == 1) {
                    $('#' + id + '_moveup').hide();
                    $('#' + id + '_movedown').show();
                    scheme_json = scheme_json + '"'+ id +'"'
                } else if(index + 1 == total) {
                    $('#' + id + '_moveup').show();
                    $('#' + id + '_movedown').hide();
                    scheme_json = scheme_json + '"'+ id +'"'
                } else {
                    $('#' + id + '_moveup').show();
                    $('#' + id + '_movedown').show(); 
                    scheme_json = scheme_json + '"'+ id +'",'
                }
            }
        });
        scheme_json = scheme_json + "]";
        console.log(scheme_json);
        $.ajaxSetup({ cache: false });
        $.getJSON("config.php?quietmode=1&handler=file&module=superfecta&file=ajax.html.php&type=update_schemes&scheme=<?php echo $scheme;?>", 
        {
            data: scheme_json
        },
        function(json) {

        });
    }
</script>
<style type="text/css">
    .superfecta_schemes {
        float: left; 
        margin: 4px 1px 0px 1px;
        cursor:pointer;
    }
    .superfecta_message {
        color:red;
        font-weight:bolder;
    }
</style>
<div class="rnav" style="width:250px;">
    <ul id="schemeorder_list" >
        <li id="add_new"><a href="config.php?display=superfecta&scheme=new">Add Caller ID Scheme</a></li>
        <?php $counter1=-1; if( isset($schemes) && is_array($schemes) && sizeof($schemes) ) foreach( $schemes as $key1 => $value1 ){ $counter1++; ?><li id="scheme_<?php echo $value1["source"];?>">
            <img id="scheme_<?php echo $value1["source"];?>_power" onclick="power_scheme(this.id);" class="superfecta_schemes button" src="assets/superfecta/images/<?php if( $value1["powered"] === TRUE ){ ?>on<?php }else{ ?>off<?php } ?>.gif" alt="On/Off" title="Turn this scheme on and off" />
                 <img id="scheme_<?php echo $value1["source"];?>_movedown" class="superfecta_schemes button" onclick="movedown_scheme(this.id);" src="images/scrolldown.gif" alt="Down Arrow" title="Move Down List" <?php if( $value1["showdown"] === FALSE ){ ?>hidden<?php } ?>/>
                 <img id="scheme_<?php echo $value1["source"];?>_moveup" class="superfecta_schemes button" onclick="moveup_scheme(this.id);" src="images/scrollup.gif" alt="Up Arrow" title="Move Up List" <?php if( $value1["showup"] === FALSE ){ ?>hidden<?php } ?>/>
                 <img id="scheme_<?php echo $value1["source"];?>_dupilcate" class="superfecta_schemes button" src="assets/superfecta/images/copy.gif" alt="Duplicate Scheme" title="Duplicate Scheme" />
            <img id="scheme_<?php echo $value1["source"];?>_delete" class="superfecta_schemes button" onclick="delete_scheme(this.id);" src="assets/superfecta/images/delete.gif" alt="Delete Button" title="Delete Scheme" />
            <a onclick="window.location.href='config.php?display=superfecta&amp;scheme=<?php echo $value1["source"];?>';" style="cursor:pointer;"><?php echo $value1["name"];?></a>
        </li><?php } ?>

    </ul>
</div>
<h1><font face="Arial">Caller ID Superfecta</font></h1>
<hr width="20%" align="left">
<p>CallerID Superfecta for FreePBX is a utility program which adds incoming CallerID name lookups to your Asterisk system using multiple data sources.<br><br> </p>