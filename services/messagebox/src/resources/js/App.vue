<template>
    <div id="app">
        <RouterView />
        <BaseModal />
    </div>
</template>
<script lang="ts">
import { defineComponent } from '@/typeHelpers';

interface Props {}

interface Data {}

interface Computed {}

interface Methods {}

export default defineComponent<Data, Methods, Computed, Props>({
    name: 'App',
});
</script>

<style lang="scss" scoped></style>
