<template>
    <div class="default-main ba-table-box">
        <el-alert class="ba-table-alert" v-if="baTable.table.remark" :title="baTable.table.remark" type="info" show-icon />

        <!-- 表格顶部菜单 -->
        <!-- 自定义按钮请使用插槽，甚至公共搜索也可以使用具名插槽渲染，参见文档 -->
        <TableHeader
            :buttons="['refresh', 'add', 'edit', 'delete', 'comSearch', 'quickSearch', 'columnDisplay']"
            :quick-search-placeholder="t('Quick search placeholder', { fields: t('dish.index.quick Search Fields') })"
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
    name: 'dish/index',
})

const { t } = useI18n()
const tableRef = useTemplateRef('tableRef')
const optButtons: OptButton[] = defaultOptButtons(['edit', 'delete'])

/**
 * baTable 内包含了表格的所有数据且数据具备响应性，然后通过 provide 注入给了后代组件
 */
const baTable = new baTableClass(
    new baTableApi('/admin/Dish/'),
    {
        pk: 'id',
        column: [
            { type: 'selection', align: 'center', operator: false },
            { label: t('dish.index.id'), prop: 'id', align: 'center', width: 70, operator: 'RANGE', sortable: 'custom' },
            { label: t('dish.index.name'), prop: 'name', align: 'center', operatorPlaceholder: t('Fuzzy query'), operator: 'LIKE', sortable: false },
            { label: t('dish.index.cover_image'), prop: 'cover_image', align: 'center', render: 'image', operator: false },
            { label: t('dish.index.price'), prop: 'price', align: 'center', operator: 'RANGE', sortable: false },
            { label: t('dish.index.category_id'), prop: 'category_id', align: 'center', operatorPlaceholder: t('Fuzzy query'), operator: 'LIKE' },
            {
                label: t('dish.index.is_favorite_toggle'),
                prop: 'is_favorite_toggle',
                align: 'center',
                render: 'switch',
                operator: 'eq',
                sortable: false,
                replaceValue: {},
            },
            {
                label: t('dish.index.is_new_toggle'),
                prop: 'is_new_toggle',
                align: 'center',
                render: 'switch',
                operator: 'eq',
                sortable: false,
                replaceValue: {},
            },
            {
                label: t('dish.index.is_recommend_toggle'),
                prop: 'is_recommend_toggle',
                align: 'center',
                render: 'switch',
                operator: 'eq',
                sortable: false,
                replaceValue: {},
            },
            { label: t('dish.index.cook_time'), prop: 'cook_time', align: 'center', operator: 'RANGE', sortable: false },
            { label: t('dish.index.created_by'), prop: 'created_by', align: 'center', operator: 'RANGE', sortable: false },
            { label: t('dish.index.avg_score'), prop: 'avg_score', align: 'center', operator: 'RANGE', sortable: false },
            { label: t('dish.index.rating_count'), prop: 'rating_count', align: 'center', operator: 'RANGE', sortable: false },
            {
                label: t('dish.index.create_time'),
                prop: 'create_time',
                align: 'center',
                render: 'datetime',
                operator: 'RANGE',
                sortable: 'custom',
                width: 160,
                timeFormat: 'yyyy-mm-dd hh:MM:ss',
            },
            {
                label: t('dish.index.update_time'),
                prop: 'update_time',
                align: 'center',
                render: 'datetime',
                operator: 'RANGE',
                sortable: 'custom',
                width: 160,
                timeFormat: 'yyyy-mm-dd hh:MM:ss',
            },
            { label: t('dish.index.delete_time'), prop: 'delete_time', align: 'center', operator: 'RANGE', sortable: false },
            { label: t('Operate'), align: 'center', width: 100, render: 'buttons', buttons: optButtons, operator: false },
        ],
        dblClickNotEditColumn: [undefined, 'is_favorite_toggle', 'is_new_toggle', 'is_recommend_toggle'],
    },
    {
        defaultItems: {},
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
