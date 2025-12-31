<template>
    <div class="default-main ba-table-box">
        <el-alert class="ba-table-alert" v-if="baTable.table.remark" :title="baTable.table.remark" type="info" show-icon />

        <!-- 表格顶部菜单 -->
        <!-- 自定义按钮请使用插槽，甚至公共搜索也可以使用具名插槽渲染，参见文档 -->
        <TableHeader
            :buttons="['refresh', 'add', 'edit', 'delete', 'comSearch', 'quickSearch', 'columnDisplay']"
            :quick-search-placeholder="t('Quick search placeholder', { fields: t('user.noticeItem.quick Search Fields') })"
        ></TableHeader>

        <!-- 表格 -->
        <!-- 表格列有多种自定义渲染方式，比如自定义组件、具名插槽等，参见文档 -->
        <!-- 要使用 el-table 组件原有的属性，直接加在 Table 标签上即可 -->
        <Table ref="tableRef">
            <template #typeNames>
                <!-- 在插槽内，您可以随意发挥，通常使用 el-table-column 组件 -->
                <el-table-column align="center" prop="typeNamesTable" label="支持类型" width="180">
                    <template #default="scope">
                        <el-tag class="type-tags" v-for="(tag, idx) in scope.row.typeNamesTable" :key="idx">{{ tag }}</el-tag>
                    </template>
                </el-table-column>
            </template>
        </Table>

        <!-- 表单 -->
        <PopupForm />
    </div>
</template>

<script setup lang="ts">
import { onMounted, provide, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import PopupForm from './popupForm.vue'
import { baTableApi } from '/@/api/common'
import { defaultOptButtons } from '/@/components/table'
import TableHeader from '/@/components/table/header/index.vue'
import Table from '/@/components/table/index.vue'
import baTableClass from '/@/utils/baTable'

defineOptions({
    name: 'user/noticeItem',
})

const { t } = useI18n()
const tableRef = ref()
const optButtons: OptButton[] = defaultOptButtons(['edit', 'delete'])

const getNoticeType = (values: anyObj) => {
    const noticeTypeApi = new baTableApi('/admin/user.NoticeType/')
    baTable.form.extend!.noticeTypeLoading = true
    noticeTypeApi.index().then((res) => {
        const noticeType: anyObj = {}
        for (const key in res.data.list) {
            noticeType[res.data.list[key]['name']] = {
                title: res.data.list[key]['title'],
                value: values && parseInt(values[res.data.list[key]['name']]) == 1 ? true : false,
            }
        }
        baTable.form.extend!.noticeType = noticeType
        baTable.form.extend!.noticeTypeLoading = false
    })
}

/**
 * baTable 内包含了表格的所有数据且数据具备响应性，然后通过 provide 注入给了后代组件
 */
const baTable = new baTableClass(
    new baTableApi('/admin/user.NoticeItem/'),
    {
        pk: 'id',
        column: [
            { type: 'selection', align: 'center', operator: false },
            { label: t('user.noticeItem.id'), prop: 'id', align: 'center', width: 70, operator: 'RANGE', sortable: 'custom' },
            {
                label: t('user.noticeItem.name'),
                prop: 'name',
                align: 'center',
                operatorPlaceholder: t('Fuzzy query'),
                operator: 'LIKE',
                sortable: false,
            },
            {
                label: t('user.noticeItem.title'),
                prop: 'title',
                align: 'center',
                operatorPlaceholder: t('Fuzzy query'),
                operator: 'LIKE',
                sortable: false,
            },
            {
                label: t('user.noticeItem.module'),
                prop: 'module',
                align: 'center',
                operatorPlaceholder: t('Fuzzy query'),
                operator: 'LIKE',
                sortable: false,
            },
            {
                label: t('user.noticeItem.group'),
                prop: 'group',
                align: 'center',
                operatorPlaceholder: t('Fuzzy query'),
                operator: 'LIKE',
                sortable: false,
            },
            {
                label: t('user.noticeItem.typenamestable__title'),
                align: 'center',
                slotName: 'typeNames',
                render: 'slot',
                operator: false,
            },
            {
                label: t('user.noticeItem.typenamestable__title'),
                prop: 'type_names',
                align: 'center',
                operator: 'FIND_IN_SET',
                show: false,
                comSearchRender: 'remoteSelect',
                remote: { pk: 'ba_user_notice_type.name', field: 'title', remoteUrl: '/admin/user.NoticeType/index', multiple: true },
            },
            {
                label: t('user.noticeItem.status'),
                prop: 'status',
                align: 'center',
                render: 'switch',
                operator: 'eq',
                sortable: false,
                replaceValue: { '0': t('user.noticeItem.status 0'), '1': t('user.noticeItem.status 1') },
            },
            {
                label: t('user.noticeItem.create_time'),
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
        dblClickNotEditColumn: [undefined, 'status'],
    },
    {
        defaultItems: { status: '1' },
    },
    {
        onSubmit() {
            const noticeTypeDefaults: anyObj = {}
            for (const key in baTable.form.extend!.noticeType) {
                noticeTypeDefaults[key] = baTable.form.extend!.noticeType[key].value
            }
            baTable.form.items!.type_default_value = noticeTypeDefaults
        },
        toggleForm({ operate }) {
            if (operate == 'Add') {
                getNoticeType({})
            }
        },
    },
    {
        requestEdit({ res }) {
            getNoticeType(res.data.row.type_default_value)
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
</script>

<style scoped lang="scss">
.type-tags {
    margin: 0 6px 6px 0;
}
</style>
