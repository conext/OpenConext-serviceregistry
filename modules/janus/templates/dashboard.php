<?php
/**
 * Main template for JANUS.
 *
 * @author     Jacob Christiansen, <jach@wayf.dk>
 * @author     Sixto Martín, <smartin@yaco.es>
 * @package    simpleSAMLphp
 * @subpackage JANUS
 * @version    $Id: janus-main.php 11 2009-03-27 13:51:02Z jach@wayf.dk $
 */
define('MODULE_JANUS_URL', '/' . $this->data['baseurlpath'] . 'module.php/janus');
define('DASHBOARD_URL', MODULE_JANUS_URL .'/dashboard.php');
define('FORM_ACTION_URL', SimpleSAML_Utilities::selfURLNoQuery());

$pageJs = array();
$this->data['head'] = '
<script type="text/javascript">
    var moduleJanusUrl = \'' . MODULE_JANUS_URL . '\';
</script>
<base href="' . MODULE_JANUS_URL . '/"></base>
';

if (IS_AJAX) {
$pageJs[] = <<<JAVASCRIPT
// Bind event handler to each form
var forms = $('form');
forms.each(function(index, form) {
    $(form).submit(formSubmitHandler);
});

/**
 * Submits the form via ajax
 *
 * @param Event submitEvent
 */
function formSubmitHandler(submitEvent) {
    submitEvent.preventDefault();

    var form = $(submitEvent.target);

    var formData = form.serializeArray();

    // Add name of the submit buttons since post handlers expect this
    var submitButton = form.find('input[type=submit]');
    formData.push({
        name : submitButton.attr('name'),
        value : submitButton.attr('value')
    });

    $.ajax({
        type: form.attr('method'),
        url: form.attr('action'),
        data: formData,
        success: function(data) {
            var tabPanel = form.parents('.ui-tabs-panel');
            tabPanel.html(data);
        }
    });
}
JAVASCRIPT;
}

$janus_config = SimpleSAML_Configuration::getConfig('module_janus.php');
$this->data['head'] .= '
<script type="text/javascript" src="resources/jquery/jquery/jquery-1.9.1.min.js"></script>
<script type="text/javascript" src="resources/jquery/jquery-ui/jquery-ui.min.js"></script>
<link rel="stylesheet" media="screen" type="text/css" href="resources/jquery/jquery-ui-themes/themes/base/minified/jquery-ui.min.css" />
<link rel="stylesheet" type="text/css" href="resources/style.css" />' . "\n";

/* START TAB ARP JS ***************************************************************************************************/
if (IS_AJAX && $this->data['selectedtab'] == SELECTED_TAB_ARPADMIN) {
    echo '<script type="text/javascript" src="resources/scripts/arp.js"></script>';
}
/* END TAB ARP JS *****************************************************************************************************/



$this->data['head'] .=  <<<JAVASCRIPT_TAB_USERDATA

<script type="text/javascript">
$(document).ready(function() {
var selectedTab = $('#tab-' + '{$this->data['selectedtab']}');
    $("#tabdiv").tabs({
        active : selectedTab.index()
    });
});

var selectedSubTabName = '{$this->data['selectedSubTab']}';

function initSubTabs(subTabContainer) {
    var subTabConfig = {};
    if (selectedSubTabName) {
        var selectedSubTab = $('#tab-' + selectedSubTabName);
        subTabConfig.active = selectedSubTab.index();
    }
    subTabContainer.tabs(subTabConfig);
}
</script>
JAVASCRIPT_TAB_USERDATA;



if ($this->data['selectedtab'] == SELECTED_TAB_USERDATA
    || $this->data['selectedtab'] == SELECTED_TAB_ADMIN) {
// This should be put into a asyncronous call instead
$usertypes = $janus_config->getValue('usertypes');

$select_type = '<select name="type">';
$select_type .= '<option>-- Select --</option>';
foreach($usertypes as $user_type) {
    $select_type .= '<option value="'.$user_type.'">'.$user_type.'</option>';
}
$select_type .= '</select>';
}


