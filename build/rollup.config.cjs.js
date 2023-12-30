import commonjs from '@rollup/plugin-commonjs';
import json from '@rollup/plugin-json';
import {nodeResolve} from '@rollup/plugin-node-resolve';


export default {
    input: 'src/assets/js/index.js',
    external: [
        'blueimp-file-upload',
        'jquery',
        'nette-forms',
    ],
    output: {
        dir: 'dist/js/commonjs',
        format: 'cjs',
        exports: 'auto',
        sourcemap: true,
        preserveModules: true,
    },
    plugins: [
        nodeResolve(),
        json(),
        commonjs(),
    ],
};
