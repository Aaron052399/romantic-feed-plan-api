<template>
    <div>
        <el-dialog title="导入预览" width="80%" v-model="baTable.table.extend!.showPreImport" class="pre-import-dialog" top="5vh">
            <el-alert
                :title="'总计 ' + baTable.table.extend!.rowCount + ' 条数据' + (baTable.table.extend!.rowCount > 101 ? '，请目检导入数据的前面50条及最后50条~':'。')"
                class="import-tips"
                :closable="false"
                type="success"
            ></el-alert>
            <el-table :data="baTable.table.extend!.data" style="width: 100%" height="600">
                <el-table-column
                    v-for="(item,idx) in baTable.table.extend!.fields"
                    :key="idx"
                    :label="item.COLUMN_COMMENT ? item.COLUMN_COMMENT : ''"
                >
                    <el-table-column :prop="item.COLUMN_NAME" :label="item.COLUMN_NAME" />
                </el-table-column>
            </el-table>
            <template #footer>
                <div :style="'width: calc(100% - ' + baTable.form.labelWidth! / 1.8 + 'px)'">
                    <el-button @click="baTable.table.extend!.showPreImport = false">{{ $t('Cancel') }}</el-button>
                    <el-button v-blur :loading="state.importBtnLoading" type="primary" @click="onImport">导入</el-button>
                </div>
            </template>
        </el-dialog>
    </div>
</template>

<script setup lang="ts">
import { inject, reactive } from 'vue'
import type baTableClass from '/@/utils/baTable'
import { importXls } from '/@/api/backend/routine/dataimport'

const baTable = inject('baTable') as baTableClass

const state = reactive({
    importBtnLoading: false,
})

const onImport = () => {
    state.importBtnLoading = true
    importXls(baTable.form.items!.data_table, baTable.table.extend!.file_url)
        .then(() => {
            baTable.table.extend!.showPreImport = false
            baTable.toggleForm()
            baTable.onTableHeaderAction('refresh', {})
        })
        .finally(() => {
            state.importBtnLoading = false
        })
}
</script>

<style scoped lang="scss">
:deep(.pre-import-dialog) .el-dialog__body {
    padding: 10px 20px;
}
.import-tips {
    margin-bottom: 10px;
}
</style>