/* START TAB ADMIN JS *************************************************************************************************/
if ($this->data['selectedtab'] == SELECTED_TAB_ADMIN) {
    if (!$this->data['selectedSubTab']) {
        $pageJs[] =  <<<JAVASCRIPT_TAB_ADMIN
    initSubTabs($("#admin_tabdiv"));
JAVASCRIPT_TAB_ADMIN;
}


/* START TAB ADMIN ENTITIES JS ****************************************************************************************/
if ($this->data['selectedSubTab'] == SELECTED_SUBTAB_ADMIN_ENTITIES) {

// Build list of translations for js
$this->data['translations']['admin_save'] = $this->t('admin_save');
$this->data['translations']['text_delete_user'] = $this->t('text_delete_user');

$pageJs[] = <<<JAVASCRIPT_TAB_ADMIN_ENTITIES
$(document).ready(function() {
    // Remove user function
    $("select.remove-user").change(function () {
        $.post(
            "AJAXRequestHandler.php",
            {
                func: "removeUserFromEntity",
                uid: $(this).val(),
                eid: this.id.substr(12)
            },
            function(data) {
                $("#entityuser-" + data.eid + "-" + data.uid).remove();
                $("select#remove-user-" + data.eid).hide();
            },
            "json"
        );
    });

    // Add user function
    $("select.add-user").change(function () {
        $.post(
            "AJAXRequestHandler.php",
            {
                func: "addUserToEntity",
                uid: $(this).val(),
                eid: this.id.substr(9)
            },
            function(data) {
                $("tr#entity-" + data.eid + " > td.users").append("<span id=\"entityuser-" + data.eid + "-" + data.uid + "\">" + data.userid + ", </span>");
                $("select#add-user-" + data.eid).hide();
            },
            "json"
        );
    });
});
JAVASCRIPT_TAB_ADMIN_ENTITIES;
}
/* END TAB ADMIN ENTITIES JS ******************************************************************************************/



/* START TAB ADMIN USERS JS *******************************************************************************************/
if ($this->data['selectedSubTab'] == SELECTED_SUBTAB_ADMIN_USERS) {
// Build list of translations for js
$this->data['translations']['admin_save'] = $this->t('admin_save');
$this->data['translations']['text_delete_user'] = $this->t('text_delete_user');

$pageJs[] = <<<JAVASCRIPT_TAB_ADMIN_USERS
$("#admin_add_user_link").click(function () {
      $("#admin_add_user").toggle("slow");
});

function editUser(uid) {
    tr_editUser = $("#delete-user-" + uid);
    td_type = tr_editUser.children("[name='type']");
    td_userid = tr_editUser.children("[name='userid']");
    td_active = tr_editUser.children("[name='active']");
    td_action = tr_editUser.children("[name='action']");
    a_edit = td_action.children("[name='admin_edit']");
    a_delete = td_action.children("[name='admin_delete']");

    if (td_active.text() == "yes") {
        checkbox_active = "<input type=\"checkbox\" name=\"active\" checked=\"checked\" />";
    } else {
        checkbox_active = "<input type=\"checkbox\" name=\"active\" />";
    }

    // Add change event to selct to add types to list
    td_type.append($('{$select_type}').change(function() {
        tmp = $("<span class=\"usertype\">" + $(this).val() + " <b style=\"color: red;\">x</b>, </span>");
        $(this).before(tmp);
        $(this).children("option:selected").remove();
        // Add event to enable remove of types
        tmp.click(function() {
            $(this).remove();
            $("[name=\"type\"]").append("<option value=\"" + $(this).html().slice(0, -11) + "\">" + $(this).html().slice(0, -11) + "</option>");
        });
        tmp.hover(function() {
            $(this).css("cursor", "pointer");
        });
    }));

    td_type.find("select").attr("id", "edit-select-" + uid);

    // Remove already present type from select
    td_type.children(".usertype").each(function() {
        $("[name=\"type\"]").children("[value=\"" + $(this).text().slice(0, -2) + "\"]").remove();
    });

    // Add event to enable remove of types
    $(td_type).children(".usertype").each(function() {
        $(this).html($(this).html().slice(0, -2) + " <b style=\"color: red;\">x</b>, ");
        $(this).click(function(event) {
            $(this).remove();
            $("[name=\"type\"]").append("<option value=\"" + $(this).html().slice(0, -11) + "\">" + $(this).html().slice(0, -11) + "</option>");
        });
        $(this).hover(function() {
            $(this).css("cursor", "pointer");
        });
    });

    td_userid.html($('<input name="userid" />').val(td_userid.text()));

    a_edit.hide();
    $("<a name=\"admin_save\" class=\"janus_button\" onclick=\"saveUser("+uid+");\">{$this->data['translations']['admin_save']}</a>&nbsp;").insertBefore(a_delete);
    td_active.html($(checkbox_active));
}

function saveUser(uid) {
    tr_editUser = $("#delete-user-" + uid);

    type = tr_editUser.children("[name='type']");

    // Get selcected types
    var types = new Array();
    type.children(".usertype").each(function() {
        $(this).text(
            $(this).text().slice(0, -4) + ", "
        );
        types.push($(this).text().slice(0, -2));
    });

    userid_input = tr_editUser.children("[name='userid']").children("[name='userid']");
    userid = userid_input.val();
    active = tr_editUser.children("[name='active']").children("[name='active']")[0].checked;

    if(active == true) {
        active = "yes";
    } else {
        active = "no";
    }

    $.post(
        "AJAXRequestHandler.php",
        {
            func: "editUser",
            uid: uid,
            'type[]' : types,
            userid: userid,
            active: active
        },
        function(data){
            if(data.status == "success") {
                td_action = tr_editUser.children("[name='action']");
                td_action.children("[name='admin_edit']").show();
                td_action.children("[name='admin_save']").remove();
                $("#edit-select-" + data.uid).remove();
                tr_editUser.children("[name='userid']").html(userid);
                tr_editUser.children("[name='active']").html(active);
            } else {
                userid_input = tr_editUser.children("[name='userid']").children("[name='userid']");
                userid_input.focus();
                userid_input.css("background-color", "#E94426");
            }
        },
        "json"
    );
}
JAVASCRIPT_TAB_ADMIN_USERS;
}
/* END TAB ADMIN USERS JS *********************************************************************************************/



/* START TAB ADMIN ENTITIES JS ****************************************************************************************/
if ($this->data['selectedSubTab'] == SELECTED_SUBTAB_ADMIN_ENTITIES) {
    $this->data['translations']['admin_select_remove_user'] = $this->t('admin_select_remove_user');
    $this->data['translations']['admin_select_add_user'] = $this->t('admin_select_add_user');

    $pageJs[] = <<<JAVASCRIPT_TAB_ADMIN_ENTITIES
    function getEntityUsers(eid) {
    if($("select#remove-user-" + eid).is(":visible")) {
        $("select#remove-user-" + eid).hide();
    } else {
        $("select#add-user-" + eid).hide();
        $.post(
            "AJAXRequestHandler.php",
            {
                func: "getEntityUsers",
                eid: eid
            },
            function(data){
                if(data.status == "success") {
                    var options = "<option value=\"0\">-- {$this->data['translations']['admin_select_remove_user']} --</option>";
                    for (var i = 0; i < data.data.length; i++) {
                        options += "<option value=\"" + data.data[i].optionValue + "\">" + data.data[i].optionDisplay + "</option>";
                    }
                    $("select#remove-user-" + eid).html(options);
                    $("select#remove-user-" + eid).show();
                } else {
                    $("select#remove-user-" + eid).hide();
                }
            },
            "json"
        );
    }
}

function getNonEntityUsers(eid) {
    if($("select#add-user-" + eid).is(":visible")) {
        $("select#add-user-" + eid).hide();
    } else {
        $("select#remove-user-" + eid).hide();
        $.post(
            "AJAXRequestHandler.php",
            {
                func: "getNonEntityUsers",
                eid: eid
            },
            function(data){
                if(data.status == "success") {
                    var options = "<option value=\"0\">-- {$this->data['translations']['admin_select_add_user']} --</option>";
                    for (var i = 0; i < data.data.length; i++) {
                        options += "<option value=\"" + data.data[i].optionValue + "\">" + data.data[i].optionDisplay + "</option>";
                    }
                    $("select#add-user-" + eid).html(options);
                    $("select#add-user-" + eid).show();
                } else {
                    $("select#add-user-" + eid).hide();
                }
            },
            "json"
        );
    }
}
JAVASCRIPT_TAB_ADMIN_ENTITIES;
}
/* END TAB ADMIN ENTITIES JS ******************************************************************************************/



/* START TAB ADMIN USERS JS *******************************************************************************************/
if ($this->data['selectedSubTab'] == SELECTED_SUBTAB_ADMIN_USERS) {
    $pageJs[] = <<<JAVASCRIPT_TAB_ADMIN_USERS
function deleteUser(uid, userid) {
    if(confirm("{$this->data['translations']['text_delete_user']}: " + userid)) {
        $.post(
            "AJAXRequestHandler.php",
            {
                func: "deleteUser",
                uid: uid
            },
            function(data){
                if(data.status == "success") {
                    $("#delete-user-" + uid).hide();
                }
            },
            "json"
        );
    }
}
JAVASCRIPT_TAB_ADMIN_USERS;
}
/* END TAB ADMIN USERS JS *********************************************************************************************/


}
/* END TAB ADMIN JS ***************************************************************************************************/




/* START TAB MESSAGE JS ***********************************************************************************************/
if ($this->data['selectedtab'] == SELECTED_TAB_MESSAGE) {
    $this->data['translations']['admin_edit'] = $this->t('admin_edit');
    $this->data['translations']['admin_delete'] = $this->t('admin_delete');

    if (!$this->data['selectedSubTab']) {
        $pageJs[] = <<<JAVASCRIPT_TAB_MESSAGE
    initSubTabs($("#message_tabdiv"));
JAVASCRIPT_TAB_MESSAGE;
    }



/* START SUBTAB MESSAGE SUBSCRIPTIONS JS ******************************************************************************/
if ($this->data['selectedSubTab'] == SELECTED_SUBTAB_MESSAGE_SUBSCRIPTIONS) {
    $pageJs[] = <<<JAVASCRIPT_SUBTAB_MESSAGE_SUBSCRIPTIONS
function addSubscription(uid, subscription) {
    $.post(
        "AJAXRequestHandler.php",
        {
            func: "addSubscription",
            uid: uid,
            subscription: subscription
        },
        function(data) {
            if(data.status == "success") {
                var text = $("select#subscriptions_select option:selected").text();
                $("#subscription_list").append("<tr id=\"subscription_list_" + data.sid + "\"><td style=\"padding: 3px;\">" + text + "</td><td id=\"subscription_type_"+data.sid+"\">INBOX</td></tr>");

                $("#subscription_list_"+data.sid).append("<td><a class=\"janus_button\" onclick=\"deleteSubscription("+uid+", "+data.sid+");\">{$this->data['translations']['admin_delete']}</a></td>");

                $("#subscription_list_"+data.sid+" td:last-child").append("  <a id=\"edit_subscription_link_"+data.sid+"\" class=\"janus_button\" onclick=\"editSubscription("+uid+", "+data.sid+");\">{$this->data['translations']['admin_edit']}</a>");

                $("tr[id^='subscription_list_']:even").addClass("even");
                $("tr[id^='subscription_list_']:odd").addClass("odd");
            }
        },
        "json"
    );
}

function updateSubscription(sid, uid, type) {
    $.post(
        "AJAXRequestHandler.php",
        {
            func: "updateSubscription",
            sid: sid,
            uid: uid,
            type: type
        },
        function(data) {
            if(data.status == "success") {
                $("#subscription_type_select_"+sid).replaceWith(type);
                $("#save_subscription_link_"+sid).replaceWith("<a id=\"edit_subscription_link_"+sid+"\" class=\"janus_button\" onclick=\"editSubscription("+uid+", "+sid+");\">Edit</a>");
            }
        },
        "json"
    );
}

function deleteSubscription(uid, sid) {
    $.post(
        "AJAXRequestHandler.php",
        {
            func: "deleteSubscription",
            uid: uid,
            sid: sid
        },
        function(data) {
            if(data.status == "success") {
                $("#subscription_list_" + sid).remove();
                $("#subscription_list tr:even").css("background-color", "#EEEEEE");
                $("#subscription_list tr:odd").css("background-color", "#FFFFFF");
            }
        },
        "json"
    );
}
JAVASCRIPT_SUBTAB_MESSAGE_SUBSCRIPTIONS;
}
/* END SUBTAB MESSAGE SUBSCRIPTIONS JS ********************************************************************************/



/* START SUBTAB MESSAGE INBOX JS **************************************************************************************/
elseif ($this->data['selectedSubTab'] == SELECTED_SUBTAB_MESSAGE_INBOX) {
    $pageJs[] = <<<JAVASCRIPT_SUBTAB_MESSAGE_INBOX
function renderMessageList(uid, page) {
    $.post(
        "AJAXRequestHandler.php",
        {
            func: "getMessageList",
            uid: uid,
            page: page
        },
        function(data) {
            if(data.status == "success") {
                $("#message-list").html(data.data);
                $(".paginator a").removeClass("selected");
                $(".paginator a.pagelink"+data.page).addClass("selected");
            }
        },
        "json"
    );
}

function openMessage(mid) {
    if($("#message-"+mid).is(":visible")) {
        $("#message-"+mid).hide();
    } else {
        $.post(
            "AJAXRequestHandler.php",
            {
                func: "getMessage",
                mid: mid
            },
            function(data) {
                if(data.status == "success") {
                    $("#message-"+mid).html(data.data);
                    $("#message-"+mid).prepend("<b>To: "+data.address+"</b><br /><br />");
                    $("#message-"+mid).prepend("<b>From: "+data.from+"</b><br />");
                    $("#message-"+mid).show();
                    markRead(mid);
                }
            },
            "json"
        );
    }
}

function markRead(mid) {
    var success = false;
    $.post(
        "AJAXRequestHandler.php",
        {
            func: "markAsRead",
            mid: mid
        },
        function(data) {
            if(data.status == "success") {
                $("#message-title-"+mid).css("font-weight", "normal");
            }
        },
        "json"
    );
}

function markAsRead() {
    $("#message-list input:checkbox:checked").each(
        function(index) {
            mid = $(this).val();
            mid = mid.substr(11,mid.length);
            markRead(mid);
        }
    );
}
JAVASCRIPT_SUBTAB_MESSAGE_INBOX;
}
/* END SUBTAB MESSAGE INBOX JS ****************************************************************************************/


}
/* END TAB MESSAGE JS *************************************************************************************************/



