const path = require('path');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');

module.exports = {
    entry: {
        main: './assets/js/main.js', // Ruta de tu archivo JS/React
        'story-values': './assets/js/story-values.js',
        style: './assets/sass/style.scss',   // Archivo principal SCSS
        icons: './assets/sass/_icons.scss',   // Archivo principal SCSS
    },
    output: {
        path: path.resolve(__dirname, 'build'),
        filename: '[name].bundle.js', // Archivo compilado para JS
    },
    module: {
        rules: [
            // Regla para Babel (JS y JSX)
            {
                test: /\.jsx?$/, // Transformar archivos .js y .jsx
                exclude: /node_modules/,
                use: {
                    loader: 'babel-loader',
                    options: {
                        presets: ['@babel/preset-env', '@babel/preset-react'], // Presets para JS moderno y React
                    },
                },
            },
            // Regla para SCSS
            {
                test: /\.scss$/, // Transformar archivos .scss
                use: [
                    MiniCssExtractPlugin.loader, // Extrae CSS a un archivo separado
                    'css-loader',               // Convierte CSS a módulos CommonJS
                    'postcss-loader',           // Aplica PostCSS (autoprefixer)
                    'sass-loader',              // Compila SCSS a CSS
                ],
            },
            {
                test: /\.svg$/,
                type: 'asset/resource', // Convierte los SVG en base64 para incluirlos en CSS
                generator: {
                    filename: 'icons/[name][ext]' // Coloca los SVG en build/icons/
                }
            }
        ],
    },
    plugins: [
        new MiniCssExtractPlugin({
            filename: '[name].bundle.css'
        })
    ],
    mode: 'development', // Cambia a 'production' para compilar para producción
    watch: process.env.NODE_ENV === 'development' // Observa cambios solo en desarrollo
};
