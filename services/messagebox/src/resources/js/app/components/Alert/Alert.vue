<template>
    <div :class="['alert', `alert--${infoType}`]">
        <h2 v-if="title || showIcon">
            <SvgVue
                v-if="showIcon"
                :icon="infoType"
                width="20px"
                focusable="false"
                aria-hidden="true"
                class="svg-icon mr-2"
            />
            <span v-if="showIcon" class="sr-only">
                {{ $t(`icons.${infoType}`) }}
            </span>
            {{ title }}
        </h2>
        <slot />
    </div>
</template>

<script lang="ts">
import { defineComponent } from '@/typeHelpers';

enum InfoType {
    BASIC = 'basic',
    INFO = 'info',
    ERROR = 'error',
}

interface Props {
    title?: string;
    infoType?: InfoType;
    showIcon?: boolean;
}

interface Data {}

interface Computed {}

interface Methods {}

export default defineComponent<Data, Methods, Computed, Props>({
    name: 'Alert',
    props: {
        title: {
            type: String,
            default: '',
        },
        infoType: {
            type: String as () => InfoType,
            default: InfoType.BASIC,
        },
        showIcon: {
            type: Boolean,
            default: false,
        },
    },
});
</script>

<style lang="scss" scoped></style>
