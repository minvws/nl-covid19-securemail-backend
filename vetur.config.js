/** @type {import('vls').VeturConfig} */

// These settings are used by vetur in the VSCode plugin

module.exports = {
    settings: {
        'vetur.useWorkspaceDependencies': true,
        'vetur.experimental.templateInterpolationService': true,
        'vetur.completion.tagCasing': 'initial',
        'vetur.validation.template': false,
    },
    projects: [
        {
            root: './services/messagebox/src',
            snippetFolder: './.vscode/vetur/snippets',
            globalComponents: ['./node_modules/svg-vue/src/svg-vue.vue'],
        },
    ],
};