/* START TAB ADMIN ENTITIES JS ****************************************************************************************/
if ($this->data['selectedtab'] == SELECTED_TAB_ADMIN) {
    if ($this->data['selectedSubTab'] == SELECTED_SUBTAB_ADMIN_ENTITIES) {
$this->data['translations']['text_disable_entity'] = $this->t('text_disable_entity');
$this->data['translations']['text_enable_entity'] = $this->t('text_enable_entity');
$this->data['translations']['text_delete_entity'] = $this->t('text_delete_entity');
$pageJs[] = <<<JAVASCRIPT_SUBTAB_ADMIN_ENTITIES
function disableEntity(eid, entityid) {
    if(confirm("{$this->data['translations']['text_disable_entity']}: " + entityid)) {
        $.post(
            "AJAXRequestHandler.php",
            {
                func: "disableEntity",
                eid: eid
            },
            function(data) {
                if(data.status == "success") {
                    $("#entity-" + eid).css("background-color", "#A9D0F5");
                    $("#entity-" + eid + " .disable_button").text("Enable");
                    $("#entity-" + eid + " .disable_button").attr("onclick", "") 
                    $("#entity-" + eid + " .disable_button").unbind("click"); 
                    $("#entity-" + eid + " .disable_button").click(function () {
                        enableEntity(eid, entityid);
                    });
                }
            },
            "json"
        );
    }
}

function enableEntity(eid, entityid) {
    if(confirm("{$this->data['translations']['text_enable_entity']}: " + entityid)) {
        $.post(
            "AJAXRequestHandler.php",
            {
                func: "enableEntity",
                eid: eid
            },
            function(data) {
                if(data.status == "success") {
                    $("#entity-" + eid).css("background-color", "");
                    $("#entity-" + eid + " .disable_button").text("Disable");
                    $("#entity-" + eid + " .disable_button").attr("onclick", "") 
                    $("#entity-" + eid + " .disable_button").unbind("click"); 
                    $("#entity-" + eid + " .disable_button").click(function () {
                        disableEntity(eid, entityid);
                    });
                }
            },
            "json"
        );
    }
}

function deleteEntity(eid, entityid) {
    if(confirm("{$this->data['translations']['text_delete_entity']}: " + entityid)) {
        $.post(
            "AJAXRequestHandler.php",
            {
                func: "deleteEntity",
                eid: eid
            },
            function(data) {
                if(data.status == "success") {
                    $("#entity-" + eid).hide();
                    $("#list-" + eid).hide();
                }
            },
            "json"
        );
    }
}

// Keyboard shortcut for search
var isCtrl = false;

$(document).keyup(function (e) {
    if(e.which == 17) isCtrl=false;
}).keydown(function (e) {
    if(e.which == 17) isCtrl=true;
    if(e.which == 83 && isCtrl == true) {
        $("#search").toggle("fast");
        $("#search input[name='q']").focus();
        return false;
    }
    if(e.which == 67 && isCtrl == true) {
        $("#options").toggle("fast");
        $("#options input[name='entityid']").focus();
        return false;
    }
});
JAVASCRIPT_SUBTAB_ADMIN_ENTITIES;
}
/* END TAB ADMIN ENTITIES JS ******************************************************************************************/



}
/* END TAB ADMIN JS ***************************************************************************************************/


if (!IS_AJAX) {
    $this->includeAtTemplateBase('includes/header.php');
}
$util = new sspmod_janus_AdminUtil();


