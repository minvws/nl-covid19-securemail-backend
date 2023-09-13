<template>
    <div>
        <BButton class="pr-4" variant="digid" :href="loginUrl" @keyup.space="activateLink()">
            <SvgVue icon="digid-logo" width="40px" class="mr-3" focusable="false" aria-hidden="true" />
            <span>{{ $t('components.digidLoginButton.text') }}</span>
        </BButton>
        <div
            class="d-flex align-items-start mt-3"
            v-if="digidResponse && digidResponse.error"
            data-testid="wrapper-digid-error"
        >
            <div>
                <SvgVue
                    class="mt-1 mr-2"
                    icon="info"
                    width="16px"
                    height="16px"
                    focusable="false"
                    aria-hidden="true"
                    stroke="#333333"
                />
            </div>
            <span v-html="$t(`errors.${digidResponse.error}.message`)"></span>
        </div>
    </div>
</template>

<script lang="ts">
import { State as SessionState } from '@/store/sessionStore/sessionStore';
import { StoreType } from '@/store/storeType';
import { defineComponent } from '@/typeHelpers';
import { mapState } from 'vuex';

interface Props {
    loginUrl: string;
}

interface Data {}

interface Computed {
    digidResponse: SessionState['digidResponse'];
}

interface Methods {}

export default defineComponent<Data, Methods, Computed, Props>({
    name: 'DigidLoginButton',
    props: {
        loginUrl: {
            type: String,
            required: true,
        },
    },
    data() {
        return {};
    },
    computed: {
        ...mapState(StoreType.SESSION, ['digidResponse']),
    },
    methods: {
        activateLink() {
            window.location.href = this.loginUrl;
        },
    },
});
</script>

<style lang="scss" scoped>
@import './resources/scss/_variables.scss';

.btn-digid {
    display: inline-flex;
    align-items: center;
    background: $digid-orange;
    color: $white;
    font-size: 1.25rem;
    padding: 0.25rem;
    text-align: center;
    text-decoration: none;

    &:hover,
    &:focus {
        background: darken($digid-orange, 10%);
        color: $white;
        outline: 0;
        text-decoration: none;
    }

    &:active,
    &:focus {
        background: darken($digid-orange, 10%);
        border: 1px solid white;
        box-shadow: 0 0 0 $btn-focus-width $primary;
        color: $white;
    }

    &:visited {
        color: $white;
    }

    svg {
        background: #000000;
        border-radius: 6px;
    }

    span {
        margin: 0 auto;
    }
}
</style>
