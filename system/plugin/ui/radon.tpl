{include file="sections/header.tpl"}
<style>
    @keyframes pulse-danger {
        0% {
            box-shadow: 0 0 0 0 rgba(220, 53, 69, 0.4);
        }

        70% {
            box-shadow: 0 0 0 10px rgba(220, 53, 69, 0);
        }

        100% {
            box-shadow: 0 0 0 0 rgba(220, 53, 69, 0);
        }
    }

    .btn-refresh {
        animation: pulse-danger 2s infinite;
    }
</style>
<!-- Enhanced Dashboard Statistics -->
<div class="row">
    <div class="col-md-3 col-sm-6">
        <div class="stats-card success" style="height: 140px; display: flex; flex-direction: column; justify-content: space-between; align-items: flex-start; position: relative; overflow: hidden;">
            <div class="stats-label">{Lang::T('Total Online Users')}</div>
            <div class="stats-value">{$totalCount}</div>
            <i class="glyphicon glyphicon-user" style="font-size: 24px; opacity: 0.7;"></i>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="stats-card info" style="height: 140px; display: flex; flex-direction: column; justify-content: space-between; align-items: flex-start; position: relative; overflow: hidden;">
            <div class="stats-label">{Lang::T('Total Data Usage')}</div>
            <div class="stats-value">{radon_formatBytes($totalUsage)}</div>
            <i class="glyphicon glyphicon-transfer" style="font-size: 24px; opacity: 0.7;"></i>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="stats-card warning" style="height: 140px; display: flex; flex-direction: column; justify-content: space-between; align-items: flex-start; position: relative; overflow: hidden;">
            <div class="stats-label">{Lang::T('Total Upload')}</div>
            <div class="stats-value">{radon_formatBytes($totalUpload)}</div>
            <i class="glyphicon glyphicon-arrow-up" style="font-size: 24px; opacity: 0.7;"></i>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="stats-card danger" style="height: 140px; display: flex; flex-direction: column; justify-content: space-between; align-items: flex-start; position: relative; overflow: hidden;">
            <div class="stats-label">{Lang::T('Total Download')}</div>
            <div class="stats-value">{radon_formatBytes($totalDownload)}</div>
            <i class="glyphicon glyphicon-arrow-down" style="font-size: 24px; opacity: 0.7;"></i>
        </div>
    </div>
</div>

<!-- Success Messages -->
{if isset($success) && count($success) > 0}
<div class="panel panel-success panel-hovered panel-stacked mb30">
    <div class="panel-heading">{Lang::T('Success')}</div>
    <div class="panel-body">
        <div class="bs-callout bs-callout-success">
            {foreach $success as $msg}
            <h4><span class="glyphicon glyphicon-ok-circle"></span> {$msg}<br></h4>
            {/foreach}
        </div>
    </div>
</div>
{/if}

<!-- Error Messages -->
{if isset($error) && count($error) > 0}
<div class="panel panel-danger panel-hovered panel-stacked mb30">
    <div class="panel-heading">{Lang::T('Error')}</div>
    <div class="panel-body">
        <div class="bs-callout bs-callout-danger">
            {foreach $error as $err}
            <h4><span class="glyphicon glyphicon-exclamation-sign"></span> {$err}<br></h4>
            {/foreach}
        </div>
    </div>
</div>
{/if}

