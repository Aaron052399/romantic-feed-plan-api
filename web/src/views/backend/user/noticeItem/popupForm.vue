<template>
    <!-- 对话框表单 -->
    <!-- 建议使用 Prettier 格式化代码 -->
    <!-- el-form 内可以混用 el-form-item、FormItem、ba-input 等输入组件 -->
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
                :style="config.layout.shrink ? '':'width: calc(100% - ' + baTable.form.labelWidth! / 2 + 'px)'"
            >
                <el-form
                    v-if="!baTable.form.loading"
                    ref="formRef"
                    @submit.prevent=""
                    @keyup.enter="baTable.onSubmit(formRef)"
                    :model="baTable.form.items"
                    :label-position="config.layout.shrink ? 'top' : 'right'"
                    :label-width="baTable.form.labelWidth + 'px'"
                    :rules="rules"
                >
                    <FormItem
                        :label="t('user.noticeItem.name')"
                        type="string"
                        v-model="baTable.form.items!.name"
                        prop="name"
                        :placeholder="t('Please input field', { field: t('user.noticeItem.name') })"
                    />
                    <FormItem
                        :label="t('user.noticeItem.title')"
                        type="string"
                        v-model="baTable.form.items!.title"
                        prop="title"
                        :placeholder="t('Please input field', { field: t('user.noticeItem.title') })"
                    />
                    <FormItem
                        :label="t('user.noticeItem.module')"
                        type="string"
                        v-model="baTable.form.items!.module"
                        prop="module"
                        :placeholder="t('Please input field', { field: t('user.noticeItem.module') })"
                        :attr="{
                            blockHelp: '用于对通知开关展示分类，同一模块的开关将显示在一起',
                        }"
                    />
                    <FormItem
                        :label="t('user.noticeItem.group')"
                        type="string"
                        v-model="baTable.form.items!.group"
                        prop="group"
                        :placeholder="t('Please input field', { field: t('user.noticeItem.group') })"
                        :attr="{
                            blockHelp: '用于对通知开关进行更细致的展示分组，同一模块、同一分组的开关将显示在一起',
                        }"
                    />
                    <FormItem
                        :label="t('user.noticeItem.type_names')"
                        type="remoteSelects"
                        v-model="baTable.form.items!.type_names"
                        prop="type_names"
                        :input-attr="{
                            pk: 'notice_type.name',
                            field: 'title',
                            'remote-url': '/admin/user.NoticeType/index',
                        }"
                        :attr="{
                            blockHelp: '通知发送由开发者实现',
                        }"
                        :placeholder="t('Please select field', { field: t('user.noticeItem.type_names') })"
                    />
                    <template v-if="!baTable.form.extend!.noticeTypeLoading">
                        <el-form-item
                            v-for="(item, idx) in baTable.form.items!.type_names"
                            :key="idx"
                            :label="baTable.form.extend!.noticeType[item].title"
                        >
                            <el-checkbox v-model="baTable.form.extend!.noticeType[item].value" label="默认开启此通知" />
                        </el-form-item>
                    </template>
                    <FormItem
                        :label="t('user.noticeItem.remark')"
                        type="textarea"
                        v-model="baTable.form.items!.remark"
                        prop="remark"
                        :input-attr="{ rows: 3 }"
                        @keyup.enter.stop=""
                        @keyup.ctrl.enter="baTable.onSubmit(formRef)"
                        :placeholder="t('Please input field', { field: t('user.noticeItem.remark') })"
                    />
                    <FormItem
                        :label="t('user.noticeItem.status')"
                        type="switch"
                        v-model="baTable.form.items!.status"
                        prop="status"
                        :data="{ content: { '0': t('user.noticeItem.status 0'), '1': t('user.noticeItem.status 1') } }"
                    />
                </el-form>
            </div>
        </el-scrollbar>
        <template #footer>
            <div :style="'width: calc(100% - ' + baTable.form.labelWidth! / 1.8 + 'px)'">
                <el-button @click="baTable.toggleForm()">{{ t('Cancel') }}</el-button>
                <el-button v-blur :loading="baTable.form.submitLoading" @click="baTable.onSubmit(formRef)" type="primary">
                    {{ baTable.form.operateIds && baTable.form.operateIds.length > 1 ? t('Save and edit next item') : t('Save') }}
                </el-button>
            </div>
        </template>
    </el-dialog>
</template>

<script setup lang="ts">
import type { FormInstance, FormItemRule } from 'element-plus'
import { inject, reactive, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import FormItem from '/@/components/formItem/index.vue'
import { useConfig } from '/@/stores/config'
import type baTableClass from '/@/utils/baTable'
import { buildValidatorData } from '/@/utils/validate'

const config = useConfig()
const formRef = ref<FormInstance>()
const baTable = inject('baTable') as baTableClass

const { t } = useI18n()

const rules: Partial<Record<string, FormItemRule[]>> = reactive({
    create_time: [buildValidatorData({ name: 'date', title: t('user.noticeItem.create_time') })],
})
</script>

<style scoped lang="scss"></style>
