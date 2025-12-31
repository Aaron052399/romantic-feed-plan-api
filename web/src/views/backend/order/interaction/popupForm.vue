<template>
    <!-- 对话框表单 -->
    <!-- 建议使用 Prettier 格式化代码 -->
    <!-- el-form 内可以混用 el-form-item、FormItem、ba-input 等输入组件 -->
    <el-dialog
        class="ba-operate-dialog"
        :close-on-click-modal="false"
        :model-value="['Add', 'Edit'].includes(baTable.form.operate!)"
        @close="baTable.toggleForm"
        width="70%"
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
                :style="config.layout.shrink ? '' : 'width: calc(100% - ' + baTable.form.labelWidth! / 2 + 'px)'"
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
                        :label="t('order.interaction.order_id')"
                        type="remoteSelect"
                        v-model="baTable.form.items!.order_id"
                        prop="order_id"
                        :input-attr="{ pk: 'id', field: 'name', remoteUrl: '' }"
                        :placeholder="t('Please select field', { field: t('order.interaction.order_id') })"
                    />
                    <FormItem
                        :label="t('order.interaction.operator_id')"
                        type="remoteSelect"
                        v-model="baTable.form.items!.operator_id"
                        prop="operator_id"
                        :input-attr="{ pk: 'id', field: 'name', remoteUrl: '' }"
                        :placeholder="t('Please select field', { field: t('order.interaction.operator_id') })"
                    />
                    <FormItem
                        :label="t('order.interaction.operator_role')"
                        type="radio"
                        v-model="baTable.form.items!.operator_role"
                        prop="operator_role"
                        :input-attr="{ content: { baby: 'operator_role baby', cook: 'operator_role cook' } }"
                        :placeholder="t('Please select field', { field: t('order.interaction.operator_role') })"
                    />
                    <FormItem
                        :label="t('order.interaction.action_type')"
                        type="radio"
                        v-model="baTable.form.items!.action_type"
                        prop="action_type"
                        :input-attr="{
                            content: {
                                later: 'action_type later',
                                immediate: 'action_type immediate',
                                completed: 'action_type completed',
                                cancel: 'action_type cancel',
                                comment: 'action_type comment',
                                score: 'action_type score',
                            },
                        }"
                        :placeholder="t('Please select field', { field: t('order.interaction.action_type') })"
                    />
                    <FormItem
                        :label="t('order.interaction.content')"
                        type="editor"
                        v-model="baTable.form.items!.content"
                        prop="content"
                        @keyup.enter.stop=""
                        @keyup.ctrl.enter="baTable.onSubmit(formRef)"
                        :placeholder="t('Please input field', { field: t('order.interaction.content') })"
                    />
                    <FormItem
                        :label="t('order.interaction.score')"
                        type="number"
                        v-model="baTable.form.items!.score"
                        prop="score"
                        :input-attr="{ step: 1 }"
                        :placeholder="t('Please input field', { field: t('order.interaction.score') })"
                    />
                    <FormItem
                        :label="t('order.interaction.delete_time')"
                        type="number"
                        v-model="baTable.form.items!.delete_time"
                        prop="delete_time"
                        :input-attr="{ step: 1 }"
                        :placeholder="t('Please input field', { field: t('order.interaction.delete_time') })"
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
import type { FormItemRule } from 'element-plus'
import { inject, reactive, useTemplateRef } from 'vue'
import { useI18n } from 'vue-i18n'
import FormItem from '/@/components/formItem/index.vue'
import { useConfig } from '/@/stores/config'
import type baTableClass from '/@/utils/baTable'
import { buildValidatorData } from '/@/utils/validate'

const config = useConfig()
const formRef = useTemplateRef('formRef')
const baTable = inject('baTable') as baTableClass

const { t } = useI18n()

const rules: Partial<Record<string, FormItemRule[]>> = reactive({
    content: [buildValidatorData({ name: 'editorRequired', title: t('order.interaction.content') })],
    score: [buildValidatorData({ name: 'number', title: t('order.interaction.score') })],
    create_time: [buildValidatorData({ name: 'date', title: t('order.interaction.create_time') })],
    update_time: [buildValidatorData({ name: 'date', title: t('order.interaction.update_time') })],
    delete_time: [buildValidatorData({ name: 'number', title: t('order.interaction.delete_time') })],
})
</script>

<style scoped lang="scss"></style>
