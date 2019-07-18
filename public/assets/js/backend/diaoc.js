define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'diaoc/index' + location.search,
                    add_url: 'diaoc/add',
                    edit_url: 'diaoc/edit',
                    del_url: 'diaoc/del',
                    multi_url: 'diaoc/multi',
                    table: 'diaoc',
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
                        {field: 'name', title: __('Name')},
                        {field: 'hyname', title: __('Hyname')},
                        {field: 'station', title: __('Station')},
                        {field: 'job', title: __('Job')},
                        {field: 'mobile', title: __('Mobile')},
                        {field: 'skillname', title: __('Skillname')},
                        {field: 'uname', title: __('Uname')},
                        {field: 'qyname', title: __('Qyname')},
                        {field: 'price', title: __('Price'), operate:'BETWEEN'},
                        {field: 'typeName', title: __('Typename')},
                        {field: 'description', title: __('Description')},
                        {field: 'hyId', title: __('Hyid')},
                        {field: 'needName', title: __('Needname')},
                        {field: 'createtime', title: __('Createtime'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'updatetime', title: __('Updatetime'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
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