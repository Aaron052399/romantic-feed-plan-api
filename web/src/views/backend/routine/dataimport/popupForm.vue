<template>
    <!-- 对话框表单 -->
    <el-dialog
        class="ba-operate-dialog"
        :close-on-click-modal="false"
        :model-value="['Add', 'Edit'].includes(baTable.form.operate!)"
        @close="baTable.toggleForm"
        width="50%"
    >
        <template #header>
            <div class="title" v-drag="['.ba-operate-dialog', '.el-dialog__header']" v-zoom="'.ba-operate-dialog'">
                {{ baTable.form.operate ? t(baTable.form.operate) : '' }}
            </div>
        </template>
        <el-scrollbar v-loading="baTable.form.loading" class="ba-table-form-scrollbar">
            <div
                class="ba-operate-form"
                :class="'ba-' + baTable.form.operate + '-form'"
                :style="'width: calc(100% - ' + baTable.form.labelWidth! / 2 + 'px)'"
            >
                <el-form
                    v-if="!baTable.form.loading"
                    ref="formRef"
                    @submit.prevent=""
                    @keyup.enter="baTable.onSubmit(formRef)"
                    :model="baTable.form.items"
                    label-position="right"
                    :label-width="baTable.form.labelWidth + 'px'"
                    :rules="rules"
                >
                    <FormItem
                        :label="t('routine.dataimport.data_table')"
                        type="select"
                        v-model="baTable.form.items!.data_table"
                        prop="data_table"
                        :data="{ content: baTable.form.extend!.tableList }"
                        :placeholder="t('Please select field', { field: t('routine.dataimport.data_table') })"
                    />
                    <el-form-item label="导入模板">
                        <div v-if="baTable.form.items!.data_table" @click="onDownloadImportTemplate" class="template-text-success">
                            点击下载导入模板文件
                        </div>
                        <div v-else class="template-text-info">请先选择数据表后可下载导入模板文件</div>
                    </el-form-item>
                    <el-form-item label="导入数据" prop="file">
                        <el-upload class="upload-xls" :show-file-list="false" accept=".xlsx,.xls" drag :auto-upload="false" @change="uploadXls">
                            <div v-if="baTable.form.extend!.fileUploadStatus == 'wait'" class="upload-file-box">
                                <Icon size="50px" color="#909399" name="el-icon-UploadFilled" />
                                <div class="el-upload__text">拖拽 .xls[x] 文件至此处 <em>或点击我上传</em></div>
                            </div>
                            <div v-if="baTable.form.extend!.fileUploadStatus == 'uploading'" class="upload-file-box">
                                <Icon size="50px" color="#ffffff" v-loading="true" name="el-icon-UploadFilled" />
                                <div class="el-upload__text">上传中...</div>
                            </div>
                            <div v-if="baTable.form.extend!.fileUploadStatus == 'success'" class="upload-file-box">
                                <Icon size="50px" color="#ffffff" v-loading="true" name="el-icon-UploadFilled" />
                                <div class="el-upload__text">文件上传成功，正在处理...</div>
                            </div>
                        </el-upload>
                    </el-form-item>
                    <el-form-item v-if="baTable.form.items!.data_table">
                        <el-alert title="提示" class="import-tips" type="success">
                            <p>1、导入数据内无`主键`字段或`主键留空`则可以使用主键自动递增</p>
                            <p>2、若数据表有设计`create_time`、`update_time`字段且导入数据内未设定这两个字段的值，则自动填充</p>
                            <p>
                                3、所有已设定值的导入数据，将原样导入，比如：`create_time`字段，数据表设计为时间戳则请导入时间戳，`status:0=隐藏,1=开启`，请导入`0`或`1`
                            </p>
                        </el-alert>
                    </el-form-item>
                </el-form>
            </div>
        </el-scrollbar>
        <template #footer>
            <div :style="'width: calc(100% - ' + baTable.form.labelWidth! / 1.8 + 'px)'">
                <el-button @click="baTable.toggleForm('')">{{ t('Cancel') }}</el-button>
            </div>
        </template>
    </el-dialog>
</template>

<script setup lang="ts">
import { reactive, ref, inject, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import type baTableClass from '/@/utils/baTable'
import FormItem from '/@/components/formItem/index.vue'
import type { ElForm, FormItemRule, UploadFile } from 'element-plus'
import { buildValidatorData } from '/@/utils/validate'
import { downloadImportTemplate, handleXls } from '/@/api/backend/routine/dataimport'
import { fileUpload } from '/@/api/common'
import NProgress from 'nprogress'
import 'nprogress/nprogress.css'

const formRef = ref<InstanceType<typeof ElForm>>()
const baTable = inject('baTable') as baTableClass

const { t } = useI18n()

const rules: Partial<Record<string, FormItemRule[]>> = reactive({
    data_table: [buildValidatorData({ name: 'required', message: '请选择数据表' })],
    file: [buildValidatorData({ name: 'required', message: '请选择导入数据文件' })],
})

const uploadXls = (file: UploadFile) => {
    if (!file || !file.raw) return

    NProgress.configure({ showSpinner: false })
    NProgress.start()

    baTable.form.extend!.fileUploadStatus = 'uploading'

    let fd = new FormData()
    fd.append('file', file.raw!)
    fileUpload(fd, {}, true, {
        onUploadProgress: (evt) => {
            NProgress.set(evt.progress!)
        },
    })
        .then((res) => {
            if (res.code == 1) {
                handleXls(baTable.form.items!.data_table, res.data.file.url)
                    .then((handleRes) => {
                        baTable.table.extend!.showPreImport = true
                        baTable.table.extend!.fields = handleRes.data.fields
                        baTable.table.extend!.rowCount = handleRes.data.rowCount
                        baTable.table.extend!.data = handleRes.data.data
                        baTable.table.extend!.file_url = res.data.file.url
                        baTable.form.extend!.fileUploadStatus = 'success'
                    })
                    .catch(() => {
                        baTable.form.extend!.fileUploadStatus = 'wait'
                    })
            }
        })
        .catch(() => {
            baTable.form.extend!.fileUploadStatus = 'wait'
        })
        .finally(() => {
            NProgress.done()
        })
}

const onDownloadImportTemplate = () => {
    window.location.href = downloadImportTemplate(baTable.form.items!.data_table)
}

watch(
    () => baTable.table.extend!.showPreImport,
    (newVal) => {
        if (newVal === false) {
            baTable.form.extend!.fileUploadStatus = 'wait'
        }
    }
)
</script>

<style scoped lang="scss">
.template-text-success {
    color: var(--el-color-success);
    cursor: pointer;
    user-select: none;
}
.template-text-info {
    color: var(--el-color-info);
}
.upload-xls {
    width: 100%;
}
.import-tips {
    line-height: 16px;
}
</style>
