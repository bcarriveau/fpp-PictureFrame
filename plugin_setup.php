<?php
// Force enable error reporting to diagnose issues
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Fallback for potential undefined variables
if (!isset($configDirectory)) {
    $configDirectory = '/home/fpp/media/config';
}

if (isset($_GET['action']) && $_GET['action'] == 'sync_gdrive') {
    // PHP handler to run the sync script
    $scriptPath = '/home/fpp/media/plugins/fpp-PictureFrame/scripts/sync_gdrive.sh';  // Adjust if script is elsewhere
    if (file_exists($scriptPath)) {
        shell_exec("bash $scriptPath 2>&1");
        $logFile = '/home/fpp/media/plugins/fpp-PictureFrame/gdrive_sync.log';
        if (file_exists($logFile)) {
            echo nl2br(file_get_contents($logFile));
        } else {
            echo "No log file generated.";
        }
    } else {
        echo "Error: sync_gdrive.sh not found at $scriptPath";
    }
    exit;
}

if (isset($_GET['action']) && $_GET['action'] == 'sync_gdrive_single' && isset($_GET['url'])) {
    // Handler for single folder sync
    $scriptPath = '/home/fpp/media/plugins/fpp-PictureFrame/scripts/sync_gdrive.sh';  // Adjust if script is elsewhere
    if (file_exists($scriptPath)) {
        $url = escapeshellarg($_GET['url']);
        shell_exec("bash $scriptPath $url 2>&1");
        $logFile = '/home/fpp/media/plugins/fpp-PictureFrame/gdrive_sync.log';
        if (file_exists($logFile)) {
            echo nl2br(file_get_contents($logFile));
        } else {
            echo "No log file generated.";
        }
    } else {
        echo "Error: sync_gdrive.sh not found at $scriptPath";
    }
    exit;
}
?>

<script>
var config = {};          // Plugin configuration

function InsertSenderRow() {
    $('#sendersBody').append("<tr><td valign='middle'>  <div class='rowGrip'> <i class='rowGripIcon fpp-icon-grip'></i> </div> </td>" +
                    "<td><input type='text' class='form-control email' size=32 maxlength=64 value='' /></td>" +
                    "<td><input type='text' class='form-control folder' size=32 maxlength=64 list='imageFolders' /></td>" +
                    "<td><input type='text' class='form-control note' size=32 maxlength=64 value='' /></td>" +
                    "</tr>");
}

function InsertFolderRow() {
    $('#foldersBody').append("<tr><td><input type='text' class='form-control folder' size=32 maxlength=64 value='' /></td></tr>");
}

function InsertGDriveRow() {
    $('#gdriveBody').append("<tr><td valign='middle'>  <div class='rowGrip'> <i class='rowGripIcon fpp-icon-grip'></i> </div> </td>" +
                    "<td><input type='text' class='form-control gdrive_url' size=60 maxlength=1024 value='' /></td>" +
                    "<td><input type='text' class='form-control last_sync' size=20 maxlength=64 value='Never' disabled /></td>" +
                    "<td><button class='buttons btn-success btn-sm' onClick='SyncGDriveRow(this);'>Sync</button></td>" +
                    "</tr>");
}

function CheckForNewImages() {
    var options = {
        id: 'fetchImagesDialog',
        title: 'Fetch New Images',
        body: "<textarea style='width: 99%; height: 500px;' disabled id='fetchImagesText'></textarea>",
        noClose: true,
        keyboard: false,
        backdrop: 'static',
        footer: '',
        buttons: {
            'Close': {
                id: 'fetchImagesCloseButton',
                click: function() { CloseModalDialog('fetchImagesDialog'); },
                disabled: true,
                class: 'btn-success'
            }
        }
    };
    $('#fetchImagesCloseButton').prop('disabled', true);
    DoModalDialog(options);
    StreamURL('runEventScript.php?scriptName=CheckForNewPictureFrameImages.sh&nohtml=1', 'fetchImagesText', 'FetchImagesDone');
}

