<template>
    <div class="wrapper">
        <Navbar :hasBackButton="!!backButton" @back="backButton ? backButton() : null" />
        <main id="main" role="main" class="container py-sm-4">
            <div class="row">
                <div class="col-12 col-lg-6 offset-lg-3">
                    <BCard body-class="mt-2">
                        <router-view></router-view>
                    </BCard>
                </div>
            </div>
        </main>
        <Footer />
    </div>
</template>

<script lang="ts">
import { defineComponent } from '@/typeHelpers';
import Navbar from '@/app/components/Navbar/Navbar.vue';
import Footer from '@/app/components/Footer/Footer.vue';

interface Props {}

interface Data {}

interface Computed {
    backButton: Function | null;
}

interface Methods {}

export default defineComponent<Data, Methods, Computed, Props>({
    components: { Footer, Navbar },
    name: 'GenericPage',
    computed: {
        backButton() {
            const backButton = this.$route.meta?.backButton;

            return backButton instanceof Function ? backButton : null;
        },
    },
});
</script>

<style lang="scss" scoped></style>
