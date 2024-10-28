import globals from 'globals';
import config from '@nepada/eslint-config';

const languageOptions = {
    globals: {
        ...globals.browser,
    },
};

export default [
    ...config.default,
    {
        languageOptions,
    },
];
