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
                        :label="t('dish.index.name')"
                        type="string"
                        v-model="baTable.form.items!.name"
                        prop="name"
                        :placeholder="t('Please input field', { field: t('dish.index.name') })"
                    />
                    <FormItem :label="t('dish.index.cover_image')" type="image" v-model="baTable.form.items!.cover_image" prop="cover_image" />
                    <FormItem
                        :label="t('dish.index.price')"
                        type="number"
                        v-model="baTable.form.items!.price"
                        prop="price"
                        :input-attr="{ step: 1 }"
                        :placeholder="t('Please input field', { field: t('dish.index.price') })"
                    />
                    <FormItem
                        :label="t('dish.index.description')"
                        type="textarea"
                        v-model="baTable.form.items!.description"
                        prop="description"
                        :input-attr="{ rows: 3 }"
                        @keyup.enter.stop=""
                        @keyup.ctrl.enter="baTable.onSubmit(formRef)"
                        :placeholder="t('Please input field', { field: t('dish.index.description') })"
                    />
                    <FormItem
                        :label="t('dish.index.category_id')"
                        type="remoteSelect"
                        v-model="baTable.form.items!.category_id"
                        prop="category_id"
                        :input-attr="{ pk: 'id', field: 'name', remoteUrl: '' }"
                        :placeholder="t('Please select field', { field: t('dish.index.category_id') })"
                    />
                    <FormItem
                        :label="t('dish.index.is_favorite_toggle')"
                        type="switch"
                        v-model="baTable.form.items!.is_favorite_toggle"
                        prop="is_favorite_toggle"
                    />
                    <FormItem :label="t('dish.index.is_new_toggle')" type="switch" v-model="baTable.form.items!.is_new_toggle" prop="is_new_toggle" />
                    <FormItem
                        :label="t('dish.index.is_recommend_toggle')"
                        type="switch"
                        v-model="baTable.form.items!.is_recommend_toggle"
                        prop="is_recommend_toggle"
                    />
                    <FormItem
                        :label="t('dish.index.cook_time')"
                        type="number"
                        v-model="baTable.form.items!.cook_time"
                        prop="cook_time"
                        :input-attr="{ step: 1 }"
                        :placeholder="t('Please input field', { field: t('dish.index.cook_time') })"
                    />
                    <FormItem
                        :label="t('dish.index.created_by')"
                        type="number"
                        v-model="baTable.form.items!.created_by"
                        prop="created_by"
                        :input-attr="{ step: 1 }"
                        :placeholder="t('Please input field', { field: t('dish.index.created_by') })"
                    />
                    <FormItem
                        :label="t('dish.index.avg_score')"
                        type="number"
                        v-model="baTable.form.items!.avg_score"
                        prop="avg_score"
                        :input-attr="{ step: 1 }"
                        :placeholder="t('Please input field', { field: t('dish.index.avg_score') })"
                    />
                    <FormItem
                        :label="t('dish.index.rating_count')"
                        type="number"
                        v-model="baTable.form.items!.rating_count"
                        prop="rating_count"
                        :input-attr="{ step: 1 }"
                        :placeholder="t('Please input field', { field: t('dish.index.rating_count') })"
                    />
                    <FormItem
                        :label="t('dish.index.delete_time')"
                        type="number"
                        v-model="baTable.form.items!.delete_time"
                        prop="delete_time"
                        :input-attr="{ step: 1 }"
                        :placeholder="t('Please input field', { field: t('dish.index.delete_time') })"
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
    price: [buildValidatorData({ name: 'number', title: t('dish.index.price') })],
    cook_time: [buildValidatorData({ name: 'number', title: t('dish.index.cook_time') })],
    created_by: [buildValidatorData({ name: 'number', title: t('dish.index.created_by') })],
    avg_score: [buildValidatorData({ name: 'number', title: t('dish.index.avg_score') })],
    rating_count: [buildValidatorData({ name: 'number', title: t('dish.index.rating_count') })],
    create_time: [buildValidatorData({ name: 'date', title: t('dish.index.create_time') })],
    update_time: [buildValidatorData({ name: 'date', title: t('dish.index.update_time') })],
    delete_time: [buildValidatorData({ name: 'number', title: t('dish.index.delete_time') })],
})
</script>

<style scoped lang="scss"></style>