if (!IS_AJAX) {

// @todo: improve this workaround and make the form reload the ajax tab
// Build urls for tabs with search and pass optional searchparameters
$entitiesUrl = DASHBOARD_URL . '/' . TAB_AJAX_CONTENT_PREFIX .'entities';
$arpAdminUrl = DASHBOARD_URL . '/' . TAB_AJAX_CONTENT_PREFIX .'arpAdmin';
if (!empty($_GET)) {
    switch($this->data['selectedtab']) {
        case SELECTED_TAB_ENTITIES :
            $entitiesUrl .= '?' . http_build_query($_GET);
            break;
        case SELECTED_TAB_ARPADMIN :
            $arpAdminUrl .= '?' . http_build_query($_GET);
            break;
    }
}
?>

<div id="tabdiv">
<h1><?php echo $this->t('text_dashboard').' for '. $this->data['user']->getUserid(); ?></h1>
<!-- TABS -->
<ul>
    <li id="tab-userdata"><a href="<?php echo DASHBOARD_URL . '/' . TAB_AJAX_CONTENT_PREFIX;?>userdata"><?php echo $this->t('tab_user_data_header'); ?></a></li>
    <li id="tab-entities"><a href="<?php echo $entitiesUrl?>"><?php echo $this->t('tab_entities_header'); ?></a></li>
    <?php
    if($this->data['uiguard']->hasPermission('arpeditor', null, $this->data['user']->getType(), TRUE)) {
        echo '<li id="tab-arpAdmin"><a href="' . $arpAdminUrl . '">' . $this->t('tab_arpedit_header') . '</a></li>';
    }
    ?>
    <li id="tab-message"><a href="<?php echo DASHBOARD_URL . '/' . TAB_AJAX_CONTENT_PREFIX;?>message"><?php echo $this->t('tab_message_header'); ?></a></li>
    <?php
    if($this->data['uiguard']->hasPermission('admintab', null, $this->data['user']->getType(), TRUE)) {
        echo '<li id="tab-admin"><a href="' . DASHBOARD_URL . '/' . TAB_AJAX_CONTENT_PREFIX . 'admin">', $this->t('tab_admin_header'), '</a></li>';
    }
    if($this->data['uiguard']->hasPermission('federationtab', null, $this->data['user']->getType(), TRUE)) {
        echo '<li id="tab-federation"><a href="' . DASHBOARD_URL . '/' . TAB_AJAX_CONTENT_PREFIX . 'federation">', $this->t('tab_federation_header'), '</a></li>';
    }
    ?>
</ul>
<!-- TABS END -->
<?php
} else {
    // Error messages
    if(isset($this->data['msg']) && substr($this->data['msg'], 0, 5) === 'error') {
        echo '<table class="frontpagebox" style="margin-left: 1.4em;"><tr><td>';
        echo '<div class="dashboard_error">'. $this->t('error_header').'</div>';
        echo '<p>'. $this->t($this->data['msg']) .'</p>';
        echo '</td></tr></table>';
    } else if(isset($this->data['msg'])) {
        echo '<table class="frontpagebox" style="margin-left: 1.4em;"><tr><td>';
        echo '<p>'. $this->t($this->data['msg']) .'</p>';
        echo '</td></tr></table>';
    }



/* START TAB ENTITIES *************************************************************************************************/
if ($this->data['selectedtab'] == SELECTED_TAB_ENTITIES) {
?>
<!-- TABS - ENTITIES -->
<div id="entities">
    <?php
    $enablematrix = $util->getAllowedTypes();

    if($this->data['uiguard']->hasPermission('createnewentity', null, $this->data['user']->getType(), TRUE)) {
    ?>
    <a class="janus_button" onclick="$('#options').toggle('fast');  $('#options input[name=\'entityid\']').focus();"><?php echo $this->t('text_entities_create'); ?></a>
    <form method="post" action="<?php echo FORM_ACTION_URL;?>">
        <table border="0" id="options" class="frontpagebox" <?php if (!isset($this->data['msg'])) echo 'style="display: none;"'; ?>>
            <tr>
                <td>
                    <input type="hidden" name="userid" value="<?php echo $this->data['userid']; ?>" />
                    <?php echo $this->t('tab_entities_new_entity_text'); ?>:
                </td>
                <td>
                    <?php
                    if (isset($this->data['old_entityid'])) {
                        echo '<input type="text" size="40" name="entityid" value="'. $this->data['old_entityid'] .'" />';
                    } else {
                        echo '<input type="text" size="40" name="entityid" />';
                    }
                    ?>
                </td>
                <td>
                    <?php
                    echo '<select name="entitytype">';
                    echo '<option value="">' . $this->t('text_select_type') . '</option>';
                    foreach ($enablematrix AS $typeid => $typedata) {
                        if ($typedata['enable'] === true) {
                            if (isset($this->data['old_entitytype']) && $this->data['old_entitytype'] == $typeid) {
                                echo '<option value="'. $typeid .'" selected="selected">'. $typedata['name'] .'</option>';
                            } else {
                                echo '<option value="'. $typeid .'">'. $typedata['name'] .'</option>';
                            }
                        }
                    }
                    echo '</select>';
                    ?>
                </td>
                <td>
                    <input class="janus_button" type="submit" name="submit" value="<?php echo $this->t('text_submit_button'); ?>" />
                </td>
            </tr>
            <tr>
                <td style="vertical-align: top;">Create entity from XML</td>
                <td colspan="2">
                    <textarea name="metadata_xml" cols="60" rows="5" onfocus="this.value = '';">Put your XML here...</textarea>
                </td>
                <td></td>
                <td></td>
            </tr>
        </table>
    </form>
    <?php
        }
    ?>
    <br />
    <?php
    // If we are not currently searching for something and the default view shows less then 50 entities,
    // hide the search form behind a button
    if (!$this->data['is_searching'] && count($this->data['entities']) < 50): ?>
    <a class="janus_button" onclick="$('#search').toggle('fast'); $('#search input[name=\'q\']').focus();"><?php echo $this->t('text_entities_search'); ?></a>
    <?php endif; ?>
    <form method="get" action="<?php echo FORM_ACTION_URL;?>">
    <table id="search"
           class="frontpagebox"
           style="display: <?php
           // If we are searching or the number of entities shown is more or equal to 50, show the search form.
           echo ($this->data['is_searching'] || count($this->data['entities']) >= 50) ? 'block' : 'none'; ?>;">
        <tr>
            <td>Search:</td>
            <td><input type="text" name="q" value="<?php echo $this->data['query']; ?>" /></td>
            <td><input type="submit" value="<?php echo $this->t('text_entities_search'); ?>" name="submit_search" class="janus_button" /></td>
        </tr>
        <tr>
            <td colspan="3"><b><?php echo $this->t('text_entities_filter'); ?></b></td>
        </tr>
        <tr>
            <td><?php echo $this->t('text_entities_filter_state'); ?>:</td>
            <td>
                <select name="entity_filter">
                    <?php
                    $states = $janus_config->getArray('workflowstates');
                    echo '<option value="nofilter">' . $this->t('text_entities_filter_select') . '</option>';
                    foreach($states AS $key => $val) {
                        if($key == $this->data['entity_filter']) {
                            echo '<option value="' . $key . '" selected="selected">' . $val['name'][$this->getLanguage()] . '</option>';
                        } else  {
                            echo '<option value="' . $key . '">' . $val['name'][$this->getLanguage()] . '</option>';
                        }
                    }
                    ?>
                </select>
            </td>
            <td></td>
        </tr>
        <tr>
            <td><?php echo $this->t('text_entities_filter_state_exclude'); ?>:</td>
            <td>
                <select name="entity_filter_exclude">
                    <?php
                    $states = $janus_config->getArray('workflowstates');
                    echo '<option value="noexclude">-- Exclude</option>';
                    foreach($states AS $key => $val) {
                        if($key == $this->data['entity_filter_exclude']) {
                            echo '<option value="' . $key . '" selected="selected">' . $val['name'][$this->getLanguage()] . '</option>';
                        } else  {
                            echo '<option value="' . $key . '">' . $val['name'][$this->getLanguage()] . '</option>';
                        }
                    }
                    ?>
                </select>
            </td>
            <td></td>
        </tr>
        <tr>
            <td><?php echo $this->t('text_entities_filter_order'); ?>:</td>
            <td>
                <select name="sort">
                    <option value="name" <?php if ($this->data['sort'] == 'name') echo 'selected="selected"'; ?>><?php echo $this->t('text_entities_filter_sort_name'); ?></option>
                    <option value="created" <?php if ($this->data['sort'] == 'created') echo 'selected="selected"'; ?>><?php echo $this->t('text_entities_filter_sort_created'); ?></option>
                </select>
                <select name="order">
                    <option value="ASC" <?php if ($this->data['order'] == 'ASC') echo 'selected="selected"'; ?>><?php echo $this->t('text_entities_filter_order_asc'); ?></option>
                    <option value="DESC" <?php if ($this->data['order'] == 'DESC') echo 'selected="selected"'; ?>><?php echo $this->t('text_entities_filter_order_desc'); ?></option>
                </select>
            </td>
            <td></td>
        </tr>
    </table>
    </form>
    <br />
    <p><?php echo $this->t('text_entities_help'); ?></p>
<?php
$connections = array();

foreach($enablematrix AS $typeid => $typedata) {
    if($typedata['enable'] === true) {
        $connections[$typeid] = array();
    }
}
$count_types = count($connections);
foreach($this->data['entities'] AS $entity) {
    $connections[$entity->getType()][] = $entity;
}
$theader = '';
$tfooter = '';

// Create table showing accessible entities
$theader .= '<tr>';
$tfooter .= '<tr>';
foreach($connections AS $ckey => $cval) {
    $theader.= '<td class="connection_header" width="' . (int) 100/$count_types . '%"><b>' . $this->t('text_'.$ckey) . ' - ' . count($cval) . '</b></td>';

    $tfooter .= '<td valign="top" class="connection_footer">';
    $tfooter .= '<table class="connection">';
    $i = 0;
    foreach($cval AS $sp) {
        //Only show disabled entities if allentities permission is granted
        $states = $janus_config->getArray('workflowstates');
        $textColor = array_key_exists('textColor', $states[$sp->getWorkflow()]) ? $states[$sp->getWorkflow()]['textColor'] : 'black';
        $tfooter .= '<tr id="list-'.$sp->getEid().'">';
        $tfooter .= '<td class="'.($i % 2 == 0 ? 'even' : 'odd').'" ';
        if ($sp->getActive() == 'no') {
            $tfooter .= ' style="background-color: #A9D0F5;" ';
        }
        $tfooter .= '>';
        $tfooter .= '<a style="color:' . $textColor . '" title="' . $sp->getEntityid() . '" href="editentity.php?eid='.$sp->getEid() . '">'. htmlspecialchars($sp->getPrettyname()) . ' - r' . $sp->getRevisionid() . '</a></td>';
        $tfooter .= '</tr>';
        $i++;
    }
    $tfooter .= '</table>';
    $tfooter .= '</td>';
}
$theader .= '</tr>';
$tfooter .= '</tr>';

// Show the table
echo '<table cellpadding="30" class="dashboard_container">';
echo $theader;
echo $tfooter;
echo '</table>';
?>

</div>
<?php
}
/* END TAB ENTITIES ***************************************************************************************************/



/* START TAB FEDERATION ***********************************************************************************************/
elseif ($this->data['selectedtab'] == SELECTED_TAB_FEDERATION) {
?>
<!-- TAB - FEDERATION -->
<?php
if($this->data['uiguard']->hasPermission('federationtab', null, $this->data['user']->getType(), TRUE)) {
?>
    <div id="federation">
    <?php
    echo '<h2>'.$this->t('tab_entities_federation_entity_subheader').'</h2>';
    echo '<a href="metadataexport.php">'.$this->t('tab_entities_federation_exporting').'</a>';
    if($this->data['uiguard']->hasPermission('validatemetadata', null, $this->data['user']->getType(), TRUE)) {
        echo '<br /><a href="' . SimpleSAML_Module::getModuleURL('janus/show-entities-validation.php') . '">';
        echo $this->t('tab_entities_federation_status');
        echo '</a>';
    }
    if($this->data['uiguard']->hasPermission('experimental', null, $this->data['user']->getType(), TRUE)) {
        echo '<br /><a href="metalisting.php">'.$this->t('tab_entities_federation_status').'</a><br />';
    }
    ?>
    </div>
<?php
}
}
/* END TAB FEDRATION **************************************************************************************************/



/* START TAB ADMIN ****************************************************************************************************/
elseif ($this->data['selectedtab'] == SELECTED_TAB_ADMIN) {
?>

<!-- TAB - ADMIN -->
<?php
if($this->data['uiguard']->hasPermission('admintab', null, $this->data['user']->getType(), TRUE)) {

    if (!$this->data['selectedSubTab']) {
?>
        <div id="admin">
            <div id="admin_tabdiv">
                <ul>
                    <?php
                    if($this->data['uiguard']->hasPermission('adminusertab', null, $this->data['user']->getType(), TRUE)) {
                        echo '<li id="tab-admin-users"><a href="' . DASHBOARD_URL . '/' . TAB_AJAX_CONTENT_PREFIX . 'admin/users">' . $this->t('tab_admin_tab_users_header') . '</a></li>';
                    }
                    if($this->data['uiguard']->hasPermission('admintab', null, $this->data['user']->getType(), TRUE)) {
                        echo '<li id="tab-admin-entities"><a href="' . DASHBOARD_URL . '/' . TAB_AJAX_CONTENT_PREFIX . 'admin/entities">' . $this->t('tab_admin_tab_entities_header') . '</a></li>';
                    }
                    ?>
                </ul>
<?php
     }
    


/* START SUBTAB ADMIN USER ********************************************************************************************/
                if ($this->data['selectedSubTab'] == SELECTED_SUBTAB_ADMIN_USERS) {
                ?>
                <!-- ADMIN USER TAB  STARTE-->
                <?php
                if($this->data['uiguard']->hasPermission('adminusertab', null, $this->data['user']->getType(), TRUE)) {
                ?>
                <div id="admin_users">
                    <?php
                    $color = 'EEEEEE';
                    $users = $this->data['users'];
                    echo '<table class="dashboard_container">';
                    echo '<thead><tr><th>'. $this->t('admin_type') .'</th><th>'. $this->t('admin_userid') .'</th><th>'. $this->t('admin_active') .'</th><th align="center">'. $this->t('admin_action') .'</th></tr></thead>';
                    echo '<tbody>';
                    $i = 0;
                    foreach($users AS $user) {
                        echo '<tr id="delete-user-'. $user->getUid() .'" class="'. ($i % 2 == 0 ? 'even' : 'odd') .'" >';
                        $type = $user->getType();
                        echo '<td name="type" class="dashboard_user">';
                        foreach($type AS $t) {
                            echo '<span class="usertype">' . $t . ', </span>';
                        }
                        echo '</td>';
                        echo '<td name="userid" class="dashboard_user">', $user->getUserid(). '</td>';
                        echo '<td name="active" class="dashboard_user">', $user->getActive(). '</td>';
                        echo '<td name="action" class="dashboard_user" align="center">';
                        echo '<a name="admin_edit" class="janus_button" onclick="editUser(', $user->getUid(), ');">'. $this->t('admin_edit') .'</a>';
                        echo '  ';
                        echo '<a name="admin_delete" class="janus_button" onclick="deleteUser(', $user->getUid(), ', \'', $user->getUserid(), '\');">'. $this->t('admin_delete') .'</a>';
                        echo '</td>';
                        echo '</tr>';
                        $i++;
                    }
                    echo '</tbody>';
                    echo '</table>';
                    echo '<br /><a id="admin_add_user_link" class="janus_button">'.$this->t('admin_add_user').'</a>';
                    ?>
                    <div id="admin_add_user" class="display_none">
                        <form id="admin_add_user_form" method="post" action="<?php echo FORM_ACTION_URL;?>">
                            <table style="margin-top: 20px;">
                                <tr>
                                    <td><?php echo $this->t('admin_type'); ?>:</td>
                                    <td><?php echo $select_type; ?><?php echo $this->t('admin_active'); ?>: <input type="checkbox" name="active" checked="checked" /></td>
                                </tr>
                                <tr>
                                    <td><?php echo $this->t('admin_userid'); ?>:</td>
                                    <td><input type="text" name="userid" value="" size="20" /></td>
                                </tr>
                                <tr>
                                    <td><?php echo $this->t('tab_user_data_otherinfo');  ?>:</td>
                                    <td><textarea name="userdata" cols="100" rows="3"></textarea></td>
                                </tr>
                                <tr>
                                    <td colspan="2"><input type="submit" name="add_usersubmit" value="<?php echo $this->t('tab_edit_entity_save'); ?>" /></td>
                                </tr>
                            </table>
                        </form>
                    </div>
                </div>
                <!-- ADMIN USER TAB END-->
                <?php
                }
                }
/* END SUBTAB ADMIN USER **********************************************************************************************/



/* START SUBTAB ADMIN ENTITIES ****************************************************************************************/
                elseif ($this->data['selectedSubTab'] == SELECTED_SUBTAB_ADMIN_ENTITIES) {
                ?>
        <!-- ADMIN ENTITIES TAB START -->
        <div id="admin_entities">
            <script type="text/javascript">
                $(document).ready(function() {
                    var entities = $('#admin_entities_table tr[id^="entity-"]'),
                        len = $(entities).length,
                        entities_search = Array(),
                        tmp;

                    // Get searchable content
                    for(var x = 0; x < len; x++){
                        entities_search[x] = $(entities[x]).find('td:eq(0)').html();
                        entities_search[x] = entities_search[x].concat(" ", $(entities[x]).find('td:eq(1)').html());
                        tmp = $(entities[x]).find('td:eq(2) span');
                        for(var y = 0; y < $(tmp).length; y++) {
                            entities_search[x] = entities_search[x].concat(" ", $(tmp[y]).html());
                        }
                    }

                    $('#admin_entities_search').keyup(function() {
                        var patt1 = new RegExp($(this).val());
                        for(var x = 0; x < len; x++){
                            if(patt1.test(entities_search[x])) {
                                $(entities[x]).show();
                            } else {
                                $(entities[x]).hide();
                            }
                        }
                    })
                });
            </script>
            <span><?php echo $this->t('text_entities_search'); ?>: </span><input type="text" id="admin_entities_search" />
            <br /><br />
            <?php
            $entities = $this->data['adminentities'];

            echo '<table class="dashboard_container2" style="border-collapse: collapse;" id="admin_entities_table">';
            echo '<thead><tr><th colspan="2">'. $this->t('tab_admin_tab_entities_header') .'</th><th>'. $this->t('admin_users') .'</th><th width=" 230px" align="center">'. $this->t('admin_permission') .'</th><th>' . $this->t('admin_action') . '</th></tr></thead>';
            echo '<tbody>';
            $i = 0;
            foreach($entities AS $entity) {
                echo '<tr id="entity-'. $entity->getEid() .'" class="'. ($i % 2 == 0 ? 'even' : 'odd') .'"';
                if ($entity->getActive() == 'no') {
                    echo ' style="background-color: #A9D0F5;" ';
                }
                echo '>';
                $entity_users = $util->hasAccess($entity->getEid());
                if ($entity->getPrettyname() !== $entity->getEntityid()) {
                    echo '<td class="dashboard_entity">', htmlspecialchars($entity->getPrettyname()) , '</td>';
                    echo '<td class="dashboard_entity">', $entity->getEntityid() , '</td>';
                } else {
                    echo '<td class="dashboard_entity" colspan="2">', htmlspecialchars($entity->getPrettyname()) , '</td>';
                }
                echo '<td class="dashboard_entity users">';
                foreach($entity_users AS $entity_user) {
                    echo '<span id="entityuser-', $entity->getEid(),'-', $entity_user['uid'],'">',$entity_user['userid'], ', </span>';
                }
                echo '</td>';
                echo '<td class="dashboard_entity" align="center">';
                echo '<a class="janus_button" onclick="getNonEntityUsers(\'', $entity->getEid(), '\');">'. $this->t('admin_add') .'</a>';
                echo '<a class="janus_button" onclick="getEntityUsers(\'', $entity->getEid(), '\');">'. $this->t('admin_remove') .'</a>';
                echo '<select class="add-user display_none" id="add-user-' .$entity->getEid(). '"><option>VOID</option></select>';
                echo '<select class="remove-user display_none" id="remove-user-' .$entity->getEid(). '"><option>VOID</option></select>';
                echo '</td>';
                echo '<td>';
                echo '<a class="janus_button" onclick="deleteEntity(\'', str_replace(array(':', '.', '#'), array('\\\\:', '\\\\.', '\\\\#'), $entity->getEid()), '\', \'' . $entity->getEntityid() . '\');">'. $this->t('admin_delete') .'</a>';
                if ($entity->getActive() == 'no') {
                    echo '<a class="janus_button disable_button" onclick="enableEntity(\'', str_replace(array(':', '.', '#'), array('\\\\:', '\\\\.', '\\\\#'), $entity->getEid()), '\', \'' . $entity->getEntityid() . '\');">' . $this->t('admin_enable') . '</a>';
                } else {
                    echo '<a class="janus_button disable_button" onclick="disableEntity(\'', str_replace(array(':', '.', '#'), array('\\\\:', '\\\\.', '\\\\#'), $entity->getEid()), '\', \'' . $entity->getEntityid() . '\');">' . $this->t('admin_disable') . '</a>';
                }
                echo '</td>';
                echo '</tr>';
                $i++;
            }
            echo '</tbody>';
            echo '</table>';
        ?>
        </div>
        <!-- ADMIN ENTITIES TAB END -->
        <?php
    }
/* END SUBTAB ADMIN ENTITIES ******************************************************************************************/



    if (!$this->data['selectedSubTab']) {
    ?>
    </div>
</div>
<?php
}
}
?>

<!-- TABS END - ADMIN -->

<?php
}
/* END TAB ADMIN ******************************************************************************************************/



/* START TAB USERDATA *************************************************************************************************/
elseif ($this->data['selectedtab'] == SELECTED_TAB_USERDATA) {
?>

<!-- TABS - USERDATA -->
<div id="userdata">
    <form method="post" action="<?php echo FORM_ACTION_URL;?>">
        <h2><?php echo $this->t('tab_user_data_subheader');  ?></h2>
        <p><?php echo $this->t('tab_user_data_username');  ?>: <?php echo $this->data['user']->getUserid(); ?></p>
        <p><?php echo $this->t('tab_user_data_email');  ?>: <input type="text" name="user_email" value="<?php echo $this->data['user']->getEmail(); ?>" /></p>
        <p><?php echo $this->t('tab_user_data_secret'); ?>: <input type="text" name="user_secret" value="<?php echo $this->data['user']->getSecret(); ?>" size="50"/></p>
        <p><?php echo $this->t('tab_user_data_otherinfo');  ?>:</p>
        <textarea name="userdata" cols="100" rows="10"><?php echo $this->data['user']->getData(); ?></textarea>
        <input type="submit" name="usersubmit" value="<?php echo $this->t('tab_edit_entity_save'); ?>" />
    </form>
</div>
<!-- TABS END - USERDATE -->

<?php
}
/* END TAB USERDATA ***************************************************************************************************/



/* START TAB MESSAGES *************************************************************************************************/
elseif ($this->data['selectedtab'] == SELECTED_TAB_MESSAGE) {
?>

<!-- TABS - MESSAGES -->
<?php
function renderPaginator($uid, $currentpage, $lastpage) {
    if($lastpage < 1) {
        $lastpage = 1;
    }
    foreach(range(1, $lastpage) as $page) {
        echo '<a class="pagelink'. $page;
        if($page == $currentpage) {
            echo ' selected';
        }
        echo '" onclick="renderMessageList('. $uid .','. $page .');">'. $page .'</a>';
        if($page%30 == 0) {
            echo '<br />';
        }
    }
}

if (empty($this->data['selectedSubTab'])) {
?>
<div id="message">
    <div id="message_tabdiv">
        <ul>
        <li id="tab-message-inbox"><a href="<?php echo DASHBOARD_URL . '/' . TAB_AJAX_CONTENT_PREFIX;?>message/inbox"><?php echo $this->t('tab_message_header'); ?></a></li>
            <?php
            if($this->data['uiguard']->hasPermission('showsubscriptions', null, $this->data['user']->getType(), TRUE)) {
                echo '<li id="tab-message-subscriptions"><a href="' . DASHBOARD_URL . '/' . TAB_AJAX_CONTENT_PREFIX . 'message/subscriptions">' . $this->t('tab_subscription_header') . '</a></li>';
            }
            ?>
        </ul>
        <?php
}


/* START SUBTAB MESSAGES INBOX ****************************************************************************************/
        if ($this->data['selectedSubTab']  == SELECTED_SUBTAB_MESSAGE_INBOX) {
        ?>
        <!-- START - INBOX SUBTAB -->
        <div id="inbox">
            <script type="text/javascript">
                $(document).ready(function() {
                    $('#select_all_messages').click(function() {
                        if (!this.checked) {
                            $('#message-list input:checkbox').prop('checked', true);
                            this.checked = true;
                        } else {
                            $('#message-list input:checkbox').prop('checked', false);
                            this.checked = false;
                        }
                    });

                    $('.dashboard_inbox, .dashboard_inbox_message_desc').hover(
                        function() {
                            $(this).css('background-color', '#F0F0F0');
                        },
                        function() {
                            $(this).css('background-color', '#FFFFFF');
                        }
                    );
                });
            </script>
            <div id="inbox_menu">
            <a id="select_all_messages" class="janus_button"><?php echo $this->t('text_message_select_all'); ?></a>
            <a id="messages_mark_as_read" class="janus_button" onclick="markAsRead();"><?php echo $this->t('text_message_mark_read'); ?></a>
            </div>
            <div class="paginator"><?php renderPaginator($this->data['user']->getUid(), $this->data['current_page'], $this->data['last_page']); ?></div>
            <div id="message-list">
            <?php
            if (empty($this->data['messages'])) {
                echo "Empty";
            } else {
                foreach($this->data['messages'] AS $message) {
                    echo '<div class="dashboard_inbox" onclick="openMessage('. $message['mid'] .')">';
                    echo '<input type="checkbox" name="message_cb[]" value="message_cb-'. $message['mid'] .'" />';
                    $messageRead = ($message['read'] == 'no') ? 'class="dashboard_inbox_unread_message"' : '';
                    echo ' <a id="message-title-'. $message['mid'] .'" '. $messageRead . '>'. date("d/n-Y H:i:s", strtotime($message['created'])) .' - '. $message['subject'] .'</a>';
                    echo '</div>';
                    echo '<div id="message-'. $message['mid'] .'" class="dashboard_inbox_message_desc"></div>';
                }
            }
            ?>
            </div>
            <div class="paginator"><?php renderPaginator($this->data['user']->getUid(), $this->data['current_page'], $this->data['last_page']); ?></div>
        </div>
        <!-- END - INBOX SUBTAB -->
        <?php
        }
/* END SUBTAB MESSAGES INBOX ******************************************************************************************/



/* START SUBTAB MESSAGES SUBSCRIPTIONS*********************************************************************************/
        else if ($this->data['selectedSubTab']  == SELECTED_SUBTAB_MESSAGE_SUBSCRIPTIONS) {
        ?>
        <!-- START - SUBSCRIPTION SUBTAB -->
        <?php
        if($this->data['uiguard']->hasPermission('showsubscriptions', null, $this->data['user']->getType(), TRUE)) {
        ?>
        <div id="subscriptions">
            <script type="text/javascript">
                $("tr[id^='subscription_list_']:even").addClass("even");
                $("tr[id^='subscription_list_']:odd").addClass("odd");

                function editSubscription(uid, sid) {
                    <?php
                    $select_types = '<option value="INBOX">Inbox</option>';
                    foreach($this->data['external_messengers'] as $kmessenger => $vmessenger) {
                        $select_types .= '<option value="'.$kmessenger.'">'.$vmessenger['name'].'</option>';
                    }
                    ?>
                    type = $("#subscription_type_"+sid).text();
                    $("#subscription_type_"+sid).html('<select id="subscription_type_select_'+sid+'"><?php echo $select_types; ?></select>');
                    $("#subscription_type_select_"+sid+' option[value="'+type+'"]').attr("selected", "selected");

                    $("#edit_subscription_link_"+sid).replaceWith("<a id=\"save_subscription_link_"+sid+"\" class=\"janus_button\" onclick=\"saveSubscription("+sid+", "+uid+");\"><?= $this->t('admin_save') ?></a>");
                }

                function saveSubscription(sid, uid) {
                    type = $("#subscription_type_select_"+sid+" option:selected").val();
                    updateSubscription(sid, uid, type);
                }
            </script>
            <?php
            echo '<table class="dashboard_container" id="subscription_list" style="border-collapse: collapse; width: 100%;">';
            echo '<thead><tr>';
            echo '<th>' . $this->t('tab_subscription_header') . '</th>';
            echo '<th>' . $this->t('admin_type') . '</th>';
            echo '<th>' . $this->t('admin_action') . '</th>';
            echo '</tr></thead><tbody>';
            foreach($this->data['subscriptions'] AS $subscription) {
                $tmp = explode("-", $subscription['subscription']);
                if($tmp[0] == 'USER') {
                    if(isset($tmp[1]) && ctype_digit((string) $tmp[1])) {
                        $user = new sspmod_janus_User($janus_config);
                        $user->setUid($tmp[1]);
                        $user->load();
                        $name = $tmp[0] . ' - ' .$user->getUserid();
                    } else if($tmp[1] == 'NEW'){
                        $name = $tmp[0] . ' - ' . 'NEW';
                    } else {
                        $name = $tmp[0];
                    }
                } else if($tmp[0] == 'ENTITYUPDATE') {
                    if(ctype_digit((string) $tmp[1])) {
                        $entity = new sspmod_janus_Entity($janus_config);
                        $entity->setEid($tmp[1]);
                        $entity->load();
                        $name = $tmp[0] . ' - ' . $entity->getEntityid();
                    } else {
                        $name = implode('-', $tmp);
                    }
                } else {
                    $name = implode('-', $tmp);
                }
                echo '<tr id="subscription_list_' . $subscription['sid'] . '">';
                echo '<td style="padding: 3px;">' . $name . '</td>';
                echo '<td id="subscription_type_' . $subscription['sid'] . '">' . $subscription['type'] . '</td>';
                echo '<td>';
                if($this->data['uiguard']->hasPermission('deletesubscriptions', null, $this->data['user']->getType(), TRUE)) {
                    echo '<a class="janus_button" onclick="deleteSubscription(' . $this->data['user']->getUid() . ', ' . $subscription['sid'] . ');">' . $this->t('admin_delete') . '</a>';
                }
                if($this->data['uiguard']->hasPermission('editsubscriptions', null, $this->data['user']->getType(), TRUE)) {
                    echo '  <a id="edit_subscription_link_' . $subscription['sid'] . '" class="janus_button" onclick="editSubscription(' . $this->data['user']->getUid() . ', ' . $subscription['sid'] . ');">' . $this->t('admin_edit') . '</a>';
                }
                echo '</td></tr>';
            }
            echo '</tbody></table>';

            if($this->data['uiguard']->hasPermission('addsubscriptions', null, $this->data['user']->getType(), TRUE)) {
                echo '<h2>' . $this->t('text_subscription_add_header') . '</h2>';
                echo '<select name="subscriptions" id="subscriptions_select">';
                echo '<option> -- ' . $this->t('tab_edit_entity_select') . ' --</option>';
                foreach($this->data['subscriptionList'] AS $subscription) {
                    $tmp = explode("-", $subscription);
                    if($tmp[0] == 'USER') {
                        if(isset($tmp[1]) && ctype_digit((string) $tmp[1])) {
                            $user = new sspmod_janus_User($janus_config);
                            $user->setUid($tmp[1]);
                            $user->load();
                            $name = $tmp[0] . ' - ' . $user->getUserid();
                        } else if(isset($tmp[1]) && $tmp[1] == 'NEW'){
                            $name = $tmp[0] . ' - ' . 'NEW';
                        } else {
                            $name = $tmp[0];
                        }
                    } else if($tmp[0] == 'ENTITYUPDATE') {
                        if(isset($tmp[1]) && ctype_digit((string) $tmp[1])) {
                            $entity = new sspmod_janus_Entity($janus_config);
                            $entity->setEid($tmp[1]);
                            $entity->load();
                            $name = $tmp[0] . ' - ' . $entity->getEntityid();
                        } else {
                            $name = implode('-', $tmp);
                        }
                    } else {
                        $name = implode('-', $tmp);
                    }
                    echo '<option value="'. $subscription .'">' . $name . '</option>';
                }
                echo '</select>';
                echo '<a class="janus_button" onclick="addSubscription(' . $this->data['user']->getUid() . ', $(\'select#subscriptions_select option:selected\').val());">' . $this->t('admin_add') . '</a>';
            }
            echo '</div>';
        }
        ?>
        <!-- END - SUBSCRIPTION SUBTAB -->
        <?php
        }
/* END SUBTAB MESSAGES SUBSCRIPTIONS***********************************************************************************/
        ?>
<?php
if (empty($this->data['selectedSubTab'])) {
        ?>
    </div>
</div>
<!-- TABS END - MESSAGES -->
<?php
}
}
/* END TAB MESSAGES ***************************************************************************************************/



/* START TAB ARPADMIN *************************************************************************************************/
elseif ($this->data['selectedtab'] == SELECTED_TAB_ARPADMIN) {
?>
<!-- TAB- ARP -->
<?php
if($this->data['uiguard']->hasPermission('arpeditor', null, $this->data['user']->getType(), TRUE)) {
    // retrieve page/pagesize/count and ARP list
    $arpparams = $util->getARPListParams();
$pageJs[] = <<<JAVASCRIPT_TAP_ARPADMIN
    $("tr[id^='arp_row_']:odd").addClass("odd");
    $("tr[id^='arp_row_']:even").addClass("even");
JAVASCRIPT_TAP_ARPADMIN;
?>
<div id="arpAdmin">
    <!-- ARP ADMIN -->
    <h3>Attribute Release Policies</h3>

    <form action="<?php echo FORM_ACTION_URL;?>" method="GET">
        <table style="display: block;" class="" id="arp-search">
            <tbody>
                <tr>
                    <td><?php echo $this->t('text_entities_search'); ?>:</td>
                    <td>
                        <input type="text" id="admin_arp_search" name="q" value="<?php echo htmlentities($arpparams['query'])?>"/>
                    </td>
                    <td>
                        <button type="submit" class="janus_button"><?php echo $this->t('text_entities_search') ?></button>
                    </td>
                </tr>
            </tbody>
        </table>
    </form>

    <hr/>
    <table id="arpList" border="0" style="border-collapse: collapse;">
        <thead>
            <tr>
                <th><?php echo $this->t('text_name'); ?></th>
                <th><?php echo $this->t('text_edit'); ?></th>
                <th><?php echo $this->t('text_delete'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($arpparams['list'] AS $arp): ?>
            <tr id="arp_row_<?php echo $arp['aid']; ?>">
                <td class="arp_name">
                    <?php if ($arp['is_default']) echo "<strong>"; ?>
                    <?php echo htmlentities($arp['name'],  ENT_QUOTES, "UTF-8"); ?>
                    <?php if ($arp['is_default']) echo " (default)</strong>"; ?>
                </td>
                <td class="arp_action">
                    <a href="#" onclick="ARP.edit(<?php echo $arp['aid']; ?>); return false;">
                        <img src="resources/images/pencil.png"
                             alt="Edit"
                             width="16"
                             height="16"
                                />
                    </a>
                </td>
                <td class="arp_action">
                    <form action="<?php echo FORM_ACTION_URL;?>" method="post">
                        <input type="hidden" name="arp_delete" value="<?php echo $arp['aid']; ?>" />
                        <a href="#" onclick="if (ARP.remove(<?php echo $arp['aid']; ?>)) { $(this).parents('form').submit(); } return false;">
                            <img src="resources/images/pm_delete_16.png"
                                 alt="Delete"
                                 width="16"
                                 height="16"
                                    />
                        </a>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (count($arpparams['list']) === 0): ?>
            <tr>
                <td colspan="3">
                    <em><?php echo $this->t('tab_arp_no_results')?></em>
                </td>
            </tr>
            <?php endif ?>
        </tbody>
        <?php if ($arpparams['total'] > 1): /* only render pagination when applicable */ ?>
        <tfoot>
            <tr>
                <td colspan="3">
                    <ul class="pagination">
                        <?php foreach(range(1, $arpparams['total']) as $page): ?>
                            <li>
                                <?php if ($arpparams['page'] == $page): /* current page*/ ?>
                                    <?php echo $page?>
                                <?php else: ?>
                                    <a href="<?php echo DASHBOARD_URL;?>/arpadmin?p=<?php echo $page?>&q=<?php echo htmlentities(urlencode($arpparams['query']))?>">
                                        <?php echo $page?>
                                    </a>
                                <?php endif?>
                            </li>
                        <?php endforeach ?>
                    </ul>
                </td>
            </tr>
        </tfoot>
        <?php endif ?>
    </table>

    <img src="resources/images/pm_plus_16.png"
         alt="Edit"
         width="16"
         height="16"
         onclick="ARP.create();" />

    <br />

    <!-- ARP Add -->
    <div id="arpEdit" style="display: none;">
        <script type="text/javascript">
        <?php
        foreach ($this->data['adminentities'] as $entity) {
            $arpId = $entity->getArp();
            $entityId   = $entity->getEntityid();
            $entityName = $entity->getPrettyname();
            $entityData = array(
                'eid'       => $entity->getEid(),
                'entityId'  => $entity->getEntityid(),
                'name'      => $entity->getPrettyname(),
                'revision'  => $entity->getRevisionid(),
            );
            $entityDataJson = json_encode($entityData, true);
            echo "ARP.setEntityForArp($arpId, $entityDataJson);" . PHP_EOL;

        }
        ?>
        </script>
        <form action="<?php echo FORM_ACTION_URL;?>" method="post" onsubmit="return ARP.validate()">
            <a href="#"
               style="float: right;"
               onclick="$(this).parents('#arpEdit').hide(); return false;"
                >
                [<?php echo strtoupper($this->t('text_close'));?>]
            </a>
            <br style="clear: both" />

            <input type="hidden" id="arp_id" name="arp_id" value="" />

            <fieldset>
                <label><?php echo $this->t('text_name'); ?></label>
                <input type="text" name="arp_name" id="arp_name" />
            </fieldset>

            <fieldset>
                <label><?php echo $this->t('text_description'); ?></label>
                <textarea rows="5" cols="80" name="arp_description" id="arp_description"></textarea>
            </fieldset>

            <fieldset>
                <label><?php echo $this->t('text_default'); ?></label>
                <input type="checkbox" name="arp_is_default" id="arp_is_default" value="true" />
            </fieldset>

            <fieldset>
                <label><?php echo $this->t('text_attributes'); ?></label>
                <table id="arp_attributes" border="0" style="border-collapse: collapse;">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Value</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <tr id="attribute_select_row">
                        <td class="arp_select_attribute">

                            <select id="attribute_select"
                                    name="attribute_key"
                                    onchange="ARP.addAttribute(this)"
                                    class="attribute_selector">
                                <option value="">-- <?php echo $this->t('tab_edit_entity_select'); ?> --</option>
                                <?php foreach($this->data['arp_attributes'] AS $label => $attribute): ?>
                                <option value="<?php echo htmlentities($attribute['name'], ENT_QUOTES, "UTF-8"); ?>">
                                    <?php echo htmlentities($label, ENT_QUOTES, "UTF-8");?>
                                </option>
                                <?php endforeach; ?>
                            </select>

                            <script>
                                ARP.availableAttributes = <?php echo json_encode($this->data['arp_attributes']); ?>;
                            </script>
                        </td>
                        <td class="arp_select_attribute_value" style="display: none">
                            <input id="attribute_select_value" type="text" value="" size="50" />
                            <img style="display: inline"
                                 alt="Add"
                                 src="resources/images/pm_plus_16.png"
                                 onclick="ARP.addAttribute($('#attribute_select'))" />
                            <script type="text/javascript">
                                $('#attribute_select_value').keypress(function(e) {
                                    var code= (e.keyCode ? e.keyCode : e.which);
                                    if (code == 13) {
                                        ARP.addAttribute($('#attribute_select'));
                                        e.preventDefault();
                                    }
                                });
                            </script>
                        </td>
                        <td>
                        </td>
                    </tr>
                </tbody>
            </table>
            </fieldset>

            <fieldset>
                <label><?php echo $this->t('text_used_by_entities') ?></label>
                <div id="arpEditEntities"></div>
            </fieldset>

            <fieldset>
                <input type="submit" id="arp_edit" name="arp_edit" value="<?php echo $this->t('text_save_and_close'); ?>" />
            </fieldset>
        </form>
    </div>
</div>
<!-- TAB END - ARP -->
<?php
}

}
/* END TAB ARPADMIN ***************************************************************************************************/
}

$jsTag = '<script type="text/javascript">' . PHP_EOL . implode(PHP_EOL, $pageJs) . PHP_EOL . '</script>' . PHP_EOL;

if (IS_AJAX) {
    echo  $jsTag;
} else {
?>
</div>
<!-- TABS DIV END -->

<p>[ <?php echo '<a href="' . htmlspecialchars($this->data['logouturl']) . '">' . $this->t('{status:logout}') . '</a>'; ?> ]</p>
<?php
    $this->data['head'] .= $jsTag;
    $this->includeAtTemplateBase('includes/footer.php');
}
?>