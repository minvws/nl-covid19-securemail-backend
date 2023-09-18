module.exports = {
    clearMocks: true,
    coverageDirectory: './coverage',
    collectCoverageFrom: ['resources/js/**/*.{js,ts,vue}'],
    moduleFileExtensions: ['js', 'json', 'ts', 'node', 'vue'],
    testMatch: ['**/*.spec.ts', '**/*.spec.js'],
    transform: {
        // process `*.vue` files with `vue-jest`
        '.*\\.(vue)$': 'vue-jest',
        '^.+\\.(j|t)s$': '<rootDir>/node_modules/babel-jest',
        'vee-validate/dist/rules': 'babel-jest',
    },
    transformIgnorePatterns: ['<rootDir>/node_modules/(?!vee-validate/dist/rules)'],
    testEnvironment: 'jsdom',
    moduleNameMapper: {
        '^@/(.*)$': '<rootDir>/resources/js/$1',
    },
    modulePathIgnorePatterns: ['.*__mocks__.*'],
    globalSetup: '<rootDir>/global-jest-setup.ts',
    setupFiles: ['<rootDir>/jest-setup.ts'],
};
