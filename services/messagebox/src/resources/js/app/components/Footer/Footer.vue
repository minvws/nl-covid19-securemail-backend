<template>
    <footer class="py-4">
        <BContainer>
            <BRow>
                <BCol cols="12" lg="8" offset-lg="2">
                    <h2 id="footerTitle" class="px-3">{{ $t('components.footer.navLabel') }}</h2>
                    <nav aria-labelledby="footerTitle">
                        <BNav id="footer" role="list" vertical>
                            <li class="nav-item">
                                <router-link :to="{ name: 'privacy' }" class="nav-link"
                                    ><SvgVue
                                        class="mr-1"
                                        icon="chevron-right"
                                        width="20px"
                                        focusable="false"
                                        aria-hidden="true"
                                    />
                                    {{ $t('components.footer.privacy') }}</router-link
                                >
                            </li>
                            <li class="nav-item">
                                <router-link :to="{ name: 'accessibility' }" class="nav-link"
                                    ><SvgVue
                                        class="mr-1"
                                        icon="chevron-right"
                                        width="20px"
                                        focusable="false"
                                        aria-hidden="true"
                                    />
                                    {{ $t('components.footer.accessibility') }}</router-link
                                >
                            </li>
                            <li class="nav-item">
                                <router-link :to="{ name: 'reportVulnerability' }" class="nav-link"
                                    ><SvgVue
                                        class="mr-1"
                                        icon="chevron-right"
                                        width="20px"
                                        focusable="false"
                                        aria-hidden="true"
                                    />
                                    {{ $t('components.footer.reportVulnerability') }}</router-link
                                >
                            </li>
                        </BNav>
                    </nav>

                    <div class="px-3 mt-3 note">
                        {{ $t('components.footer.about') }}
                    </div>

                    <div v-if="version && environment != EnvironmentType.Production" class="version mt-3 text-center">
                        {{ $t('app.version', { version }) }}
                    </div>
                </BCol>
            </BRow>
        </BContainer>
    </footer>
</template>

<script lang="ts">
import { defineComponent } from '@/typeHelpers';
import env, { EnvironmentType } from '@/env';

interface Props {}

interface Data {
    version: string;
    environment: string;
    EnvironmentType: typeof EnvironmentType;
}

interface Computed {}

interface Methods {}

export default defineComponent<Data, Methods, Computed, Props>({
    name: 'Footer',
    data() {
        return {
            version: env.version,
            environment: env.environment,
            EnvironmentType,
        };
    },
});
</script>

<style lang="scss" scoped>
@import './resources/scss/_variables.scss';

footer {
    background-color: $lightest-grey;
    box-shadow: inset 0px -1px 0px $light-grey;
    font-size: 1rem;
    line-height: 1.5rem;

    @media (min-width: $breakpoint-sm) {
        box-shadow: 0px -1px 0px rgba(123, 123, 135, 0.1);
    }

    @media print {
        display: none;
    }

    ul li {
        a {
            color: $body-color;
            display: flex;

            &:focus {
                outline-offset: -0.25rem;
            }

            svg {
                fill: $body-color;
                height: 1.5rem;
            }
        }
    }

    .note {
        color: $gray-dark;
        margin-bottom: 0.25rem;
        font-family: 'RijksoverheidSansHeading';
        line-height: 1.5rem;
        font-size: 1rem;
    }
}
</style>