<div class="row" style="padding: 5px">
    <div class="col-lg-12">
        <div class="pull-right">
            <a href="{$_url}plugin/radon_users" class="btn btn-info btn-sm" style="margin-right: 5px;">
                <span class="glyphicon glyphicon-refresh"></span> {Lang::T('Refresh')}
            </a>
            <div class="btn-group" role="group">
                <button type="button"
                    class="btn btn-danger btn-sm waves-effect modern-danger btn-refresh dropdown-toggle"
                    data-toggle="dropdown">
                    <span class="glyphicon glyphicon-cog"></span> {Lang::T('Database Management')} <span class="caret"></span>
                </button>
                <ul class="dropdown-menu" role="menu">
                    <li><a href="{$_url}plugin/radon_users_cleandb&action=all"
                            onclick="return confirm('{Lang::T('Are you sure you want to TRUNCATE ALL records in the RADACCT table? This action cannot be undone!')}')">
                            <span class="glyphicon glyphicon-trash"></span> {Lang::T('Truncate All Records')}</a>
                    </li>
                    <li><a href="{$_url}plugin/radon_users_cleandb&action=stopped"
                            onclick="return confirm('{Lang::T('Are you sure you want to delete all stopped sessions?')}')">
                            <span class="glyphicon glyphicon-remove-circle"></span> {Lang::T('Delete Stopped
                            Sessions')}</a>
                    </li>
                    <li><a href="{$_url}plugin/radon_users_cleandb&action=old&days=30"
                            onclick="return confirm('{Lang::T('Are you sure you want to delete records older than 30 days?')}')">
                            <span class="glyphicon glyphicon-time"></span> {Lang::T('Delete Records Older Than 30
                            Days')}</a>
                    </li>
                    <li><a href="{$_url}plugin/radon_users_cleandb&action=old&days=90"
                            onclick="return confirm('{Lang::T('Are you sure you want to delete records older than 90 days?')}')">
                            <span class="glyphicon glyphicon-time"></span> {Lang::T('Delete Records Older Than 90
                            Days')}</a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>
<!-- Main Panel -->
<div class="row">
    <div class="col-sm-12">
        <div class="panel panel-hovered mb20 panel-primary">
            <div class="panel-heading">
                <h3 class="panel-title"><span class="glyphicon glyphicon-list"></span> {Lang::T('Online Users')}
                    ({$totalCount})</h3>
            </div>
            <div class="panel-body">
                <!-- Mass Action Toolbar -->
                <div class="table-actions" id="massActions" style="display: none;">
                    <div class="row">
                        <div class="col-md-6">
                            <strong><span id="selectedCount">0</span> {Lang::T('user(s) selected')}</strong>
                        </div>
                        <div class="col-md-6 text-right">
                            <button type="button" class="btn btn-default btn-sm" onclick="clearSelection()">
                                <span class="glyphicon glyphicon-remove"></span> {Lang::T('Clear Selection')}
                            </button>
                            <button type="button" class="btn btn-danger btn-sm" id="massDisconnectBtn">
                                <span class="glyphicon glyphicon-alert"></span> {Lang::T('Disconnect Selected')}
                            </button>
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table id="onlineTable" class="table table-striped table-bordered table-hover">
                        <thead>
                            <tr>
                                <th class="checkbox-column">
                                    <input type="checkbox" id="selectAll" title="{Lang::T('Select All')}">
                                </th>
                                <th>{Lang::T('Full Name')}</th>
                                <th>{Lang::T('Username')}</th>
                                <th>{Lang::T('NAS')}</th>
                                <th>{Lang::T('Type')}</th>
                                <th>{Lang::T('IP Address')}</th>
                                <th>{Lang::T('MAC Address')}</th>
                                <th>{Lang::T('Uptime')}</th>
                                <th>{Lang::T('Upload')}</th>
                                <th>{Lang::T('Download')}</th>
                                <th>{Lang::T('Total Usage')}</th>
                                <th>{Lang::T('Last Updated')}</th>
                                <th>{Lang::T('Manage')}</th>
                            </tr>
                        </thead>
                        <tbody>
                            {foreach $useron as $userson}
                            {$userTotalUsage = $userson['acctinputoctets'] + $userson['acctoutputoctets']}
                            <tr>
                                <td class="checkbox-column" style="text-align: center;">
                                    <input type="checkbox" class="user-checkbox" name="selected_users[]"
                                        value="{$userson['username']}" data-username="{$userson['username']}">
                                </td>
                                <td>{if isset($customerFullNames[$userson['username']])}{$customerFullNames[$userson['username']]}{else}N/A{/if}</td>
                                <td><a href="{$_url}customers/viewu/{$userson['username']}"
                                        class="text-primary"><strong>{$userson['username']}</strong></a>
                                </td>
                                <td>{$userson['nasipaddress']}</td>
                                <td><span class="label label-info">{$userson['calledstationid']}</span></td>
                                <td><code>{$userson['framedipaddress']}</code></td>
                                <td><code>{$userson['callingstationid']}</code></td>
                                <td><span
                                        class="badge badge-info">{radon_secondsToTime($userson['acctsessiontime'])}</span>
                                </td>
                                <td class="text-success">
                                    <strong>{radon_formatBytes($userson['acctinputoctets'])}</strong>
                                </td>
                                <td class="text-danger">
                                    <strong>{radon_formatBytes($userson['acctoutputoctets'])}</strong>
                                </td>
                                <td class="text-primary"><strong>{radon_formatBytes($userTotalUsage)}</strong></td>
                                <td>{$userson['acctupdatetime']}</td>
                                <td>
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-danger btn-xs disconnect-btn"
                                            data-username="{$userson['username']}" title="Disconnect">
                                            <span class="glyphicon glyphicon-alert" aria-hidden="true"></span>
                                            {Lang::T('Disconnect')}
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            {/foreach}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    // Wait for jQuery to be available
    (function () {
        function initRadon() {
            if (typeof jQuery === 'undefined') {
                setTimeout(initRadon, 100);
                return;
            }

            var $ = jQuery;

            // Set up event handlers first (they work independently of DataTable)
            setupEventHandlers();

            // Load DataTables only if not already loaded
            if (typeof $.fn.DataTable === 'undefined') {
                var script = document.createElement('script');
                script.src = 'https://cdn.datatables.net/1.11.3/js/jquery.dataTables.min.js';
                script.onload = function () {
                    initializeDataTable();
                };
                document.head.appendChild(script);
            } else {
                initializeDataTable();
            }

            function setupEventHandlers() {
                // Select All checkbox
                $(document).on('click', '#selectAll', function () {
                    var isChecked = $(this).prop('checked');
                    $('.user-checkbox').prop('checked', isChecked);
                    updateMassActions();
                });

                // Individual checkbox change
                $(document).on('change', '.user-checkbox', function () {
                    updateMassActions();
                    updateSelectAll();
                });

                // Single user disconnect
                $(document).on('click', '.disconnect-btn', function () {
                    var username = $(this).data('username');
                    var btn = $(this);

                    Swal.fire({
                        title: '{Lang::T("Disconnect User")}?',
                        text: '{Lang::T("Are you sure you want to disconnect")} ' + username + '?',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#3085d6',
                        confirmButtonText: '{Lang::T("Yes, disconnect")}',
                        cancelButtonText: '{Lang::T("Cancel")}',
                        showLoaderOnConfirm: true,
                        preConfirm: function () {
                            return new Promise(function (resolve, reject) {
                                $.ajax({
                                    url: '{$_url}plugin/radon_users',
                                    type: 'POST',
                                    data: {
                                        ajax_disconnect: true,
                                        username: username
                                    },
                                    dataType: 'json',
                                    success: function (response) {
                                        if (response.success) {
                                            resolve(response);
                                        } else {
                                            reject(response.message || '{Lang::T("Failed to disconnect user")}');
                                        }
                                    },
                                    error: function () {
                                        reject('{Lang::T("Network error occurred")}');
                                    }
                                });
                            });
                        },
                        allowOutsideClick: false
                    }).then(function (result) {
                        if (result.isConfirmed) {
                            Swal.fire({
                                title: '{Lang::T("Disconnected")}!',
                                text: result.value.message || '{Lang::T("User disconnected successfully")}',
                                icon: 'success',
                                timer: 2000,
                                showConfirmButton: false
                            }).then(function () {
                                location.reload();
                            });
                        }
                    }).catch(function (error) {
                        Swal.fire({
                            title: '{Lang::T("Error")}!',
                            text: error || '{Lang::T("Failed to disconnect user")}',
                            icon: 'error'
                        });
                    });
                });

                // Mass disconnect
                $(document).on('click', '#massDisconnectBtn', function () {
                    var selectedUsers = [];
                    $('.user-checkbox:checked').each(function () {
                        selectedUsers.push($(this).val());
                    });

                    if (selectedUsers.length === 0) {
                        Swal.fire({
                            title: '{Lang::T("No Selection")}',
                            text: '{Lang::T("Please select at least one user")}',
                            icon: 'warning'
                        });
                        return;
                    }

                    Swal.fire({
                        title: '{Lang::T("Disconnect Multiple Users")}?',
                        text: '{Lang::T("Are you sure you want to disconnect")} ' + selectedUsers.length + ' {Lang::T("selected user(s)?")}',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#3085d6',
                        confirmButtonText: '{Lang::T("Yes, disconnect all")}',
                        cancelButtonText: '{Lang::T("Cancel")}',
                        showLoaderOnConfirm: true,
                        preConfirm: function () {
                            return new Promise(function (resolve, reject) {
                                $.ajax({
                                    url: '{$_url}plugin/radon_users',
                                    type: 'POST',
                                    data: {
                                        ajax_mass_disconnect: true,
                                        selected_users: selectedUsers
                                    },
                                    dataType: 'json',
                                    success: function (response) {
                                        if (response.success) {
                                            resolve(response);
                                        } else {
                                            reject(response.message || '{Lang::T("Failed to disconnect users")}');
                                        }
                                    },
                                    error: function () {
                                        reject('{Lang::T("Network error occurred")}');
                                    }
                                });
                            });
                        },
                        allowOutsideClick: false
                    }).then(function (result) {
                        if (result.isConfirmed) {
                            var message = result.value.message || '{Lang::T("Users disconnected successfully")}';
                            if (result.value.failed > 0) {
                                message += ' ({Lang::T("Failed")}: ' + result.value.failed + ')';
                            }
                            Swal.fire({
                                title: '{Lang::T("Disconnected")}!',
                                text: message,
                                icon: 'success',
                                timer: 3000,
                                showConfirmButton: false
                            }).then(function () {
                                location.reload();
                            });
                        }
                    }).catch(function (error) {
                        Swal.fire({
                            title: '{Lang::T("Error")}!',
                            text: error || '{Lang::T("Failed to disconnect users")}',
                            icon: 'error'
                        });
                    });
                });
            }

            function initializeDataTable() {
                var $table = $('#onlineTable');
                if ($table.length === 0) {
                    console.warn('DataTable element not found');
                    return;
                }

                // Check if DataTable is already initialized
                if ($.fn.DataTable.isDataTable('#onlineTable')) {
                    return;
                }

                var table = $table.DataTable({
                    "pageLength": 25,
                    "order": [[1, "asc"]],
                    "columnDefs": [
                        { "orderable": false, "targets": [0, 11] }
                    ],
                    "language": {
                        "search": "{Lang::T('Search')}:",
                        "lengthMenu": "{Lang::T('Show')} _MENU_ {Lang::T('entries')}",
                        "info": "{Lang::T('Showing')} _START_ {Lang::T('to')} _END_ {Lang::T('of')} _TOTAL_ {Lang::T('entries')}",
                        "infoEmpty": "{Lang::T('Showing')} 0 {Lang::T('to')} 0 {Lang::T('of')} 0 {Lang::T('entries')}",
                        "infoFiltered": "({Lang::T('filtered from')} _MAX_ {Lang::T('total entries')})",
                        "zeroRecords": "{Lang::T('No matching records found')}",
                        "paginate": {
                            "first": "{Lang::T('First')}",
                            "last": "{Lang::T('Last')}",
                            "next": "{Lang::T('Next')}",
                            "previous": "{Lang::T('Previous')}"
                        }
                    }
                });
            }

            function updateMassActions() {
                var checkedCount = $('.user-checkbox:checked').length;
                var $selectedCount = $('#selectedCount');
                var $massActions = $('#massActions');

                if ($selectedCount.length) {
                    $selectedCount.text(checkedCount);
                }

                if ($massActions.length) {
                    if (checkedCount > 0) {
                        $massActions.slideDown();
                    } else {
                        $massActions.slideUp();
                    }
                }
            }

            function updateSelectAll() {
                var $selectAll = $('#selectAll');
                if ($selectAll.length) {
                    var totalCheckboxes = $('.user-checkbox').length;
                    var checkedCheckboxes = $('.user-checkbox:checked').length;
                    $selectAll.prop('checked', totalCheckboxes === checkedCheckboxes);
                }
            }

            window.clearSelection = function () {
                $('.user-checkbox').prop('checked', false);
                $('#selectAll').prop('checked', false);
                $('#massActions').slideUp();
                $('#selectedCount').text('0');
            };
        }

        // Initialize when DOM is ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initRadon);
        } else {
            initRadon();
        }
    })();
</script>

{include file="sections/footer.tpl"}