function FetchImagesDone() {
    $('#fetchImagesCloseButton').prop('disabled', false);
    EnableModalDialogCloseButton('fetchImagesDialog');
}

function SyncGDrive() {
    var options = {
        id: 'syncGDriveDialog',
        title: 'Sync Google Drive Folders',
        body: "<p>Syncing... this may take a while depending on folder size. Please wait for completion.</p><textarea style='width: 99%; height: 470px;' disabled id='syncGDriveText'></textarea>",
        noClose: true,
        keyboard: false,
        backdrop: 'static',
        footer: '',
        buttons: {
            'Close': {
                id: 'syncGDriveCloseButton',
                click: function() { CloseModalDialog('syncGDriveDialog'); LoadGDriveConfig(); },
                disabled: true,
                class: 'btn-success'
            }
        }
    };
    $('#syncGDriveCloseButton').prop('disabled', true);
    DoModalDialog(options);
    StreamURL('plugin.php?plugin=fpp-PictureFrame&page=plugin_setup.php&action=sync_gdrive&nopage=1', 'syncGDriveText', 'SyncDone');
}

function SyncGDriveRow(button) {
    var row = $(button).closest('tr');
    var url = row.find('.gdrive_url').val().trim();
    if (url == '') {
        alert('No URL in this row.');
        return;
    }
    var options = {
        id: 'syncGDriveDialog',
        title: 'Sync Single Google Drive Folder',
        body: "<p>Syncing... this may take a while depending on folder size. Please wait for completion.</p><textarea style='width: 99%; height: 470px;' disabled id='syncGDriveText'></textarea>",
        noClose: true,
        keyboard: false,
        backdrop: 'static',
        footer: '',
        buttons: {
            'Close': {
                id: 'syncGDriveCloseButton',
                click: function() { CloseModalDialog('syncGDriveDialog'); LoadGDriveConfig(); },
                disabled: true,
                class: 'btn-success'
            }
        }
    };
    $('#syncGDriveCloseButton').prop('disabled', true);
    DoModalDialog(options);
    StreamURL('plugin.php?plugin=fpp-PictureFrame&page=plugin_setup.php&action=sync_gdrive_single&url=' + encodeURIComponent(url) + '&nopage=1', 'syncGDriveText', 'SyncDone');
}

function SyncDone() {
    $('#syncGDriveCloseButton').prop('disabled', false);
    EnableModalDialogCloseButton('syncGDriveDialog');
    LoadGDriveConfig();  // Ensure reload after sync to update last_sync
}

