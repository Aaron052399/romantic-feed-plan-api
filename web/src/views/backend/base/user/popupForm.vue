<template>
    <!-- 对话框表单 -->
    <!-- 建议使用 Prettier 格式化代码 -->
    <!-- el-form 内可以混用 el-form-item、FormItem、ba-input 等输入组件 -->
    <el-dialog
        class="ba-operate-dialog"
        :close-on-click-modal="false"
        :model-value="['Add', 'Edit'].includes(baTable.form.operate!)"
        @close="baTable.toggleForm"
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
                        :label="t('base.user.short_id')"
                        type="remoteSelect"
                        v-model="baTable.form.items!.short_id"
                        prop="short_id"
                        :input-attr="{ pk: 'id', field: 'name', remoteUrl: '' }"
                        :placeholder="t('Please select field', { field: t('base.user.short_id') })"
                    />
                    <FormItem
                        :label="t('base.user.nickname')"
                        type="string"
                        v-model="baTable.form.items!.nickname"
                        prop="nickname"
                        :placeholder="t('Please input field', { field: t('base.user.nickname') })"
                    />
                    <FormItem
                        :label="t('base.user.role')"
                        type="radio"
                        v-model="baTable.form.items!.role"
                        prop="role"
                        :input-attr="{ content: { baby: 'role baby', cook: 'role cook' } }"
                        :placeholder="t('Please select field', { field: t('base.user.role') })"
                    />
                    <FormItem :label="t('base.user.avatar')" type="image" v-model="baTable.form.items!.avatar" prop="avatar" />
                    <FormItem
                        :label="t('base.user.partner_id')"
                        type="remoteSelect"
                        v-model="baTable.form.items!.partner_id"
                        prop="partner_id"
                        :input-attr="{ pk: 'id', field: 'name', remoteUrl: '' }"
                        :placeholder="t('Please select field', { field: t('base.user.partner_id') })"
                    />
                    <FormItem
                        :label="t('base.user.notice_order_toggle')"
                        type="switch"
                        v-model="baTable.form.items!.notice_order_toggle"
                        prop="notice_order_toggle"
                    />
                    <FormItem
                        :label="t('base.user.notice_dish_ready_toggle')"
                        type="switch"
                        v-model="baTable.form.items!.notice_dish_ready_toggle"
                        prop="notice_dish_ready_toggle"
                    />
                    <FormItem
                        :label="t('base.user.notice_whisper_toggle')"
                        type="switch"
                        v-model="baTable.form.items!.notice_whisper_toggle"
                        prop="notice_whisper_toggle"
                    />
                    <FormItem
                        :label="t('base.user.sweetness')"
                        type="number"
                        v-model="baTable.form.items!.sweetness"
                        prop="sweetness"
                        :input-attr="{ step: 1 }"
                        :placeholder="t('Please input field', { field: t('base.user.sweetness') })"
                    />
                    <FormItem
                        :label="t('base.user.heart_bounce_toggle')"
                        type="switch"
                        v-model="baTable.form.items!.heart_bounce_toggle"
                        prop="heart_bounce_toggle"
                    />
                    <FormItem
                        :label="t('base.user.is_dark_toggle')"
                        type="switch"
                        v-model="baTable.form.items!.is_dark_toggle"
                        prop="is_dark_toggle"
                    />
                    <FormItem
                        :label="t('base.user.accent')"
                        type="radio"
                        v-model="baTable.form.items!.accent"
                        prop="accent"
                        :input-attr="{
                            content: {
                                pink: 'accent pink',
                                blue: 'accent blue',
                                tiffany: 'accent tiffany',
                                green: 'accent green',
                                purple: 'accent purple',
                                amber: 'accent amber',
                            },
                        }"
                        :placeholder="t('Please select field', { field: t('base.user.accent') })"
                    />
                    <FormItem
                        :label="t('base.user.delete_time')"
                        type="number"
                        v-model="baTable.form.items!.delete_time"
                        prop="delete_time"
                        :input-attr="{ step: 1 }"
                        :placeholder="t('Please input field', { field: t('base.user.delete_time') })"
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
    sweetness: [buildValidatorData({ name: 'number', title: t('base.user.sweetness') })],
    create_time: [buildValidatorData({ name: 'date', title: t('base.user.create_time') })],
    update_time: [buildValidatorData({ name: 'date', title: t('base.user.update_time') })],
    delete_time: [buildValidatorData({ name: 'number', title: t('base.user.delete_time') })],
})
</script>

<style scoped lang="scss"></style>
