<ul class="nav nav-tabs" data-tabs="tabs" id="maintabs">
    <li class="active"><a data-toggle="tab" href="#general">{{ lang._('General') }}</a></li>
    <li><a data-toggle="tab" href="#users">{{ lang._('Users') }}</a></li>
</ul>
<div class="tab-content content-box tab-content">
    <div id="general" class="tab-pane fade in active">
        {{ partial("layout_partials/base_form", ['fields': generalForm, 'id': 'frm_GeneralSettings']) }}
    </div>
    <div id="users" class="tab-pane fade in">
        <table id="grid-users" class="table table-condensed table-hover table-striped"
               data-editAlert="UserChangeMessage" data-editDialog="DialogEditUser">
            <thead>
                <tr>
                    <th data-column-id="uuid" data-type="string"
                        data-identifier="true" data-visible="false">{{ lang._('ID') }}</th>
                    <th data-column-id="enabled" data-width="6em" data-type="string"
                        data-formatter="rowtoggle">{{ lang._('Enabled') }}</th>
                    <th data-column-id="username" data-type="string">{{ lang._('Local user') }}</th>
                    <th data-column-id="subject" data-type="string">{{ lang._('Provider account') }}</th>
                    <th data-column-id="commands" data-width="7em"
                        data-formatter="commands" data-sortable="false">{{ lang._('Commands') }}</th>
                </tr>
            </thead>
            <tbody></tbody>
            <tfoot>
                <tr>
                    <td></td>
                    <td colspan="4">
                        <button data-action="add" type="button" class="btn btn-xs btn-primary">
                            <span class="fa fa-plus fa-fw"></span>
                        </button>
                        <button data-action="deleteSelected" type="button" class="btn btn-xs btn-default">
                            <span class="fa fa-trash fa-fw"></span>
                        </button>
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<div class="alert alert-info hidden" role="alert" id="responseMsg"></div>

<div class="col-md-12">
    <button class="btn btn-primary" id="saveAct" type="button">
        <b>{{ lang._('Save') }}</b>
    </button>
    <button class="btn btn-default" id="testAct" type="button">
        <b>{{ lang._('Test provider') }}</b>
    </button>
</div>

{{ partial("layout_partials/base_dialog", ['fields': formDialogUser, 'id': 'DialogEditUser', 'label': lang._('Edit User')]) }}

<script type="text/javascript">
$( document ).ready(function() {
    mapDataToFormUI({'frm_GeneralSettings': "/api/oidclogin/settings/get"}).done(function(data){
        formatTokenizersUI();
        $('.selectpicker').selectpicker('refresh');
    });

    $("#grid-users").UIBootgrid({
        search:'/api/oidclogin/settings/searchUser/',
        get:'/api/oidclogin/settings/getUser/',
        set:'/api/oidclogin/settings/setUser/',
        add:'/api/oidclogin/settings/addUser/',
        del:'/api/oidclogin/settings/delUser/',
        toggle:'/api/oidclogin/settings/toggleUser/'
    });

    $("#saveAct").click(function(){
        saveFormToEndpoint("/api/oidclogin/settings/set", 'frm_GeneralSettings', function(){
            $("#responseMsg").removeClass("hidden").text("{{ lang._('Saved.') }}");
        });
    });

    $("#testAct").click(function(){
        ajaxCall("/api/oidclogin/settings/test", {}, function(data, status) {
            $("#responseMsg").removeClass("hidden").text(
                status === "success" && data.message ? data.message : "{{ lang._('Test did not run.') }}"
            );
        });
    });
});
</script>
