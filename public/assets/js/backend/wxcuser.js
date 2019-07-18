define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'wxcuser/index' + location.search,
                    add_url: 'wxcuser/add',
                    edit_url: 'wxcuser/edit',
                    del_url: 'wxcuser/del',
                    multi_url: 'wxcuser/multi',
                    table: 'wxcuser',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        {field: 'openid', title: __('Openid')},
                        {field: 'nickname', title: __('Nickname')},
                        {field: 'sex', title: __('Sex')},
                        {field: 'avatar', title: __('Avatar'), events: Table.api.events.image, formatter: Table.api.formatter.image},
                        {field: 'email', title: __('Email')},
                        {field: 'moblie', title: __('Moblie')},
                        {field: 'company_name', title: __('Company_name')},
                        {field: 'user_name', title: __('User_name')},
                        {field: 'invited_code', title: __('Invited_code')},
                        {field: 'type', title: __('Type')},
                        {field: 'father_id', title: __('Father_id')},
                        {field: 'grandpa_id', title: __('Grandpa_id')},
                        {field: 'userFee', title: __('Userfee'), operate:'BETWEEN'},
                        {field: 'score', title: __('Score')},
                        {field: 'createtime', title: __('Createtime')},
                        {field: 'updatetime', title: __('Updatetime'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'team.userId', title: __('Team.userid')},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        add: function () {
            Controller.api.bindevent();
        },
        edit: function () {
            Controller.api.bindevent();
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});