function GeneratePlaylist() {
    var duration = 0;
    var pl = {};
    pl.name = "Slideshow-Example";
    pl.version = 3;
    pl.repeat = 0;
    pl.loopCount = 0;
    pl.empty = false;
    pl.desc = "Slideshow-Example";
    pl.random = 0;

    var leadIn = [];
    var mainPlaylist = [];
    var leadOut = [];
    var playlistInfo = {};

    var f = {};
    f.type = "command";
    f.enabled = 1;
    f.playOnce = 0;
    f.command = "Run Script";
    f.args = ["CheckForNewPictureFrameImages.sh", "", ""];
    leadIn.push(f);

    var mon_on = {};
    mon_on.type = "command";
    mon_on.enabled = 1;
    mon_on.playOnce = 0;
    mon_on.command = "Run Script";
    mon_on.args = ["pf-monitor_on.sh", "", ""];
    leadIn.push(mon_on);

    $('#foldersBody > tr').each(function() {
        var folder = $(this).find('.folder').val();
        var i = {};
        i.type = "image";
        i.enabled = 1;
        i.playOnce = 0;
        i.imagePath = "\/home\/fpp\/media\/images\/" + folder + "\/";
        i.modelName = 'fb0';
        mainPlaylist.push(i);

        var p = {};
        p.type = "pause";
        p.enabled = 1;
        p.playOnce = 0;
        p.duration = 60;
        mainPlaylist.push(p);

        duration += 60;
    });

    if (duration == 0) {
        var i = {};
        i.type = "image";
        i.enabled = 1;
        i.playOnce = 0;
        i.imagePath = "\/home\/fpp\/media\/images\/";
        i.modelName = 'fb0';
        mainPlaylist.push(i);

        var p = {};
        p.type = "pause";
        p.enabled = 1;
        p.playOnce = 0;
        p.duration = 60;
        mainPlaylist.push(p);

        duration += 60;
    }

    var mon_off = {};
    mon_off.type = "command";
    mon_off.enabled = 1;
    mon_off.playOnce = 0;
    mon_off.command = "Run Script";
    mon_off.args = ["pf-monitor_off.sh", "", ""];
    leadOut.push(mon_off);

    playlistInfo.total_duration = duration;
    playlistInfo.total_items = leadIn.length + mainPlaylist.length + leadOut.length;

    pl.leadIn = leadIn;
    pl.mainPlaylist = mainPlaylist;
    pl.leadOut = leadOut;
    pl.playlistInfo = playlistInfo;

    var str = JSON.stringify(pl, true);

    $.ajax({
        url: "api/playlist/Slideshow-Example",
        type: 'POST',
        contentType: 'application/json',
        data: str,
        async: false,
        dataType: 'json',
        success: function (data) {
            $.jGrowl("Playlist Created", { themeState: 'success' });
            location.href = 'playlists.php?playlist=Slideshow-Example';
        },
        error: function (...args) {
            DialogError('Unable to save playlist', "Error: Unable to save playlist." + show_details(args));
        }
    });
}

function UpdateFolderDatalist() {
    var options = "";
    $('#foldersBody > tr').each(function() {
        var folder = $(this).find('.folder').val();
        options += "<option value='" + folder + "'>" + folder + "</option>";
    });
    $('#imageFolders').html(options);
}

function SaveFolders() {
    var folders = [];
    $('#foldersBody > tr').each(function() {
        var folder = $(this).find('.folder').val();
        if (folder != '') {
            Post('api/dir/Images/' + folder, true, '');
            folders.push(folder);
        }
    });
    UpdateFolderDatalist();
}

function SavePictureFrameConfig() {
    var config = {};
    var senders = [];
    $('#sendersBody > tr').each(function() {
        var sender = {};
        sender.email = $(this).find('.email').val().trim();
        sender.folder = $(this).find('.folder').val().trim();
        sender.note = $(this).find('.note').val().trim();
        senders[senders.length] = sender;

        if (sender.folder != '')
            Post('api/dir/Images/' + sender.folder, true, '');
    });
    config.senders = senders;

    var gdriveFolders = [];
    $('#gdriveBody > tr').each(function() {
        var folder = {};
        folder.url = $(this).find('.gdrive_url').val().trim();
        folder.last_sync = $(this).find('.last_sync').val().trim();
        if (folder.url != '') {
            gdriveFolders.push(folder);
        }
    });
    config.gdriveFolders = gdriveFolders;

    var configStr = JSON.stringify(config);
    console.log('Saving config:', configStr);  // Debug: Log the config being saved

    $.post('/api/configfile/plugin.fpp-PictureFrame.json', configStr)
        .done(function(data) {
            console.log('Config saved successfully. Server response:', data);  // Debug: Log success response
            $.jGrowl('FPP Picture Frame Config Saved');
            LoadGDriveConfig();  // Reload to show saved state
        })
        .fail(function(jqXHR, textStatus, errorThrown) {
            console.error('Error saving config:', textStatus, errorThrown);  // Debug: Log failure details
            alert('Error, could not save plugin.fpp-PictureFrame.json config file. Check console for details.');
        });
}

