<template>
    <div class="default-main ba-table-box">
        <el-alert class="ba-table-alert" v-if="baTable.table.remark" :title="baTable.table.remark" type="info" show-icon />

        <!-- 表格顶部菜单 -->
        <!-- 自定义按钮请使用插槽，甚至公共搜索也可以使用具名插槽渲染，参见文档 -->
        <TableHeader
            :buttons="['refresh', 'add', 'edit', 'delete', 'comSearch', 'quickSearch', 'columnDisplay']"
            :quick-search-placeholder="t('Quick search placeholder', { fields: t('base.user.quick Search Fields') })"
        ></TableHeader>

        <!-- 表格 -->
        <!-- 表格列有多种自定义渲染方式，比如自定义组件、具名插槽等，参见文档 -->
        <!-- 要使用 el-table 组件原有的属性，直接加在 Table 标签上即可 -->
        <Table ref="tableRef"></Table>

        <!-- 表单 -->
        <PopupForm />
    </div>
</template>

<script setup lang="ts">
import { onMounted, provide, useTemplateRef } from 'vue'
import { useI18n } from 'vue-i18n'
import PopupForm from './popupForm.vue'
import { baTableApi } from '/@/api/common'
import { defaultOptButtons } from '/@/components/table'
import TableHeader from '/@/components/table/header/index.vue'
import Table from '/@/components/table/index.vue'
import baTableClass from '/@/utils/baTable'

defineOptions({
    name: 'base/user',
})

const { t } = useI18n()
const tableRef = useTemplateRef('tableRef')
const optButtons: OptButton[] = defaultOptButtons(['edit', 'delete'])

/**
 * baTable 内包含了表格的所有数据且数据具备响应性，然后通过 provide 注入给了后代组件
 */
const baTable = new baTableClass(
    new baTableApi('/admin/base.User/'),
    {
        pk: 'id',
        column: [
            { type: 'selection', align: 'center', operator: false },
            { label: t('base.user.id'), prop: 'id', align: 'center', width: 70, operator: 'RANGE', sortable: 'custom' },
            { label: t('base.user.short_id'), prop: 'short_id', align: 'center', operatorPlaceholder: t('Fuzzy query'), operator: 'LIKE' },
            {
                label: t('base.user.nickname'),
                prop: 'nickname',
                align: 'center',
                operatorPlaceholder: t('Fuzzy query'),
                operator: 'LIKE',
                sortable: false,
            },
            {
                label: t('base.user.role'),
                prop: 'role',
                align: 'center',
                render: 'tag',
                operator: 'eq',
                sortable: false,
                replaceValue: { baby: 'role baby', cook: 'role cook' },
            },
            { label: t('base.user.avatar'), prop: 'avatar', align: 'center', render: 'image', operator: false },
            { label: t('base.user.partner_id'), prop: 'partner_id', align: 'center', operatorPlaceholder: t('Fuzzy query'), operator: 'LIKE' },
            {
                label: t('base.user.notice_order_toggle'),
                prop: 'notice_order_toggle',
                align: 'center',
                render: 'switch',
                operator: 'eq',
                sortable: false,
                replaceValue: {},
            },
            {
                label: t('base.user.notice_dish_ready_toggle'),
                prop: 'notice_dish_ready_toggle',
                align: 'center',
                render: 'switch',
                operator: 'eq',
                sortable: false,
                replaceValue: {},
            },
            {
                label: t('base.user.notice_whisper_toggle'),
                prop: 'notice_whisper_toggle',
                align: 'center',
                render: 'switch',
                operator: 'eq',
                sortable: false,
                replaceValue: {},
            },
            { label: t('base.user.sweetness'), prop: 'sweetness', align: 'center', operator: 'RANGE', sortable: false },
            {
                label: t('base.user.heart_bounce_toggle'),
                prop: 'heart_bounce_toggle',
                align: 'center',
                render: 'switch',
                operator: 'eq',
                sortable: false,
                replaceValue: {},
            },
            {
                label: t('base.user.is_dark_toggle'),
                prop: 'is_dark_toggle',
                align: 'center',
                render: 'switch',
                operator: 'eq',
                sortable: false,
                replaceValue: {},
            },
            {
                label: t('base.user.accent'),
                prop: 'accent',
                align: 'center',
                render: 'tag',
                operator: 'eq',
                sortable: false,
                replaceValue: {
                    pink: 'accent pink',
                    blue: 'accent blue',
                    tiffany: 'accent tiffany',
                    green: 'accent green',
                    purple: 'accent purple',
                    amber: 'accent amber',
                },
            },
            {
                label: t('base.user.create_time'),
                prop: 'create_time',
                align: 'center',
                render: 'datetime',
                operator: 'RANGE',
                sortable: 'custom',
                width: 160,
                timeFormat: 'yyyy-mm-dd hh:MM:ss',
            },
            {
                label: t('base.user.update_time'),
                prop: 'update_time',
                align: 'center',
                render: 'datetime',
                operator: 'RANGE',
                sortable: 'custom',
                width: 160,
                timeFormat: 'yyyy-mm-dd hh:MM:ss',
            },
            { label: t('base.user.delete_time'), prop: 'delete_time', align: 'center', operator: 'RANGE', sortable: false },
            { label: t('Operate'), align: 'center', width: 100, render: 'buttons', buttons: optButtons, operator: false },
        ],
        dblClickNotEditColumn: [
            undefined,
            'notice_order_toggle',
            'notice_dish_ready_toggle',
            'notice_whisper_toggle',
            'heart_bounce_toggle',
            'is_dark_toggle',
        ],
    },
    {
        defaultItems: {
            notice_order_toggle: '1',
            notice_dish_ready_toggle: '1',
            notice_whisper_toggle: '1',
            sweetness: 60,
            heart_bounce_toggle: '1',
            accent: 'pink',
        },
    }
)

provide('baTable', baTable)

onMounted(() => {
    baTable.table.ref = tableRef.value
    baTable.mount()
    baTable.getData()?.then(() => {
        baTable.initSort()
        baTable.dragSort()
    })
})
</script>

<style scoped lang="scss"></style>
