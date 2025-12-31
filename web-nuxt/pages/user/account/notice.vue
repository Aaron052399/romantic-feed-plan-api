<template>
    <div class="user-views">
        <el-card class="user-views-card" shadow="hover">
            <template #header>
                <div class="card-header">
                    <span>通知配置</span>
                </div>
            </template>
            <div v-for="(noticeItem, noticeIdx) in state.notice" :key="noticeIdx">
                <h3>{{ noticeIdx }}</h3>
                <div v-for="(item, idx) in state.notice[noticeIdx]" :key="idx" class="notice-box">
                    <h4 class="title">{{ idx }}</h4>
                    <div class="notice-item" v-for="(subItem, subIdx) in state.notice[noticeIdx][idx]" :key="subIdx">
                        <div class="item-title">{{ subItem.title }}</div>
                        <div>
                            <el-checkbox
                                v-for="(checkboxItem, checkboxIdx) in subItem.typeNamesTable"
                                :key="checkboxIdx"
                                @change="onChange($event, subItem.name, checkboxIdx)"
                                v-model="subItem.values[checkboxIdx]"
                                :label="checkboxItem"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </el-card>
    </div>
</template>

<script setup lang="ts">
import type { CheckboxValueType } from 'element-plus'
import { getNoticeSwitch, postNoticeSwitch } from '~/api/user/noticeSwitch'

definePageMeta({
    name: 'account/notice',
})

const state: {
    notice: anyObj[]
} = reactive({
    notice: [],
})

const { data } = await getNoticeSwitch()
if (data.value?.code == 1) {
    state.notice = data.value.data.notices
}

const onChange = (value: CheckboxValueType, noticeName: string, noticeType: any) => {
    postNoticeSwitch({
        value,
        name: noticeName,
        type: noticeType,
    })
}
</script>

<style scoped lang="scss">
.notice-box {
    .title {
        padding-top: 20px;
        color: var(--el-text-color-regular);
    }
    .notice-item {
        display: flex;
        align-items: center;
        color: var(--el-text-color-regular);
        margin: 15px 0;
        .item-title {
            width: 140px;
        }
    }
}
</style>