function LoadConfig() {
    $.ajax({
        url: '/api/configfile/plugin.fpp-PictureFrame.json',
        async: false,
        success: function(data) {
            config = data;

            var rows = "";
            for (var x = 0; x < config.senders.length; x++) {
                rows += "<tr><td valign='middle'>  <div class='rowGrip'> <i class='rowGripIcon fpp-icon-grip'></i> </div> </td>" +
                    "<td><input type='text' class='form-control email' size=32 maxlength=64 value='" + config.senders[x].email + "' /></td>" +
                    "<td><input type='text' class='form-control folder' size=32 maxlength=64 value='" + config.senders[x].folder + "' list='imageFolders' /></td>" +
                    "<td><input type='text' class='form-control note' size=32 maxlength=64 value='" + config.senders[x].note + "' /></td>" +
                    "</tr>";
            }
            $('#sendersBody').html(rows);
        }
    });
}

function LoadGDriveConfig() {
    $.ajax({
        url: '/api/configfile/plugin.fpp-PictureFrame.json',
        async: false,  // Make synchronous to ensure load completes before continuing
        success: function(data) {
            console.log('Loaded GDrive config:', data);  // Debug: Log the loaded data
            var rows = "";
            for (var x = 0; x < data.gdriveFolders.length; x++) {
                rows += "<tr><td valign='middle'>  <div class='rowGrip'> <i class='rowGripIcon fpp-icon-grip'></i> </div> </td>" +
                    "<td><input type='text' class='form-control gdrive_url' size=60 maxlength=1024 value='" + data.gdriveFolders[x].url + "' /></td>" +
                    "<td><input type='text' class='form-control last_sync' size=20 maxlength=64 value='" + data.gdriveFolders[x].last_sync + "' disabled /></td>" +
                    "<td><button class='buttons btn-success btn-sm' onClick='SyncGDriveRow(this);'>Sync</button></td>" +
                    "</tr>";
            }
            $('#gdriveBody').html(rows);
            var lastFullSync = data.last_full_sync || 'Never';
            $('#lastFullSync').text('Last full sync: ' + lastFullSync);
            // Add auto-save on URL blur with debounce
            var debounceTimer;
            $(document).off('blur', '.gdrive_url').on('blur', '.gdrive_url', function() {
                var currentInput = $(this);
                var originalValue = currentInput.data('original-value') || '';  // Store original on focus if needed
                if (currentInput.val().trim() !== originalValue) {  // Only save if changed
                    console.log('Blur detected on GDrive URL, triggering save if changed.');  // Debug: Log blur trigger
                    clearTimeout(debounceTimer);
                    debounceTimer = setTimeout(function() {
                        SavePictureFrameConfig();
                    }, 500);  // 500ms debounce
                }
            });
            // Optional: Store original value on focus for change detection
            $(document).off('focus', '.gdrive_url').on('focus', '.gdrive_url', function() {
                $(this).data('original-value', $(this).val().trim());
            });
        },
        error: function(jqXHR, textStatus, errorThrown) {
            console.error('Error loading GDrive config:', textStatus, errorThrown);  // Debug: Log load errors
        }
    });
}

function DeleteSelectedFolder() {
    if (folderTableInfo.selected >= 0) {
        var folder = $('#foldersBody .fppTableSelectedEntry').find('.folder').val();
        $('#foldersBody .fppTableSelectedEntry').remove();
        folderTableInfo.selected = -1;
        SetButtonState("#btnDeleteFolder", "disable");
        if (folder != '') {
            $.ajax({
                url: 'api/dir/Images/' + folder,
                type: 'DELETE',
                async: true,
                dataType: 'json',
                error: function () {
                    $.jGrowl('Unable to delete folder ' + folder, { themeState: 'danger' });
                }
            });
        }
    }
    UpdateFolderDatalist();
}

function DeleteSelectedSender() {
    if (senderTableInfo.selected >= 0) {
        $('#sendersBody .fppTableSelectedEntry').remove();
        senderTableInfo.selected = -1;
        SetButtonState("#btnDeleteSender", "disable");
    }
}

function DeleteSelectedGDrive() {
    if (gdriveTableInfo.selected >= 0) {
        $('#gdriveBody .fppTableSelectedEntry').remove();
        gdriveTableInfo.selected = -1;
        SetButtonState("#btnDeleteGDrive", "disable");
    }
}

