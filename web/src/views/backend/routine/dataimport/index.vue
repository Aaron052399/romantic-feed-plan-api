<template>
    <div class="default-main ba-table-box">
        <el-alert class="ba-table-alert" v-if="baTable.table.remark" :title="baTable.table.remark" type="info" show-icon />

        <!-- 表格顶部菜单 -->
        <TableHeader
            :buttons="['refresh', 'add', 'delete', 'comSearch', 'quickSearch', 'columnDisplay']"
            :quick-search-placeholder="t('Quick search placeholder', { fields: t('routine.dataimport.quick Search Fields') })"
        />

        <!-- 表格 -->
        <!-- 要使用`el-table`组件原有的属性，直接加在Table标签上即可 -->
        <Table ref="tableRef" />

        <!-- 表单 -->
        <PopupForm />

        <PreImportDialog />
    </div>
</template>

<script setup lang="ts">
import { ref, provide, onMounted } from 'vue'
import baTableClass from '/@/utils/baTable'
import { defaultOptButtons } from '/@/components/table'
import { baTableApi } from '/@/api/common'
import { useI18n } from 'vue-i18n'
import PopupForm from './popupForm.vue'
import PreImportDialog from './preImportDialog.vue'
import Table from '/@/components/table/index.vue'
import TableHeader from '/@/components/table/header/index.vue'
import { add } from '/@/api/backend/routine/dataimport'

const { t } = useI18n()
const tableRef = ref()
const optButtons = defaultOptButtons(['delete'])
const baTable = new baTableClass(
    new baTableApi('/admin/routine.Dataimport/'),
    {
        pk: 'id',
        column: [
            { type: 'selection', align: 'center', operator: false },
            { label: t('routine.dataimport.id'), prop: 'id', align: 'center', width: 70, operator: 'RANGE', sortable: 'custom' },
            {
                label: t('routine.dataimport.data_table'),
                prop: 'data_table',
                align: 'center',
                operatorPlaceholder: t('Fuzzy query'),
                operator: 'LIKE',
                sortable: false,
            },
            {
                label: t('routine.dataimport.admin_id'),
                prop: 'admin.username',
                align: 'center',
                operatorPlaceholder: t('Fuzzy query'),
                render: 'tags',
                operator: 'LIKE',
                replaceValue: {},
            },
            { label: t('routine.dataimport.records'), prop: 'records', align: 'center', operator: 'RANGE', sortable: true },
            {
                label: t('routine.dataimport.import_success_records'),
                prop: 'import_success_records',
                align: 'center',
                operator: 'RANGE',
                sortable: true,
            },
            {
                label: t('routine.dataimport.radio'),
                prop: 'radio',
                align: 'center',
                render: 'tag',
                operator: '=',
                sortable: false,
                replaceValue: {
                    upload: t('routine.dataimport.radio upload'),
                    import: t('routine.dataimport.radio import'),
                    cancel: t('routine.dataimport.radio cancel'),
                },
            },
            {
                label: t('routine.dataimport.create_time'),
                prop: 'create_time',
                align: 'center',
                render: 'datetime',
                operator: 'RANGE',
                sortable: 'custom',
                width: 160,
                timeFormat: 'yyyy-mm-dd hh:MM:ss',
            },
            { label: t('Operate'), align: 'center', width: 100, render: 'buttons', buttons: optButtons, operator: false },
        ],
        dblClickNotEditColumn: [undefined],
    },
    {
        defaultItems: { radio: 'upload' },
    },
    {
        toggleForm: ({ operate }) => {
            if (operate == 'Add') {
                baTable.form.loading = true
                baTable.form.extend!.fileUploadStatus = 'wait'
                add()
                    .then((res) => {
                        baTable.form.extend!.tableList = res.data.tables
                    })
                    .finally(() => {
                        baTable.form.loading = false
                    })
            }
        },
    }
)

provide('baTable', baTable)

onMounted(() => {
    baTable.table.ref = tableRef.value
    baTable.mount()
    baTable.getIndex()?.then(() => {
        baTable.initSort()
        baTable.dragSort()
    })
})

defineOptions({
    name: 'routine/dataimport',
})
</script>

<style scoped lang="scss"></style>
