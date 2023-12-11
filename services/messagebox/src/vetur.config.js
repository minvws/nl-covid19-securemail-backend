/** @type {import('vls').VeturConfig} */

// These settings are for the VTI setup in the dev/build process

module.exports = {
    settings: {
        'vetur.useWorkspaceDependencies': true,
        'vetur.experimental.templateInterpolationService': false,
        'vetur.completion.tagCasing': 'initial',
        'vetur.validation.interpolation': false,
        'vetur.validation.script': true,
        'vetur.validation.style': false,
        'vetur.validation.template': false,
    },
};