var folderTableInfo = {
    tableName: "foldersTable",
    selected: -1,
    enableButtons: [ "btnDeleteFolder" ],
    disableButtons: [],
    sortable: 0
};

var senderTableInfo = {
    tableName: "sendersTable",
    selected: -1,
    enableButtons: [ "btnDeleteSender" ],
    disableButtons: [],
    sortable: 1
};

var gdriveTableInfo = {
    tableName: "gdriveTable",
    selected: -1,
    enableButtons: [ "btnDeleteGDrive" ],
    disableButtons: [],
    sortable: 1
};

$(document).ready(function() {
    SetupSelectableTableRow(folderTableInfo);
    SetupSelectableTableRow(senderTableInfo);
    SetupSelectableTableRow(gdriveTableInfo);
    LoadConfig();
    LoadGDriveConfig();
    $('[data-bs-toggle="tooltip"]').tooltip();  // Target only elements with tooltips
});
</script>

<?php
$needfb = 1;
if (file_exists($configDirectory . '/model-overlays.json')) {
    $data = file_get_contents($configDirectory . '/model-overlays.json');
    $models = json_decode($data, true);
    foreach ( $models['models'] as $model) {
        if (($model['Name'] == 'fb0') || ($model['Name'] == 'fb1')) {
            $needfb = 0;
        }
    }
}
if ($needfb) {
    echo "<span class='alert alert-danger'><b>WARNING: Could not find Pixel Overlay Model, click for <a href='javascript:void();' onClick='DisplayHelp();'>more info</a>.</b></span>\n";
}
?>

<div id="warningsRow" class="alert alert-danger"><div id="warningsTd"><div id="warningsDiv"></div></div></div>
<div id="global" class="settings">
    <fieldset>
        <div class="row tablePageHeader">
            <div class="col-md"><h3>Picture Folders</h3></div>
            <div class="col-md-auto ms-lg-auto">
                <div class="form-actions">
                    <input type=button value='Delete' onClick='DeleteSelectedFolder();' data-btn-enabled-class="btn-outline-danger" id='btnDeleteFolder' class='disableButtons'>

                    <button class='buttons btn-outline-success' value='Add' onClick='InsertFolderRow();'><i class="fas fa-plus"></i> Add</button>
                    <input type='button' class='buttons btn-success' value='Save' onClick='SaveFolders();'>
                </div>
            </div>
        </div>

        <div class='fppTableWrapper fppTableWrapperAsTable'>
            <div class='fppTableContents table-responsive'>
                <table id='foldersTable' class='table fppSelectableRowTable'>
                    <tbody id='foldersBody'>
<?php
$imageFolders = array();
$imageDir = '/home/fpp/media/images';
foreach (scandir($imageDir) as $fileName) {
    if ($fileName != '.' && $fileName != '..') {
        if (is_dir($imageDir . '/' . $fileName)) {
            array_push($imageFolders, $fileName);
        }
    }
}
foreach ($imageFolders as $dirName) {
    printf( "<tr><td><input type='text' class='form-control folder' size=32 maxlength=64 value='%s' disabled/></td></tr>", $dirName);
}
?>
                    </tbody>
                </table>
                <b>NOTE: Folders are automatically deleted in FPP when the Delete button is used, but are only created when the Save button is used.</b>
            </div>
        </div>
        <br>
        <hr>
        <br>

        <div class="row tablePageHeader">
            <div class="col-md"><h3>Valid Sender List</h3></div>
            <div class="col-md-auto ms-lg-auto">
                <div class="form-actions">
                    <input type=button value='Delete' onClick='DeleteSelectedSender();' data-btn-enabled-class="btn-outline-danger" id='btnDeleteSender' class='disableButtons'>

                    <button class='buttons btn-outline-success' value='Add' onClick='InsertSenderRow();'><i class="fas fa-plus"></i> Add</button>
                    <input type='button' class='buttons btn-success' value='Save' onClick='SavePictureFrameConfig();'>
                </div>
            </div>
        </div>

        <div class='fppTableWrapper fppTableWrapperAsTable'>
            <div class='fppTableContents table-responsive'>
                <table id='sendersTable' class='table fppSelectableRowTable'>
                    <thead>
                        <tr class='tblheader'>
                            <th></th>
                            <th title='Email'>Email</th>
                            <th title='Folder'>Folder</th>
                            <th title='Note'>Note</th>
                        </tr>
                    </thead>
                    <tbody id='sendersBody' class='ui-sortable'>
                    </tbody>
                </table>
            </div>
        </div>

        <br>
<?php
PrintSettingGroup('pfimapsettings', '', '', '', 'fpp-PictureFrame');
?>

        <input type='button' class='buttons btn-success' value='Check For New Images' onClick='CheckForNewImages();'>
        <input type='button' class='buttons btn-success' value='Generate Example Playlist' onClick='GeneratePlaylist();'>
        <br><br>
        <hr>
        <br>

        <div class="row tablePageHeader">
            <div class="col-md"><h3>Google Drive Folders</h3></div>
            <div class="col-md-auto ms-lg-auto">
                <div class="form-actions">
                    <input type=button value='Delete' onClick='DeleteSelectedGDrive();' data-btn-enabled-class="btn-outline-danger" id='btnDeleteGDrive' class='disableButtons'>

                    <button class='buttons btn-outline-success' value='Add' onClick='InsertGDriveRow();'><i class="fas fa-plus"></i> Add</button>
                    <input type='button' class='buttons btn-success' value='Save' onClick='SavePictureFrameConfig();'>
                </div>
            </div>
        </div>

        <p id="lastFullSync">Last full sync: Never</p>

        <div class='fppTableWrapper fppTableWrapperAsTable'>
            <div class='fppTableContents table-responsive'>
                <table id='gdriveTable' class='table fppSelectableRowTable'>
                    <thead>
                        <tr class='tblheader'>
                            <th></th>
                            <th title='URL'>Shared Folder URL</th>
                            <th title='Last Sync'>Last Sync</th>
                            <th>Sync</th>
                        </tr>
                    </thead>
                    <tbody id='gdriveBody' class='ui-sortable'>
                    </tbody>
                </table>
            </div>
        </div>

        <input type='button' class='buttons btn-success' value='Sync All' onClick='SyncGDrive();'>
        <br>
        <b>To set up automatic background syncing of Google Drive folders:</b>
        <ol>
            <li>Go to <b>Status/Control > Scheduler</b> and click <b>+ Add</b>.</li>
            <li>Set <b>Active</b>, <b>Start Date</b>, <b>End Date</b>, <b>Day(s)</b>, and <b>Start Time</b> (e.g., 12:00 AM for daily start).</li>
            <li>Set <b>Schedule Type</b> to <b>Command</b>.</li>
            <li>Select <b>Command</b>: <b>Run Script</b>.</li>
            <li>Set <b>Args</b>: "sync_gdrive.sh" (or the full path: /home/fpp/media/plugins/fpp-PictureFrame/scripts/sync_gdrive.sh).</li>
            <li>Set <b>Stop Type</b> to <b>Graceful</b> (allows sync to finish if interrupted).</li>
            <li>Set <b>Repeat</b>: Your interval (60min is max currently for daily setups).</li>
            <li>Click <b>Save</b>.</li>
        </ol>
        This will run the full sync periodically without manual intervention.
    </fieldset>
</div>
<div id='emailPopup' title='Checking for new images' style="display: none">
    <textarea style='width: 99%; height: 500px;' disabled id='emailText'>
    </textarea>
    <input id='closeDialogButton' type='button' class='buttons' value='Close' onClick="$('#emailPopup').fppDialog('close');" style='display: none;'>
</div>
<datalist id='imageFolders'>
<?php
foreach ($imageFolders as $dirName) {
    printf( "<option value='%s'>%s</option>\n", $dirName, $dirName);
}
?>
</datalist>